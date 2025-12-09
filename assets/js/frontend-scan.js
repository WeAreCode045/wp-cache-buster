jQuery(document).ready(function($){
    if($('#wpcb-scan-modal').length === 0){
        $('body').append(`
            <div id="wpcb-scan-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-lg w-11/12 md:w-4/5 p-6 relative">
                    <button id="wpcb-close-modal" class="absolute top-2 right-2 text-gray-700 hover:text-gray-900 font-bold text-2xl">&times;</button>
                    <h2 class="text-2xl font-bold mb-4">Page Assets Scan</h2>
                    <table id="wpcb-frontend-assets-table" class="min-w-full table-auto bg-white shadow rounded">
                        <thead class="bg-gray-100">
                            <tr>
                                <th>Type</th><th>URL</th><th>Location</th><th>Group</th><th>Last Modified</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        `);
    }

    function populate_table(data){
        var tbody = '';
        ['styles','scripts'].forEach(type=>{
            if(data[type].length){
                data[type].forEach(a=>{
                    tbody += `<tr>
                        <td class="px-4 py-2 border">${type}</td>
                        <td class="px-4 py-2 border break-words">${a.url}</td>
                        <td class="px-4 py-2 border">${a.location}</td>
                        <td class="px-4 py-2 border">${a.group}</td>
                        <td class="px-4 py-2 border">${a.modified}</td>
                    </tr>`;
                });
            }
        });
        $('#wpcb-frontend-assets-table tbody').html(tbody);

        if(!$.fn.DataTable.isDataTable('#wpcb-frontend-assets-table')){
            $('#wpcb-frontend-assets-table').DataTable({
                dom:'Bfrtip',
                buttons:['copy','csv','excel','pdf','print'],
                pageLength:25
            });
        }
    }

    function scan_page(url){
        $.post(WPCB_SCAN.ajax,{action:'wpcb_scan_url',nonce:WPCB_SCAN.nonce,url:url},function(r){
            if(r.success){
                populate_table(r.data);
                $('#wpcb-scan-modal').fadeIn().css('display','flex');
            } else {
                $('<iframe>',{src: WPCB_SCAN.fallback_url, style:'display:none'}).appendTo('body');
                alert('AJAX scan failed. Using fallback.');
            }
        }).fail(function(){
            $('<iframe>',{src: WPCB_SCAN.fallback_url, style:'display:none'}).appendTo('body');
            alert('AJAX blocked. Using fallback.');
        });
    }

    $('.wpcb-scan-button').click(function(e){ e.preventDefault(); scan_page(window.location.href); });
    $(document).on('click','#wpcb-close-modal',function(){ $('#wpcb-scan-modal').fadeOut(); });
    $(document).on('click','#wpcb-scan-modal',function(e){ if(e.target.id==='wpcb-scan-modal') $(this).fadeOut(); });
});
