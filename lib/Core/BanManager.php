<?php

namespace EmailVerificationPro\Core;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

class BanManager
{
    public static function banIp($ip, $durationDays = null, $adminId = null)
    {
        if (self::isIpBanned($ip)) {
            return false;
        }
        $days = $durationDays ?: (int)Database::setting('ban_duration_days', 30);
        \Capsule::table('mod_emailverificationpro_bans')->insert([
            'ban_type'      => 'ip',
            'ban_value'     => $ip,
            'duration_days' => $days,
            'banned_at'     => date('Y-m-d H:i:s'),
            'expires_at'    => date('Y-m-d H:i:s', strtotime("+{$days} days")),
            'admin_id'      => $adminId,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
        ActivityLog::add(null, 'ip_banned', "IP {$ip} banned for {$days} days.", $ip);
        return true;
    }

    public static function banEmail($email, $durationDays = null, $adminId = null)
    {
        if (self::isEmailBanned($email)) {
            return false;
        }
        $days = $durationDays ?: (int)Database::setting('ban_duration_days', 30);
        \Capsule::table('mod_emailverificationpro_bans')->insert([
            'ban_type'      => 'email',
            'ban_value'     => $email,
            'duration_days' => $days,
            'banned_at'     => date('Y-m-d H:i:s'),
            'expires_at'    => date('Y-m-d H:i:s', strtotime("+{$days} days")),
            'admin_id'      => $adminId,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
        ActivityLog::add(null, 'email_banned', "Email {$email} banned for {$days} days.");
        return true;
    }

    public static function banEmailProvider($provider, $adminId = null)
    {
        if (self::isProviderBanned($provider)) {
            return false;
        }
        \Capsule::table('mod_emailverificationpro_bans')->insert([
            'ban_type'      => 'provider',
            'ban_value'     => $provider,
            'duration_days' => null,
            'banned_at'     => date('Y-m-d H:i:s'),
            'expires_at'    => null,
            'admin_id'      => $adminId,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
        ActivityLog::add(null, 'provider_banned', "Email provider {$provider} permanently banned.");
        return true;
    }

    public static function unban($id)
    {
        return \Capsule::table('mod_emailverificationpro_bans')->where('id', $id)->delete();
    }

    public static function isIpBanned($ip)
    {
        $bans = \Capsule::table('mod_emailverificationpro_bans')
            ->where('ban_type', 'ip')
            ->where('ban_value', $ip)
            ->get();

        foreach ($bans as $ban) {
            if ($ban->expires_at === null || strtotime($ban->expires_at) > time()) {
                return true;
            }
        }
        return false;
    }

    public static function isEmailBanned($email)
    {
        $bans = \Capsule::table('mod_emailverificationpro_bans')
            ->where('ban_type', 'email')
            ->where('ban_value', $email)
            ->get();

        foreach ($bans as $ban) {
            if ($ban->expires_at === null || strtotime($ban->expires_at) > time()) {
                return true;
            }
        }
        return false;
    }

    public static function isProviderBanned($provider)
    {
        return \Capsule::table('mod_emailverificationpro_bans')
            ->where('ban_type', 'provider')
            ->where('ban_value', $provider)
            ->exists();
    }

    public static function isEmailDomainBanned($email)
    {
        $parts = explode('@', $email);
        if (count($parts) != 2) {
            return false;
        }
        $provider = strtolower($parts[1]);
        return self::isProviderBanned($provider);
    }

    public static function getAll($page = 1, $perPage = 25, $search = '', $type = 'all')
    {
        $query = \Capsule::table('mod_emailverificationpro_bans as b')
            ->leftJoin('tbladmins as a', 'a.id', '=', 'b.admin_id')
            ->select('b.*', 'a.username as admin_username');

        if ($search) {
            $query->where('b.ban_value', 'LIKE', "%{$search}%");
        }
        if ($type !== 'all') {
            $query->where('b.ban_type', $type);
        }

        $total = $query->count();
        $results = $query->orderByDesc('b.created_at')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return ['total' => $total, 'results' => $results];
    }

    public static function cleanExpired()
    {
        \Capsule::table('mod_emailverificationpro_bans')
            ->where('ban_type', '!=', 'provider')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', date('Y-m-d H:i:s'))
            ->delete();
    }
}
