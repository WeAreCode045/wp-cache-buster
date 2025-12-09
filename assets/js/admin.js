jQuery(document).ready(function($){
    function ajax_post(action, nonce, successMsg){
        $.post(WPCB.ajax, {action: action, nonce: nonce}, function(r){
            if(r.success){
                alert(successMsg);
                location.reload();
            } else {
                alert('AJAX failed: '+r.data+'\nTrying fallback...');
                $('<iframe>', {src: WPCB.fallback[action], style:'display:none'}).appendTo('body');
            }
        }).fail(function(){
            $('<iframe>', {src: WPCB.fallback[action], style:'display:none'}).appendTo('body');
            alert('AJAX blocked. Using fallback.');
        });
    }

    $('#wpcb-flush-gd').click(function(){ ajax_post('wpcb_full_site_scan', WPCB.nonce, 'GoDaddy cache flush gestart!'); });
    $('#wpcb-flush-object').click(function(){ ajax_post('wpcb_object_flush', WPCB.nonce, 'Object cache flush gestart!'); });
    $('#wpcb-full-scan').click(function(){
        if(confirm('Scan the entire site?')) ajax_post('wpcb_full_site_scan', WPCB.nonce, 'Full site scan completed!');
    });
});
