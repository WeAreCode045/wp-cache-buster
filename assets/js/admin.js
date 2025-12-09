jQuery(function($){
$("#wpcb-flush-gd").on("click",function(e){e.preventDefault();$.post(WPCB.ajax,{action:"wpcb_flush_gd_cache",_ajax_nonce:WPCB.nonce},function(r){alert(r.data);});});
$("#wpcb-flush-object").on("click",function(e){e.preventDefault();$.post(WPCB.ajax,{action:"wpcb_flush_object_cache",_ajax_nonce:WPCB.nonce},function(r){alert(r.data);});});
});