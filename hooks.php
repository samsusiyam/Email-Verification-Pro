<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/lib/Core/Database.php';
require_once __DIR__ . '/lib/Core/Verification.php';
require_once __DIR__ . '/lib/Core/BanManager.php';
require_once __DIR__ . '/lib/Core/ActivityLog.php';
require_once __DIR__ . '/lib/Core/Language.php';
require_once __DIR__ . '/lib/Client/ClientController.php';

use EmailVerificationPro\Core\Database;
use EmailVerificationPro\Core\Verification;
use EmailVerificationPro\Core\BanManager;
use EmailVerificationPro\Core\ActivityLog;
use EmailVerificationPro\Client\ClientController;

function emailverificationpro_hook_client_area($vars)
{
    if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $isVerifyPage = false;

    if (isset($_GET['m']) && $_GET['m'] === 'emailverificationpro') {
        $isVerifyPage = true;
        $_SESSION['evp_on_verify_page'] = 1;
    }

    if (isset($_GET['evp_token'])) {
        $isVerifyPage = true;
        $_SESSION['evp_on_verify_page'] = 1;
    }

    if (isset($_GET['evp_action'])) {
        $isVerifyPage = true;
        $_SESSION['evp_on_verify_page'] = 1;
    }

    if (isset($_SESSION['evp_on_verify_page']) && $_SESSION['evp_on_verify_page'] == 1) {
        if (isset($_GET['m']) && $_GET['m'] === 'emailverificationpro') {
            $isVerifyPage = true;
        }
        if (!isset($_GET['m']) && !isset($_GET['evp_token']) && !isset($_GET['evp_action'])) {
            $_SESSION['evp_on_verify_page'] = 0;
        }
    }

    $clientId = $_SESSION['clients'] ?? 0;

    if (!$clientId) {
        return;
    }

    $settings = Database::settingAll();
    $mode = $settings['verification_mode'] ?? 'checkout';

    $isVerified = ClientController::isClientVerified($clientId);

    if ($isVerified) {
        $_SESSION['evp_verified'] = 1;
        return;
    }

    if ($isVerifyPage) {
        return;
    }

    if ($mode === 'allpages') {
        $redirectUrl = 'index.php?m=emailverificationpro';
        header("Location: " . $redirectUrl);
        exit;
    }

    if ($mode === 'checkout') {
        $isCheckout = false;
        if (isset($_GET['cart']) || isset($_GET['checkout']) || (isset($_GET['a']) && $_GET['a'] === 'checkout')) {
            $isCheckout = true;
        }

        if ($isCheckout) {
            $redirectUrl = 'index.php?m=emailverificationpro';
            header("Location: " . $redirectUrl);
            exit;
        }
    }
}

function emailverificationpro_hook_client_register($vars)
{
    $clientId = $vars['userid'] ?? 0;
    $email = $vars['email'] ?? '';

    if (!$clientId || !$email) {
        return;
    }

    $isBannedIp = BanManager::isIpBanned(ActivityLog::getClientIp());
    if ($isBannedIp) {
        return;
    }

    $isBannedEmail = BanManager::isEmailBanned($email);
    if ($isBannedEmail) {
        return;
    }

    $isBannedProvider = BanManager::isEmailDomainBanned($email);
    if ($isBannedProvider) {
        return;
    }

    $token = Verification::create($clientId, $email);
    Verification::sendVerificationEmail($clientId, $email, $token);

    ActivityLog::add($clientId, 'verification_sent', "Verification email sent to {$email}.");

    $_SESSION['evp_verified'] = 0;
}

function emailverificationpro_hook_client_area_page($vars)
{
    if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $clientId = $_SESSION['clients'] ?? 0;
    if (!$clientId) {
        return;
    }

    $settings = Database::settingAll();

    if (isset($_SESSION['evp_verified']) && $_SESSION['evp_verified'] == 1) {
        return;
    }

    $isVerified = ClientController::isClientVerified($clientId);
    if ($isVerified) {
        $_SESSION['evp_verified'] = 1;
        return;
    }

    $isVerifyPage = (isset($_GET['m']) && $_GET['m'] === 'emailverificationpro')
        || (isset($_SESSION['evp_on_verify_page']) && $_SESSION['evp_on_verify_page'] == 1)
        || isset($_GET['evp_token'])
        || isset($_GET['evp_action']);

    if ($isVerifyPage) {
        return;
    }

    $mode = $settings['verification_mode'] ?? 'checkout';
    if ($mode !== 'allpages') {
        return;
    }

    $blockedMsg = $settings['blocked_pages_message'] ?? 'Please verify your email address to access this page.';
    $verifyUrl = 'index.php?m=emailverificationpro';

    echo '<div id="evp-block-overlay" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);z-index:99999;display:flex;align-items:center;justify-content:center;">
        <div style="background:#fff;padding:40px;border-radius:8px;text-align:center;max-width:500px;width:90%;box-shadow:0 4px 20px rgba(0,0,0,0.3);">
            <h2 style="color:#e74c3c;margin-bottom:15px;">Email Verification Required</h2>
            <p style="color:#333;margin-bottom:20px;">' . htmlspecialchars($blockedMsg) . '</p>
            <a href="' . $verifyUrl . '" style="display:inline-block;padding:12px 30px;background:#3498db;color:#fff;text-decoration:none;border-radius:5px;font-size:16px;">Verify Email Now</a>
        </div>
    </div>';
}

function emailverificationpro_hook_daily_cron_job($vars)
{
    $settings = Database::settingAll();

    $autoTerminateDays = (int)($settings['auto_terminate_days'] ?? 0);
    if ($autoTerminateDays > 0) {
        $expired = \Capsule::table('mod_emailverificationpro_verification')
            ->where('is_verified', 0)
            ->where('expires_at', '<', date('Y-m-d H:i:s'))
            ->get();

        foreach ($expired as $record) {
            \Capsule::table('tblclients')->where('id', $record->client_id)->update(['status' => 'Closed']);
            ActivityLog::add($record->client_id, 'auto_terminated', "Account auto-terminated due to unverified email after {$autoTerminateDays} days.");
        }
    }

    $autoDeleteDays = (int)($settings['auto_delete_days'] ?? 0);
    if ($autoDeleteDays > 0) {
        $deleteDate = date('Y-m-d H:i:s', strtotime("-{$autoDeleteDays} days"));
        $unverifiedOld = \Capsule::table('mod_emailverificationpro_verification')
            ->where('is_verified', 0)
            ->where('created_at', '<', $deleteDate)
            ->get();

        foreach ($unverifiedOld as $record) {
            $hasOrder = \Capsule::table('tblorders')
                ->where('userid', $record->client_id)
                ->exists();

            if (!$hasOrder) {
                \Capsule::table('tblclients')->where('id', $record->client_id)->delete();
                \Capsule::table('mod_emailverificationpro_verification')->where('id', $record->id)->delete();
                ActivityLog::add($record->client_id, 'auto_deleted', "Unverified account with no orders auto-deleted after {$autoDeleteDays} days.");
            }
        }
    }

    $autoResendDays = (int)($settings['auto_resend_days'] ?? 0);
    if ($autoResendDays > 0) {
        $resendDate = date('Y-m-d H:i:s', strtotime("-{$autoResendDays} days"));
        $needResend = \Capsule::table('mod_emailverificationpro_verification')
            ->where('is_verified', 0)
            ->where(function ($q) use ($resendDate) {
                $q->where('last_resend_at', '<', $resendDate)
                    ->orWhere(function ($q2) use ($resendDate) {
                        $q2->whereNull('last_resend_at')
                            ->where('created_at', '<', $resendDate);
                    });
            })
            ->get();

        foreach ($needResend as $record) {
            $newToken = Verification::resend($record->id);
            if ($newToken) {
                Verification::sendVerificationEmail($record->client_id, $record->email, $newToken);
                ActivityLog::add($record->client_id, 'auto_resend', "Auto-resend verification email to {$record->email}.");
            }
        }
    }

    BanManager::cleanExpired();
}
