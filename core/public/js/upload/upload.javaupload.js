    $('.browseMIDASLink').click(function()
      {
        loadDialog("select","/browse/movecopy/?selectElement=true");
        showDialog('Browse');
      });
      
      $('.destinationId').val($('#destinationId').val());
      $('.destinationUpload').html($('#destinationUpload').html());
      
      