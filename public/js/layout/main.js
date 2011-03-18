var json
var itemselected = false;

if (typeof console != "object") {
	var console = {
		'log':function(){}
	};
}

$(function() { 
  json = jQuery.parseJSON($('div.jsonContent').html());
  if(!json.global.logged)
    {
    loadAjaxDynamicBar('login','/user/login');
    }
  
  $("a.loginLink").click(function()
    {
    showOrHideDynamicBar('login');
    loadAjaxDynamicBar('login','/user/login');
    });
    
  $("li.myAccountLink").click(function()
    {
    if($("div.TopDynamicBar").is(':hidden'))
      {
        $("div.TopDynamicBar").show('blind', function() {
 
        });   
      }
    loadAjaxDynamicBar('settings','/user/settings');
    });
    
   $("li.settingsLink").click(function()
    {
    if($("div.TopDynamicBar").is(':hidden'))
      {
        $("div.TopDynamicBar").show('blind', function() {
        });   
      }
    loadAjaxDynamicBar('settings','/user/settings');
    });
    
   $("li.modulesLink").click(function()
    {
    if($("div.TopDynamicBar").is(':hidden'))
      {
        $("div.TopDynamicBar").show('blind', function() {
        });   
      }
    loadAjaxDynamicBar('settings','/user/settings');
    });
  
  $("a.registerLink").click(function()
    {
    showOrHideDynamicBar('register');
    loadAjaxDynamicBar('register','/user/register');
    });
  
  // Live search
  $.widget( "custom.catcomplete", $.ui.autocomplete, {
    _renderMenu: function( ul, items ) {
      var self = this,
        currentCategory = "";
      $.each( items, function( index, item ) {
        if ( item.category != currentCategory ) {
          ul.append( '<li class="search-category">' + item.category + "</li>" );
          currentCategory = item.category;
        }
        self._renderItem( ul, item );
      });
    }
  });
  
  var cache = {},
  lastXhr;
  $("#live_search").catcomplete({
  minLength: 2,
  delay: 10,
  source: function( request, response ) {
    var term = request.term;
    if ( term in cache ) {
      response( cache[ term ] );
      return;
    }

    $("#searchloading").show();
    
    lastXhr = $.getJSON( $('.webroot').val()+"/search/live", request, function( data, status, xhr ) {
      $("#searchloading").hide();
      cache[ term ] = data;
      if ( xhr === lastXhr ) {
        itemselected = false;
        response( data );
      }
      });
   }, // end source
   select: function(event, ui) { 
     itemselected = true;
     if(ui.item.itemid) // if we have an item
       {
       window.location.replace($('.webroot').val()+'/item/'+ui.item.itemid); 
       }
     else if(ui.item.communityid) // if we have a community
       {
       window.location.replace($('.webroot').val()+'/community/'+ui.item.communityid); 
       }
     else if(ui.item.folderid) // if we have a folder
       {
       window.location.replace($('.webroot').val()+'/folder/'+ui.item.folderid); 
       }
     else if(ui.item.userid) // if we have a user
       {
       window.location.replace($('.webroot').val()+'/user/'+ui.item.userid); 
       }
     else
       {
       window.location.replace($('.webroot').val()+'/search/'+ui.item.value);
       }
     }
   });

  $('#live_search').focus(function() {
    if($('#live_search_value').val() == 'init')
      {
      $('#live_search_value').val($('#live_search').val());
      $('#live_search').val('');
      }
    });
  
  $('#live_search').focusout(function() {
    if($('#live_search').val() == '')
      {
      $('#live_search').val($('#live_search_value').val());
      $('#live_search_value').val('init');
      }
    });
  
  $('#live_search').keyup(function(e) 
    {
    if(e.keyCode == 13 && !itemselected) // enter key has been pressed
      {
      window.location.replace($('.webroot').val()+'/search/'+$('#live_search').val());
      }
    });
  
  $('#menuUserInfo').click(function(){
      globalAuthAsk(json.global.webroot+'/user/userpage');
  });
  $("div.TopDynamicBar .closeButton").click(function()
  {
    if(!$("div.TopDynamicBar").is(':hidden'))
    {
      $("div.TopDynamicBar").hide('blind');
    }
  });
  
  $('div.HeaderAction li.uploadFile').click(function()
  {
    if(json.global.logged)
    {
     // loadDialog("upload","/upload/simpleupload");
     // showDialog("Upload",false);
    }
    else
    {
     
      createNotive(json.login.contentUploadLogin,4000)
      $("div.TopDynamicBar").show('blind');
      loadAjaxDynamicBar('login','/user/login');
    }
  });
 

  if(json.global.logged)
    {
    $('div.HeaderAction li.uploadFile').cluetip({
     cluetipClass: 'jtip',
     dropShadow: false,
     hoverIntent: false,
     activation: 'click', 
     arrows: true, 
     closePosition: 'title',
     closeText: '<img src="'+json.global.webroot+'/public/images/icons/close.png" alt="close" />',  
     positionBy:'uploadElement',
     topOffset:        -100,   
     leftOffset:       -550,
     width:            600
    });
    }
});
function globalAuthAsk(url)
{
    if(json.global.logged)
      {
      window.location.replace(url);
      }
    else
      {
      createNotive(json.login.titleUploadLogin,4000)
      $("div.TopDynamicBar").show('blind');
      loadAjaxDynamicBar('login','/user/login');
      }
}

function createNotive(text,delay)
{
    $(".viewNotice").html(text);  
  	$(".viewNotice").fadeIn(100).delay(delay).fadeOut(100);
}

function loadAjaxDynamicBar(name,url)
{
  if($('.DynamicContentPage').val()!=name)
  {
    $('.DynamicContentPage').val(name);
    $('div.TopDynamicContent').fadeOut('slow',function()
    {
      $('div.TopDynamicContent').html("");
      $("div.TopDynamicLoading").show();   
      
      $.ajax({
        url: $('.webroot').val()+url,
        contentType: "application/x-www-form-urlencoded;charset=ISO-8859-15",
        success: function(data) {
          $("div.TopDynamicLoading").hide();
          $('div.TopDynamicContent').hide();
          $('div.TopDynamicContent').html(data);
          $('div.TopDynamicContent').fadeIn("slow");
        }
      });
    });   
  }
}

function showOrHideDynamicBar(name)
{  
  if($("div.TopDynamicBar").is(':hidden'))
  {
    $("div.TopDynamicBar").show('blind', function() {
      $('#email').focus();
    });   
  }
  else if($('.DynamicContentPage').val()==name)
  {
    $("div.TopDynamicBar").hide('blind');
  }
}

function loadDialog(name,url)
{
  if($('.DialogContentPage').val()!=name)
  {
    $('.DialogContentPage').val(name);
    $('div.MainDialogContent').html("");
    $("div.MainDialogLoading").show();
    $.ajax({
      url: $('.webroot').val()+url,
      contentType: "application/x-www-form-urlencoded;charset=ISO-8859-15",
      success: function(data) {
        $('div.MainDialogContent').html(data);
        $("div.MainDialogLoading").hide();
        $('.dialogTitle').hide();        
      }
    });
  } 
}

function showDialog(title,button)
{  
  if(button)
  {
    $( "div.MainDialog" ).dialog({
			resizable: false,
      width:450,
			modal: false,
      draggable:true,
      title: title		,
      buttons: { "Ok": function() { $(this).dialog("close"); } } 
		});
    
  }
  else
  {
    $( "div.MainDialog" ).dialog({
			resizable: false,
      width:450,
			modal: false,
      draggable:true,
      title: title			
		});
  }
}

function showDialogWithContent(title,content,button)
{
  $('.DialogContentPage').val('');
  $('div.MainDialogContent').html(content);
  $("div.MainDialogLoading").hide();
  showDialog(title,button);
}
