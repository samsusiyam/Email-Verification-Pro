<?php

namespace EmailVerificationPro\Core;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

class ActivityLog
{
    public static function add($clientId, $action, $details = '', $ipAddress = null)
    {
        if (Database::setting('enable_activity_log', '1') !== '1') {
            return;
        }

        if (!$ipAddress) {
            $ipAddress = self::getClientIp();
        }

        \Capsule::table('mod_emailverificationpro_activity_logs')->insert([
            'client_id'  => $clientId,
            'action'     => $action,
            'details'    => $details,
            'ip_address' => $ipAddress,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function getAll($page = 1, $perPage = 50, $search = '', $action = '', $clientId = null)
    {
        $query = \Capsule::table('mod_emailverificationpro_activity_logs as l')
            ->leftJoin('tblclients as c', 'c.id', '=', 'l.client_id')
            ->select('l.*', 'c.firstname', 'c.lastname', 'c.email as client_email');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('l.details', 'LIKE', "%{$search}%")
                    ->orWhere('l.ip_address', 'LIKE', "%{$search}%")
                    ->orWhere('c.email', 'LIKE', "%{$search}%");
            });
        }

        if ($action) {
            $query->where('l.action', $action);
        }

        if ($clientId) {
            $query->where('l.client_id', $clientId);
        }

        $total = $query->count();
        $results = $query->orderByDesc('l.created_at')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return ['total' => $total, 'results' => $results];
    }

    public static function clear()
    {
        \Capsule::table('mod_emailverificationpro_activity_logs')->truncate();
    }

    public static function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
