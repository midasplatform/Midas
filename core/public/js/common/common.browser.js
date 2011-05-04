    var ajaxSelectRequest='';
    function genericCallbackSelect(node)
    {
      $('img.infoLoading').show();
      $('div.ajaxInfoElement').html('');
      if(ajaxSelectRequest!='')
        {        
        ajaxSelectRequest.abort();
        }
        createAction(node);
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
    
    var arraySelected=new Array();
    
    function genericCallbackCheckboxes(node)
    {
      arraySelected=new Array();
      arraySelected['folders']=new Array();
      arraySelected['items']=new Array();
      
      var folders='';
      var items='';
      node.find(".treeCheckbox:checked").each(function(){
        if($(this).parents('tr').attr('type')!='item')
          {
          arraySelected['folders'].push($(this).attr('element'));
          folders+=$(this).attr('element')+'-';
          }
        else
          {
          arraySelected['items'].push($(this).attr('element'));
          items+=$(this).attr('element')+'-';
          }
      }); 
      var link=json.global.webroot+'/download?folders='+folders+'&items='+items;
      if((arraySelected['folders'].length+arraySelected['items'].length)>0)
        {
        $('div.viewSelected').show();
        var html=(arraySelected['folders'].length+arraySelected['items'].length);
        html+=' '+json.browse.element;
        if((arraySelected['folders'].length+arraySelected['items'].length)>1)
          {
           html+='s'; 
          }
        html+='<br/><a href="'+link+'">'+json.browse.download+'</a>'; 
        $('div.viewSelected span').html(html); 
        }
      else
        {
        $('div.viewSelected').hide();
        $('div.viewSelected span').html('');
        }
    }

    function genericCallbackDblClick(node)
    {
      if(node.attr('type')=='community')
        {
        window.location.replace(json.global.webroot+'/community/'+node.attr('element'));
        }
      if(node.attr('type')=='folder')
        {
        window.location.replace(json.global.webroot+'/folder/'+node.attr('element'));
        }
      if(node.attr('type')=='item')
        {
        window.location.replace(json.global.webroot+'/item/'+node.attr('element'));
        }
    }
    
    function createNewFolder(id)
    {
      loadDialog('folderId'+id,'/folder/createfolder?folderId='+id);
      showDialog(json.browse.createFolder,false);
    }
    
    function deleteFolder(id)
    {
      var html='';
      html+=json.browse['deleteMessage'];
      html+='<br/>';
      html+='<br/>';
      html+='<br/>';
      html+='<input style="margin-left:140px;" class="globalButton deleteFolderYes" element="'+id+'" type="button" value="'+json.global.Yes+'"/>';
      html+='<input style="margin-left:50px;" class="globalButton deleteFolderNo" type="button" value="'+json.global.No+'"/>';
      
      showDialogWithContent(json.browse['delete'],html,false);
      
      $('input.deleteFolderYes').unbind('click').click(function()
        { 
          var node=$('table.treeTable tr.parent[element='+id+']');
          $.post(json.global.webroot+'/folder/delete', {folderId: id},
           function(data) {
               jsonResponse = jQuery.parseJSON(data);
                if(jsonResponse==null)
                  {
                    createNotive('Error',4000);
                    return;
                  }
                if(jsonResponse[0])
                  {
                    createNotive(jsonResponse[1],1500);
                    node.each(function(){
                      var children = childrenOf($(this));
                      if(children!=undefined)
                        {
                        children.remove();
                        }
                    });
                    node.remove();
                    $( "div.MainDialog" ).dialog('close');
                  }
                else
                  {
                    createNotive(jsonResponse[1],4000);
                  }
           });

        });
      $('input.deleteFolderNo').unbind('click').click(function()
        {
           $( "div.MainDialog" ).dialog('close');
        });         
      
    }
    
    
    function editFolder(id)
    {
        loadDialog("editFolder"+id,"/folder/edit?folderId="+id);
        showDialog(json.browse.edit,false);
    }
    
    function removeItem(id)
    {
      var html='';
      html+=json.browse['removeMessage'];
      html+='<br/>';
      html+='<br/>';
      html+='<br/>';
      html+='<input style="margin-left:140px;" class="globalButton deleteFolderYes" element="'+id+'" type="button" value="'+json.global.Yes+'"/>';
      html+='<input style="margin-left:50px;" class="globalButton deleteFolderNo" type="button" value="'+json.global.No+'"/>';
      
      showDialogWithContent(json.browse['delete'],html,false);
      
      $('input.deleteFolderYes').unbind('click').click(function()
        { 
          var node=$('table.treeTable tr[element='+id+']');
          var parent;
          //get parent
          var classNames = node.attr('class').split(' ');    
          for(key in classNames) {
            if(classNames[key].match("child-of-")) {
              parent = $("#" + classNames[key].substring(9));
            }
          }
          $.post(json.global.webroot+'/folder/removeitem', {folderId: parent.attr('element'), itemId: id},
           function(data) {
               jsonResponse = jQuery.parseJSON(data);
                if(jsonResponse==null)
                  {
                    createNotive('Error',4000);
                    return;
                  }
                if(jsonResponse[0])
                  {
                    createNotive(jsonResponse[1],1500);
                    node.remove();
                    $( "div.MainDialog" ).dialog('close');
                    parent.find('span.elementCount').remove();
                    parent.find('span.elementSize').after("<img class='folderLoading'  element='{"+parent.attr('element')+"}' alt='' src='"+json.global.coreWebroot+"/public/images/icons/loading.gif'/>");
                    parent.find('span.elementSize').remove();
                    getElementsSize();
                  }
                else
                  {
                    createNotive(jsonResponse[1],4000);
                  }
           });

        });
      $('input.deleteFolderNo').unbind('click').click(function()
        {
           $( "div.MainDialog" ).dialog('close');
        });         
      
    }
    
    function createAction(node)
    {
      var type=node.attr('type');
      var element=node.attr('element');
      var policy=node.attr('policy');
      $('div.viewAction ul').fadeOut('fast',function()
      {
        $('div.viewAction ul').html('');
        var html='';
        if(type=='community')
          {
            html+='<li><a href="'+json.global.webroot+'/community/'+element+'">'+json.browse.view+'</a></li>';            
          }
        if(type=='folder')
          {
            html+='<li><a href="'+json.global.webroot+'/folder/'+element+'">'+json.browse.view+'</a></li>';
            html+='<li><a href="'+json.global.webroot+'/download?folders='+element+'">'+json.browse.download+'</a></li>';
            if(policy>=1)
              { 
              html+='<li><a onclick="createNewFolder('+element+');">'+json.browse.createFolder+'</a></li>';
              html+='<li><a rel="'+json.global.webroot+'/upload/simpleupload/?parent='+element+'" class="uploadInFolder">'+json.browse.uploadIn+'</a></li>';
              html+='<li><a onclick="editFolder('+element+');">'+json.browse.edit+'</a></li>';
              if(node.attr('deletable')!=undefined && node.attr('deletable')=='true')
                {
                html+='<li><a type="folder" element="'+element+'" class="sharingLink">'+json.browse.share+'</a></li>';                
                html+='<li><a onclick="deleteFolder('+element+');">'+json.browse['delete']+'</a></li>'; 
                }
              }                
          }
        if(type=='item')
          {
            html+='<li><a href="'+json.global.webroot+'/item/'+element+'">'+json.browse.view+'</a></li>';
            html+='<li><a href="'+json.global.webroot+'/download?items='+element+'">'+json.browse.downloadLastest+'</a></li>';
            if(policy>=1)
              {
              html+='<li><a  type="item" element="'+element+'" class="sharingLink">'+json.browse.share+'</a></li>';
              html+='<li class="removeItemLi"><a onclick="removeItem('+element+');">'+json.browse['removeItem']+'</a></li>'; 
              }   
          }
         $('div.viewAction ul').html(html);
         
         $('a.uploadInFolder').cluetip({
           cluetipClass: 'jtip',
           dropShadow: false,
           hoverIntent: false,
           activation: 'click', 
           arrows: true, 
           closePosition: 'title',
           closeText: '<img src="'+json.global.coreWebroot+'/public/images/icons/close.png" alt="close" />',  
           positionBy:'uploadElement',
           topOffset:        20,   
           leftOffset:       -450,
           width:            600
          });
         
         $('a.sharingLink').click(function(){
            loadDialog("sharing"+$(this).attr('type')+$(this).attr('element'),"/share/dialog?type="+$(this).attr('type')+'&element='+$(this).attr('element'));
            showDialog(json.browse.share);
          });
         $('div.viewAction ul').fadeIn('fast');
      });
    }
    
    function createInfo(jsonContent)
    {
      arrayElement=jQuery.parseJSON(jsonContent);
      var html='';
      if(arrayElement['type']=='community')
        {
        html+='<img class="infoLogo" alt="Data Type" src="'+json.global.coreWebroot+'/public/images/icons/community-big.png" />';
        }
      else if(arrayElement['type']=='folder')
        {
        html+='<img class="infoLogo" alt="Data Type" src="'+json.global.coreWebroot+'/public/images/icons/folder-big.png" />';
        }
      else
        {
        html+='<img class="infoLogo" alt="Data Type" src="'+json.global.coreWebroot+'/public/images/icons/document-big.png" />';
        }
        html+='<span class="infoTitle" >'+arrayElement['name']+'</span>';
        html+='<table>';
        html+='  <tr>';
        html+='    <td>'+arrayElement.translation.Created+'</td>';
        html+='    <td>'+arrayElement.creation+'</td>';
        html+='  </tr>';
      if(arrayElement['type']=='community')
        {
        html+='  <tr>';
        html+='    <td>Member';
        if(parseInt(arrayElement['members'])>1)
          {
            html+='s';
          }
        html+=     '</td>';
        html+='    <td>'+arrayElement['members']+'</td>';
        html+='  </tr>';                
        }
      if(arrayElement['type']=='item')
        {
        html+='  <tr>';
        html+='    <td>'+arrayElement.translation.Uploaded+'</td>';
        html+='    <td><a href="'+json.global.webroot+'/user/'+arrayElement['uploaded']['user_id']+'">'+arrayElement['uploaded']['firstname']+' '+arrayElement['uploaded']['lastname']+'</a></td>';
        html+='  </tr>';
        html+='  <tr>';
        html+='    <td>Revision';
        if(parseInt(arrayElement['revision']['revision'])>1)
          {
            html+='s';
          }
        html+=     '</td>';
        html+='    <td>'+arrayElement['revision']['revision']+'</td>';
        html+='  </tr>';
        html+='  <tr>';
        html+='    <td>'+arrayElement.translation.File;
              if(parseInt(arrayElement['nbitstream'])>1)
          {
            html+='s';
          }
        html+=    '</td>';
        html+='    <td>'+arrayElement['nbitstream']+'</td>';
        html+='  </tr>';
            
        }
        
      if(arrayElement['type']=='folder')
        {
        html+='  <tr>';
        html+='    <td colspan="2">';
        html+=arrayElement['teaser'];
        html+=     '</td>';
        html+='  </tr>';                
        }
      html+='</table>';    
      if(arrayElement['type']=='community'&&arrayElement['privacy']==2)
        {
         html+='<h4>'+arrayElement.translation.Private+'</h4>';     
        }
      
      
      if(arrayElement['thumbnail']!=undefined&&arrayElement['thumbnail']!='')
        {
        html+='<h1>'+json.browse.preview+'</h1><a href="'+json.global.webroot+'/item/'+arrayElement['item_id']+'"><img class="infoLogo" alt="" src="'+json.global.webroot+'/'+arrayElement['thumbnail']+'" /></a>'; 
        }

      $('div.ajaxInfoElement').html(html); 
    }
    