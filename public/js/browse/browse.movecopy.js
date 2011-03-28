    $("#moveTable").treeTable();
    $("img.tableLoading").hide();
    $("table#moveTable").show();
   
 
  //dependance: common/browser.js
    var ajaxSelectRequest='';
    function callbackSelect(node)
    {
      $('#selectedDestinationHidden').val(node.attr('element'));
      $('#selectedDestination').html(node.find('span:last').html());
      $('#copyElement').removeAttr('disabled');
      $('#moveElements').removeAttr('disabled');
    }
    
    
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

    function callbackDblClick(node)
    {
    //  genericCallbackDblClick(node);
    }
    
    function callbackCheckboxes(node)
    {
    //  genericCallbackCheckboxes(node);
    }
    
    function customElements(node,elements,first)
    {
        var i = 1;
        var id=node.attr('id');
        elements['folders'] = jQuery.makeArray(elements['folders']);

        var padding=parseInt(node.find('td:first').css('padding-left').slice(0,-2));
        var html='';
        $.each(elements['folders'], function(index, value) {
          html+= "<tr id='"+id+"-"+i+"' class='parent child-of-"+id+"' ajax='"+value['folder_id']+"'type='folder'  policy='"+value['policy']+"' element='"+value['folder_id']+"'>";
          html+=     "  <td><span class='folder'>"+trimName(value['name'],padding)+"</span></td>";
          html+=     "</tr>";
          i++;
          });
       return html;
    }
    
    
    function trimName(name,padding)
    {
      if(name.length*7+padding>350)
        { 
        toremove=(name.length*7+padding-350)/8;  
        if(toremove<13)
          {
          return 'error';
          }
        name=name.substring(0,10)+'...'+name.substring(name.length+13-toremove);
        return name;
        }
    return name;
    }