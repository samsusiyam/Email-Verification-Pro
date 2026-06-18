<div class="evp-admin-wrap">
    <style>
        .evp-admin-wrap { padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .evp-section { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .evp-section h2 { margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 1px solid #eee; font-size: 18px; }
        .evp-table { width: 100%; border-collapse: collapse; }
        .evp-table th, .evp-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 13px; }
        .evp-table th { background: #f8f9fa; font-weight: 600; color: #555; }
        .evp-badge { padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .evp-badge-danger { background: #f8d7da; color: #721c24; }
        .evp-badge-warning { background: #fff3cd; color: #856404; }
        .evp-badge-purple { background: #e8daef; color: #6c3483; }
        .evp-form-inline { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; }
        .evp-form-inline input, .evp-form-inline select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; }
        .evp-btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; text-decoration: none; display: inline-block; }
        .evp-btn-danger { background: #e74c3c; color: #fff; }
        .evp-btn-primary { background: #3498db; color: #fff; }
        .evp-btn-success { background: #27ae60; color: #fff; }
        .evp-pagination { margin-top: 15px; text-align: center; }
        .evp-pagination a { padding: 6px 12px; margin: 0 2px; border: 1px solid #ddd; text-decoration: none; color: #333; border-radius: 4px; font-size: 13px; }
        .evp-pagination a.active { background: #3498db; color: #fff; border-color: #3498db; }
    </style>

    <h1>Ban Management</h1>

    <div class="evp-section">
        <h2>Add Ban</h2>
        <div style="margin-bottom: 15px;">
            <h3 style="font-size:14px; margin-bottom:8px;">Ban IP Address</h3>
            <form method="post" action="{$module_url}&action=ban_ip" class="evp-form-inline">
                <input type="text" name="ip_address" placeholder="IP Address" required>
                <input type="number" name="duration_days" placeholder="Days (empty=permanent)" min="1">
                <button type="submit" class="evp-btn evp-btn-danger">Ban IP</button>
            </form>
        </div>
        <div style="margin-bottom: 15px;">
            <h3 style="font-size:14px; margin-bottom:8px;">Ban Email</h3>
            <form method="post" action="{$module_url}&action=ban_email" class="evp-form-inline">
                <input type="email" name="email" placeholder="Email Address" required>
                <input type="number" name="duration_days" placeholder="Days (empty=permanent)" min="1">
                <button type="submit" class="evp-btn evp-btn-danger">Ban Email</button>
            </form>
        </div>
        <div>
            <h3 style="font-size:14px; margin-bottom:8px;">Ban Email Provider</h3>
            <form method="post" action="{$module_url}&action=ban_provider" class="evp-form-inline">
                <input type="text" name="provider" placeholder="e.g. tempmail.com" required>
                <button type="submit" class="evp-btn evp-btn-danger">Ban Provider</button>
            </form>
        </div>
    </div>

    <div class="evp-section">
        <h2>Ban List ({$total} records)</h2>
        <form method="get" class="evp-form-inline">
            <input type="hidden" name="module" value="emailverificationpro">
            <input type="hidden" name="cmd" value="bans">
            <input type="text" name="search" placeholder="Search ban value..." value="{$search}">
            <select name="type">
                <option value="all" {if $type == 'all'}selected{/if}>All Types</option>
                <option value="ip" {if $type == 'ip'}selected{/if}>IP</option>
                <option value="email" {if $type == 'email'}selected{/if}>Email</option>
                <option value="provider" {if $type == 'provider'}selected{/if}>Provider</option>
            </select>
            <button type="submit" class="evp-btn evp-btn-primary">Filter</button>
        </form>

        {if $bans|@count > 0}
        <table class="evp-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Duration</th>
                    <th>Banned At</th>
                    <th>Expires At</th>
                    <th>By</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                {foreach $bans as $ban}
                <tr>
                    <td>{$ban->id}</td>
                    <td>
                        {if $ban->ban_type == 'ip'}
                            <span class="evp-badge evp-badge-danger">IP</span>
                        {elseif $ban->ban_type == 'email'}
                            <span class="evp-badge evp-badge-warning">Email</span>
                        {else}
                            <span class="evp-badge evp-badge-purple">Provider</span>
                        {/if}
                    </td>
                    <td>{$ban->ban_value}</td>
                    <td>{if $ban->duration_days}{$ban->duration_days} days{else}Permanent{/if}</td>
                    <td>{$ban->banned_at}</td>
                    <td>{if $ban->expires_at}{$ban->expires_at}{else}Never{/if}</td>
                    <td>{$ban->admin_username}</td>
                    <td>
                        <form method="post" action="{$module_url}&action=unban" style="display:inline;">
                            <input type="hidden" name="ban_id" value="{$ban->id}">
                            <button type="submit" class="evp-btn evp-btn-success" onclick="return confirm('Remove this ban?')">Unban</button>
                        </form>
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>

        {if $total_pages > 1}
        <div class="evp-pagination">
            {for $p=1 to $total_pages}
                <a href="{$module_url}&cmd=bans&page={$p}&search={$search}&type={$type}" {if $p == $page}class="active"{/if}>{$p}</a>
            {/for}
        </div>
        {/if}
        {else}
        <p>No bans found.</p>
        {/if}
    </div>
</div>
