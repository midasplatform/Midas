var midas = midas || {};
midas.browse = midas.browse || {};

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
                parent = $("#moveCopyTable #" + classNames[key].substring(9));
            }
        }
        if(parent != null) {
            selectedElement = parent.find('span:eq(1)').html()+'/'+selectedElement;
            current = parent;
        }
    }

    midas.browse.moveCopyToggleButton(false);
    if(node.attr('type') == 'folder' || node.attr('type') == 'item') {
        $('#selectedDestinationHidden').val(node.attr('element'));
        $('#selectedDestination').html(sliceFileName(selectedElement, 40));
        if(typeof node.attr('policy') == 'undefined') {
            var params = {
                type: node.attr('type'),
                id: node.attr('element')
            };
            $.post(json.global.webroot+'/browse/getmaxpolicy', params, function (retVal) {
                var resp = $.parseJSON(retVal);
                node.attr('policy', resp.policy);
                midas.browse.checkMoveDestinationValid(node, resp.policy);
            });
        }
        else {
            midas.browse.checkMoveDestinationValid(node, node.attr('policy'));
        }
    }
};

midas.browse.checkMoveDestinationValid = function (node, policy) {
    if(node.attr('valid') != 'false' && policy >= 1) {
        midas.browse.moveCopyToggleButton(true);
    }
};

midas.browse.moveCopyToggleButton = function (on) {
    if(on) {
        $('#selectElement').removeAttr('disabled');
        $('#shareElement').removeAttr('disabled');
        $('#duplicateElement').removeAttr('disabled');
        $('#moveElement').removeAttr('disabled');
    }
    else {
        $('#selectElement').attr('disabled', 'disabled');
        $('#shareElement').attr('disabled', 'disabled');
        $('#duplicateElement').attr('disabled', 'disabled');
        $('#moveElement').attr('disabled', 'disabled');
    }
};

midas.browse.moveCopyCallbackDblClick = function (node) {
};

midas.browse.moveCopyCallbackCheckboxes = function (node) {
};

midas.browse.moveCopyCallbackCustomElements = function (node,elements,first) {
    var i = 1;
    var id = node.attr('id');
    elements['folders'] = jQuery.makeArray(elements['folders']);
    var padding = parseInt(node.find('td:first').css('padding-left').slice(0, -2));
    var html = '';
    $.each(elements.folders, function(index, value) {
        html+= "<tr id='"+id+"-"+i+"' class='parent child-of-"+id+"' ajax='"+value.folder_id+"'type='folder' element='"+value.folder_id+"'>";
        html+= "  <td><span class='folder'>"+trimName(value.name, padding)+"</span></td>";
        html+= "</tr>";
        i++;
    });
    return html;
};

$(document).ready(function () {
    $('#moveCopyForm').submit(function () {
        $('img.submitWaiting').show();
        return true;
    });

    $("#moveCopyTable").treeTable({
        callbackSelect: midas.browse.moveCopyCallbackSelect,
        callbackCheckboxes: midas.browse.moveCopyCallbackCheckboxes,
        callbackDblClick: midas.browse.moveCopyCallbackDblClick,
        callbackCustomElements: midas.browse.moveCopyCallbackCustomElements,
        pageLength: 99999 // do not page this table (preserves old functionality)
    });
    $("img.tableLoading").hide();
    $("table#moveCopyTable").show();

    $('applet').hide();

    if($('#selectElement') != undefined) {
        $('#selectElement').click(function () {
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
});
