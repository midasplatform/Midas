    $("#moveTable").treeTable();
    $("img.tableLoading").hide();
    $("table#moveTable").show();
   
   if($('#selectElements')!=undefined)
     {
       $('#selectElements').click(function(){
         $('#destinationUpload').html($('#selectedDestination').html());
         $('#destinationId').val($('#selectedDestinationHidden').val());
         $( "div.MainDialog" ).dialog('close');
         return false;
       });
     }
 
  //dependance: common/browser.js
    var ajaxSelectRequest='';
    function callbackSelect(node)
    {
      $('#selectedDestinationHidden').val(node.attr('element'));
      $('#selectedDestination').html(node.find('span:last').html());
      $('#selectElements').removeAttr('disabled');
      $('#copyElement').removeAttr('disabled');
      $('#moveElements').removeAttr('disabled');
    }
    
    
     $('img.infoLoading').show();
      $('div.ajaxInfoElement').html('');
     

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
          if(value['policy']!='0')
            {
            html+= "<tr id='"+id+"-"+i+"' class='parent child-of-"+id+"' ajax='"+value['folder_id']+"'type='folder'  policy='"+value['policy']+"' element='"+value['folder_id']+"'>";
            html+=     "  <td><span class='folder'>"+trimName(value['name'],padding)+"</span></td>";
            html+=     "</tr>";
            i++;
            }
            });
       return html;
    }
    
    