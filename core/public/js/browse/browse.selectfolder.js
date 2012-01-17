$("#moveTable").treeTable({
  callbackSelect: selectFolderCallbackSelect,
  callbackDblClick: selectFolderCallbackDblClick,
  callbackReloadNode: selectFolderCallbackReloadNode,
  callbackCheckboxes: selectFolderCallbackCheckboxes,
  callbackCustomElements: selectFolderCallbackCustomElements
  });
$("div.MainDialogContent img.tableLoading").hide();
$("table#moveTable").show();

if($('div.MainDialogContent #selectElements') != undefined)
  {
  $('div.MainDialogContent #selectElements').click(function(){
    var folderName = $('#selectedDestination').html();
    var folderId = $('#selectedDestinationHidden').val();
    midas.doCallback('CALLBACK_CORE_UPLOAD_FOLDER_CHANGED', {folderName: folderName, folderId: folderId});

    $('#destinationUpload').html(folderName);
    $('#destinationId').val(folderId);
    $('.destinationUpload').html(folderName);
    $('.destinationId').val(folderId);
    $( "div.MainDialog" ).dialog('close');

    if(typeof folderSelectionCallback == 'function')
      {
      folderSelectionCallback(folderName, folderId);
      }
    return false;
    });
  }

//dependance: common/browser.js
var ajaxSelectRequest = '';
function selectFolderCallbackSelect(node)
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

  $('div.MainDialogContent #createFolderContent').hide();
  if(node.attr('element') == -1)
    {
    $('div.MainDialogContent #selectElements').attr('disabled', 'disabled');
    $('div.MainDialogContent #createFolderButton').hide();
    }
  else
    {
    $('div.MainDialogContent #selectedDestinationHidden').val(node.attr('element'));
    $('div.MainDialogContent #selectedDestination').html(sliceFileName(selectedElement, 40));
    $('div.MainDialogContent #selectElements').removeAttr('disabled');

    if($('div.MainDialogContent #defaultPolicy').val() != 0)
      {
      $('div.MainDialogContent #createFolderButton').show();
      }
    }
  }

$('#moveTable ajaimg.infoLoading').show();
$('div.MainDialogContent div.ajaxInfoElement').html('');

$('div.MainDialogContent #createFolderButton').click(function(){
  if($('div.MainDialogContent #createFolderContent').is(':hidden'))
    {
    $('div.MainDialogContent #createFolderContent').html('<img  src="'+json.global.webroot+'/core/public/images/icons/loading.gif" alt="Loading..." />').show();
    var url = json.global.webroot+'/folder/createfolder?folderId='+$('#selectedDestinationHidden').val();
    $('div.MainDialogContent #createFolderContent').load(url);
    }
  else
    {
    $('div.MainDialogContent #createFolderContent').hide();
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
    var node = $('#moveTable tr[element='+jsonResponse[2].folder_id+']');
    node.reload();

    $('div.MainDialogContent #createFolderContent').hide();

    newFolder = jsonResponse[3].folder_id;
    }
  else
    {
    createNotive(jsonResponse[1],4000);
    }
  }

function selectFolderCallbackReloadNode(mainNode)
  {
  if(newFolder != false)
    {
    callbackSelect($('#moveTable tr[element='+newFolder+']'));
    }
  }

function selectFolderCallbackDblClick(node)
  {
  //  genericCallbackDblClick(node);
  }

function selectFolderCallbackCheckboxes(node)
  {
  //  genericCallbackCheckboxes(node);
  }

function selectFolderCallbackCustomElements(node,elements,first)
  {
  var i = 1;
  var id = node.attr('id');
  elements['folders'] = jQuery.makeArray(elements['folders']);

  var padding = parseInt(node.find('td:first').css('padding-left').slice(0,-2));
  var html = '';
  $.each(elements['folders'], function(index, value) {
    if(value['policy'] >= parseInt($('div.MainDialogContent #defaultPolicy').val()))
      {
      html+= "<tr id='"+id+"-"+i+"' class='parent child-of-"+id+"' ajax='"+value['folder_id']+"'type='folder'  policy='"+value['policy']+"' element='"+value['folder_id']+"'>";
      html+=     "  <td><span class='folder'>"+trimName(value['name'],padding)+"</span></td>";
      html+=     "</tr>";
      i++;
      }
    });
  return html;
  }
