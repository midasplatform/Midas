var json
var itemselected = false;

if (typeof console != "object") {
	var console = {
		'log':function(){}
	};
}

  function sliceFileName(name,nchar)
  {
    if(name.length>nchar)
      {
      toremove=(name.length)-nchar;
      if(toremove<13)
        {
        return name;
        }
      name=name.substring(0,10)+'...'+name.substring(13+toremove);
      return name;
      }
  return name;
  }

  function trimName(name,padding)
  {
    if(name.length*7+padding>350)
      {
      toremove=(name.length*7+padding-350)/8;
      if(toremove<13)
        {
        return 'error';
        }
      name=name.substring(0,10)+'...'+name.substring(name.length+13-toremove);
      return name;
      }
  return name;
  }

$(function() {
  json = jQuery.parseJSON($('div.jsonContent').html());
  if(!json.global.logged)
    {
    loadAjaxDynamicBar('login','/user/login');
    }

  $('[qtip]').qtip({
   content: {
      attr: 'qtip'
   }
})

  //menu
  $('div.TopbarRighta li.first').hover(
			function() {$('ul', this).css('display', 'block');},
			function() {$('ul', this).css('display', 'none');});

  // If we are not logged in
  if(json.global.needToLog)
    {
    showOrHideDynamicBar('login');
    loadAjaxDynamicBar('login','/user/login');
    return;
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
    if($(this).attr('userid')!=undefined)
      {
      loadAjaxDynamicBar('settings'+$(this).attr('userid'),'/user/settings?userId='+$(this).attr('userid'));
      }
    else
      {
      loadAjaxDynamicBar('settings','/user/settings');
      }
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

  var uploadPageLoaded = false;
  $('div.HeaderAction li.uploadFile').click(function()
  {
    if(json.global.logged)
    {
    if(!uploadPageLoaded)
      {
      $('img#uploadAFile').hide();
      $('img#uploadAFileLoadiing').show();
      uploadPageLoaded = true;
      }
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
    $('div.HeaderAction li.uploadFile').qtip(
      {
         content: {
            // Set the text to an image HTML string with the correct src URL to the loading image you want to use
            text: '<img  src="'+json.global.webroot+'/core/public/images/icons/loading.gif" alt="Loading..." />',
            ajax: {
               url: $('div.HeaderAction li.uploadFile').attr('rel') // Use the rel attribute of each element for the url to load
            },
            title: {
               text: 'Upload', // Give the tooltip a title using each elements text
               button: true
            }
         },
         position: {
            at: 'bottom center', // Position the tooltip above the link
            my: 'top right',
            viewport: $(window), // Keep the tooltip on-screen at all times
            effect: true // Disable positioning animation
         },
         show: {
            event: 'click',
            solo: true // Only show one tooltip at a time
         },
         hide: {
          event: false
         },
         style: {
            classes: 'uploadqtip ui-tooltip-light ui-tooltip-shadow ui-tooltip-rounded'
         }
      })
    $('.uploadqtip').css('z-index:500');
    }

  $('div.viewAction li a').hover(function(){
    $(this).parents('li').css('background-color','#E5E5E5');
  }, function(){
    $(this).parents('li').css('background-color','white');
  });

  // show Notification Message
  if(json.triggerNotification != undefined)
    {
    jQuery.each(json.triggerNotification, function(i, val) {
       createNotive(val, 4000)
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
    createGrowl(false, text, delay);
}

 window.createGrowl = function(persistent, text, delay) {
      // Use the last visible jGrowl qtip as our positioning target
      var target = $('.qtip.jgrowl:visible:last');
      text = '<img style="float:right;"  src="'+json.global.webroot+'/core/public/images/icons/message.png" alt="" />'+text
      // Create your jGrowl qTip...
      $(document.body).qtip({
         // Any content config you want here really.... go wild!
         content: {
            text: text
         },
         position: {
            my: 'top right', // Not really important...
            at: (target.length ? 'bottom' : 'top') + ' right', // If target is window use 'top right' instead of 'bottom right'
            target: target.length ? target : $(document.body), // Use our target declared above
            adjust: { y: 5 } // Add some vertical spacing
         },
         show: {
            event: false, // Don't show it on a regular event
            ready: true, // Show it when ready (rendered)
            effect: function() { $(this).stop(0,1).fadeIn(400); }, // Matches the hide effect
            delay: 0, // Needed to prevent positioning issues

            // Custom option for use with the .get()/.set() API, awesome!
            persistent: persistent
         },
         hide: {
            event: false, // Don't hide it on a regular event
            effect: function(api) {
               // Do a regular fadeOut, but add some spice!
               $(this).stop(0,1).fadeOut(400).queue(function() {
                  // Destroy this tooltip after fading out
                  api.destroy();

                  // Update positions
                  updateGrowls();
               })
            }
         },
         style: {
            classes: 'jgrowl ui-tooltip-dark ui-tooltip-rounded', // Some nice visual classes
            tip: false // No tips for this one (optional ofcourse)
         },
         events: {
            render: function(event, api) {
               // Trigger the timer (below) on render
               timerGrowl.call(api.elements.tooltip, event, delay);
            }
         }
      })
      .removeData('qtip');
   };

   // Make it a window property see we can call it outside via updateGrowls() at any point
   window.updateGrowls = function() {
      // Loop over each jGrowl qTip
      var each = $('.qtip.jgrowl:not(:animated)');
      each.each(function(i) {
         var api = $(this).data('qtip');

         // Set the target option directly to prevent reposition() from being called twice.
         api.options.position.target = !i ? $(document.body) : each.eq(i - 1);
         api.set('position.at', (!i ? 'top' : 'bottom') + ' right');
      });
   };

 // Setup our timer function
 function timerGrowl(event, delay) {
    var api = $(this).data('qtip'),
       lifespan = delay; // 5 second lifespan

    // If persistent is set to true, don't do anything.
    if(api.get('show.persistent') === true) { return; }

    // Otherwise, start/clear the timer depending on event type
    clearTimeout(api.timer);
    if(event.type !== 'mouseover') {
       api.timerGrowl = setTimeout(api.hide, lifespan);
    }
 }

 // Utilise delegate so we don't have to rebind for every qTip!
 $(document).delegate('.qtip.jgrowl', 'mouseover mouseout', timerGrowl);

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
  var x= $('div.HeaderSearch').position().left+150;
  var y= 100;
  if(button)
  {
    $( "div.MainDialog" ).dialog({
			resizable: false,
      width:450,
			modal: false,
      draggable:false,
      title: title,
      position: [x,y],
      zIndex: 15100,
      buttons: {"Ok": function() {$(this).dialog("close");}}
		});


  }
  else
  {
    $( "div.MainDialog" ).dialog({
			resizable: false,
      width:450,
			modal: false,
      draggable:false,
      title: title		,
      zIndex: 15100,
      position: [x,y]
		});
  }

}

function showBigDialog(title,button)
{
  var x= $('div.HeaderSearch').position().left+50;
  var y= 100;
  if(button)
  {
    $( "div.MainDialog" ).dialog({
			resizable: false,
      width:700,
			modal: false,
      draggable:false,
      title: title,
      position: [x,y],
      buttons: {"Ok": function() {$(this).dialog("close");}}
		});

  }
  else
  {
    $( "div.MainDialog" ).dialog({
			resizable: false,
      width:700,
			modal: false,
      draggable:false,
      title: title		,
      position: [x,y]
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

function showBigDialogWithContent(title,content,button)
{
  $('.DialogContentPage').val('');
  $('div.MainDialogContent').html(content);
  $("div.MainDialogLoading").hide();
  showBigDialog(title,button);
}

