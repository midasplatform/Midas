$("#moveTable").treeTable({
  container: "moveTable"
  });
$("div.MainDialogContent img.tableLoading").hide();
$("table#moveTable").show();

if($('div.MainDialogContent #selectElements') != undefined)
  {
  $('div.MainDialogContent #selectElements').click(function(){
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

function reloadNodeCallback(mainNode)
  {
  if(newFolder != false)
    {
    callbackSelect($('#moveTable tr[element='+newFolder+']'));
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
