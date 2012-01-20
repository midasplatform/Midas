  var disableElementSize=true;

$(document).ready(function() {
    
    initCommunityPrivacy();

    $( "#tabsGeneric" ).tabs({
      select: function(event, ui) {
        $('div.genericAction').show();
        $('div.genericCommunities').show();
        $('div.genericStats').show();
        $('div.viewInfo').hide();
        $('div.memberSelection').hide();
        $('div.groupUsersSelection').hide();
        $('div.viewAction').hide();
        $('td.tdUser input').removeAttr('checked');
        }
      });
    $("#tabsGeneric").show();
    $('img.tabsLoading').hide();
    
    
     $('a#communityDeleteLink').click(function()
    {
      var html='';
      html+=json.community.message['deleteMessage'];
      html+='<br/>';
      html+='<br/>';
      html+='<br/>';
      html+='<input style="margin-left:140px;" class="globalButton deleteCommunityYes" element="'+$(this).attr('element')+'" type="button" value="'+json.global.Yes+'"/>';
      html+='<input style="margin-left:50px;" class="globalButton deleteCommunityNo" type="button" value="'+json.global.No+'"/>';
      
      showDialogWithContent(json.community.message['delete'],html,false);
      
      $('input.deleteCommunityYes').unbind('click').click(function()
        { 
          location.replace(json.global.webroot+'/community/delete?communityId='+json.community.community_id);
        });
      $('input.deleteCommunityNo').unbind('click').click(function()
        {
           $( "div.MainDialog" ).dialog('close');
        });         
      
    });
    
    $('#editCommunityForm').ajaxForm( {beforeSubmit: validateInfoChange, success:       successInfoChange} );
    
    
    //init group tab
    init();
    $('.dataTable').each(function(){
      var obj= $(this).dataTable(
      {
      "sScrollY": "100px",
      "bScrollCollapse": true,
      "bPaginate": true,
      "bLengthChange": false,
      "bFilter": false,
      "bSort": true,
      "bInfo": false,
      "bAutoWidth": true ,
      "oLanguage": {
        "sEmptyTable": "No users in this group"
        }
      });
      var groupid=$(this).attr('groupid');
      if(groupid!=undefined)
        {
          datatable[groupid]=obj;
        }
    });
    
    //init tree
    $('img.tabsLoading').hide()
    
  
     $('table')
        .filter(function() {
            return this.id.match(/browseTable*/);
        })
        .treeTable();
    ;
    $("img.tableLoading").hide();
    $("table#browseTable").show();
    
    $('div.userPersonalData').hide();
    
    initDragAndDrop();    
    $('td.tdUser input').removeAttr('checked');
  });
  
  
      //dependance: common/browser.js
    var ajaxSelectRequest='';
    function callbackSelect(node)
    {
      $('div.genericAction').show();
      $('div.genericCommunities').hide();
      $('div.genericStats').hide();
      $('div.viewInfo').show();
      $('div.viewAction').show();
      midas.genericCallbackSelect(node);
    }

    function callbackDblClick(node)
    {
    }
    
    function callbackCheckboxes(node)
    {
      midas.genericCallbackCheckboxes(node);
    }
    
    function callbackCreateElement(node)
    {
      initDragAndDrop();
    }

function initDragAndDrop()
{
      $("#browseTable .file, #browseTable .folder:not(.notdraggable)").draggable({
      helper: "clone",
      cursor: "move",
      opacity: .75,
      refreshPositions: true, // Performance?
      revert: "invalid",
      revertDuration: 300,
      scroll: true,
      start: function() {            
          $('div.userPersonalData').show();            
        }
      });
      
      // Configure droppable rows
      $("#browseTable .folder").each(function() {
        $(this).parents("tr").droppable({
          accept: ".file, .folder",
          drop: function(e, ui) { 
            // Call jQuery treeTable plugin to move the branch
           var elements='';
           if($(ui.draggable).parents("tr").attr('type')=='folder')
             {
               elements=$(ui.draggable).parents("tr").attr('element')+';';
             }
           else
             {
               elements=';'+$(ui.draggable).parents("tr").attr('element');
             }
           var from_ojbect;
           var classNames=$(ui.draggable).parents("tr").attr('class').split(' ');
            for(key in classNames) {
              if(classNames[key].match('child-of-')) {
                from_obj = "#" + classNames[key].substring(9); 
              }
            }
           var destination_obj=this;
           
           // do nothing if drop item(s) to its current folder
           if ($(this).attr('id') != $(from_obj).attr('id')){
             $.post(json.global.webroot+'/browse/movecopy', {moveElement: true, elements: elements , destination:$(this).attr('element'),from:$(from_obj).attr('element'),ajax:true},
             function(data) {

               jsonResponse = jQuery.parseJSON(data);
                if(jsonResponse==null)
                  {
                    createNotive('Error',4000);
                    return;
                  }
                if(jsonResponse[0])
                  {
                    createNotive(jsonResponse[1],1500);
                    $($(ui.draggable).parents("tr")).appendBranchTo(destination_obj);       
                  }
                else
                  {
                    createNotive(jsonResponse[1],4000);
                  }
             });
           }       
          },
          hoverClass: "accept",
          over: function(e, ui) {
            // Make the droppable branch expand when a draggable node is moved over it.
            if(this.id != $(ui.draggable.parents("tr")[0]).id && !$(this).is(".expanded")) {
              $(this).expand();
            }
          }
        });
      });
}

function init()
{
  groupUsersSelected=new Array();
  memberSelected=new Array();
  var mainDialogContentDiv = $('div.MainDialogContent');
  var createGroupFromDiv = $('div#createGroupFrom');
  $('a.groupLink').each(function(){
    var id=$(this).attr('groupid');
    $(this).parent('li').find('span').html(' ('+($('div#groupList_'+id+' td.tdUser').size())+')');
  });

      $('a#createGroupLink').click(function()
      {
        $('div.groupUsersSelection').hide();
        $('td.tdUser input').removeAttr('checked');
        mainDialogContentDiv.html('');
        createGroupFromDiv.find('input[name=groupId]').val('0');
        createGroupFromDiv.find('input[name=name]').val('');
        showDialogWithContent(json.community.message.createGroup,createGroupFromDiv.html(),false);
        mainDialogContentDiv.find('form.editGroupForm').ajaxForm( {beforeSubmit: validateGroupChange, success:       successGroupChange} );
      });
      
    $('a.editGroupLink').click(function()
      {
        mainDialogContentDiv.html('');
        var id=$(this).attr('groupid');
        createGroupFromDiv.find('input[name=groupId]').val(id);
        var groupName=$(this).parent('li').find('a:first').html();
        showDialogWithContent(json.community.message.editGroup,createGroupFromDiv.html(),false);
        $('form.editGroupForm input#name').val(groupName);
        mainDialogContentDiv.find('form.editGroupForm').ajaxForm( {beforeSubmit: validateGroupChange, success:       successGroupChange} );
      });
      
    $('a.groupLink').click(function()
      {
        $('td#userGroupSelected').html('');
        $('td#userMemberSelected').html('');
        $('div.communityMemberList').show();
        $('div.groupList').hide();
        var id=$(this).attr('groupid');
        $('div#groupList_'+ id).show();
        $('td.tdUser input').removeAttr('checked');
        groupSelected=id;
      });
      
    $('td.tdUser input').click(function()
      {
        initCheckboxSelection();
      });
      
      
    $('a.deleteGroupLink').click(function()
    {
      var html='';
      html+=json.community.message['deleteGroupMessage'];
      html+='<br/>';
      html+='<br/>';
      html+='<br/>';
      html+='<input style="margin-left:140px;" class="globalButton deleteGroupYes" element="'+$(this).attr('groupid')+'" type="button" value="'+json.global.Yes+'"/>';
      html+='<input style="margin-left:50px;" class="globalButton deleteGroupNo" type="button" value="'+json.global.No+'"/>';
      
      showDialogWithContent(json.community.message['delete'],html,false);
      
      $('input.deleteGroupYes').unbind('click').click(function()
        { 
          var groupid=$(this).attr('element');
          $.post(json.global.webroot+'/community/manage', {communityId: json.community.community_id, deleteGroup: 'true', groupId:groupid},
           function(data) {
               jsonResponse = jQuery.parseJSON(data);
                if(jsonResponse==null)
                  {
                    createNotive('Error',4000);
                    return;
                  }
                if(jsonResponse[0])
                  {
                    $( "div.MainDialog" ).dialog("close");
                    $('a.groupLink[groupid='+groupid+']').parent('li').remove();
                    createNotive(jsonResponse[1],4000);
                    init();
                  }
                else
                  {
                    createNotive(jsonResponse[1],4000);
                  }
           });
        });
      $('input.deleteGroupNo').unbind('click').click(function()
        {
           $( "div.MainDialog" ).dialog('close');
        });         

    });
    

}
var datatable=new Array();
var groupSelected;
var groupUsersSelected=new Array();
var memberSelected=new Array();
function initCheckboxSelection()
  {
    $('td#userGroupSelected').html('');
    $('.memberSelection').hide();
    $('.groupUsersSelection').hide();
    groupUsersSelected=new Array();
    memberSelected=new Array();
    $('div.groupMemberList input:checked').each(function()
    {
      groupUsersSelected.push($(this).attr('userid'));
      $('.groupUsersSelection').show();
    });
    $('div.communityMemberList input:checked').each(function()
    {
      memberSelected.push($(this).attr('userid'));
      $('.memberSelection').show();
    });
    

    $('a.removeUserLink').click(function()
    {
    var users='';
    $.each( groupUsersSelected, function(i, v){
       users+=v+'-';
     });
     $.post(json.global.webroot+'/community/manage', {communityId: json.community.community_id, removeUser: 'true', groupId:groupSelected,users:users},
     function(data) {
         jsonResponse = jQuery.parseJSON(data);
          if(jsonResponse==null)
            {
              createNotive('Error',4000);
              return;
            }
          if(jsonResponse[0])
            {
              createNotive(jsonResponse[1],4000);
              window.location.replace(json.global.webroot+'/community/manage?communityId='+json.community['community_id']+'#tabs-2');
              window.location.reload();
            }
          else
            {
              createNotive(jsonResponse[1],4000);
            }
     });

    });
    
    $('a.removeFromCommunity').click(function()
    {
    var users='';
    $.each( memberSelected, function(i, v){
      if($('div#memberList input[admin=false][userid='+v+']').length>0)
      {
       users+=v+'-'; 
      }

     });
     $.post(json.global.webroot+'/community/manage', {communityId: json.community.community_id, removeUser: 'true', groupId:json.community.memberGroup.group_id,users:users},
     function(data) {
         jsonResponse = jQuery.parseJSON(data);
          if(jsonResponse==null)
            {
              createNotive('Error',4000);
              return;
            }
          if(jsonResponse[0])
            {
              createNotive(jsonResponse[1],4000);
              window.location.replace(json.global.webroot+'/community/manage?communityId='+json.community['community_id']+'#tabs-2');
              window.location.reload();
            }
          else
            {
              createNotive(jsonResponse[1],4000);
            }
     });

    });
    
    $('a.addUserLink').click(function()
    {
    var users='';
    $.each( memberSelected, function(i, v){
       users+=v+'-';
     });
     $.post(json.global.webroot+'/community/manage', {communityId: json.community.community_id, addUser: 'true', groupId:$(this).attr('element'),users:users},
     function(data) {
         jsonResponse = jQuery.parseJSON(data);
          if(jsonResponse==null)
            {
              createNotive('Error',4000);
              return;
            }
          if(jsonResponse[0])
            {
              createNotive(jsonResponse[1],4000);
              window.location.replace(json.global.webroot+'/community/manage?communityId='+json.community['community_id']+'#tabs-2');
              window.location.reload();
            }
          else
            {
              createNotive(jsonResponse[1],4000);
            }
     });
     $(this).remove();
    });
    
    $('a.addModeratorLink').click(function()
    {
    var users='';
    $.each( memberSelected, function(i, v){
       users+=v+'-';
     });
     $.post(json.global.webroot+'/community/manage', {communityId: json.community.community_id, addUser: 'true', groupId:json.community.moderatorGroup.group_id,users:users},
     function(data) {
         jsonResponse = jQuery.parseJSON(data);
          if(jsonResponse==null)
            {
              createNotive('Error',4000);
              return;
            }
          if(jsonResponse[0])
            {
              createNotive(jsonResponse[1],4000);
              window.location.replace(json.global.webroot+'/community/manage?communityId='+json.community['community_id']+'#tabs-2');
              window.location.reload();
            }
          else
            {
              createNotive(jsonResponse[1],4000);
            }
     });
     $(this).remove();
    });
  }
function validateGroupChange(formData, jqForm, options) { 
 
    var form = jqForm[0]; 
    if (form.name.value.length<1)
      {
        createNotive(json.community.message.infoErrorName,4000);
        return false;
      }
}

function successGroupChange(responseText, statusText, xhr, form) 
{
  $( "div.MainDialog" ).dialog("close");
  jsonResponse = jQuery.parseJSON(responseText);
  if(jsonResponse==null)
    {
      createNotive('Error',4000);
      return;
    }
  if(jsonResponse[0])
    {
      createNotive(jsonResponse[1],4000);
      var obj=$('a.groupLink[groupId='+jsonResponse[2].group_id+']');
      if(obj.length>0)
        {
        obj.html(jsonResponse[2].name);
        }
       else
         {
         
         }
       init();
       window.location.replace(json.global.webroot+'/community/manage?communityId='+json.community['community_id']+'#tabs-2');
       window.location.reload();
    }
  else
    {
      createNotive(jsonResponse[1],4000);
    }
}

function validateInfoChange(formData, jqForm, options) { 
 
    var form = jqForm[0]; 
    if (form.name.value.length<1)
      {
        createNotive(json.community.message.infoErrorName,4000);
        return false;
      }
}

function successInfoChange(responseText, statusText, xhr, form) 
{
  jsonResponse = jQuery.parseJSON(responseText);
  if(jsonResponse==null)
    {
      createNotive('Error',4000);
      return;
    }
  if(jsonResponse[0])
    {
      $('div.genericName').html(jsonResponse[2]);
      createNotive(jsonResponse[1],4000);
    }
  else
    {
      createNotive(jsonResponse[1],4000);
    }
}


function initCommunityPrivacy()
{
var inputCanJoin = $('input[name=canJoin]');
var inputPrivacy = $('input[name=privacy]');
var canJoinDiv = $('div#canJoinDiv');

if(inputPrivacy.filter(':checked').val()== 1) //private
  {
    inputCanJoin.attr('disabled','disabled');
    inputCanJoin.removeAttr('checked');
    inputCanJoin.filter('[value=0]').attr('checked', true); //invitation
    canJoinDiv.hide();
  }
else
  {
    inputCanJoin.removeAttr('disabled');
    canJoinDiv.show();
  }
    inputPrivacy.change(function(){
    initCommunityPrivacy();
  });
}
    