<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification Required</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .evp-container { background: #fff; border-radius: 12px; box-shadow: 0 2px 20px rgba(0,0,0,0.1); padding: 40px; max-width: 480px; width: 90%; text-align: center; }
        .evp-icon { width: 80px; height: 80px; margin: 0 auto 20px; background: #3498db; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .evp-icon svg { width: 40px; height: 40px; fill: #fff; }
        h1 { color: #2c3e50; font-size: 24px; margin-bottom: 10px; }
        .evp-subtitle { color: #7f8c8d; font-size: 14px; margin-bottom: 25px; }
        .evp-email-display { background: #ecf0f1; padding: 12px 20px; border-radius: 6px; color: #2c3e50; font-weight: 600; margin-bottom: 20px; word-break: break-all; }
        .evp-btn { display: inline-block; padding: 12px 30px; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.3s; }
        .evp-btn-primary { background: #3498db; color: #fff; }
        .evp-btn-primary:hover { background: #2980b9; }
        .evp-btn-resend { background: #ecf0f1; color: #7f8c8d; margin-top: 10px; }
        .evp-btn-resend:hover { background: #bdc3c7; color: #2c3e50; }
        .evp-alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .evp-alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .evp-alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .evp-alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .evp-footer { margin-top: 25px; font-size: 12px; color: #95a5a6; }
    </style>
</head>
<body>
    <div class="evp-container">
        <div class="evp-icon">
            <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
        </div>

        <h1>Verify Your Email Address</h1>
        <p class="evp-subtitle">We have sent a verification link to your email. Please check your inbox.</p>

        {if $msg}
            <div class="evp-alert evp-alert-{$msg_type}">{$msg}</div>
        {/if}

        <div class="evp-email-display">{$email}</div>

        {if $enable_recaptcha == '1'}
            <script src="https://www.google.com/recaptcha/api.js?render={$recaptcha_site_key}"></script>
        {/if}
        {if $enable_turnstile == '1'}
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        {/if}

        <a href="index.php?m=emailverificationpro&evp_action=resend" class="evp-btn evp-btn-resend" onclick="return confirm('Are you sure you want to resend the verification email?');">
            Resend Verification Email
        </a>

        <p class="evp-footer">If you don't see the email, please check your spam/junk folder.</p>
    </div>
</body>
</html>
