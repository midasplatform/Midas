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
    }
  else
    {
      createNotive(jsonResponse[1],4000);
    }
}
