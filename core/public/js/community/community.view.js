$(document).ready(function() {
  $( "#tabsGeneric" ).tabs({
    select: function(event, ui) {
      $('div.genericAction').show();
      $('div.genericCommunities').show();
      $('div.genericStats').show();
      $('div.viewInfo').hide();
      $('div.viewAction').hide();
      }
    });
  $("#tabsGeneric").show();
  $('img.tabsLoading').hide();

  $("#browseTable").treeTable({
    onFirstInit: enableRangeSelect,
    onNodeShow: enableRangeSelect,
    onNodeHide: enableRangeSelect
  });
  // Select/deslect all rows. If we are doing deselect all, we include hidden rows
  $('#browseTableHeaderCheckbox').click(function() {
    var selector = this.checked ? '.treeCheckbox:visible' : '.treeCheckbox';
    $('#browseTable').find(selector).prop("checked", this.checked);
    genericCallbackCheckboxes($('#browseTable'));
  });
  $("img.tableLoading").hide();
  $("table#browseTable").show();

 $('a#sendInvitationLink').click(function()
  {
  loadDialog("invitationCommunity", "/community/invitation?communityId=" + json.community.community_id);
  showDialog(json.community.sendInvitation,false);
  });

});

//dependency: common.browser.js
var ajaxSelectRequest='';
function callbackSelect(node)
  {
  $('div.genericAction').show();
  $('div.genericCommunities').hide();
  $('div.genericStats').hide();
  $('div.viewInfo').show();
  $('div.viewAction').show();
  genericCallbackSelect(node);
  }

function callbackDblClick(node)
  {
  genericCallbackDblClick(node);
  }

function callbackCheckboxes(node)
  {
  genericCallbackCheckboxes(node);
  }
