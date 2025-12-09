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
        e.stopPropagation();
        
        var btn = $(this);
        var targetId = btn.data('target');
        var icon = btn.find('.toggle-icon');
        var assetRow = btn.closest('tr');
        var detailRow = assetRow.next('tr.detail-row-' + targetId);
        
        // Check of detail row al bestaat
        if(detailRow.length){
            // Toggle visibility
            if(detailRow.is(':visible')){
                detailRow.remove();
                icon.text('+');
            } else {
                detailRow.show();
                icon.text('-');
            }
        } else {
            // Maak nieuwe detail row
            var pages = JSON.parse(assetRow.attr('data-pages'));
            var html = '<tr class="detail-row-' + targetId + '" style="background-color: #f9fafb;">' +
                '<td colspan="7" class="px-4 py-4">' +
                '<div class="ml-8">' +
                '<h4 class="font-semibold mb-2">Geladen op de volgende pagina\'s:</h4>' +
                '<ul class="list-disc list-inside space-y-1">';
            
            pages.forEach(function(page){
                html += '<li class="text-sm text-gray-700">' +
                    '<a href="' + page + '" target="_blank" class="text-blue-600 hover:underline">' +
                    page + '</a></li>';
            });
            
            html += '</ul></div></td></tr>';
            
            assetRow.after(html);
            icon.text('-');
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

        $.ajax({
            url: WPCB.ajax,
            type: 'POST',
            data: {
                action: 'wpcb_scan_all_pages',
                nonce: WPCB.nonce
            },
            timeout: 300000 // 5 minuten timeout
        })
        .done(function(res){
            if(res.success){
                alert(res.data.message + ' (' + res.data.count + ' pages)');
                // Herlaad pagina om nieuwe gegroepeerde data te tonen
                location.reload();
            } else {
                alert('Scan failed: ' + (res.data && res.data.message ? res.data.message : 'Unknown error'));
                btn.prop('disabled',false).text('Scan All Pages');
            }
        })
        .fail(function(xhr, status, error){
            console.error('Scan error:', status, error);
            alert('Scan failed! Error: ' + status + '\nPlease check the browser console for details.');
            btn.prop('disabled',false).text('Scan All Pages');
        });
    });
});
