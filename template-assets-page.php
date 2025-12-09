<?php
if (!defined('ABSPATH')) exit;


$assets = $this->scan_filesystem_assets();
$page_assets = get_transient('wpcb_page_assets') ?: [];
?>
<div class="wrap p-4">
<h1 class="text-2xl font-bold mb-4">Asset Overview</h1>


<div class="flex gap-2 mb-4">
<a href="<?php echo admin_url('admin-post.php?action=wpcb_export_json'); ?>" class="px-3 py-1 bg-blue-600 text-white rounded">Export JSON</a>
<a href="<?php echo admin_url('admin-post.php?action=wpcb_export_csv'); ?>" class="px-3 py-1 bg-gray-600 text-white rounded">Export CSV</a>
</div>


<form id="wpcb-scan-page-form" class="mb-4 flex gap-2">
<input type="text" id="wpcb-scan-url" placeholder="https://example.com/page/" class="border p-1 flex-1">
<button class="px-3 py-1 bg-green-600 text-white rounded" id="wpcb-scan-page-btn">Scan PAGE</button>
</form>


<div id="wpcb-scan-page-result" class="hidden mb-4">
<h3 class="text-lg font-semibold">Scan Resultaat</h3>
<pre id="wpcb-scan-page-output" class="bg-gray-100 p-2 rounded overflow-auto h-64"></pre>
</div>


<table class="wpcb-table min-w-full border-collapse">
<thead class="bg-gray-200">
<tr>
<th class="px-2 py-1">Type</th>
<th class="px-2 py-1">Path</th>
<th class="px-2 py-1">URL</th>
<th class="px-2 py-1">Modified</th>
<th class="px-2 py-1">Group</th>
<th class="px-2 py-1">Used on Pages</th>
</tr>
</thead>
<tbody>
<?php foreach ($assets as $a):
$used_on = [];
foreach ($page_assets as $url => $pa) {
foreach ($pa['styles'] as $st) if ($st['url']===$a['url']) $used_on[]=$url;
foreach ($pa['scripts'] as $sc) if ($sc['url']===$a['url']) $used_on[]=$url;
}
$used_list = ($used_on ? implode("<br>", $used_on) : '<span class="text-red-600">Unused</span>');
?>
<tr>
<td class="px-2 py-1"><?php echo esc_html($a['type']); ?></td>
<td class="px-2 py-1"><?php echo esc_html($a['path']); ?></td>
<td class="px-2 py-1"><a href="<?php echo esc_url($a['url']); ?>" target="_blank"><?php echo esc_html($a['url']); ?></a></td>
<td class="px-2 py-1"><?php echo esc_html($a['modified']); ?></td>
<td class="px-2 py-1"><?php echo esc_html($a['group']); ?></td>
<td class="px-2 py-1"><?php echo $used_list; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>


<script>
jQuery(function($){
$("#wpcb-scan-page-btn").on("click", function(e){
e.preventDefault();
let url = $("#wpcb-scan-url").val();
if(!url) return alert('Voer een URL in.');
$.post(ajaxurl, {action:"wpcb_scan_url", url: url, _ajax_nonce: "<?php echo wp_create_nonce('wpcb_nonce'); ?>"}, function(resp){
$("#wpcb-scan-page-output").text(JSON.stringify(resp,null,2));
$("#wpcb-scan-page-result").removeClass('hidden');
$.post(ajaxurl, {action:"wpcb_save_page_scan", url:url, data:resp,_ajax_nonce:"<?php echo wp_create_nonce('wpcb_nonce'); ?>"});
});
});
});
</script>