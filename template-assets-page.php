<?php
if (!defined('ABSPATH')) exit;

// Haal assets op vanuit transient
$assets = get_transient('wpcb_page_assets') ?: [];
?>

<div class="wrap p-6 bg-gray-50 min-h-screen">
    <h1 class="text-3xl font-bold mb-6">WP Asset Overview</h1>

    <!-- Action buttons -->
    <div class="mb-4 flex flex-wrap gap-2">
        <button id="wpcb-flush-gd" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Flush GoDaddy Cache</button>
        <button id="wpcb-flush-object" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">Flush Object Cache</button>
        <button id="wpcb-full-scan" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition">Scan Entire Site</button>
    </div>

    <!-- Page selector -->
    <div class="mb-4">
        <label class="block font-semibold mb-1">Select Page:</label>
        <select id="wpcb-page-select" class="border rounded p-2 w-full md:w-1/3">
            <option value="all">All Pages</option>
            <?php foreach($assets as $url => $data): ?>
                <option value="<?php echo esc_attr($url); ?>"><?php echo esc_html($url); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Asset table -->
    <div class="overflow-x-auto">
        <table id="wpcb-assets-table" class="min-w-full table-auto bg-white shadow rounded">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 border">URL</th>
                    <th class="px-4 py-2 border">Type</th>
                    <th class="px-4 py-2 border">Last Modified</th>
                    <th class="px-4 py-2 border">Group</th>
                    <th class="px-4 py-2 border">Location</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach($assets as $url => $data){
                    foreach(['styles','scripts'] as $type){
                        if(!empty($data[$type])){
                            foreach($data[$type] as $asset){
                                echo '<tr>';
                                echo '<td class="px-4 py-2 border break-words">'.$asset['url'].'</td>';
                                echo '<td class="px-4 py-2 border">'.$type.'</td>';
                                echo '<td class="px-4 py-2 border">'.$asset['modified'].'</td>';
                                echo '<td class="px-4 py-2 border">'.$asset['group'].'</td>';
                                echo '<td class="px-4 py-2 border">'.$asset['location'].'</td>';
                                echo '</tr>';
                            }
                        }
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- DataTables & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
jQuery(document).ready(function($){
    // Initialize DataTable
    var table = $('#wpcb-assets-table').DataTable({
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        pageLength: 25
    });

    // Page filter
    $('#wpcb-page-select').on('change', function(){
        var val = $(this).val();
        table.rows().every(function(){
            var url = this.data()[0];
            if(val === 'all' || url.indexOf(val) !== -1){
                $(this.node()).show();
            } else {
                $(this.node()).hide();
            }
        });
    });

    // AJAX flush GoDaddy Cache
    $('#wpcb-flush-gd').click(function(){
        $.post(WPCB.ajax, {action:'wpcb_flush_gd_cache', nonce: WPCB.nonce}, function(r){
            alert(r.data);
        });
    });

    // AJAX flush Object Cache
    $('#wpcb-flush-object').click(function(){
        $.post(WPCB.ajax, {action:'wpcb_flush_object_cache', nonce: WPCB.nonce}, function(r){
            alert(r.data);
        });
    });

    // Full site scan
    $('#wpcb-full-scan').click(function(){
        if(!confirm('Scan the entire site? This may take time.')) return;
        $.post(WPCB.ajax, {action:'wpcb_full_site_scan', nonce: WPCB.nonce}, function(r){
            alert('Full site scan completed!');
            location.reload();
        });
    });
});
</script>
<?php