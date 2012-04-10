var jsonShare = jQuery.parseJSON($('div.jsonShareContent').html());


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
  
  var invitationSearchcache = {},
  lastShareXhr;
  var itemShareSelected;
  $("#live_invitation_search").catcomplete({
  minLength: 2,
  delay: 10,
  source: function( request, response ) {
    var term = request.term;
    if ( term in invitationSearchcache ) {
      response( invitationSearchcache[ term ] );
      return;
    }

    $("#searchInvitationLoading").show();
    
    lastShareXhr = $.getJSON( $('.webroot').val()+"/search/live?userSearch=true", request, function( data, status, xhr ) {
      $("#searchInvitationLoading").hide();
      invitationSearchcache[ term ] = data;
      if ( xhr === lastShareXhr ) {
        itemShareSelected = false;
        response( data );
      }
      });
   }, // end source
   select: function(event, ui) { 
     $.post(json.global.webroot+'/community/invitation', {sendInvitation:true,userId:ui.item.userid,communityId:json.community.community_id},
       function(data) {
         jsonResponse = jQuery.parseJSON(data);
          if(jsonResponse[0])
            {
              midas.createNotice(jsonResponse[1],1500);
              $( "div.MainDialog" ).dialog('close');
            }
          else
            {
              midas.createNotice(jsonResponse[1],4000);
            }
       });
     itemShareSelected = true;

     $( "div.MainDialog" ).dialog('close');
     }
   });

  $('#live_invitation_search').focus(function() {
    if($('#live_invitation_search_value').val() == 'init')
      {
      $('#live_invitation_search_value').val($('#live_invitation_search').val());
      $('#live_invitation_search').val('');
      }
    });
  
  $('#live_invitation_search').focusout(function() {
    if($('#live_invitation_search').val() == '')
      {
      $('#live_invitation_search').val($('#live_invitation_search_value').val());
      $('#live_invitation_search_value').val('init');
      }
    });
  