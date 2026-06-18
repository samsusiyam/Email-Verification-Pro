<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

if (!class_exists('Capsule') && !class_exists('WHMCS\\Database\\Capsule')) {
    @require_once dirname(__DIR__, 3) . '/init.php';
}
if (!class_exists('Capsule') && !class_exists('WHMCS\\Database\\Capsule')) {
    @require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
}

if (!class_exists('Capsule') && class_exists('WHMCS\\Database\\Capsule')) {
    class_alias('WHMCS\\Database\\Capsule', 'Capsule');
}

require_once __DIR__ . '/lib/Core/Database.php';
require_once __DIR__ . '/lib/Core/Verification.php';
require_once __DIR__ . '/lib/Core/BanManager.php';
require_once __DIR__ . '/lib/Core/ActivityLog.php';
require_once __DIR__ . '/lib/Core/Language.php';
require_once __DIR__ . '/lib/Admin/AdminController.php';
require_once __DIR__ . '/lib/Client/ClientController.php';

function emailverificationpro_config()
{
    return array(
        'name'            => 'Email Verification Pro',
        'description'     => 'Email Verification Module - Checkout & All Pages Modes',
        'version'         => '7.0.3',
        'author'          => 'MD Samsuzzaman Siyam',
        'language'        => 'english',
        'fields'          => array(
            'license_key' => array(
                'FriendlyName' => 'License Key',
                'Type'         => 'text',
                'Size'         => '40',
                'Default'      => '',
                'Description'  => 'Enter your license key',
            ),
        ),
    );
}

function emailverificationpro_activate()
{
    Capsule::schema()->create('mod_emailverificationpro_settings', function ($table) {
        $table->increments('id');
        $table->string('setting_key', 100)->unique();
        $table->text('setting_value')->nullable();
        $table->timestamps();
    });

    Capsule::schema()->create('mod_emailverificationpro_verification', function ($table) {
        $table->increments('id');
        $table->integer('client_id')->unsigned();
        $table->string('email', 255);
        $table->string('token', 64)->unique();
        $table->tinyInteger('is_verified')->default(0);
        $table->timestamp('verified_at')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->integer('resend_count')->default(0);
        $table->timestamp('last_resend_at')->nullable();
        $table->timestamps();

        $table->index('client_id');
        $table->index('token');
        $table->index('is_verified');
    });

    Capsule::schema()->create('mod_emailverificationpro_bans', function ($table) {
        $table->increments('id');
        $table->string('ban_type', 20);
        $table->string('ban_value', 255);
        $table->integer('duration_days')->nullable();
        $table->timestamp('banned_at')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->integer('admin_id')->unsigned()->nullable();
        $table->timestamps();

        $table->index('ban_type');
        $table->index('ban_value');
    });

    Capsule::schema()->create('mod_emailverificationpro_activity_logs', function ($table) {
        $table->increments('id');
        $table->integer('client_id')->unsigned()->nullable();
        $table->string('action', 50);
        $table->text('details')->nullable();
        $table->string('ip_address', 45)->nullable();
        $table->timestamps();

        $table->index('client_id');
        $table->index('action');
    });

    $defaultSettings = array(
        'verification_mode'          => 'checkout',
        'enable_recaptcha'           => '0',
        'recaptcha_site_key'         => '',
        'recaptcha_secret_key'       => '',
        'enable_turnstile'           => '0',
        'turnstile_site_key'         => '',
        'turnstile_secret_key'       => '',
        'auto_terminate_days'        => '0',
        'auto_delete_days'           => '0',
        'auto_resend_days'           => '0',
        'resend_email_limit'         => '5',
        'verification_expiry_days'   => '3',
        'verification_template_id'   => '0',
        'enable_ban_ip'              => '1',
        'enable_ban_email'           => '1',
        'enable_ban_provider'        => '1',
        'ban_duration_days'          => '30',
        'sub_user_verify'            => '1',
        'enable_activity_log'        => '1',
        'blocked_pages_message'      => 'Please verify your email address to access this page.',
    );

    foreach ($defaultSettings as $key => $value) {
        Capsule::table('mod_emailverificationpro_settings')->insert(array(
            'setting_key'   => $key,
            'setting_value' => $value,
        ));
    }

    return array(
        'status'  => 'success',
        'message' => 'Email Verification Pro activated successfully.',
    );
}

function emailverificationpro_deactivate()
{
    Capsule::schema()->dropIfExists('mod_emailverificationpro_settings');
    Capsule::schema()->dropIfExists('mod_emailverificationpro_verification');
    Capsule::schema()->dropIfExists('mod_emailverificationpro_bans');
    Capsule::schema()->dropIfExists('mod_emailverificationpro_activity_logs');

    return array(
        'status'  => 'success',
        'message' => 'Email Verification Pro deactivated.',
    );
}

function emailverificationpro_upgrade($versions)
{
    return array(
        'status'  => 'success',
        'message' => 'Upgrade completed.',
    );
}

function emailverificationpro_output($vars)
{
    $controller = new EmailVerificationPro\Admin\AdminController($vars);
    $controller->handle();
}

function emailverificationpro_clientarea()
{
    $controller = new EmailVerificationPro\Client\ClientController();
    return $controller->handle();
}
