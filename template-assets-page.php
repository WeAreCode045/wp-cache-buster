<?php
if(!defined('ABSPATH')) exit;

$assets_option = get_option('wpcb_page_assets', []);
?>
<div class="wrap p-6">
    <h1 class="text-2xl font-bold mb-4">WP Cache Buster & Asset Scanner</h1>

    <div class="mb-4 flex space-x-4">
        <button id="wpcb-flush-gd" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Flush GoDaddy Cache</button>
        <button id="wpcb-flush-object" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Flush Object Cache</button>
        <button id="wpcb-scan-all-pages" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">Scan All Pages</button>
    </div>

    <table id="wpcb-assets-table" class="min-w-full border border-gray-300">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-2">URL</th>
                <th class="px-4 py-2">Type</th>
                <th class="px-4 py-2">Location</th>
                <th class="px-4 py-2">Group</th>
                <th class="px-4 py-2">Last Modified</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if(!empty($assets_option)):
                foreach($assets_option as $page_url => $assets):
                    foreach(['scripts','styles'] as $type):
                        if(isset($assets[$type])):
                            foreach($assets[$type] as $a):
                                ?>
                                <tr class="border-t">
                                    <td class="px-4 py-2"><?php echo esc_url($a['url']); ?></td>
                                    <td class="px-4 py-2"><?php echo esc_html($type); ?></td>
                                    <td class="px-4 py-2"><?php echo esc_html($a['location']); ?></td>
                                    <td class="px-4 py-2"><?php echo esc_html($a['group']); ?></td>
                                    <td class="px-4 py-2"><?php echo esc_html($a['modified']); ?></td>
                                </tr>
                                <?php
                            endforeach;
                        endif;
                    endforeach;
                endforeach;
            endif;
            ?>
        </tbody>
    </table>
</div>
