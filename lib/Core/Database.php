<?php

namespace EmailVerificationPro\Core;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

if (!class_exists('Capsule') && class_exists('WHMCS\\Database\\Capsule')) {
    class_alias('WHMCS\\Database\\Capsule', 'Capsule');
}

class Database
{
    public static function setting($key, $default = null)
    {
        $row = \Capsule::table('mod_emailverificationpro_settings')
            ->where('setting_key', $key)
            ->first();
        return $row ? $row->setting_value : $default;
    }

    public static function settingAll()
    {
        $rows = \Capsule::table('mod_emailverificationpro_settings')->get();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row->setting_key] = $row->setting_value;
        }
        return $settings;
    }

    public static function updateSetting($key, $value)
    {
        \Capsule::table('mod_emailverificationpro_settings')
            ->where('setting_key', $key)
            ->update(['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public static function updateSettings(array $data)
    {
        foreach ($data as $key => $value) {
            self::updateSetting($key, $value);
        }
    }
}
