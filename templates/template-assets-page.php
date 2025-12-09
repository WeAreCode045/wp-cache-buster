<?php
$assets = get_option('wpcb_page_assets', ['styles'=>[],'scripts'=>[]]);
?>
<div class="wrap p-6">
    <h1 class="text-3xl font-bold mb-6">Asset Overview</h1>

    <!-- Action Buttons -->
    <div class="mb-6 flex flex-wrap gap-2">
        <button id="wpcb-flush-gd" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">Flush GoDaddy Cache</button>
        <button id="wpcb-flush-object" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">Flush Object Cache</button>
        <button id="wpcb-full-scan" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded shadow">Scan Current Page</button>
        <button id="wpcb-scan-all-pages" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded shadow">Scan All Pages</button>
    </div>

    <!-- Dropdown to select view -->
    <div class="mb-4">
        <select id="wpcb-asset-view" class="p-2 border rounded">
            <option value="single">Current Page Assets</option>
            <option value="all">All Pages Assets</option>
        </select>
    </div>

    <!-- Assets Table -->
    <div class="overflow-x-auto bg-white shadow rounded">
        <table id="wpcb-assets-table" class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left text-sm font-semibold">Type</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold">URL</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold">Location</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold">Group</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold">Last Modified</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach(['styles','scripts'] as $type):
                    foreach($assets[$type]??[] as $a): ?>
                        <tr>
                            <td class="px-4 py-2"><?= esc_html($type) ?></td>
                            <td class="px-4 py-2 break-words"><a href="<?= esc_url($a['url']) ?>" target="_blank" class="text-blue-600 hover:underline"><?= esc_html($a['url']) ?></a></td>
                            <td class="px-4 py-2"><?= esc_html($a['location']) ?></td>
                            <td class="px-4 py-2"><?= esc_html($a['group']) ?></td>
                            <td class="px-4 py-2"><?= esc_html($a['modified']) ?></td>
                        </tr>
                <?php endforeach; endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Optional modal for frontend scan -->
<div id="wpcb-scan-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-11/12 md:w-4/5 p-6 relative">
        <button id="wpcb-close-modal" class="absolute top-2 right-2 text-gray-700 hover:text-gray-900 font-bold text-2xl">&times;</button>
        <h2 class="text-2xl font-bold mb-4">Page Assets Scan</h2>
        <div class="overflow-x-auto">
            <table id="wpcb-frontend-assets-table" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-semibold">Type</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold">URL</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold">Location</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold">Group</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold">Last Modified</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- DataTables Initialization -->
<script>
jQuery(document).ready(function($){
    if(!$.fn.DataTable.isDataTable('#wpcb-assets-table')){
        $('#wpcb-assets-table').DataTable({
            dom: 'Bfrtip',
            buttons: ['copy','csv','excel','pdf','print'],
            pageLength: 25
        });
    }
});
</script>
