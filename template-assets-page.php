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
                <th class="px-4 py-2"></th>
                <th class="px-4 py-2">URL</th>
                <th class="px-4 py-2">Type</th>
                <th class="px-4 py-2">Location</th>
                <th class="px-4 py-2">Group</th>
                <th class="px-4 py-2">Last Modified</th>
                <th class="px-4 py-2">Pages</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if(!empty($assets_option)):
                // Groepeer assets per URL en type
                $grouped_assets = [];
                foreach($assets_option as $page_url => $assets):
                    foreach(['scripts','styles'] as $type):
                        if(isset($assets[$type])):
                            foreach($assets[$type] as $a):
                                $key = $a['url'] . '|' . $type;
                                if(!isset($grouped_assets[$key])){
                                    $grouped_assets[$key] = [
                                        'url' => $a['url'],
                                        'type' => $type,
                                        'location' => $a['location'],
                                        'group' => $a['group'],
                                        'modified' => $a['modified'],
                                        'pages' => []
                                    ];
                                }
                                $grouped_assets[$key]['pages'][] = $page_url;
                            endforeach;
                        endif;
                    endforeach;
                endforeach;

                // Toon gegroepeerde assets
                foreach($grouped_assets as $key => $asset):
                    $unique_id = md5($key);
                    $page_count = count($asset['pages']);
                    $pages_json = json_encode($asset['pages']);
                    ?>
                    <tr class="border-t asset-row" data-asset-id="<?php echo esc_attr($unique_id); ?>" data-pages="<?php echo esc_attr($pages_json); ?>">
                        <td class="px-4 py-2 text-center">
                            <button class="toggle-pages text-blue-600 hover:text-blue-800 font-bold" data-target="<?php echo esc_attr($unique_id); ?>">
                                <span class="toggle-icon">+</span>
                            </button>
                        </td>
                        <td class="px-4 py-2"><?php echo esc_url($asset['url']); ?></td>
                        <td class="px-4 py-2"><?php echo esc_html($asset['type']); ?></td>
                        <td class="px-4 py-2"><?php echo esc_html($asset['location']); ?></td>
                        <td class="px-4 py-2"><?php echo esc_html($asset['group']); ?></td>
                        <td class="px-4 py-2"><?php echo esc_html($asset['modified']); ?></td>
                        <td class="px-4 py-2 text-center">
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm font-semibold"><?php echo $page_count; ?></span>
                        </td>
                    </tr>
                    <?php
                endforeach;
            endif;
            ?>
        </tbody>
    </table>
</div>
