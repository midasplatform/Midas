  $(document).ready(function() {
    $('a.showHideHelp').click(function(){
      $(this).parents('li').next('div').toggle();
    });
  });
