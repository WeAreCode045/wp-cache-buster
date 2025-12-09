jQuery(document).ready(function($){
    $('#wpcb-flush-gd').click(function(){
        var url = $(this).data('url');
        $('<iframe>', {src:url, style:'display:none'}).appendTo('body');
        alert('GoDaddy cache flush gestart!');
    });

    $('#wpcb-flush-object').click(function(){
        var url = $(this).data('url');
        $('<iframe>', {src:url, style:'display:none'}).appendTo('body');
        alert('Object cache flush gestart!');
    });

    $('#wpcb-full-scan').click(function(){
        if(!confirm('Scan the entire site?')) return;
        $.post(WPCB.ajax, {action:'wpcb_full_site_scan', nonce: WPCB.nonce}, function(r){
            alert('Full site scan completed!');
            location.reload();
        });
    });

    $('#wpcb-assets-table').DataTable({
        dom: 'Bfrtip',
        buttons: ['copy','csv','excel','pdf','print'],
        pageLength: 25
    });
});
