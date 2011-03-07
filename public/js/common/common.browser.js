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
          data: { type: node.attr('type'), id: node.attr('element') },
          success: function(jsonContent){
            createInfo(jsonContent);
            $('img.infoLoading').hide();

          }
        });       
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
            if(policy==2)
              {
              html+='<li><a>'+json.browse.edit+'</a></li>';
              }
            if(policy>=1)
              {
              html+='<li><a>'+json.browse.community.invit+'</a></li>';
              html+='<li><a>'+json.browse.community.advanced+'</a></li>';
              }              
          }
        if(type=='folder')
          {
            html+='<li><a href="'+json.global.webroot+'/folder/'+element+'">'+json.browse.view+'</a></li>';
            html+='<li><a href="'+json.global.webroot+'/download?folders='+element+'">'+json.browse.download+'</a></li>';
            if(policy==2)
              {
              html+='<li><a>'+json.browse.edit+'</a></li>';
              html+='<li><a>'+json.browse['delete']+'</a></li>';
              html+='<li><a>'+json.browse.move+'</a></li>';
              html+='<li><a>'+json.browse.copy+'</a></li>';
              }
            if(policy>=1)
              {
              html+='<li><a>'+json.browse.share+'</a></li>';
              }                
          }
        if(type=='item')
          {
            html+='<li><a href="'+json.global.webroot+'/item/'+element+'">'+json.browse.view+'</a></li>';
            html+='<li><a href="'+json.global.webroot+'/download?items='+element+'">'+json.browse.download+'</a></li>';
            if(policy==2)
              {
              html+='<li><a>'+json.browse.edit+'</a></li>';
              html+='<li><a>'+json.browse['delete']+'</a></li>';
              html+='<li><a>'+json.browse.move+'</a></li>';
              html+='<li><a>'+json.browse.copy+'</a></li>';
              }
            if(policy>=1)
              {
              html+='<li><a>'+json.browse.share+'</a></li>';
              }   
          }
         $('div.viewAction ul').html(html);
         $('div.viewAction ul').fadeIn('fast');
      });
    }
    
    function createInfo(jsonContent)
    {
      arrayElement=jQuery.parseJSON(jsonContent);
      var html='';
      if(arrayElement['type']=='community')
        {
        html+='<img class="infoLogo alt="Data Type" src="'+json.global.webroot+'/public/images/icons/community-big.png" />';
        }
      else if(arrayElement['type']=='folder')
        {
        html+='<img class="infoLogo alt="Data Type" src="'+json.global.webroot+'/public/images/icons/folder-big.png" />';
        }
      else
        {
        html+='<img class="infoLogo alt="Data Type" src="'+json.global.webroot+'/public/images/icons/document-big.png" />';
        }
      html+='<span class="infoTitle" >'+arrayElement['name']+'</span>';
      html+='<table>';
      html+='  <tr>';
      html+='    <td>'+arrayElement.translation.Created+'</td>';
      html+='    <td>'+arrayElement.creation+'</td>';
      html+='  </tr>';
      $('div.ajaxInfoElement').html(html); 
    }
    