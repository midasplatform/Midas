$(document).ready(function() {
    midas.registerCallback('CALLBACK_CORE_RESOURCES_SELECTED', 'keyfiles', function(params) {
        var html = '<li>';
        html += '<img alt="" src="'+json.global.coreWebroot
             +'/public/images/icons/key.png"/> ';
        html += '<a href="'+json.global.webroot+'/keyfiles/download/batch?items='
             +params.items.join('-')+'&folders='+params.folders.join('-')
             +'">Download key files</a></li>';
        html += '</li>';

        params.selectedActionsList.append(html);
    });
});
