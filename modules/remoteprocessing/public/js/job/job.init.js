var currentBrowser = false;

$(document).ready(function(){

  $('.selectInputFileLink').click(function(){
    loadDialog("selectitem_"+$(this).attr('order'),"/browse/selectitem");
    showDialog('Browse');
    currentBrowser = $(this).attr('order');
  });

  $('.selectOutputFolderLink').click(function(){
    loadDialog("selectfolder_"+$(this).attr('order'),"/browse/selectfolder?policy=write");
    showDialog('Browse');
    currentBrowser = $(this).attr('order');
  });

  $('.selectInputFolderLink').click(function(){
    loadDialog("selectfolder_"+$(this).attr('order'),"/browse/selectfolder?policy=read");
    showDialog('Browse');
    currentBrowser = $(this).attr('order');
  });

  $('#creatJobLink').click(function(){
    var cansubmit = true;
    var i = 0;
    var results = new Array;
    $('.optionWrapper').each(function(){
    if($(this).find('.selectedFolder').length > 0)
      {
      if($(this).find('.nameOutputOption').val() == '' || $(this).find('.selectedFolder').attr('element') == '')
        {
        createNotive('Please set '+$(this).attr('name'), 4000);
        cansubmit = false;
        }
      else
        {
        results[i] = $(this).find('.selectedFolder').attr('element')+';;'+$(this).find('.nameOutputOption').val();
        }
      }
    else if($(this).find('.selectInputFileLink').length > 0)
      {
      if($(this).find('.selectedItem').attr('element') == '' && $(this).find('.selectedFolderContent').attr('element') == '')
        {
        createNotive('Please set '+$(this).attr('name'), 4000);
        cansubmit = false;
        }
      else
        {
        var folderElement = $(this).find('.selectedFolderContent').attr('element');
        if(folderElement != '')
          {
          results[i] = 'folder'+folderElement;
          }
        else
          {
          results[i] = $(this).find('.selectedItem').attr('element');
          }
        }
      }
    else
      {
      if($(this).find('.valueInputOption').val() == '')
        {
        createNotive('Please set '+$(this).attr('name'), 4000);
        cansubmit = false;
        }
      else
        {
        results[i] = $(this).find('.valueInputOption').val();
        }
      }
    i++;
    });

    if(cansubmit)
      {
      req = { 'results[]' : results};
      $(this).after('<img  src="'+json.global.webroot+'/core/public/images/icons/loading.gif" alt="Saving..." />')
      $(this).remove();
      $.ajax({
           type: "POST",
           url: "",
           data: req ,
           success: function(x){
             window.location.replace($('.webroot').val()+'/remoteprocessing/job/manage/?itemId='+json.item.item_id+'&inprogress=true')
           }
         });
      }

  });

  $('input').change(function(){
    updateGeneratedCommand();
  });

  updateGeneratedCommand();
});



function itemSelectionCallback(name, id)
  {
  var optionWrapper = $('#option_'+currentBrowser);
  optionWrapper.find('.selectedItem').html('Item '+name);
  optionWrapper.find('.selectedItem').attr('element',id);
  optionWrapper.find('.selectedFolder').attr('element', '');
  updateGeneratedCommand()
  }

function folderSelectionCallback(name, id)
  {
  var optionWrapper = $('#option_'+currentBrowser);
  optionWrapper.find('.selectedFolderContent').html('Folder '+name);
  optionWrapper.find('.selectedFolder').html('Folder '+name);
  optionWrapper.find('.selectedItem').html('Folder '+name);
  optionWrapper.find('.selectedFolderContent').attr('element', id);
  optionWrapper.find('.selectedFolder').attr('element', id);
  optionWrapper.find('.selectedItem').attr('element', '');
  updateGeneratedCommand()
  }

function updateGeneratedCommand()
  {
  var html = json.item.name;
  $('.optionWrapper').each(function(){
    html += ' ';
    var tag = $(this).attr('tag');
    if(tag != '')
      {
      html += tag+' ';
      internal += tag+' ';
      }
    if($(this).find('.selectedFolder').length > 0)
      {
      html += "'"+$(this).find('.selectedFolder').html()+"/"+$(this).find('.nameOutputOption').val()+"' ";
      }
    else if($(this).find('.selectInputFileLink').length > 0)
      {
      html += "'"+$(this).find('.selectedItem').html()+"' ";
      }
    else
      {
      html += $(this).find('.valueInputOption').val();
      }
  });

  $('#commandGenerated').html(html);
  }