var midas = midas || {};
$(document).ready(
    function() {
        $("#browseTable").treeTable();
        $("img.tableLoading").hide();
        $("table#browseTable").show();
        
        $('div.feedThumbnail img').fadeTo("slow",0.4);
        $('div.feedThumbnail img').mouseover(
            function() {
                $(this).fadeTo("fast",1);
            });
 
        $('div.feedThumbnail img').mouseout(
            function() {
                $(this).fadeTo("fast",0.4);
            });
    
        $('a.createCommunity').click(
            function() {
                if(json.global.logged) {
                    loadDialog("createCommunity","/community/create");
                    showDialog(json.community.createCommunity,false);
                }
                else {
                    createNotive(json.community.contentCreateLogin,4000);
                    $("div.TopDynamicBar").show('blind');
                    loadAjaxDynamicBar('login','/user/login');
                }
            });
   
      
        $('.itemBlock').click(
            function() {
                $(location).attr('href',($('> .itemTitle',this).attr('href')));
            });
    
    });
  
//dependance: common/browser.js
// Treetable depends on some global functions. This is terrible. Our javascript
// is absolutely shameful. That's why I didn't namespace these functions.
midas.ajaxSelectRequest='';
var callbackSelect = function(node) {
    $('div.defaultSide').hide();
    $('div.viewAction').show();
    $('div.viewInfo').show();
    $('div.ajaxInfoElement').show();
    midas.genericCallbackSelect(node);
};

var callbackDblClick = function(node) {
    midas.genericCallbackDblClick(node);
};
    
var callbackCheckboxes = function(node) {
    midas.genericCallbackCheckboxes(node);
};
