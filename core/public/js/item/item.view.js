$(document).ready(function() {
    $('a.metadataDeleteLink img').fadeTo('fast', 0.4);
    $('a.metadataDeleteLink img').hover(function() {
        $(this).fadeTo('fast', 1.0);
    },
    function() {
        $(this).fadeTo('fast', 0.4);
    });
    $('a.metadataDeleteLink').click(function () {
        var metadataCell = $(this).parents('tr');
        var metadataId = $(this).attr('element');
        var itemrevision = $(this).attr('itemrevision');
        var html = '';
        html+=json.item.message['deleteMetadataMessage'];
        html+='<br/><br/><br/>';
        html+='<div style="float: right;">';
        html+='<input class="globalButton deleteMetaDataYes" element="'+$(this).attr('element')+'" type="button" value="'+json.global.Yes+'"/>';
        html+='<input style="margin-left:15px;" class="globalButton deleteMetaDataNo" type="button" value="'+json.global.No+'"/>';
        html+='</div>';
        midas.showDialogWithContent(json.item.message['delete'],html,false);

        $('input.deleteMetaDataYes').unbind('click').click(function () {
            $.post(json.global.webroot+'/item/'+json.item.item_id, { element: metadataId, itemrevision: itemrevision, deleteMetadata: true});
            metadataCell.remove();
            $( "div.MainDialog" ).dialog('close');
        });
        $('input.deleteMetaDataNo').unbind('click').click(function() {
            $( "div.MainDialog" ).dialog('close');
        });
    });
    $('a.metadataEditLink img').fadeTo('fast', 0.4);
    $('a.metadataEditLink img').hover(function() {
        $(this).fadeTo('fast', 1.0);
    },
    function() {
        $(this).fadeTo('fast', 0.4);
    });
    $('a.metadataEditLink').click(function () {
        var metadataId = $(this).attr('element');
        var itemrevision = $(this).attr('itemrevision');
        midas.loadDialog("editmetadata"+metadataId, "/item/editmetadata/?metadataId="+metadataId+"&itemId="+json.item.item_id+"&itemrevision="+itemrevision);
        midas.showDialog('MetaData');
    });


    $('a.addMetadataLink').click(function () {
        var metadataId = $(this).attr('element');
        midas.loadDialog("editmetadata"+metadataId, "/item/editmetadata/?itemId="+json.item.item_id);
        var options = { buttons: {"Add": function () {
            $(this).dialog("close");
            // since we are adding, be sure that the metadata doesn't already exist
            // if it does, give the user an error message and don't add the new metadata
            requestData = {};
            requestData["element"] = $('#midas_item_metadata_element').val();
            requestData["qualifier"] = $('#midas_item_metadata_qualifier').val();
            requestData["metadatatype"] = $('#midas_item_metadata_metadatatype').val();
            requestData["itemId"] = json.item.item_id;
            $.ajax({
                type: "POST",
                url: json.global.webroot+"/item/getmetadatavalueexists",
                data: requestData,
                success: function (jsonContent) {
                    var data = $.parseJSON(jsonContent);
                    if(data.exists === "1") {
                        midas.createNotice("Metadata already exists for that metadatatype, element and qualifier", 4000, 'error');
                        // clear the form values
                        $('#midas_item_metadata_element').val('');
                        $('#midas_item_metadata_qualifier').val('');
                        $('#midas_item_metadata_value').val('');
                    }
                    else {
                        $('#editMetadataForm').submit();
                    }
                }
            });
        }}};
        midas.showDialog('MetaData', true, options);
    });

    $('a.deleteItemRevision img').fadeTo('fast', 0.4);
    $('a.deleteItemRevision img').hover(function () {
        $(this).fadeTo('fast', 1.0);
    },
    function() {
        $(this).fadeTo('fast', 0.4);
    });

    $('a.deleteItemRevision').click(function () {
        var itemId = $(this).attr('itemId');
        var itemrevisionId = $(this).attr('itemrevisionId');
        var html = '';
        html+=json.item.message['deleteItemrevisionMessage'];
        html+='<br/>';
        html+='<br/>';
        html+='<br/>';
        html+='<div style="float: right;">';
        html+='<input class="globalButton deleteItemRevisionYes" element="'+$(this).attr('element')+'" type="button" value="'+json.global.Yes+'"/>';
        html+='<input style="margin-left:15px;" class="globalButton deleteItemRevisionNo" type="button" value="'+json.global.No+'"/>';
        html+='</div>';
        midas.showDialogWithContent(json.item.message['delete'],html,false);

        $('input.deleteItemRevisionYes').unbind('click').click(function() {
            $( "div.MainDialog" ).dialog('close');
            $('#deleteItemrevisionForm'+itemrevisionId).submit();
        });
        $('input.deleteItemRevisionNo').unbind('click').click(function() {
            $( "div.MainDialog" ).dialog('close');
        });
    });



    $('a.shareItemLink').click(function() {
        midas.loadDialog("shareItem","/browse/movecopy/?share=true&items="+json.item.item_id);
        midas.showDialog(json.item.message.share);
    });

    $('a.duplicateItemLink').click(function() {
        midas.loadDialog("duplicateItem","/browse/movecopy/?duplicate=true&items="+json.item.item_id);
        midas.showDialog(json.item.message.duplicate);
    });

    $('a.linkItemLink').click(function() {
        midas.loadDialog("linkToItem","/share/links?type=item&id="+json.item.item_id);
        midas.showDialog('Link to this item');
    });

    $('tr.bitstreamRow img.bitstreamInfoIcon').qtip({
        content: {
            text: function(api) {
                var name = $(this).parents('td').find('div span[name=filename]').html();
                var md5 = $(this).parents('td').find('div span[name=md5]').html();
                var size = $(this).parents('td').find('div span[name=sizeBytes]').html();
                var type = $(this).parents('td').find('div span[name=mimeType]').html();
                var text = '<b>Filename:</b> ' + name + '<br/>';
                text += '<b>Size:</b> ' + size + ' bytes<br/>';
                text += '<b>MIME Type:</b> ' + type + '<br/>';
                text += '<b>MD5:</b> ' + md5 + '<br/>';
                return text;
            }
        }
    }).click(function() {
        var id = $(this).parents('td').find('div span[name=bitstream_id]').html();
        var name = $(this).parents('td').find('div span[name=filename]').html();
        var md5 = $(this).parents('td').find('div span[name=md5]').html();
        var size = $(this).parents('td').find('div span[name=sizeBytes]').html();
        var type = $(this).parents('td').find('div span[name=mimeType]').html();
        var text = '<b>Filename:</b> ' + name + '<br/>';
        text += '<b>Size:</b> ' + size + ' bytes<br/>';
        text += '<b>MIME Type:</b> ' + type + '<br/>';
        text += '<b>MD5:</b> ' + md5 + '<br/>';
        text += '<b>Bitstream ID:</b> ' + id + '<br/>';
        midas.showDialogWithContent('Bitstream Information', text, false);
    });

    $('a#itemDeleteLink').click(function () {
        $.ajax({
            type: "GET",
            url: json.global.webroot+'/item/checkshared',
            data: {itemId: json.item.item_id},
            success: function (jsonContent) {
                var $itemIsShared = $.parseJSON(jsonContent);
                var html='';
                if ($itemIsShared == true) {
                    html+=json.item.message['sharedItem'];
                }
                html+=json.item.message['deleteMessage'];
                html+='<br/>';
                html+='<br/>';
                html+='<br/>';
                html+='<div style="float: right;">';
                html+='<input class="globalButton deleteItemYes" element="'+$(this).attr('element')+'" type="button" value="'+json.global.Yes+'"/>';
                html+='<input style="margin-left:15px;" class="globalButton deleteItemNo" type="button" value="'+json.global.No+'"/>';
                html+='</div>';

                midas.showDialogWithContent(json.item.message['delete'], html, false);

                $('input.deleteItemYes').unbind('click').click(function() {
                    location.replace(json.global.webroot+'/item/delete?itemId='+json.item.item_id);
                });
                $('input.deleteItemNo').unbind('click').click(function() {
                    $( "div.MainDialog" ).dialog('close');
                });
            }
        });
    });

    $('a.sharingLink').click(function () {
        midas.loadDialog("sharing"+$(this).attr('type')+$(this).attr('element'),"/share/dialog?type="+$(this).attr('type')+'&element='+$(this).attr('element'));
        midas.showDialog(json.browse.share);
    });


    var itemId = $('a.uploadRevisionLink').attr('element');
    $('a.uploadRevisionLink').qtip({
        content: {
            // Set the text to an image HTML string with the correct src URL to the loading image you want to use
            text: '<img  src="'+json.global.webroot+'/core/public/images/icons/loading.gif" alt="Loading..." />',
            ajax: {
                url: json.global.webroot+"/upload/revision?itemId="+itemId // Use the rel attribute of each element for the url to load
            },
            title: {
                text: $('a.uploadRevisionLink').html(), // Give the tooltip a title using each elements text
                button: true
            }
        },
        position: {
            at: 'bottom center', // Position the tooltip above the link
            my: 'top right',
            viewport: $(window), // Keep the tooltip on-screen at all times
            effect: true // Disable positioning animation
        },
        show: {
            modal: {
                on: true,
                blur: false
            },
            event: 'click',
            solo: true // Only show one tooltip at a time
        },
        hide: {
            event: false
        },
        style: {
            classes: 'uploadqtip ui-tooltip-light ui-tooltip-shadow ui-tooltip-rounded'
        }
    });

    $('a.editItemLink').click(function () {
        midas.loadDialog("editItem"+json.item.item_id,"/item/edit?itemId="+json.item.item_id);
        midas.showDialog(json.browse.edit, false, {
            width: 545
        });
    });
    $('#revisionList').accordion({
        clearStyle: true,
        collapsible: true,
        autoHeight: false
    });
    $('#revisionList').show();
    $('#historyLoading').hide();

    $('a.licenseLink').click(function () {
        var licenseId = $(this).attr('element');
        midas.loadDialog('viewLicense'+licenseId, '/license/view?licenseId='+licenseId);
        midas.showDialog($(this).attr('name'), false);
    });

    if($('.pathBrowser li').length > 5) {
        while($('.pathBrowser li').length > 5) {
            $('.pathBrowser li:first').remove();
        }
        $('.pathBrowser li:first').before('<li><span>...</span></li>');
    }
});
