		
    
    var swfu;
    
    $('img#uploadAFile').show();
    $('img#uploadAFileLoadiing').hide();
      function uploadPreStart(file)
      {
        swfu.setPostParams({"sid" : $('.sessionId').val(),"parent": $('#destinationId').val(),'license': $('select[name=licenseSelect]').val()});
        //uploadStart(file);
      }
      
      
		$( "#uploadTabs" ).tabs({
			ajaxOptions: {
        beforeSend: function()
        {
          $('div.MainDialogLoading').show();
        },
        success: function()
        {
          $('div.MainDialogLoading').hide();
          $( "#uploadTabs" ).show();
        },
				error: function( xhr, status, index, anchor ) {
					$( anchor.hash ).html(
						"Couldn't load this tab. We'll try to fix this as soon as possible. " );
				}
			}
		});
    $( "#uploadTabs" ).show();
      $('#linkForm').ajaxForm(function() { 
         // $('input[name=url]').val('http://');
          $('.uploadedLinks').val(parseInt($('.uploadedLinks').val())+1);
          updateUploadedCount();
      });
     
     
     
    $('#swfuploadContent').hide();
    $('#jqueryFileUploadContent').show();
    initJqueryFileupload();
    
    function sendParentToJavaSession()
    {
      $.post(json.global.webroot+'/upload/javaupload', {parent: $('#destinationId').val(),license: $('select[name=licenseSelect]:last').val()},
         function(data) {
             console.log(data);
         });
    }
     
    function successJavaUpload()
    {
      $('.uploadedJava').val(parseInt($('.uploadedJava').val())+1);
      updateUploadedCount();
    }
      
    function updateUploadedCount()
    {
      var count=parseInt($('.uploadedSimple').val())+parseInt($('.uploadedLinks').val())+parseInt($('.uploadedJava').val());
      $('.globalUploadedCount').html(count);
      if(count>0)
        {
        $('.reviewUploaded').show();
        }
      else
        {
        
        $('.reviewUploaded').hide();
        }
    }
    
    
    function initJqueryFileupload()
    {
      updateUploadedCount();
       //see http://aquantum-demo.appspot.com/file-upload
        $('#file_upload').fileUploadUIX({
          beforeSend: function (event, files, index, xhr, handler, callBack) {
            handler.uploadRow.find('.file_upload_start').click(function () {
                handler.formData = {
                    parent: $('#destinationId').val(),
                    license: $('select[name=licenseSelect]').val()
                };
                callBack();
            });
          },
         onComplete:  function (event, files, index, xhr, handler) {
              $('.uploadedSimple').val(parseInt($('.uploadedSimple').val())+1);
              updateUploadedCount();
          },
        sequentialUploads: true
        });
        
        $('#startUploadLink').click(function () {
            if($('#destinationId').val()==undefined||$('#destinationId').val().length==0)
              {
                createNotive("Please select where you want to upload your files.", 4000);
                return false;
              }
            $('.file_upload_start button').click();
            return false;
        });
    
    }
    
    
    
    $('.browseMIDASLink').click(function()
      {
        loadDialog("select","/browse/movecopy/?selectElement=true");
        showDialog('Browse');
      });
      