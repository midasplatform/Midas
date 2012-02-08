$('#createFolderForm').ajaxForm( {beforeSubmit: validateCreateFolder, success:       successCreateFolder} );

 if(typeof callbackDblClick != 'function') {
      function childrenOf(node) {
         if(node[0]==undefined)
          {
          return null;
          }
        return $("table.treeTable tbody tr.child-of-" + node[0].id);
      };
    }



function validateCreateFolder(formData, jqForm, options) {

    var form = jqForm[0];
    if (form.name.value.length<1)
      {
        createNotive('Error name',4000);
        return false;
      }
}

function successCreateFolder(responseText, statusText, xhr, form)
{
 if(typeof successCreateFolderCallback == 'function')
    {
    successCreateFolderCallback(responseText, statusText, xhr, form);
    return;
    }
  $( "div.MainDialog" ).dialog("close");
  jsonResponse = jQuery.parseJSON(responseText);
  if(jsonResponse==null)
    {
      createNotive('Error',4000);
      return;
    }
  if(jsonResponse[0])
    {
      createNotive(jsonResponse[1],4000);
      var node=$('table.treeTable tr[element='+jsonResponse[2].folder_id+']');
      if(node.length>0)
        {
          node.reload();
        }
      // the new folder is a top level folder  
      else
        {
          var newNodeId = '';
          if($("#browseTable > tbody > tr:last").length > 0)
            {
              var lastTopLevelNodeId = $("#browseTable > tbody > tr:last").attr("id").split("-")[2];
              newNodeId = parseInt(eval(lastTopLevelNodeId)+1);
            }
          else
            {
              newNodeId = '1'
            }
          
          var newRow = '';
          // policy: 2 <=> MIDAS_POLICY_ADMIN
          newRow += "<tr id='node--" + newNodeId + "' policy='2' deletable='true' class='parent' privacy='" + jsonResponse[3].privacy_status + "' type='folder' element='" + jsonResponse[3].folder_id + "' ajax='" + jsonResponse[3].folder_id + "'>";
          newRow += "  <td class='treeBrowseElement'><span class='folder'>" + jsonResponse[3].name + "</span></td>";
          newRow += "  <td><img class='folderLoading'  element='" + jsonResponse[3].folder_id + "' alt='' /></td>";
          newRow += "  <td>" + jsonResponse[4] + "</td>";
          newRow += "  <td><input type='checkbox' class='treeCheckbox' type='folder' element='" + jsonResponse[3].folder_id + "' /></td>";
          newRow += "</tr>";                              
        
          if($("#browseTable > tbody > tr:last").length > 0) 
          {
            $(newRow).insertAfter("#browseTable > tbody > tr:last");
          } 
          else 
          {
            $(newRow).appendTo("#browseTable > tbody");
          }
          $("#browseTable").treeTable({
            onFirstInit: midas.enableRangeSelect,
             onNodeShow: midas.enableRangeSelect,
             onNodeHide: midas.enableRangeSelect
          });
        }
      // create a top level folder  
      else
        {
        location.reload();  
        }
    }
  else
    {
      createNotive(jsonResponse[1],4000);
    }
}
