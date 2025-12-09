<?php
$assets = get_option('wpcb_page_assets', ['styles'=>[],'scripts'=>[]]);
?>
<div class="wrap p-6">
    <h1 class="text-2xl font-bold mb-4">Asset Overview</h1>
    <div class="mb-4 space-x-2">
        <button id="wpcb-flush-gd" class="button button-primary">Flush GoDaddy Cache</button>
        <button id="wpcb-flush-object" class="button button-secondary">Flush Object Cache</button>
        <button id="wpcb-full-scan" class="button button-secondary">Scan Current Page</button>
        <button id="wpcb-scan-all-pages" class="button button-secondary">Scan All Pages</button>
    </div>

    <select id="wpcb-asset-view" class="mb-4 p-2 border rounded">
        <option value="single">Current Page Assets</option>
        <option value="all">All Pages Assets</option>
    </select>

    <table id="wpcb-assets-table" class="min-w-full table-auto bg-white shadow rounded">
        <thead class="bg-gray-100">
            <tr>
                <th>Type</th><th>URL</th><th>Location</th><th>Group</th><th>Last Modified</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach(['styles','scripts'] as $type):
                foreach($assets[$type]??[] as $a): ?>
                    <tr>
                        <td class="px-4 py-2 border"><?= esc_html($type) ?></td>
                        <td class="px-4 py-2 border break-words"><?= esc_html($a['url']) ?></td>
                        <td class="px-4 py-2 border"><?= esc_html($a['location']) ?></td>
                        <td class="px-4 py-2 border"><?= esc_html($a['group']) ?></td>
                        <td class="px-4 py-2 border"><?= esc_html($a['modified']) ?></td>
                    </tr>
            <?php endforeach; endforeach; ?>
        </tbody>
    </table>
</div>
