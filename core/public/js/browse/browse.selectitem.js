    $("#moveTable").treeTable();
    $("img.tableLoading").hide();
    $("table#moveTable").show();

    $('applet').hide();

   if($('#selectElements')!=undefined)
     {
       $('#selectElements').click(function(){
         $('#destinationUpload').html($('#selectedDestination').html());
         $('#destinationId').val($('#selectedDestinationHidden').val());
         $('.destinationUpload').html($('#selectedDestination').html());
         $('.destinationId').val($('#selectedDestinationHidden').val());
         $( "div.MainDialog" ).dialog('close');
         $('applet').show();

         if(typeof itemSelectionCallback == 'function')
            {
            itemSelectionCallback($('#selectedDestination').html(), $('#selectedDestinationHidden').val());
            }
         return false;
       });
     }

  //dependance: common/browser.js
    var ajaxSelectRequest='';
    function callbackSelect(node)
    {
      if(node.attr('type') == 'item')
        {
        var selectedElement = node.find('span:eq(0)').html();

        $('#selectedDestinationHidden').val(node.attr('element'));
        $('#selectedDestination').html(sliceFileName(selectedElement, 40));
        $('#selectElements').removeAttr('disabled');
        }
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

          $.each(elements['items'], function(index, value) {

          html+=  "<tr id='"+id+"-"+i+"' class='child-of-"+id+"' privacy='"+value['privacy_status']+"'  type='item' policy='"+value['policy']+"' element='"+value['item_id']+"'>";
          html+=     "  <td><span class='file'>"+trimName(value['name'],padding)+"</span></td>";
          html+=     "</tr>";
          i++;
          });
       return html;


    }

