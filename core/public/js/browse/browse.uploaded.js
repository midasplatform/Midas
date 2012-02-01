var midas = midas || {};
midas.browse = midas.browse || {};
midas.browse.uploaded = midas.browse.uploaded || {};
$(document).ready(
    function() {
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
      if(midas.ajaxSelectRequest!='')
        {        
        midas.ajaxSelectRequest.abort();
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
      
        midas.ajaxSelectRequest = $.ajax({
          type: "POST",
          url: json.global.webroot+'/browse/getelementinfo',
          data: {type: node.attr('type'), id: node.attr('element')},
          success: function(jsonContent){
            midas.createInfo(jsonContent);
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
              midas.ajaxSelectRequest = $.ajax({
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
      midas.genericCallbackDblClick(node);
    }
    
    function callbackCheckboxes(node) {
        var arraySelected = [];
        arraySelected['items'] = [];
        var selectedRows = [];
      
      var items = '';
      node.find(".treeCheckbox:checked").each(
          function(){
              arraySelected['items'].push($(this).attr('element'));
              items+=$(this).attr('element')+'-';
              selectedRows.push($(this).closest('tr').attr('id'));
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
        
        $('.mergeItemSubmit').click(
            function() {
                midas.browse.uploaded.mergeItems(link+'&name='+$('#mergeItemValue').val(),
                                                 selectedRows);
//           window.location.replace(link+'&name='+$('#mergeItemValue').val());
        });
      });
    }

/**
 * Makes an ajax call to merge items by calling the passed link. It then
 * @param link the url oto perform the merge.
 */
midas.browse.uploaded.mergeItems = function (link, selectedRows) {
    $("table#browseTable").hide();
    $("img.tableLoading").show();
    $.getJSON(link, function(data) {
        var lastId = selectedRows[selectedRows.length-1];
        var newRow = "<tr id='" + lastId + "'";
        newRow += " policy='"+ data.policy + "' class='' type='item' ";
        newRow += "element='" + data.item_id + "' >";
        newRow += "  <td class='treeBrowseElement'>";
        newRow += "    <span class='file'>" + data.name + "</span></td>";
        newRow += "  <td>" + data.size + "</td>";
        newRow += "  <td>" + data.date + "</td>";
        newRow += "  <td><input type='checkbox' ";
        newRow += "class='treeCheckbox' type='item' ";
        newRow += "element='" + data.item_id + "' /></td>";
        newRow += "</tr>";
        for (var curIndex in selectedRows) {
            $('tr#'+selectedRows[curIndex]).remove();
        }
        // Insert the new row as the first in the table or as the only row
        // if a first row does not exist.
        var newNode = '';
        if($("#browseTable > tbody > tr:first").length > 0) {
            newNode = $(newRow).insertBefore("#browseTable > tbody > tr:first");
        } 
        else {
            newNode = $(newRow).appendTo("#browseTable > tbody");
        }
        // Make the new row checked and selected.
        newNode.addClass("selected");
        var newCheck = newNode.find(".treeCheckbox");
        newCheck.attr("checked", true);
        callbackCheckboxes(newCheck);
        callbackSelect(newNode);
        createNotice("Item, " + data.name + ", merged from " +
                     selectedRows.length + " items.", 5000);
    })
    .error( function() { 
        createNotice("The item merge failed. Please contact an administrator.", 
                     5000);
    })                
    .complete( function() {
        // Close the dialog
        $( "div.MainDialog" ).dialog("close");

        // Refresh the table
        $("#browseTable").treeTable();
        $("img.tableLoading").hide();
        $("table#browseTable").show();
        midas.browser.enableSelectAll({ callback : callbackCheckboxes});
   });
};

$(document).ready(
    function() {
        $("#browseTable").treeTable();
        $("img.tableLoading").hide();
        $("table#browseTable").show();
        midas.browser.enableSelectAll({ callback : callbackCheckboxes});
    });
