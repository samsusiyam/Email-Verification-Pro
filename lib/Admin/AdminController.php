<?php

namespace EmailVerificationPro\Admin;

use EmailVerificationPro\Core\Database;
use EmailVerificationPro\Core\Verification;
use EmailVerificationPro\Core\BanManager;
use EmailVerificationPro\Core\ActivityLog;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

if (!class_exists('Capsule') && class_exists('WHMCS\\Database\\Capsule')) {
    class_alias('WHMCS\\Database\\Capsule', 'Capsule');
}

class AdminController
{
    private $vars;

    public function __construct($vars)
    {
        $this->vars = $vars;
    }

    public function handle()
    {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';
        $cmd = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost($action);
        }

        switch ($cmd) {
            case 'settings':
                return $this->settingsPage();
            case 'bans':
                return $this->bansPage();
            case 'clients':
                return $this->clientsPage();
            case 'logs':
                return $this->logsPage();
            default:
                return $this->dashboardPage();
        }
    }

    private function handlePost($action)
    {
        switch ($action) {
            case 'save_settings':
                $this->saveSettings();
                break;
            case 'ban_ip':
                $this->banIp();
                break;
            case 'ban_email':
                $this->banEmail();
                break;
            case 'ban_provider':
                $this->banProvider();
                break;
            case 'unban':
                $this->unban();
                break;
            case 'manual_verify':
                $this->manualVerify();
                break;
            case 'manual_unverify':
                $this->manualUnverify();
                break;
            case 'delete_verification':
                $this->deleteVerification();
                break;
            case 'clear_logs':
                ActivityLog::clear();
                header('Location: ' . $this->getUrl('logs'));
                exit;
        }
    }

    private function dashboardPage()
    {
        $unverified = Verification::getUnverifiedCount();
        $totalClients = \Capsule::table('tblclients')->count();
        $totalVerified = \Capsule::table('mod_emailverificationpro_verification')->where('is_verified', 1)->count();
        $totalBans = \Capsule::table('mod_emailverificationpro_bans')->count();
        $recentLogs = ActivityLog::getAll(1, 10);

        $settings = Database::settingAll();

        $templateDir = __DIR__ . '/../../templates/admin';
        $compileDir = defined('WHMCS_BASE_PATH') ? WHMCS_BASE_PATH . '/templates_c' : dirname(__FILE__, 5) . '/templates_c';

        $smarty = new \Smarty();
        $smarty->setTemplateDir($templateDir);
        $smarty->setCompileDir($compileDir);

        $smarty->assign('unverified_count', $unverified);
        $smarty->assign('total_clients', $totalClients);
        $smarty->assign('total_verified', $totalVerified);
        $smarty->assign('total_bans', $totalBans);
        $smarty->assign('recent_logs', $recentLogs['results'] ?? []);
        $smarty->assign('settings', $settings);
        $smarty->assign('module_url', $this->getUrl());

        return $smarty->fetch('dashboard.tpl');
    }

    private function settingsPage()
    {
        $settings = Database::settingAll();

        $templateDir = __DIR__ . '/../../templates/admin';
        $compileDir = defined('WHMCS_BASE_PATH') ? WHMCS_BASE_PATH . '/templates_c' : dirname(__FILE__, 5) . '/templates_c';

        $smarty = new \Smarty();
        $smarty->setTemplateDir($templateDir);
        $smarty->setCompileDir($compileDir);

        $smarty->assign('settings', $settings);
        $smarty->assign('module_url', $this->getUrl());
        $smarty->assign('saved', $_GET['saved'] ?? 0);

        return $smarty->fetch('settings.tpl');
    }

    private function saveSettings()
    {
        $fields = [
            'verification_mode', 'enable_recaptcha', 'recaptcha_site_key', 'recaptcha_secret_key',
            'enable_turnstile', 'turnstile_site_key', 'turnstile_secret_key',
            'auto_terminate_days', 'auto_delete_days', 'auto_resend_days',
            'resend_email_limit', 'verification_expiry_days', 'verification_template_id',
            'enable_ban_ip', 'enable_ban_email', 'enable_ban_provider',
            'ban_duration_days', 'sub_user_verify', 'enable_activity_log',
            'blocked_pages_message',
        ];

        $data = [];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $data[$field] = $_POST[$field];
            }
        }

        Database::updateSettings($data);

        header('Location: ' . $this->getUrl('settings') . '&saved=1');
        exit;
    }

    private function bansPage()
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $search = $_GET['search'] ?? '';
        $type = $_GET['type'] ?? 'all';

        $bans = BanManager::getAll($page, 25, $search, $type);
        $totalPages = ceil($bans['total'] / 25);

        $templateDir = __DIR__ . '/../../templates/admin';
        $compileDir = defined('WHMCS_BASE_PATH') ? WHMCS_BASE_PATH . '/templates_c' : dirname(__FILE__, 5) . '/templates_c';

        $smarty = new \Smarty();
        $smarty->setTemplateDir($templateDir);
        $smarty->setCompileDir($compileDir);

        $smarty->assign('bans', $bans['results']);
        $smarty->assign('total', $bans['total']);
        $smarty->assign('page', $page);
        $smarty->assign('total_pages', $totalPages);
        $smarty->assign('search', $search);
        $smarty->assign('type', $type);
        $smarty->assign('module_url', $this->getUrl());

        return $smarty->fetch('bans.tpl');
    }

    private function banIp()
    {
        $ip = trim($_POST['ip_address'] ?? '');
        $days = (int)($_POST['duration_days'] ?? 0);
        if ($ip) {
            BanManager::banIp($ip, $days ?: null, $_SESSION['adminid'] ?? null);
        }
        header('Location: ' . $this->getUrl('bans'));
        exit;
    }

    private function banEmail()
    {
        $email = trim($_POST['email'] ?? '');
        $days = (int)($_POST['duration_days'] ?? 0);
        if ($email) {
            BanManager::banEmail($email, $days ?: null, $_SESSION['adminid'] ?? null);
        }
        header('Location: ' . $this->getUrl('bans'));
        exit;
    }

    private function banProvider()
    {
        $provider = trim($_POST['provider'] ?? '');
        if ($provider) {
            BanManager::banEmailProvider($provider, $_SESSION['adminid'] ?? null);
        }
        header('Location: ' . $this->getUrl('bans'));
        exit;
    }

    private function unban()
    {
        $id = (int)($_POST['ban_id'] ?? 0);
        if ($id) {
            BanManager::unban($id);
        }
        header('Location: ' . $this->getUrl('bans'));
        exit;
    }

    private function clientsPage()
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $search = $_GET['search'] ?? '';
        $filter = $_GET['filter'] ?? 'all';

        $results = Verification::getAll($page, 25, $search, $filter);
        $totalPages = ceil($results['total'] / 25);

        $templateDir = __DIR__ . '/../../templates/admin';
        $compileDir = defined('WHMCS_BASE_PATH') ? WHMCS_BASE_PATH . '/templates_c' : dirname(__FILE__, 5) . '/templates_c';

        $smarty = new \Smarty();
        $smarty->setTemplateDir($templateDir);
        $smarty->setCompileDir($compileDir);

        $smarty->assign('verifications', $results['results']);
        $smarty->assign('total', $results['total']);
        $smarty->assign('page', $page);
        $smarty->assign('total_pages', $totalPages);
        $smarty->assign('search', $search);
        $smarty->assign('filter', $filter);
        $smarty->assign('module_url', $this->getUrl());

        return $smarty->fetch('clients.tpl');
    }

    private function manualVerify()
    {
        $id = (int)($_POST['verify_id'] ?? 0);
        if ($id) {
            Verification::markVerified($id);
        }
        header('Location: ' . $this->getUrl('clients'));
        exit;
    }

    private function manualUnverify()
    {
        $id = (int)($_POST['unverify_id'] ?? 0);
        if ($id) {
            Verification::markUnverified($id);
        }
        header('Location: ' . $this->getUrl('clients'));
        exit;
    }

    private function deleteVerification()
    {
        $id = (int)($_POST['delete_id'] ?? 0);
        if ($id) {
            Verification::delete($id);
        }
        header('Location: ' . $this->getUrl('clients'));
        exit;
    }

    private function logsPage()
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $search = $_GET['search'] ?? '';
        $action = $_GET['action_filter'] ?? '';

        $results = ActivityLog::getAll($page, 50, $search, $action);
        $totalPages = ceil($results['total'] / 50);

        $templateDir = __DIR__ . '/../../templates/admin';
        $compileDir = defined('WHMCS_BASE_PATH') ? WHMCS_BASE_PATH . '/templates_c' : dirname(__FILE__, 5) . '/templates_c';

        $smarty = new \Smarty();
        $smarty->setTemplateDir($templateDir);
        $smarty->setCompileDir($compileDir);

        $smarty->assign('logs', $results['results']);
        $smarty->assign('total', $results['total']);
        $smarty->assign('page', $page);
        $smarty->assign('total_pages', $totalPages);
        $smarty->assign('search', $search);
        $smarty->assign('action_filter', $action);
        $smarty->assign('module_url', $this->getUrl());

        return $smarty->fetch('logs.tpl');
    }

    private function getUrl($action = null)
    {
        $base = 'addonmodules.php?module=emailverificationpro';
        if ($action) {
            $base .= '&action=' . $action;
        }
        return $base;
    }
}
