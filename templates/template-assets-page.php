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
        <table id="wpcb-assets-table" class="min-w-full divide-y divide-gray-200 border border-gray-300">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left text-sm font-semibold border border-gray-300">Type</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold border border-gray-300">URL</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold border border-gray-300">Location</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold border border-gray-300">Group</th>
                    <th class="px-4 py-2 text-left text-sm font-semibold border border-gray-300">Last Modified</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach(['styles','scripts'] as $type):
                    foreach($assets[$type]??[] as $a): ?>
                        <tr>
                            <td class="px-4 py-2 border"><?= esc_html($type) ?></td>
                            <td class="px-4 py-2 border break-words"><a href="<?= esc_url($a['url']) ?>" target="_blank" class="text-blue-600 hover:underline"><?= esc_html($a['url']) ?></a></td>
                            <td class="px-4 py-2 border"><?= esc_html($a['location']) ?></td>
                            <td class="px-4 py-2 border"><?= esc_html($a['group']) ?></td>
                            <td class="px-4 py-2 border"><?= esc_html($a['modified']) ?></td>
                        </tr>
                <?php endforeach; endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Frontend Modal Scan -->
<div id="wpcb-scan-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-11/12 md:w-4/5 p-6 relative">
        <button id="wpcb-close-modal" class="absolute top-2 right-2 text-gray-700 hover:text-gray-900 font-bold text-2xl">&times;</button>
        <h2 class="text-2xl font-bold mb-4">Page Assets Scan</h2>
        <div class="overflow-x-auto">
            <table id="wpcb-frontend-assets-table" class="min-w-full divide-y divide-gray-200 border border-gray-300">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-semibold border border-gray-300">Type</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold border border-gray-300">URL</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold border border-gray-300">Location</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold border border-gray-300">Group</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold border border-gray-300">Last Modified</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- DataTables + Tailwind Integration -->
<script>
jQuery(document).ready(function($){
    // Initialize DataTable with Tailwind classes for buttons
    if(!$.fn.DataTable.isDataTable('#wpcb-assets-table')){
        $('#wpcb-assets-table').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { extend: 'copy', className: 'bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 mr-2' },
                { extend: 'csv', className: 'bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 mr-2' },
                { extend: 'excel', className: 'bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 mr-2' },
                { extend: 'pdf', className: 'bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 mr-2' },
                { extend: 'print', className: 'bg-purple-600 text-white px-3 py-1 rounded hover:bg-purple-700' }
            ],
            pageLength: 25
        });
    }
});
</script>
