jQuery(document).ready(function($){
    // DataTable init
    $('#wpcb-assets-table').DataTable({
        dom: 'Bfrtip',
        buttons: [
            { extend:'copy', className:'bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 mr-2' },
            { extend:'csv', className:'bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 mr-2' },
            { extend:'excel', className:'bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 mr-2' },
            { extend:'pdf', className:'bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 mr-2' },
            { extend:'print', className:'bg-purple-600 text-white px-3 py-1 rounded hover:bg-purple-700' }
        ],
        pageLength:25
    });

    // Flush GoDaddy
    $('#wpcb-flush-gd').on('click',function(){
        $.post(WPCB.ajax,{action:'wpcb_flush_cache',nonce:WPCB.nonce},function(res){
            alert(res.data.message);
        });
    });

    // Flush Object Cache
    $('#wpcb-flush-object').on('click',function(){
        $.post(WPCB.ajax,{action:'wpcb_flush_object_cache',nonce:WPCB.nonce},function(res){
            alert(res.data.message);
        });
    });

    // Scan All Pages
    $('#wpcb-scan-all-pages').on('click',function(){
        if(!confirm('Scan all pages? This may take some time.')) return;
        var btn=$(this); btn.prop('disabled',true).text('Scanning...');
        $.post(WPCB.ajax,{action:'wpcb_scan_all_pages',nonce:WPCB.nonce},function(res){
            alert(res.data.message+' ('+res.data.count+' pages)');
            location.reload();
        }).fail(function(){
            alert('Scan failed!');
            btn.prop('disabled',false).text('Scan All Pages');
        });
    });
});
