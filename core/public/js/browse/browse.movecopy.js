var midas = midas || {};
midas.browse = midas.browse || {};
$(document).ready(
    function() {
        $("#moveTable").treeTable(
            {
                callbackSelect: midas.browse.moveCopyCallbackSelect,
                callbackCheckboxes: midas.browse.moveCopyCallbackCheckboxes,
                callbackDblClick: midas.browse.moveCopyCallbackDblClick,
                callbackCustomElements: midas.browse.moveCopyCallbackCustomElements
            });
        $("img.tableLoading").hide();
        $("table#moveTable").show();
     
        $('applet').hide();
       
        if($('#selectElement') != undefined) {
            $('#selectElement').click(
                function() {
                    var destHtml = $('#selectedDestination').html();
                    var destValue = $('#selectedDestinationHidden').val();
                    $('#destinationUpload').html(destHtml);
                    $('#destinationId').val(destValue);
                    $('.destinationUpload').html(destHtml);
                    $('.destinationId').val(destValue);
                    $( "div.MainDialog" ).dialog('close');
                    $('applet').show();
                    return false;
                });
        }
        $('img.infoLoading').show();
        $('div.ajaxInfoElement').html('');
    });

//dependance: common/browser.js
midas.ajaxSelectRequest='';
midas.browse.moveCopyCallbackSelect = function (node) {
    var selectedElement = node.find('span:eq(1)').html();
    var parent = true;
    var current = node;

    while(parent != null) {
        parent = null;
        var classNames = current[0].className.split(' ');
        for(key in classNames) {
            if(classNames[key].match("child-of-")) {
                parent = $("#" + classNames[key].substring(9));
            }
        }
        if(parent != null) {
            selectedElement = parent.find('span:eq(1)').html()+'/'+selectedElement;
            current = parent;
        }
    }

    $('#selectedDestinationHidden').val(node.attr('element'));
    $('#selectedDestination').html(sliceFileName(selectedElement, 40));
    $('#selectElement').removeAttr('disabled');
    $('#shareElement').removeAttr('disabled');
    $('#duplicateElement').removeAttr('disabled');
    $('#moveElement').removeAttr('disabled');
};


midas.browse.moveCopyCallbackDblClick = function (node) {
    //  midas.genericCallbackDblClick(node);
};

midas.browse.moveCopyCallbackCheckboxes = function (node) {
    //  midas.genericCallbackCheckboxes(node);
};

midas.browse.moveCopyCallbackCustomElements = function (node,elements,first) {
    var i = 1;
    var id=node.attr('id');
    elements['folders'] = jQuery.makeArray(elements['folders']);
    var padding=parseInt(node.find('td:first').css('padding-left').slice(0,-2));
    var html='';
    $.each(elements['folders'], 
           function(index, value) {
               if(value['policy']!='0') {
                   html+= "<tr id='"+id+"-"+i+"' class='parent child-of-"+id+"' ajax='"+value['folder_id']+"'type='folder'  policy='"+value['policy']+"' element='"+value['folder_id']+"'>";
                   html+=     "  <td><span class='folder'>"+trimName(value['name'],padding)+"</span></td>";
                   html+=     "</tr>";
                   i++;
               }
           });
    return html;
};
