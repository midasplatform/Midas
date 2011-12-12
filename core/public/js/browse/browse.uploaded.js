  $(document).ready(function() {

    $("#browseTable").treeTable();
    
    $("img.tableLoading").hide();
    $("table#browseTable").show();
  });
  
    function callbackSelect(node)
    {
      $('div.viewAction').show();
      $('div.viewInfo').show();
      $('img.infoLoading').show();
      $('div.ajaxInfoElement').html('');
      if(ajaxSelectRequest!='')
        {        
        ajaxSelectRequest.abort();
        }
      var type=node.attr('type');
      var element=node.attr('element');
      var policy=node.attr('policy');
      $('div.viewAction ul').fadeOut('fast',function()
      {
        $('div.viewAction ul').html('');
        var html='';
        if(type=='item')
          {
            html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/view.png"/> <a href="'+json.global.webroot+'/item/'+element+'">'+json.browse.view+'</a></li>';
            html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/download.png"/> <a href="'+json.global.webroot+'/download?items='+element+'">'+json.browse.downloadLatest+'</a></li>';
            if(policy>=1)
              {
              html+='<li><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/lock.png"/> <a  type="item" element="'+element+'" class="sharingLink">'+json.browse.share+'</a></li>';
              html+='<li ><img alt="" src="'+json.global.coreWebroot+'/public/images/icons/close.png"/> <a class onclick="deleteItem('+element+');">'+json.browse['delete']+'</a></li>'; 
              }   
          }
         $('div.viewAction ul').html(html);
         
         

         $('a.sharingLink').click(function(){
            loadDialog("sharing"+$(this).attr('type')+$(this).attr('element'),"/share/dialog?type="+$(this).attr('type')+'&element='+$(this).attr('element'));
            showDialog(json.browse.share);
          });
         $('div.viewAction ul').fadeIn('fast');
      });
      
        ajaxSelectRequest = $.ajax({
          type: "POST",
          url: json.global.webroot+'/browse/getelementinfo',
          data: {type: node.attr('type'), id: node.attr('element')},
          success: function(jsonContent){
            createInfo(jsonContent);
            $('img.infoLoading').hide();

          }
        });  
    }
    
    
    function deleteItem(element)
      {
        var html='';
        html+=json.item.message['deleteMessage'];
        html+='<br/>';
        html+='<br/>';
        html+='<br/>';
        html+='<input style="margin-left:140px;" class="globalButton deleteItemYes" element="'+element+'" type="button" value="'+json.global.Yes+'"/>';
        html+='<input style="margin-left:50px;" class="globalButton deleteItemNo" type="button" value="'+json.global.No+'"/>';

        showDialogWithContent(json.item.message['delete'],html,false);

        $('input.deleteItemYes').unbind('click').click(function()
          { 
              ajaxSelectRequest = $.ajax({
                type: "POST",
                url: json.global.webroot+'/item/delete',
                data: {itemId: element},
                success: function(jsonContent){
                   $('tr[element='+element+']').remove();
                  $( "div.MainDialog" ).dialog('close');
                }
              });  

          });
        $('input.deleteItemNo').unbind('click').click(function()
          {
             $( "div.MainDialog" ).dialog('close');
          });         

      }

    function callbackDblClick(node)
    {
      genericCallbackDblClick(node);
    }
    
    function callbackCheckboxes(node)
    {
      arraySelected=new Array();
      arraySelected['items']=new Array();
      
      var items='';
      node.find(".treeCheckbox:checked").each(function(){
        arraySelected['items'].push($(this).attr('element'));
        items+=$(this).attr('element')+'-';
      }); 

      var link=json.global.webroot+'/item/merge?items='+items;
      if((arraySelected['items'].length)>1)
        {
        $('div.viewSelected').show();
        var html=(arraySelected['items'].length);
        html+=' '+json.browse.element;
        if((arraySelected['items'].length)>1)
          {
           html+='s'; 
          }
        html+='<br/><a class="mergeItemsLink" link="'+link+'">'+json.item.message.merge+'</a>'; 
        $('div.viewSelected span').html(html); 
        }
      else
        {
        $('div.viewSelected').hide();
        $('div.viewSelected span').html('');
        }
        
      $('a.mergeItemsLink').click(function(){
        var link=$(this).attr('link');
        var html='';
        html+=json.item.message.mergeName+':';
        html+='<br/>';
        html+='<input type="text" id="mergeItemValue" value=""/>';
        html+='<br/>';
        html+='<br/>';
        html+='<input style="margin-left:140px;" class="globalButton mergeItemSubmit" value="'+json.browse.edit+'"/>';

        showDialogWithContent(json.item.message.merge,html,false);
        
        $('.mergeItemSubmit').click(function(){
           window.location.replace(link+'&name='+$('#mergeItemValue').val());
        });
      });
    }