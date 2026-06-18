<div class="evp-admin-wrap">
    <style>
        .evp-admin-wrap { padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .evp-section { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .evp-section h2 { margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 1px solid #eee; font-size: 18px; }
        .evp-table { width: 100%; border-collapse: collapse; }
        .evp-table th, .evp-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 13px; }
        .evp-table th { background: #f8f9fa; font-weight: 600; color: #555; }
        .evp-badge { padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .evp-badge-info { background: #d1ecf1; color: #0c5460; }
        .evp-badge-success { background: #d4edda; color: #155724; }
        .evp-badge-warning { background: #fff3cd; color: #856404; }
        .evp-badge-danger { background: #f8d7da; color: #721c24; }
        .evp-form-inline { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; }
        .evp-form-inline input, .evp-form-inline select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; }
        .evp-btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .evp-btn-primary { background: #3498db; color: #fff; }
        .evp-btn-danger { background: #e74c3c; color: #fff; }
        .evp-pagination { margin-top: 15px; text-align: center; }
        .evp-pagination a { padding: 6px 12px; margin: 0 2px; border: 1px solid #ddd; text-decoration: none; color: #333; border-radius: 4px; font-size: 13px; }
        .evp-pagination a.active { background: #3498db; color: #fff; border-color: #3498db; }
    </style>

    <h1>Activity Logs</h1>

    <div class="evp-section">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h2 style="margin:0; border:none; padding:0;">Logs ({$total} total)</h2>
            <form method="post" action="{$module_url}&action=clear_logs" style="display:inline;">
                <button type="submit" class="evp-btn evp-btn-danger" onclick="return confirm('Clear all activity logs? This cannot be undone.')">Clear All Logs</button>
            </form>
        </div>

        <form method="get" class="evp-form-inline">
            <input type="hidden" name="module" value="emailverificationpro">
            <input type="hidden" name="cmd" value="logs">
            <input type="text" name="search" placeholder="Search details/IP/email..." value="{$search}">
            <select name="action_filter">
                <option value="">All Actions</option>
                <option value="verification_sent" {if $action_filter == 'verification_sent'}selected{/if}>Verification Sent</option>
                <option value="email_verified" {if $action_filter == 'email_verified'}selected{/if}>Email Verified</option>
                <option value="admin_verified" {if $action_filter == 'admin_verified'}selected{/if}>Admin Verified</option>
                <option value="resend_success" {if $action_filter == 'resend_success'}selected{/if}>Resend Success</option>
                <option value="auto_resend" {if $action_filter == 'auto_resend'}selected{/if}>Auto Resend</option>
                <option value="auto_terminated" {if $action_filter == 'auto_terminated'}selected{/if}>Auto Terminated</option>
                <option value="auto_deleted" {if $action_filter == 'auto_deleted'}selected{/if}>Auto Deleted</option>
                <option value="ip_banned" {if $action_filter == 'ip_banned'}selected{/if}>IP Banned</option>
                <option value="email_banned" {if $action_filter == 'email_banned'}selected{/if}>Email Banned</option>
                <option value="provider_banned" {if $action_filter == 'provider_banned'}selected{/if}>Provider Banned</option>
            </select>
            <button type="submit" class="evp-btn evp-btn-primary">Filter</button>
        </form>

        {if $logs|@count > 0}
        <table class="evp-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                {foreach $logs as $log}
                <tr>
                    <td>{$log->id}</td>
                    <td>{$log->created_at}</td>
                    <td>
                        {if $log->client_id}
                            {$log->firstname} {$log->lastname} ({$log->client_email})
                        {else}
                            -
                        {/if}
                    </td>
                    <td>
                        {if $log->action == 'email_verified' || $log->action == 'admin_verified'}
                            <span class="evp-badge evp-badge-success">{$log->action}</span>
                        {elseif $log->action == 'auto_terminated' || $log->action == 'auto_deleted' || $log->action == 'ip_banned' || $log->action == 'email_banned'}
                            <span class="evp-badge evp-badge-danger">{$log->action}</span>
                        {elseif $log->action == 'provider_banned' || $log->action == 'verification_deleted'}
                            <span class="evp-badge evp-badge-warning">{$log->action}</span>
                        {else}
                            <span class="evp-badge evp-badge-info">{$log->action}</span>
                        {/if}
                    </td>
                    <td>{$log->details}</td>
                    <td>{$log->ip_address}</td>
                </tr>
                {/foreach}
            </tbody>
        </table>

        {if $total_pages > 1}
        <div class="evp-pagination">
            {for $p=1 to $total_pages}
                <a href="{$module_url}&action=logs&page={$p}&search={$search}&action_filter={$action_filter}" {if $p == $page}class="active"{/if}>{$p}</a>
            {/for}
        </div>
        {/if}
        {else}
        <p>No activity logs found.</p>
        {/if}
    </div>
</div>
