<div class="evp-admin-wrap">
    <style>
        .evp-admin-wrap { padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .evp-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .evp-stat-card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .evp-stat-card h3 { font-size: 28px; margin: 0 0 5px 0; }
        .evp-stat-card p { color: #666; margin: 0; font-size: 13px; }
        .evp-stat-card.red h3 { color: #e74c3c; }
        .evp-stat-card.green h3 { color: #27ae60; }
        .evp-stat-card.blue h3 { color: #3498db; }
        .evp-stat-card.orange h3 { color: #f39c12; }
        .evp-section { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .evp-section h2 { margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 1px solid #eee; font-size: 18px; }
        .evp-table { width: 100%; border-collapse: collapse; }
        .evp-table th, .evp-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 13px; }
        .evp-table th { background: #f8f9fa; font-weight: 600; color: #555; }
        .evp-badge { padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .evp-badge-success { background: #d4edda; color: #155724; }
        .evp-badge-danger { background: #f8d7da; color: #721c24; }
        .evp-badge-info { background: #d1ecf1; color: #0c5460; }
        .evp-btn { display: inline-block; padding: 8px 16px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: 600; border: none; cursor: pointer; }
    </style>

    <h1>Email Verification Pro - Dashboard</h1>

    <div class="evp-stats">
        <div class="evp-stat-card red">
            <h3>{$unverified_count}</h3>
            <p>Unverified Emails</p>
        </div>
        <div class="evp-stat-card green">
            <h3>{$total_verified}</h3>
            <p>Verified Emails</p>
        </div>
        <div class="evp-stat-card blue">
            <h3>{$total_clients}</h3>
            <p>Total Clients</p>
        </div>
        <div class="evp-stat-card orange">
            <h3>{$total_bans}</h3>
            <p>Active Bans</p>
        </div>
    </div>

    <div class="evp-section">
        <h2>Quick Actions</h2>
        <a href="{$module_url}&action=settings" class="evp-btn" style="background:#3498db;color:#fff;margin-right:10px;">Settings</a>
        <a href="{$module_url}&action=clients" class="evp-btn" style="background:#27ae60;color:#fff;margin-right:10px;">Client Management</a>
        <a href="{$module_url}&action=bans" class="evp-btn" style="background:#f39c12;color:#fff;margin-right:10px;">Ban Management</a>
        <a href="{$module_url}&action=logs" class="evp-btn" style="background:#95a5a6;color:#fff;">Activity Logs</a>
    </div>

    <div class="evp-section">
        <h2>Recent Activity</h2>
        {if $recent_logs|@count > 0}
        <table class="evp-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                {foreach $recent_logs as $log}
                <tr>
                    <td>{$log->created_at}</td>
                    <td>{$log->firstname} {$log->lastname}</td>
                    <td><span class="evp-badge evp-badge-info">{$log->action}</span></td>
                    <td>{$log->details}</td>
                    <td>{$log->ip_address}</td>
                </tr>
                {/foreach}
            </tbody>
        </table>
        {else}
        <p>No recent activity.</p>
        {/if}
    </div>
</div>
