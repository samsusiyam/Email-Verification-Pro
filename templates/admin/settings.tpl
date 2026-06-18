<div class="evp-admin-wrap">
    <style>
        .evp-admin-wrap { padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .evp-section { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .evp-section h2 { margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 1px solid #eee; font-size: 18px; }
        .evp-form-group { margin-bottom: 15px; }
        .evp-form-group label { display: block; font-weight: 600; margin-bottom: 5px; font-size: 13px; color: #333; }
        .evp-form-group input[type="text"], .evp-form-group input[type="number"], .evp-form-group select, .evp-form-group textarea {
            width: 100%; max-width: 500px; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;
        }
        .evp-form-group .help { font-size: 12px; color: #999; margin-top: 3px; }
        .evp-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; max-width: 600px; }
        .evp-alert { padding: 10px 15px; border-radius: 4px; margin-bottom: 15px; }
        .evp-alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .evp-toggle { position: relative; display: inline-block; width: 44px; height: 22px; }
        .evp-toggle input { opacity: 0; width: 0; height: 0; }
        .evp-toggle .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #ccc; transition: .3s; border-radius: 22px; }
        .evp-toggle .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background: white; transition: .3s; border-radius: 50%; }
        .evp-toggle input:checked + .slider { background: #3498db; }
        .evp-toggle input:checked + .slider:before { transform: translateX(22px); }
    </style>

    <h1>Email Verification Pro - Settings</h1>

    {if $saved}
    <div class="evp-alert evp-alert-success">Settings saved successfully.</div>
    {/if}

    <form method="post" action="{$module_url}&action=save_settings">
        <div class="evp-section">
            <h2>General Settings</h2>
            <div class="evp-form-group">
                <label>Verification Mode</label>
                <select name="verification_mode">
                    <option value="checkout" {if $settings.verification_mode == 'checkout'}selected{/if}>Checkout (Verify before order)</option>
                    <option value="allpages" {if $settings.verification_mode == 'allpages'}selected{/if}>All Pages (Block all until verified)</option>
                </select>
            </div>
            <div class="evp-form-group">
                <label>Blocked Pages Message</label>
                <textarea name="blocked_pages_message" rows="3">{$settings.blocked_pages_message}</textarea>
            </div>
        </div>

        <div class="evp-section">
            <h2>Google reCAPTCHA v3</h2>
            <div class="evp-form-group">
                <label>Enable reCAPTCHA</label>
                <label class="evp-toggle">
                    <input type="hidden" name="enable_recaptcha" value="0">
                    <input type="checkbox" name="enable_recaptcha" value="1" {if $settings.enable_recaptcha == '1'}checked{/if}>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="evp-form-row">
                <div class="evp-form-group">
                    <label>Site Key</label>
                    <input type="text" name="recaptcha_site_key" value="{$settings.recaptcha_site_key}">
                </div>
                <div class="evp-form-group">
                    <label>Secret Key</label>
                    <input type="text" name="recaptcha_secret_key" value="{$settings.recaptcha_secret_key}">
                </div>
            </div>
        </div>

        <div class="evp-section">
            <h2>Cloudflare Turnstile</h2>
            <div class="evp-form-group">
                <label>Enable Turnstile</label>
                <label class="evp-toggle">
                    <input type="hidden" name="enable_turnstile" value="0">
                    <input type="checkbox" name="enable_turnstile" value="1" {if $settings.enable_turnstile == '1'}checked{/if}>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="evp-form-row">
                <div class="evp-form-group">
                    <label>Site Key</label>
                    <input type="text" name="turnstile_site_key" value="{$settings.turnstile_site_key}">
                </div>
                <div class="evp-form-group">
                    <label>Secret Key</label>
                    <input type="text" name="turnstile_secret_key" value="{$settings.turnstile_secret_key}">
                </div>
            </div>
        </div>

        <div class="evp-section">
            <h2>Auto Actions</h2>
            <div class="evp-form-row">
                <div class="evp-form-group">
                    <label>Auto-Terminate After (Days, 0=off)</label>
                    <input type="number" name="auto_terminate_days" value="{$settings.auto_terminate_days}" min="0">
                    <div class="help">Terminate unverified accounts after X days</div>
                </div>
                <div class="evp-form-group">
                    <label>Auto-Delete After (Days, 0=off)</label>
                    <input type="number" name="auto_delete_days" value="{$settings.auto_delete_days}" min="0">
                    <div class="help">Delete unverified accounts with no orders</div>
                </div>
            </div>
            <div class="evp-form-row">
                <div class="evp-form-group">
                    <label>Auto-Resend After (Days, 0=off)</label>
                    <input type="number" name="auto_resend_days" value="{$settings.auto_resend_days}" min="0">
                </div>
                <div class="evp-form-group">
                    <label>Max Resend Count</label>
                    <input type="number" name="resend_email_limit" value="{$settings.resend_email_limit}" min="1">
                </div>
            </div>
            <div class="evp-form-group">
                <label>Verification Expiry (Days)</label>
                <input type="number" name="verification_expiry_days" value="{$settings.verification_expiry_days}" min="1" style="max-width:200px;">
            </div>
            <div class="evp-form-group">
                <label>Verification Email Template ID (0=custom)</label>
                <input type="number" name="verification_template_id" value="{$settings.verification_template_id}" min="0" style="max-width:200px;">
                <div class="help">Set to 0 to use default email sending method</div>
            </div>
        </div>

        <div class="evp-section">
            <h2>Ban Settings</h2>
            <div class="evp-form-group">
                <label>Enable IP Banning</label>
                <label class="evp-toggle">
                    <input type="hidden" name="enable_ban_ip" value="0">
                    <input type="checkbox" name="enable_ban_ip" value="1" {if $settings.enable_ban_ip == '1'}checked{/if}>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="evp-form-group">
                <label>Enable Email Banning</label>
                <label class="evp-toggle">
                    <input type="hidden" name="enable_ban_email" value="0">
                    <input type="checkbox" name="enable_ban_email" value="1" {if $settings.enable_ban_email == '1'}checked{/if}>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="evp-form-group">
                <label>Enable Email Provider Banning</label>
                <label class="evp-toggle">
                    <input type="hidden" name="enable_ban_provider" value="0">
                    <input type="checkbox" name="enable_ban_provider" value="1" {if $settings.enable_ban_provider == '1'}checked{/if}>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="evp-form-group">
                <label>Ban Duration (Days)</label>
                <input type="number" name="ban_duration_days" value="{$settings.ban_duration_days}" min="1" style="max-width:200px;">
            </div>
        </div>

        <div class="evp-section">
            <h2>Miscellaneous</h2>
            <div class="evp-form-group">
                <label>Sub-user Email Verification</label>
                <label class="evp-toggle">
                    <input type="hidden" name="sub_user_verify" value="0">
                    <input type="checkbox" name="sub_user_verify" value="1" {if $settings.sub_user_verify == '1'}checked{/if}>
                    <span class="slider"></span>
                </label>
                <div class="help">Require verification when sub-user email is changed</div>
            </div>
            <div class="evp-form-group">
                <label>Activity Logging</label>
                <label class="evp-toggle">
                    <input type="hidden" name="enable_activity_log" value="0">
                    <input type="checkbox" name="enable_activity_log" value="1" {if $settings.enable_activity_log == '1'}checked{/if}>
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        <button type="submit" class="evp-btn" style="padding: 10px 30px; background: #3498db; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">Save Settings</button>
    </form>
</div>
