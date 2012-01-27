var currentBrowser = false;
var executableValid = false;
var isExecutableMeta = false;
var isDefineAjax = true;
var results = new Array;

$(document).ready(function(){
  // Initialize Smart Wizard
  $('#wizard').smartWizard(
  {
  // Properties
    keyNavigation: true, // Enable/Disable key navigation(left and right keys are used if enabled)
    enableAllSteps: false,  // Enable/Disable all steps on first load
    transitionEffect: 'fade', // Effect on navigation, none/fade/slide/slideleft
    contentURL:null, // specifying content url enables ajax content loading
    contentCache:false, // cache step contents, if false content is fetched always from ajax url
    cycleSteps: false, // cycle step navigation
    enableFinishButton: false, // makes finish button enabled always
    errorSteps:[],    // array of step numbers to highlighting as error steps
    labelNext:'Next', // label for Next button
    labelPrevious:'Previous', // label for Previous button
    labelFinish:'Create Job',  // label for Finish button
    // Events
    onLeaveStep: onLeaveStepCallback, // triggers when leaving a step
    onShowStep: onShowStepCallback,  // triggers when showing a step
    onFinish: onFinishCallback  // triggers when Finish button is clicked
  }
  );

  $('#uploadContentBlock').load(json.global.webroot+'/upload/simpleupload');
  $('#wizard').show();

  if($('#selectedExecutableId').val() != '')
    {
    executableValid = true;
    isExecutableMeta = true;
    }

  $('#addJobLink').click(function(){
    showCreationJobDialog();
  })

  initScheduler();

});

function showCreationJobDialog()
  {
  var x= $('div.HeaderSearch').position().left+250;
  var y= 100;
  $('#selectExecutable').hide();
  $('#jobNameForm').val('');
  $('#dialogJobCreation select:first').val('-1');
  $( "#dialogJobCreation" ).dialog({
      position: [x,y],
      width:300,
			modal: true
		});

  $('#jobTypeForm').change(function(){
    $('#selectExecutable').hide();
    if($(this).val() == 'executable')
      {
      $('#selectExecutable').show();
      $('#selectedExecutable').html('None');
      $('#selectedExecutableId').val('');
      $('#browseExecutableFile').click(function(){
        loadDialog("selectitem_executable","/browse/selectitem");
        showDialog('Browse');
        currentBrowser = 'executable';
      });
      }
  });

  $('#createJobLink').unbind('click').click(function(){createJobLink()})
  }

function createJobLink()
  {
  var isValid = true;
  if($('#jobNameForm').val() == '')
    {
    createNotive("Please select a job name.", 4000);
    isValid = false;
    }
  if($('#jobTypeForm').val() == '-1')
    {
    createNotive("Please select a job type.", 4000);
    isValid = false;
    }
  if($('#jobTypeForm').val() == 'executable' && !executableValid)
    {
    createNotive("The selected item is not a valid executable.", 4000);
    isValid = false;
    }

  var nameUsed = false;
  $( ".executableForm b:first").each(function(){
    if($('#jobNameForm').val() == $(this).html())
      {
      nameUsed = true;
      }
  });

  if(nameUsed)
    {
    createNotive("This name is already used.", 4000);
    isValid = false;
    }

  if(isValid)
    {
    $( "#dialogJobCreation" ).dialog('close');
    $('#jobListing').append('<div class="executableForm" loaded=""></div>');
    var itemid = $('#selectedExecutableId').val();
    var name = $('#jobNameForm').val();
    if($('.executableForm:last').attr('loaded') != itemid)
      {
      $('.executableForm:last').attr('loaded',itemid);
      $('.executableForm:last').load(json.global.webroot+'/remoteprocessing/job/getinitexecutable?scheduled='+json.job.scheduled+'&itemId='+ itemid, new Array(), function(){
        initExecutableForm();
        $('.executableForm:last').prepend('<button class="toggleJob"></button><b style="position:relative;top:-6px;left:5px;font-size:14px;">'+name+'</b>');
        $('.executableForm:last .optionWrapper').each(function(){
          $(this).attr('id',  $(this).attr('id')+"--"+name);
        });

        $('.executableForm:last .selectInputFileLink').each(function(){
          $(this).attr('order',  $(this).attr('order')+"--"+name);
        });

        $('.executableForm:last .selectOutputFolderLink').each(function(){
          $(this).attr('order',  $(this).attr('order')+"--"+name);
        });

        $( ".executableForm:last button.toggleJob" ).button({
            icons: {
                primary: "ui-icon-triangle-1-s"
            },
            text: false
        });
        $( ".executableForm:last button.toggleJob" ).click(function(){
          if($(this).parent('div').find('.optionWrapper:first').is(':hidden'))
            {
            $(this).parent('div').find('.optionWrapper').fadeIn();
            $(this).find('span').removeClass('ui-icon-triangle-1-e').addClass('ui-icon-triangle-1-s');
            }
          else
            {
            $(this).parent('div').find('.optionWrapper').fadeOut();
            $(this).find('span').removeClass('ui-icon-triangle-1-s').addClass('ui-icon-triangle-1-e');
            }
        });
        synchronizeJobs();
      });
      }
    }
  }

function synchronizeJobs()
  {
  var outputsParams = new Array();
  var i = 0;
  $( ".executableForm").each(function(){
    $(this).attr('orderIndice', i);
    var name = $(this).find('b:first').html();
    var tmp = new Array();
    tmp['name'] = name;
    tmp['indice'] = i;
    tmp['params'] = new Array();
    $(this).find('[channel=output]').each(function(){
       tmp['params'].push($(this).attr('name'));
    });
    $(this).find('[channel=ouput]').each(function(){
       tmp['params'].push($(this).attr('name'));
    });
    outputsParams.push(tmp);
    i++;
  });
  $( ".executableForm").each(function(){
    var name = $(this).find('b:first').html();
    var indice = $(this).attr('orderIndice');
    $(this).find('.selectInputFileLink').each(function(){
       var html = '';
       var parentDiv = $(this).parent('div');
       var willBeAdded = false;
       var selectedValue = parentDiv.find('select').val();
       parentDiv.find('select').remove()
       html += '<select style="margin-left:5px;"><option value="-1">-Or select a job output-</option>';
       $.each(outputsParams, function(key, valueArray) {
          if(name == valueArray['name']) return;
          if(indice < valueArray['indice']) return;
          html += '<optgroup label="Job: '+valueArray['name']+'"></optgroup>';
          $.each(valueArray['params'], function(key, value) {
              html += '<option value="'+valueArray['name']+'-'+value+'">'+valueArray['name']+'-'+value+'</option>';
              willBeAdded = true;
            });
        });
       html += '</select>';
       if (willBeAdded)
         {
         parentDiv.find('a:last').after(html);
         parentDiv.find('select').val(selectedValue);
         parentDiv.find('select').unbind('change').change(function(){
            parentDiv.find('.selectedItem').html('Job Output: '+$(this).val());
            parentDiv.find('.selectedItem').attr('element', '');
            parentDiv.find('.selectedFolder').attr('element', '');
         });
         }
    });
  });
  }

function onLeaveStepCallback(obj)
  {
  if(obj.attr('rel') == 2)
    {
    return validateSteps(2);
    }
  return true;
  }

function onFinishCallback()
  {
   if(validateAllSteps())
     {
      var date = '';
      var every = '0';
      if(!$('#checkboxSchedule').is(':checked'))
        {
        date = $('#datepicker').val();
        every = $('#intervalSelect').val();
        }

      var objResults = {};
      $.each(results, function(index, value) {
        objResults[index] = $.extend({}, results[index]);
      });

      req = {'results' :  objResults, 'name': $('#jobName').val(), 'date' : date, 'interval': every};
      $(this).after('<img  src="'+json.global.webroot+'/core/public/images/icons/loading.gif" alt="Saving..." />')
      $(this).remove();
      $.ajax({
           type: "POST",
           url: json.global.webroot+"/remoteprocessing/job/init?itemId="+$('#selectedExecutableId').val(),
           data: req ,
           success: function(x){
             window.location.replace($('.webroot').val()+'/remoteprocessing/job/manage')
           }
         });
     }
   else
     {
     createNotive("There are some errors.", 4000);
     }
  }


function validateSteps(stepnumber)
  {
  var isStepValid = true;

  if(stepnumber == 2)
    {
    var i = 0;
    results = new Array();
    $( ".executableForm").each(function(){
      var name = $(this).find('b:first').html();
      var indice = $(this).attr('orderIndice');
      results[indice] = new Array();
      results[indice]['name'] = name;
      results[indice]['options'] = new Array();
      results[indice]['executable'] = $(this).attr('loaded');
    });

    if($('.optionWrapper').length == 0)
      {
      createNotive('The workflow is empty.', 4000);
      isStepValid = false;
      }

    $('.optionWrapper').each(function(){
    var indice = $(this).parents('.executableForm').attr('orderIndice');
    var required = false;
    if($(this).attr('isrequired') == 'true')
      {
      required = true;
      }

    if($(this).find('.selectedFolder').length > 0)
      {
      if($(this).find('.nameOutputOption').val() == '' || $(this).find('.selectedFolder').attr('element') == '')
        {
        if(required) createNotive('Please set '+$(this).attr('name'), 4000);
        if(required) isStepValid = false;
        }
      else if($(this).find('.nameOutputOption').val().indexOf(".") == -1)
        {
        if(required) createNotive('Please set an extension in the option '+$(this).attr('name'), 4000);
        if(required) isStepValid = false;
        }
      else
        {
        results[indice]['options'].push($(this).find('.selectedFolder').attr('element')+';;'+$(this).find('.nameOutputOption').val());
        }
      }
    else if($(this).find('.selectInputFileLink').length > 0)
      {
      if(($(this).find('select').length == 0 || $(this).find('select').val() == -1) && $(this).find('.selectedItem').attr('element') == '' && $(this).find('.selectedFolderContent').attr('element') == '')
        {
        if(required) createNotive('Please set '+$(this).attr('name'), 4000);
        if(required) isStepValid = false;
        }
      else if($(this).find('select').length != 0 && $(this).find('select').val() != -1 )
        {
          results[indice]['options'].push('jobOuput;;'+$(this).find('select').val());
        }
      else
        {
        var folderElement = $(this).find('.selectedFolderContent').attr('element');
        if(folderElement != '')
          {
          results[indice]['options'].push('folder'+folderElement);
          }
        else
          {
          results[indice]['options'].push($(this).find('.selectedItem').attr('element'));
          }
        }
      }
    else
      {
      if($(this).find('.valueInputOption').val() == '')
        {
        if(required) createNotive('Please set '+$(this).attr('name'), 4000);
        if(required) isStepValid = false;
        }
      else
        {
        results[indice]['options'].push($(this).find('.valueInputOption').val());
        }
      }

    i++;
    });
    }

  if(isStepValid)
    {
    $('#wizard').smartWizard('setError',{stepnum:stepnumber,iserror:false});
    }
  else
    {
    $('#wizard').smartWizard('setError',{stepnum:stepnumber,iserror:true});
    }
  return isStepValid;
  }

function validateAllSteps()
  {
  return validateSteps(1) && validateSteps(2);
  }

function onShowStepCallback(obj)
  {

  }

/* not currently used*/
function loadRecentUpload()
  {
  $.getJSON(json.global.webroot+'/remoteprocessing/job/getentry?type=getRecentExecutable', function(data) {
      if(data.length == 0)
        {
        $('#recentuploadContentBlock').html('');
        return;
        }
      var html = "<br/><br/><b>Or select a Recently Uploaded File:</b><ul>";

      $('#recentuploadContentBlock').html('<ul>');
      $.each(data, function(key, val) {
        html += '<li class="recentUploadItemLi" element="'+val.item_id+'"><a>' + val.name + '</a></li>';
      });
      html += "</ul>";
      $('#recentuploadContentBlock').html(html);

      $('.recentUploadItemLi').click(function(){
        $('#selectedExecutable').html($(this).find('a').html());
        $('#selectedExecutableId').val($(this).attr('element'));
        createNotive("Please set the executable meta informaiton.", 4000);
        $('#metaPageBlock').load(json.global.webroot+'/remoteprocessing/executable/define?itemId='+$(this).attr('element'));
        $('#metaWrapper').show();
        isExecutableMeta = false;
        executableValid = true;
      });

    });
  }

function initScheduler()
  {
  $( "#datepicker" ).datetimepicker();
  $('#ui-datepicker-div').hide();
  $('#checkboxSchedule').change(function(){
    if(!$(this).is(':checked'))
      {
      $('#schedulerWrapper').show();
      }
    else
      {
       $('#schedulerWrapper').hide();
      }
  })
  }

function initExecutableForm()
{
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
  $('[qtip]').qtip({
     content: {
        attr: 'qtip'
     }
  })
}

function itemSelectionCallback(name, id)
  {
  if(currentBrowser == 'executable')
    {
    $('#selectedExecutable').html(name);
    $('#selectedExecutableId').val(id);
    $.post(json.global.webroot+"/remoteprocessing/job/validentry", {entry: id, type: "isexecutable"},
      function(data){
        if(data.search('true')!=-1)
        {
        executableValid = true;
        $.post(json.global.webroot+"/remoteprocessing/job/validentry", {entry: id, type: "ismeta"},
          function(data){
            if(data.search('true')!=-1)
              {
              isExecutableMeta = true;
              }
            else
              {
              isExecutableMeta = false;
              createNotive("Please set the executable meta informaiton.", 4000);
              loadDialog("meta_"+id, '/remoteprocessing/executable/define?itemId='+id);
              showBigDialog("MetaInformation", false);
              }
          });
        }
        else
        {
        executableValid = false;
        createNotive("The selected item is not a valid executable", 4000);
        }
      });

    return;
    }
  var optionWrapper = $('#option_'+currentBrowser);
  optionWrapper.find('.selectedItem').html('Item '+name);
  optionWrapper.find('.selectedItem').attr('element',id);
  optionWrapper.find('.selectedFolder').attr('element', '');
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
  }
