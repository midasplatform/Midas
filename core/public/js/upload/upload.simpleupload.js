

    var swfu;

    $('img#uploadAFile').show();
    $('img#uploadAFileLoadiing').hide();
      function uploadPreStart(file)
      {
        swfu.setPostParams({"sid" : $('.sessionId').val(),"parent": $('#destinationId').val(),'license': $('select[name=licenseSelect]').val()});
        //uploadStart(file);
      }
      
    // detect chrom or firefox 7+
    try {
        var is_chrome = /chrome/.test( navigator.userAgent.toLowerCase() );
        var version = jQuery.browser.version.split('.');
        version = parseInt(version[0]);
        var is_firefox = jQuery.browser.mozilla != undefined && version > 6;  
    
        if(is_chrome)
          {
          $('#changeUploadMode').show();
          }
        } catch (exception) { }

    var mode = "file";
    var htmlFileMode = $('.file_upload_label').html();
    var htmlFolderMode = $('.file_upload_label_folder').html();
    $('#uploadModeFileLink').hide();
    $('.uploadModeLink').click(function(){
      if(mode == 'file')
        {
        mode = 'folder'; 
        $('.fileUploaderInput').attr('webkitdirectory', '');
        $('.fileUploaderInput').attr('directory', '');
        $('.fileUploaderInput').attr('mozdirectory', '');
        $('.file_upload_label').html(htmlFolderMode);
        $('#uploadModeFileLink').show();
        $('#uploadModeFolderLink').hide();
        }
      else
        {
        mode = 'file'; 
        $('.fileUploaderInput').removeAttr('webkitdirectory');
        $('.fileUploaderInput').removeAttr('directory');
        $('.fileUploaderInput').removeAttr('mozdirectory');
        $('.file_upload_label').html(htmlFileMode);
        $('#uploadModeFileLink').hide();
        $('#uploadModeFolderLink').show();
        }
    });
    
      
    $( ".uploadTabs" ).tabs({
      ajaxOptions: {
        beforeSend: function()
        {
          $('div.MainDialogLoading').show();
        },
        success: function()
        {
          $('div.MainDialogLoading').hide();
          $( ".uploadTabs" ).show();
        },
        error: function( xhr, status, index, anchor ) {
          $( anchor.hash ).html(
            "Couldn't load this tab. We'll try to fix this as soon as possible. " );
        }
      }
    });

    $( ".uploadTabs" ).show();
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
    
     $('#startUploadLink').qtip({
         content: {
            attr: 'qtip'
         }
      });

    
    function initJqueryFileupload()
      {
      updateUploadedCount();
       //see http://aquantum-demo.appspot.com/file-upload
        $('.file_upload:visible').fileUploadUIX({
          beforeSend: function (event, files, index, xhr, handler, callBack) {
            handler.uploadRow.find('.file_upload_start').click(function () {
              var path = '';
              $.each(files, function (index, file) {
                    path += file.webkitRelativePath+';;';
                });
                handler.formData = {
                    parent: $('#destinationId').val(),
                    path: path,
                    license: $('select[name=licenseSelect]').val()
                };
                callBack();
            });
          $('.file_name').each(function(){
            if($(this).html() == '.' || $(this).html() == '..'  )
              {
              $(this).parent('tr').find('.file_upload_cancel button').click();
              }
          });
        
          $('#startUploadLink').css('box-shadow', '0 0 5px blue');
          $('#startUploadLink').css('-webkit-box-shadow', '0 0 5px blue');
          $('#startUploadLink').css('-moz-box-shadow', '0 0 5px blue');
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
            $('#startUploadLink').css('box-shadow', '0 0 0px blue');
            $('#startUploadLink').css('-webkit-box-shadow', '0 0 0px blue');
            $('#startUploadLink').css('-moz-box-shadow', '0 0 0px blue');
            return false;
        });

      }



    $('.browseMIDASLink').click(function()
      {
      loadDialog("select","/browse/selectfolder/?policy=write");
      showDialog('Browse');
      });
