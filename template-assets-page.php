<?php
if(!defined('ABSPATH')) exit;

// Dynamic flush URLs
$gd_flush_url = admin_url('plugin-install.php?tab=gd-recommended');
$gd_flush_nonce = wp_create_nonce('wpaas_flush_cache');
$gd_flush_url = add_query_arg(['wpaas_action'=>'flush_cache','wpaas_nonce'=>$gd_flush_nonce], $gd_flush_url);

$obj_flush_url = admin_url('options-general.php?page=objectcache');
$obj_flush_nonce = wp_create_nonce('object_cache_flush');
$obj_flush_url = add_query_arg(['action'=>'flush-cache','_wpnonce'=>$obj_flush_nonce], $obj_flush_url);

$assets = get_transient('wpcb_page_assets') ?: [];
?>

<div class="wrap p-6 bg-gray-50 min-h-screen">
    <h1 class="text-3xl font-bold mb-6">WP Asset Overview</h1>

    <div class="mb-4 flex flex-wrap gap-2">
        <button id="wpcb-flush-gd" data-url="<?php echo esc_url($gd_flush_url); ?>" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Flush GoDaddy Cache</button>
        <button id="wpcb-flush-object" data-url="<?php echo esc_url($obj_flush_url); ?>" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">Flush Object Cache</button>
        <button id="wpcb-full-scan" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition">Scan Entire Site</button>
    </div>

    <div class="overflow-x-auto">
        <table id="wpcb-assets-table" class="min-w-full table-auto bg-white shadow rounded">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 border">Page</th>
                    <th class="px-4 py-2 border">Type</th>
                    <th class="px-4 py-2 border">URL</th>
                    <th class="px-4 py-2 border">Last Modified</th>
                    <th class="px-4 py-2 border">Group</th>
                    <th class="px-4 py-2 border">Location</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($assets as $page => $data):
                foreach(['styles','scripts'] as $type):
                    if(!empty($data[$type])):
                        foreach($data[$type] as $asset):
            ?>
                <tr>
                    <td class="px-4 py-2 border"><?php echo esc_html($page); ?></td>
                    <td class="px-4 py-2 border"><?php echo esc_html($type); ?></td>
                    <td class="px-4 py-2 border break-words"><?php echo esc_url($asset['url']); ?></td>
                    <td class="px-4 py-2 border"><?php echo esc_html($asset['modified']); ?></td>
                    <td class="px-4 py-2 border"><?php echo esc_html($asset['group']); ?></td>
                    <td class="px-4 py-2 border"><?php echo esc_html($asset['location']); ?></td>
                </tr>
            <?php
                        endforeach;
                    endif;
                endforeach;
            endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
