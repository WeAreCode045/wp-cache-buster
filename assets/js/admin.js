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
    $('#wpcb-scan-all-pages').click(function(){
        if(confirm('Scan all pages of the site? This may take a while.')){
            $.post(WPCB.ajax,{action:'wpcb_scan_all_pages',nonce:WPCB.nonce},function(r){
                if(r.success){
                    alert('All pages scanned successfully!');
                    location.reload();
                } else {
                    alert('Scan failed: '+r.data);
                }
            });
        }
    });

    $('#wpcb-asset-view').change(function(){
        var val = $(this).val();
        if(val==='all'){
            $.post(WPCB.ajax,{action:'wpcb_get_all_pages_assets',nonce:WPCB.nonce},function(r){
                if(r.success){
                    populateAssetsTable(r.data);
                }
            });
        } else {
            location.reload();
        }
    });

    function populateAssetsTable(data){
        var tbody = '';
        for(var pid in data){
            var page = data[pid];
            ['styles','scripts'].forEach(type=>{
                if(page.assets[type].length){
                    page.assets[type].forEach(a=>{
                        tbody += `<tr>
                            <td>${type}</td>
                            <td>${a.url}</td>
                            <td>${a.location}</td>
                            <td>${a.group}</td>
                            <td>${a.modified}</td>
                        </tr>`;
                    });
                }
            });
        }
        $('#wpcb-assets-table tbody').html(tbody);
        if($.fn.DataTable.isDataTable('#wpcb-assets-table')){
            $('#wpcb-assets-table').DataTable().destroy();
        }
        $('#wpcb-assets-table').DataTable({
            dom:'Bfrtip',
            buttons:['copy','csv','excel','pdf','print'],
            pageLength:25
        });
    }
});
