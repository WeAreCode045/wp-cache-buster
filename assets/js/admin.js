jQuery(document).ready(function($){
    var table;

    function initDataTable(){
        if(table) table.destroy(); // destroy alleen als table al bestaat

        table = $('#wpcb-assets-table').DataTable({
            dom: 'Bfrtip',
            buttons: [
                { extend:'copy', className:'bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 mr-2' },
                { extend:'csv', className:'bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 mr-2' },
                { extend:'excel', className:'bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 mr-2' },
                { extend:'pdf', className:'bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 mr-2' },
                { extend:'print', className:'bg-purple-600 text-white px-3 py-1 rounded hover:bg-purple-700' }
            ],
            pageLength:25,
            columnDefs: [
                { orderable: false, targets: 0 } // Toggle kolom niet sorteerbaar
            ],
            order: [[1, 'asc']] // Sorteer op URL kolom
        });
    }

    initDataTable();

    // Toggle uitklappen van pagina's
    $(document).on('click', '.toggle-pages', function(e){
        e.preventDefault();
        var targetId = $(this).data('target');
        var detailRow = $('#detail-' + targetId);
        var icon = $(this).find('.toggle-icon');
        
        if(detailRow.hasClass('hidden')){
            detailRow.removeClass('hidden');
            icon.text('-');
        } else {
            detailRow.addClass('hidden');
            icon.text('+');
        }
    });

    // Flush GoDaddy cache
    $('#wpcb-flush-gd').on('click',function(){
        $.post(WPCB.ajax,{action:'wpcb_flush_cache',nonce:WPCB.nonce},function(res){
            alert(res.data.message);
        });
    });

    // Flush Object Cache
    $('#wpcb-flush-object').on('click',function(){
        $.post(WPCB.ajax,{action:'wpcb_flush_object_cache',nonce:WPCB.nonce})
            .done(function(res){
                if(res.success){
                    alert(res.data.message);
                } else {
                    alert('Error: ' + res.data.message);
                }
            })
            .fail(function(){
                alert('Failed to flush object cache. Please try again.');
            });
    });

    // Scan All Pages
    $('#wpcb-scan-all-pages').on('click',function(){
        if(!confirm('Scan all pages? This may take some time.')) return;
        var btn=$(this); btn.prop('disabled',true).text('Scanning...');

        $.post(WPCB.ajax,{action:'wpcb_scan_all_pages',nonce:WPCB.nonce})
            .done(function(res){
                alert(res.data.message+' ('+res.data.count+' pages)');

                // Herlaad pagina om nieuwe gegroepeerde data te tonen
                location.reload();
            })
            .fail(function(){
                alert('Scan failed!');
                btn.prop('disabled',false).text('Scan All Pages');
            });
    });
});
