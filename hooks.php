<?php

if (!defined("WHMCS")) {
    die("Access to this file is not possible");
}

use WHMCS\Database\Capsule;

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

add_hook('ClientAreaPage', 1, function ($vars) {
    if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    if (!empty($_SESSION['evp_redirecting'])) {
        unset($_SESSION['evp_redirecting']);
        return;
    }

    if (isset($_REQUEST['m']) && $_REQUEST['m'] === 'emailverificationpro') {
        return;
    }
    if (isset($_GET['evp_token'])) {
        return;
    }
    if (isset($_GET['evp_action'])) {
        return;
    }

    $client = Menu::context('client');
    if (is_null($client) || !$client) {
        return;
    }

    $clientId = $client->id ?? 0;
    if (!$clientId) {
        return;
    }

    if (isset($_SESSION['evp_verified']) && $_SESSION['evp_verified'] == 1) {
        return;
    }

    $settings = Database::settingAll();
    $mode = $settings['verification_mode'] ?? 'checkout';

    if ($mode === 'allpages') {
        $isVerified = ClientController::isClientVerified($clientId);
        if (!$isVerified) {
            $_SESSION['evp_redirecting'] = 1;
            header('Location: index.php?m=emailverificationpro');
            exit;
        }
    }

    if ($mode === 'checkout') {
        $isCheckout = false;
        if (isset($_GET['cart']) || isset($_GET['checkout']) || (isset($_GET['a']) && $_GET['a'] === 'checkout')) {
            $isCheckout = true;
        }
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'cart.php') !== false) {
            $isCheckout = true;
        }

        if ($isCheckout) {
            $isVerified = ClientController::isClientVerified($clientId);
            if (!$isVerified) {
                $_SESSION['evp_redirecting'] = 1;
                header('Location: index.php?m=emailverificationpro');
                exit;
            }
        }
    }
});

add_hook('ClientAreaRegister', 1, function ($vars) {
    if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $clientId = $vars['userid'] ?? 0;
    $email = $vars['email'] ?? '';

    if (!$clientId || !$email) {
        return;
    }

    $isBannedIp = BanManager::isIpBanned(ActivityLog::getClientIp());
    if ($isBannedIp) {
        ActivityLog::add($clientId, 'register_blocked', "Registration blocked: IP is banned.");
        return;
    }

    $isBannedEmail = BanManager::isEmailBanned($email);
    if ($isBannedEmail) {
        ActivityLog::add($clientId, 'register_blocked', "Registration blocked: email {$email} is banned.");
        return;
    }

    $isBannedProvider = BanManager::isEmailDomainBanned($email);
    if ($isBannedProvider) {
        ActivityLog::add($clientId, 'register_blocked', "Registration blocked: email provider is banned.");
        return;
    }

    $token = Verification::create($clientId, $email);
    Verification::sendVerificationEmail($clientId, $email, $token);

    ActivityLog::add($clientId, 'verification_sent', "Verification email sent to {$email}.");

    if (isset($_SESSION['evp_verified'])) {
        $_SESSION['evp_verified'] = 0;
    }
});

add_hook('ShoppingCartValidateCheckout', 1, function ($vars) {
    $settings = Database::settingAll();
    $mode = $settings['verification_mode'] ?? 'checkout';

    if ($mode !== 'checkout') {
        return;
    }

    $client = Menu::context('client');
    if (is_null($client) || !$client) {
        return;
    }

    $clientId = $client->id ?? 0;
    if (!$clientId) {
        return;
    }

    $isVerified = ClientController::isClientVerified($clientId);
    if (!$isVerified) {
        return array('Please verify your email address before completing checkout.');
    }
});

add_hook('PreRegistrarRegisterDomain', 1, function ($vars) {
    $settings = Database::settingAll();
    $mode = $settings['verification_mode'] ?? 'checkout';

    $client = Menu::context('client');
    if (is_null($client) || !$client) {
        return;
    }

    $clientId = $client->id ?? 0;
    if (!$clientId) {
        return;
    }

    $isVerified = ClientController::isClientVerified($clientId);
    if (!$isVerified) {
        return array('abortWithError' => 'Please verify your email address before registering a domain.');
    }
});

add_hook('PreModuleCreate', 1, function ($vars) {
    $settings = Database::settingAll();
    $mode = $settings['verification_mode'] ?? 'checkout';

    $client = Menu::context('client');
    if (is_null($client) || !$client) {
        return;
    }

    $clientId = $client->id ?? 0;
    if (!$clientId) {
        return;
    }

    $isVerified = ClientController::isClientVerified($clientId);
    if (!$isVerified) {
        return array('abortcmd' => true);
    }
});

add_hook('DailyCronJob', 1, function ($vars) {
    $settings = Database::settingAll();

    $autoTerminateDays = (int)($settings['auto_terminate_days'] ?? 0);
    if ($autoTerminateDays > 0) {
        $expired = Capsule::table('mod_emailverificationpro_verification')
            ->where('is_verified', 0)
            ->where('expires_at', '<', date('Y-m-d H:i:s'))
            ->get();

        foreach ($expired as $record) {
            Capsule::table('tblclients')->where('id', $record->client_id)->update(['status' => 'Closed']);
            ActivityLog::add($record->client_id, 'auto_terminated', "Account auto-terminated due to unverified email after {$autoTerminateDays} days.");
        }
    }

    $autoDeleteDays = (int)($settings['auto_delete_days'] ?? 0);
    if ($autoDeleteDays > 0) {
        $deleteDate = date('Y-m-d H:i:s', strtotime("-{$autoDeleteDays} days"));
        $unverifiedOld = Capsule::table('mod_emailverificationpro_verification')
            ->where('is_verified', 0)
            ->where('created_at', '<', $deleteDate)
            ->get();

        foreach ($unverifiedOld as $record) {
            $hasOrder = Capsule::table('tblorders')
                ->where('userid', $record->client_id)
                ->exists();

            if (!$hasOrder) {
                Capsule::table('tblclients')->where('id', $record->client_id)->delete();
                Capsule::table('mod_emailverificationpro_verification')->where('id', $record->id)->delete();
                ActivityLog::add($record->client_id, 'auto_deleted', "Unverified account with no orders auto-deleted after {$autoDeleteDays} days.");
            }
        }
    }

    $autoResendDays = (int)($settings['auto_resend_days'] ?? 0);
    if ($autoResendDays > 0) {
        $resendDate = date('Y-m-d H:i:s', strtotime("-{$autoResendDays} days"));
        $needResend = Capsule::table('mod_emailverificationpro_verification')
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
});
