  $(document).ready(function() {
    
    $('a.metadataDeleteLink img').fadeTo("fast",0.4);
    $('a.metadataDeleteLink').click(function(){
      var metadataCell = $(this).parents('tr');
      var metadataId = $(this).attr('element');
      var html='';
      html+=json.item.message['deleteMetadataMessage'];
      html+='<br/>';
      html+='<br/>';
      html+='<br/>';
      html+='<input style="margin-left:140px;" class="globalButton deleteMetaDataYes" element="'+$(this).attr('element')+'" type="button" value="'+json.global.Yes+'"/>';
      html+='<input style="margin-left:50px;" class="globalButton deleteMetaDataNo" type="button" value="'+json.global.No+'"/>';
      showDialogWithContent(json.item.message['delete'],html,false);

      $('input.deleteMetaDataYes').unbind('click').click(function()
        {
          $.post(json.global.webroot+'/item/'+json.item.item_id, { element: metadataId, deleteMetadata: true});
          metadataCell.remove();
          $( "div.MainDialog" ).dialog('close');
        });
      $('input.deleteMetaDataNo').unbind('click').click(function()
        {
           $( "div.MainDialog" ).dialog('close');
        });
      });
    $('a.metadataEditLink img').fadeTo("fast",0.4);

    $('a.metadataEditLink').click(function(){
      var metadataId = $(this).attr('element');
      loadDialog("editmetadata"+metadataId, "/item/editmetadata/?metadataId="+metadataId+"&itemId="+json.item.item_id);
      showDialog('MetaData');

    });
    $('a.addMetadataLink').click(function(){
      var metadataId = $(this).attr('element');
      loadDialog("editmetadata"+metadataId, "/item/editmetadata/?itemId="+json.item.item_id);
      showDialog('MetaData');

    });


    $('a.shareItemLink').click(function()
      {
        loadDialog("shareItem","/browse/movecopy/?share=true&items="+json.item.item_id);
        showDialog(json.item.message.share);
      });
      
    $('a.duplicateItemLink').click(function()
      {
        loadDialog("duplicateItem","/browse/movecopy/?duplicate=true&items="+json.item.item_id);
        showDialog(json.item.message.duplicate);
      });

     $('a#itemDeleteLink').click(function()
    {       
     $.ajax({
           type: "GET",
           url: json.global.webroot+'/item/checkshared',
           data: {itemId: json.item.item_id},
           success: function(jsonContent){
             
             var $itemIsShared = $.parseJSON(jsonContent);
             var html='';
             if ($itemIsShared == true)
               {
               html+=json.item.message['sharedItem'];
               }
             html+=json.item.message['deleteMessage'];
             html+='<br/>';
             html+='<br/>';
             html+='<br/>';
             html+='<input style="margin-left:140px;" class="globalButton deleteItemYes" element="'+$(this).attr('element')+'" type="button" value="'+json.global.Yes+'"/>';
             html+='<input style="margin-left:50px;" class="globalButton deleteItemNo" type="button" value="'+json.global.No+'"/>';

             showDialogWithContent(json.item.message['delete'],html,false);

             $('input.deleteItemYes').unbind('click').click(function()
             {
             location.replace(json.global.webroot+'/item/delete?itemId='+json.item.item_id);
             });
            $('input.deleteItemNo').unbind('click').click(function()
            {
              $( "div.MainDialog" ).dialog('close');
              });
           }
     });
        
   });

    $('a.sharingLink').click(function(){
      loadDialog("sharing"+$(this).attr('type')+$(this).attr('element'),"/share/dialog?type="+$(this).attr('type')+'&element='+$(this).attr('element'));
      showDialog(json.browse.share);
    });


    var itemId = $('a.uploadRevisionLink').attr('element');
    $('a.uploadRevisionLink').qtip(
      {
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
      })
    //$('.uploadqtip').css('z-index:500');

    //edit
    $('a.editItemLink').click(function(){
      loadDialog("editItem"+json.item.item_id,"/item/edit?itemId="+json.item.item_id);
        showDialog(json.browse.edit,false);
    });

  });
