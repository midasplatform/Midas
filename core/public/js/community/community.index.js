  $(document).ready(function() {
    $('a.createCommunity').click(function()
    {
      if(json.global.logged)
        {
        loadDialog("createCommunity","/community/create");
        showDialog(json.community.createCommunity,false);
        }
      else
        {
        createNotive(json.community.contentCreateLogin,4000)
        $("div.TopDynamicBar").show('blind');
        loadAjaxDynamicBar('login','/user/login');
        }
    });
    
    $('a.moreDescription').click(function(){
      $(this).parents('div').find('.shortDescription').hide();
      $(this).parents('div').find('.fullDescription').show();
    })
    $('a.lessDescription').click(function(){
      $(this).parents('div').find('.shortDescription').show();
      $(this).parents('div').find('.fullDescription').hide();
    })

  });
  
