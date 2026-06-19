<?php

namespace EmailVerificationPro\Core;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

if (!class_exists('Capsule') && class_exists('WHMCS\\Database\\Capsule')) {
    class_alias('WHMCS\\Database\\Capsule', 'Capsule');
}

class Verification
{
    public static function generateToken($length = 64)
    {
        return bin2hex(random_bytes($length / 2));
    }

    public static function create($clientId, $email)
    {
        $existing = \Capsule::table('mod_emailverificationpro_verification')
            ->where('client_id', $clientId)
            ->where('is_verified', 0)
            ->first();

        if ($existing) {
            return $existing->token;
        }

        $token = self::generateToken();
        $expiryDays = (int)Database::setting('verification_expiry_days', 3);

        \Capsule::table('mod_emailverificationpro_verification')->insert([
            'client_id'   => $clientId,
            'email'       => $email,
            'token'       => $token,
            'is_verified' => 0,
            'expires_at'  => date('Y-m-d H:i:s', strtotime("+{$expiryDays} days")),
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    public static function verify($token)
    {
        $record = \Capsule::table('mod_emailverificationpro_verification')
            ->where('token', $token)
            ->where('is_verified', 0)
            ->first();

        if (!$record) {
            return ['success' => false, 'message' => 'Invalid or already verified.'];
        }

        if ($record->expires_at && strtotime($record->expires_at) < time()) {
            return ['success' => false, 'message' => 'Verification link has expired.'];
        }

        \Capsule::table('mod_emailverificationpro_verification')
            ->where('id', $record->id)
            ->update([
                'is_verified' => 1,
                'verified_at' => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);

        \Capsule::table('tblclients')
            ->where('id', $record->client_id)
            ->update(['verified' => 1]);

        ActivityLog::add($record->client_id, 'email_verified', "Email {$record->email} verified via token.");

        return ['success' => true, 'client_id' => $record->client_id];
    }

    public static function isVerified($clientId)
    {
        if (!$clientId) {
            return true;
        }

        $record = \Capsule::table('mod_emailverificationpro_verification')
            ->where('client_id', $clientId)
            ->where('is_verified', 1)
            ->first();

        return (bool)$record;
    }

    public static function getByClientId($clientId)
    {
        return \Capsule::table('mod_emailverificationpro_verification')
            ->where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->first();
    }

    public static function getByToken($token)
    {
        return \Capsule::table('mod_emailverificationpro_verification')
            ->where('token', $token)
            ->first();
    }

    public static function resend($id)
    {
        $maxResend = (int)Database::setting('resend_email_limit', 5);

        $record = \Capsule::table('mod_emailverificationpro_verification')->find($id);
        if (!$record) {
            return false;
        }

        if ($record->resend_count >= $maxResend) {
            return false;
        }

        $newToken = self::generateToken();
        $expiryDays = (int)Database::setting('verification_expiry_days', 3);

        \Capsule::table('mod_emailverificationpro_verification')
            ->where('id', $id)
            ->update([
                'token'          => $newToken,
                'resend_count'   => $record->resend_count + 1,
                'last_resend_at' => date('Y-m-d H:i:s'),
                'expires_at'     => date('Y-m-d H:i:s', strtotime("+{$expiryDays} days")),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

        return $newToken;
    }

    public static function sendVerificationEmail($clientId, $email, $token)
    {
        $tplId = Database::setting('verification_template_id', 0);
        $verifyUrl = self::getVerifyUrl($token);

        if ($tplId && $tplId != 0) {
            $mergeFields = [
                'client_id'  => $clientId,
                'email'      => $email,
                'token'      => $token,
                'verify_url' => $verifyUrl,
            ];
            $postarray = [
                'messagename'  => $tplId,
                'custommergid' => $clientId,
                'mergefields'  => $mergeFields,
            ];
            $result = \localAPI('SendEmail', $postarray);
            if ($result['result'] == 'success') {
                return true;
            }
        }

        $subject = "Verify Your Email Address";
        $body = "Hello,\n\nPlease verify your email address by clicking the link below:\n\n{$verifyUrl}\n\nThis link will expire in " . Database::setting('verification_expiry_days', 3) . " days.\n\nIf you did not create an account, please ignore this email.";

        $mailer = new \Mailer();
        $mailer->setTo($email);
        $mailer->setSubject($subject);
        $mailer->setMessage($body);
        $mailer->send();

        return true;
    }

    public static function getVerifyUrl($token)
    {
        $whmcsUrl = \Configuration::getWHMCSUrl();
        return rtrim($whmcsUrl, '/') . '/index.php?m=emailverificationpro&evp_token=' . $token;
    }

    public static function getUnverifiedCount()
    {
        return (int)\Capsule::table('mod_emailverificationpro_verification')
            ->where('is_verified', 0)
            ->count();
    }

    public static function getAll($page = 1, $perPage = 25, $search = '', $filter = 'all')
    {
        $query = \Capsule::table('mod_emailverificationpro_verification as v')
            ->leftJoin('tblclients as c', 'c.id', '=', 'v.client_id')
            ->select('v.*', 'c.firstname', 'c.lastname', 'c.companyname');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('v.email', 'LIKE', "%{$search}%")
                    ->orWhere('c.firstname', 'LIKE', "%{$search}%")
                    ->orWhere('c.lastname', 'LIKE', "%{$search}%")
                    ->orWhere('v.token', 'LIKE', "%{$search}%");
            });
        }

        if ($filter === 'verified') {
            $query->where('v.is_verified', 1);
        } elseif ($filter === 'unverified') {
            $query->where('v.is_verified', 0);
        }

        $total = $query->count();
        $results = $query->orderByDesc('v.created_at')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return ['total' => $total, 'results' => $results];
    }

    public static function markVerified($id)
    {
        $record = \Capsule::table('mod_emailverificationpro_verification')->find($id);
        if (!$record) {
            return false;
        }

        \Capsule::table('mod_emailverificationpro_verification')
            ->where('id', $id)
            ->update([
                'is_verified' => 1,
                'verified_at' => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);

        \Capsule::table('tblclients')
            ->where('id', $record->client_id)
            ->update(['verified' => 1]);

        ActivityLog::add($record->client_id, 'admin_verified', "Email {$record->email} manually verified by admin.");

        return true;
    }

    public static function markUnverified($id)
    {
        $record = \Capsule::table('mod_emailverificationpro_verification')->find($id);
        if (!$record) {
            return false;
        }

        \Capsule::table('mod_emailverificationpro_verification')
            ->where('id', $id)
            ->update([
                'is_verified' => 0,
                'verified_at' => null,
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);

        \Capsule::table('tblclients')
            ->where('id', $record->client_id)
            ->update(['verified' => 0]);

        ActivityLog::add($record->client_id, 'admin_unverified', "Email {$record->email} manually unverified by admin.");

        return true;
    }

    public static function delete($id)
    {
        $record = \Capsule::table('mod_emailverificationpro_verification')->find($id);
        if (!$record) {
            return false;
        }

        \Capsule::table('mod_emailverificationpro_verification')->where('id', $id)->delete();

        ActivityLog::add($record->client_id, 'verification_deleted', "Verification record for {$record->email} deleted.");

        return true;
    }
}
