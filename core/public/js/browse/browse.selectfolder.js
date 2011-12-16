$("#moveTable").treeTable();
$("img.tableLoading").hide();
$("table#moveTable").show();

if($('#selectElements')!=undefined)
  {
  $('#selectElements').click(function(){
    $('#destinationUpload').html($('#selectedDestination').html());
    $('#destinationId').val($('#selectedDestinationHidden').val());
    $('.destinationUpload').html($('#selectedDestination').html());
    $('.destinationId').val($('#selectedDestinationHidden').val());
    $( "div.MainDialog" ).dialog('close');

    if(typeof folderSelectionCallback == 'function')
      {
      folderSelectionCallback($('#selectedDestination').html(), $('#selectedDestinationHidden').val());
      }
    return false;
    });
   }

//dependance: common/browser.js
var ajaxSelectRequest='';
function callbackSelect(node)
  {
  var selectedElement = node.find('span:eq(1)').html();

  var parent = true;
  var current = node;

  while(parent != null)
    {
    parent = null;
    var classNames = current[0].className.split(' ');
    for(key in classNames)
      {
      if(classNames[key].match("child-of-"))
        {
        parent = $("div.MainDialogContent #" + classNames[key].substring(9));
        }
      }
    if(parent != null)
      {
      selectedElement = parent.find('span:eq(1)').html()+'/'+selectedElement;
      current = parent;
      }
    }

  $('#createFolderContent').hide();
  if(node.attr('element') == -1)
    {
    $('#selectElements').attr('disabled', 'disabled');
    $('#createFolderButton').hide();
    }
  else
    {
    $('#selectedDestinationHidden').val(node.attr('element'));
    $('#selectedDestination').html(sliceFileName(selectedElement, 40));
    $('#selectElements').removeAttr('disabled');

    if($('#defaultPolicy').val() != 0)
      {
      $('#createFolderButton').show();
      }
    }
  }

$('img.infoLoading').show();
$('div.ajaxInfoElement').html('');

$('#createFolderButton').click(function(){
  if($('#createFolderContent').is(':hidden'))
    {
    $('#createFolderContent').html('<img  src="'+json.global.webroot+'/core/public/images/icons/loading.gif" alt="Loading..." />').show();
    var url = json.global.webroot+'/folder/createfolder?folderId='+$('#selectedDestinationHidden').val();
    $('#createFolderContent').load(url);
    }
  else
    {
    $('#createFolderContent').hide();
    }
  });


var newFolder = false;
function successCreateFolderCallback(responseText, statusText, xhr, form)
  {
  jsonResponse = jQuery.parseJSON(responseText);
  if(jsonResponse==null)
    {
    createNotive('Error',4000);
    return;
    }
  if(jsonResponse[0])
    {
    createNotive(jsonResponse[1],4000);
    var node = $('table.treeTable tr[element='+jsonResponse[2].folder_id+']');
    node.reload();

    $('#createFolderContent').hide();

    newFolder = jsonResponse[3].folder_id;
    }
  else
    {
    createNotive(jsonResponse[1],4000);
    }
  }

function reloadNodeCallback(mainNode)
  {
  if(newFolder != false)
    {
    callbackSelect($('table.treeTable tr[element='+newFolder+']'));
    }
  }

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
    if(value['policy'] >= parseInt($('#defaultPolicy').val()))
      {
      html+= "<tr id='"+id+"-"+i+"' class='parent child-of-"+id+"' ajax='"+value['folder_id']+"'type='folder'  policy='"+value['policy']+"' element='"+value['folder_id']+"'>";
      html+=     "  <td><span class='folder'>"+trimName(value['name'],padding)+"</span></td>";
      html+=     "</tr>";
      i++;
      }
    });
  return html;
  }
