$('a.createCommunity').click(function()
{
  if(json.global.logged)
    {
    loadDialog("createCommunity","/community/create");
    showDialog(json.community.createCommunity,false);
    }
  else
    {
    showDialogWithContent(json.community.titleCreateLogin, json.community.contentCreateLogin,true);
    $("div.TopDynamicBar").show('blind');
    loadAjaxDynamicBar('login','/user/login');
    }

});
