<div class="evp-admin-wrap">
    <style>
        .evp-admin-wrap { padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .evp-section { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .evp-section h2 { margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 1px solid #eee; font-size: 18px; }
        .evp-table { width: 100%; border-collapse: collapse; }
        .evp-table th, .evp-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 13px; }
        .evp-table th { background: #f8f9fa; font-weight: 600; color: #555; }
        .evp-badge { padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .evp-badge-success { background: #d4edda; color: #155724; }
        .evp-badge-danger { background: #f8d7da; color: #721c24; }
        .evp-form-inline { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; }
        .evp-form-inline input, .evp-form-inline select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; }
        .evp-btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; }
        .evp-btn-success { background: #27ae60; color: #fff; }
        .evp-btn-danger { background: #e74c3c; color: #fff; }
        .evp-btn-primary { background: #3498db; color: #fff; }
        .evp-pagination { margin-top: 15px; text-align: center; }
        .evp-pagination a { padding: 6px 12px; margin: 0 2px; border: 1px solid #ddd; text-decoration: none; color: #333; border-radius: 4px; font-size: 13px; }
        .evp-pagination a.active { background: #3498db; color: #fff; border-color: #3498db; }
    </style>

    <h1>Client Email Verification Management</h1>

    <div class="evp-section">
        <h2>Verification Records ({$total} total)</h2>
        <form method="get" class="evp-form-inline">
            <input type="hidden" name="module" value="emailverificationpro">
            <input type="hidden" name="cmd" value="clients">
            <input type="text" name="search" placeholder="Search email/name/token..." value="{$search}">
            <select name="filter">
                <option value="all" {if $filter == 'all'}selected{/if}>All</option>
                <option value="verified" {if $filter == 'verified'}selected{/if}>Verified</option>
                <option value="unverified" {if $filter == 'unverified'}selected{/if}>Unverified</option>
            </select>
            <button type="submit" class="evp-btn evp-btn-primary">Filter</button>
        </form>

        {if $verifications|@count > 0}
        <table class="evp-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Token</th>
                    <th>Created</th>
                    <th>Verified At</th>
                    <th>Expires</th>
                    <th>Resends</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                {foreach $verifications as $v}
                <tr>
                    <td>{$v->id}</td>
                    <td>{$v->firstname} {$v->lastname}</td>
                    <td>{$v->email}</td>
                    <td>
                        {if $v->is_verified}
                            <span class="evp-badge evp-badge-success">Verified</span>
                        {else}
                            <span class="evp-badge evp-badge-danger">Unverified</span>
                        {/if}
                    </td>
                    <td title="{$v->token}">{$v->token|substr:0:12}...</td>
                    <td>{$v->created_at}</td>
                    <td>{if $v->verified_at}{$v->verified_at}{else}-{/if}</td>
                    <td>{$v->expires_at}</td>
                    <td>{$v->resend_count}</td>
                    <td>
                        {if !$v->is_verified}
                        <form method="post" action="{$module_url}&action=manual_verify" style="display:inline;">
                            <input type="hidden" name="verify_id" value="{$v->id}">
                            <button type="submit" class="evp-btn evp-btn-success" onclick="return confirm('Manually verify this email?')">Verify</button>
                        </form>
                        {/if}
                        <form method="post" action="{$module_url}&action=delete_verification" style="display:inline;">
                            <input type="hidden" name="delete_id" value="{$v->id}">
                            <button type="submit" class="evp-btn evp-btn-danger" onclick="return confirm('Delete this record?')">Delete</button>
                        </form>
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>

        {if $total_pages > 1}
        <div class="evp-pagination">
            {for $p=1 to $total_pages}
                <a href="{$module_url}&action=clients&page={$p}&search={$search}&filter={$filter}" {if $p == $page}class="active"{/if}>{$p}</a>
            {/for}
        </div>
        {/if}
        {else}
        <p>No verification records found.</p>
        {/if}
    </div>
</div>
