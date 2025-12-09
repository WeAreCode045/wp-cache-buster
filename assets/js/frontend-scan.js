jQuery(function($){
$("#wp-admin-bar-wpcb-scan a").on("click",function(e){
e.preventDefault();
$.post(WPCB_SCAN.ajax,{action:"wpcb_scan_url",url:window.location.href,_ajax_nonce:WPCB_SCAN.nonce},function(resp){
$("#wpcb-scan-output").text(JSON.stringify(resp,null,2));
$("#wpcb-scan-modal").removeClass('hidden');
});
});
$(document).on("click","#wpcb-close-modal",function(){$("#wpcb-scan-modal").addClass('hidden');});
});