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
    
    
    $('a.moveCopyLink').click(function()
      {
        loadDialog("movecopy","/browse/movecopy/?move=false&items="+json.item.item_id);
        showDialog(json.item.message.movecopy);
      });
   
     $('a#itemDeleteLink').click(function()
    {
      var html='';
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
      
    });
    
    $('a.sharingLink').click(function(){
      loadDialog("sharing"+$(this).attr('type')+$(this).attr('element'),"/share/dialog?type="+$(this).attr('type')+'&element='+$(this).attr('element'));
      showDialog(json.browse.share);
    });
    
    $('a.uploadRevisionLink').click(function(){
      loadDialog("uploadrevision"+$(this).attr('element'),"/upload/revision?itemId="+$(this).attr('element'));
      showDialog($(this).html());
    });
    
    //edit
    $('a.editItemLink').click(function(){
      loadDialog("editItem"+json.item.item_id,"/item/edit?itemId="+json.item.item_id);
        showDialog(json.browse.edit,false);
    });
        
  });
  