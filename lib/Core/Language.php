<?php

namespace EmailVerificationPro\Core;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

class Language
{
    private static $strings = [];
    private static $loaded = false;

    public static function load($lang = null)
    {
        if (self::$loaded) {
            return;
        }

        if (!$lang) {
            $lang = $_SESSION['language'] ?? 'english';
        }

        $langFile = __DIR__ . '/../../lang/' . $lang . '.php';
        $defaultFile = __DIR__ . '/../../lang/english.php';

        if (file_exists($langFile)) {
            self::$strings = include $langFile;
        } elseif (file_exists($defaultFile)) {
            self::$strings = include $defaultFile;
        } else {
            self::$strings = [];
        }

        self::$loaded = true;
    }

    public static function get($key, $replacements = [])
    {
        self::load();

        $string = self::$strings[$key] ?? $key;

        if ($replacements) {
            foreach ($replacements as $k => $v) {
                $string = str_replace('{' . $k . '}', $v, $string);
            }
        }

        return $string;
    }
}
