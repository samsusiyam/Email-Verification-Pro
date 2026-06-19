<?php

namespace EmailVerificationPro\Client;

use EmailVerificationPro\Core\Database;
use EmailVerificationPro\Core\Verification;
use EmailVerificationPro\Core\BanManager;
use EmailVerificationPro\Core\ActivityLog;
use EmailVerificationPro\Core\Language;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

if (!class_exists('Capsule') && class_exists('WHMCS\\Database\\Capsule')) {
    class_alias('WHMCS\\Database\\Capsule', 'Capsule');
}

class ClientController
{
    public function handle()
    {
        if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = $_GET['evp_token'] ?? '';

        if ($token) {
            $this->doVerify($token);
            return '';
        }

        $action = $_GET['evp_action'] ?? 'page';

        if ($action === 'resend') {
            $this->doResend();
            return '';
        }

        return $this->renderVerificationPage(
            $_SESSION['client_id'] ?? 0
        );
    }

    public static function renderVerificationPage($clientId)
    {
        Language::load();

        $email = '';

        if ($clientId) {
            $client = \Capsule::table('tblclients')->find($clientId);
            if ($client) {
                $email = $client->email;
            }
        }

        $_SESSION['evp_on_verify_page'] = 1;

        $msg = '';
        $msgType = 'info';

        if (isset($_GET['msg'])) {
            $msg = $_GET['msg'];
            $msgType = $_GET['msg_type'] ?? 'info';
        }

        $enableRecaptcha = Database::setting('enable_recaptcha', '0');
        $recaptchaSiteKey = Database::setting('recaptcha_site_key', '');
        $enableTurnstile = Database::setting('enable_turnstile', '0');
        $turnstileSiteKey = Database::setting('turnstile_site_key', '');

        $templateDir = dirname(__FILE__, 2) . '/templates/client';
        $compileDir = defined('WHMCS_BASE_PATH') ? WHMCS_BASE_PATH . '/templates_c' : dirname(__FILE__, 5) . '/templates_c';

        $smarty = new \Smarty();
        $smarty->setTemplateDir($templateDir);
        $smarty->setCompileDir($compileDir);

        $smarty->assign('email', $email);
        $smarty->assign('client_id', $clientId);
        $smarty->assign('msg', $msg);
        $smarty->assign('msg_type', $msgType);
        $smarty->assign('enable_recaptcha', $enableRecaptcha);
        $smarty->assign('recaptcha_site_key', $recaptchaSiteKey);
        $smarty->assign('enable_turnstile', $enableTurnstile);
        $smarty->assign('turnstile_site_key', $turnstileSiteKey);

        return $smarty->fetch('verify.tpl');
    }

    private function doResend()
    {
        Language::load();

        $clientId = $_SESSION['client_id'] ?? 0;
        if (!$clientId) {
            header('Location: index.php?m=emailverificationpro&msg=' . urlencode('Please log in first.') . '&msg_type=danger');
            exit;
        }

        $client = \Capsule::table('tblclients')->find($clientId);
        if (!$client) {
            header('Location: index.php?m=emailverificationpro&msg=' . urlencode('Client not found.') . '&msg_type=danger');
            exit;
        }

        $ver = Verification::getByClientId($clientId);
        if ($ver) {
            $newToken = Verification::resend($ver->id);
            if ($newToken) {
                Verification::sendVerificationEmail($clientId, $client->email, $newToken);
                ActivityLog::add($clientId, 'resend_success', "Verification email resent to {$client->email}.");
                header('Location: index.php?m=emailverificationpro&msg=' . urlencode(Language::get('resend_success')) . '&msg_type=success');
                exit;
            } else {
                header('Location: index.php?m=emailverificationpro&msg=' . urlencode(Language::get('resend_limit')) . '&msg_type=danger');
                exit;
            }
        }

        header('Location: index.php?m=emailverificationpro&msg=' . urlencode('No verification record found.') . '&msg_type=danger');
        exit;
    }

    private function doVerify($token)
    {
        if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $result = Verification::verify($token);

        if ($result['success']) {
            $_SESSION['evp_verified'] = 1;
            unset($_SESSION['evp_on_verify_page']);
            \Capsule::table('tblclients')
                ->where('id', $result['client_id'])
                ->update(['verified' => 1]);

            header('Location: clientarea.php?action=products&evp_msg=' . urlencode('Email verified successfully!'));
            exit;
        } else {
            header('Location: index.php?m=emailverificationpro&msg=' . urlencode($result['message']) . '&msg_type=danger');
            exit;
        }
    }

    public static function isClientVerified($clientId)
    {
        if (!$clientId) {
            return true;
        }

        if (isset($_SESSION['evp_verified']) && $_SESSION['evp_verified'] == 1) {
            return true;
        }

        return Verification::isVerified($clientId);
    }
}
