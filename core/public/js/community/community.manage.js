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
      "bPaginate": false,
      "bLengthChange": false,
      "bFilter": false,
      "bSort": false,
      "bInfo": false,
      "bAutoWidth": true ,
      "oLanguage": {
        "sEmptyTable": "No users in this group"
        }
      });
      if($(this).attr('groupid')!=undefined)
        {
          datatable[$(this).attr('groupid')]=obj;
        }
    });
    
    //init tree
    $('img.tabsLoading').hide()
    
    $("#browseTable").treeTable();
    $("img.tableLoading").hide();
    $("table#browseTable").show();
    
    initDragAndDrop();    

  });
  
  
      //dependance: common/browser.js
    var ajaxSelectRequest='';
    function callbackSelect(node)
    {
      $('div.genericAction').hide();
      $('div.genericCommunities').hide();
      $('div.genericStats').hide();
      $('div.viewInfo').show();
      $('div.viewAction').show()
      genericCallbackSelect(node);  
    }

    function callbackDblClick(node)
    {
    }
    
    function callbackCheckboxes(node)
    {
      genericCallbackCheckboxes(node);
    }
    
    function callbackCreateElement(node)
    {
      initDragAndDrop();
    }

function initDragAndDrop()
{
      $("#browseTable .file, #browseTable .folder:not(.notdraggable)").draggable({
      helper: "clone",
      opacity: .75,
      refreshPositions: true, // Performance?
      revert: "invalid",
      revertDuration: 300,
      scroll: true
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
           var from;
           var classNames=$(ui.draggable).parents("tr").attr('class').split(' ');
            for(key in classNames) {
              if(classNames[key].match('child-of-')) {
                from= $("#" + classNames[key].substring(9)).attr('element');
              }
            }
           var destination_obj=this;
           $.post(json.global.webroot+'/browse/movecopy', {moveElement: true, elements: elements , destination:$(this).attr('element'),from:from,ajax:true},
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
                    $(destination_obj).reload();
                  }
                else
                  {
                    createNotive(jsonResponse[1],4000);
                  }
           });
            
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
  $('a.groupLink').each(function(){
    var id=$(this).attr('groupid');
    $(this).parent('li').find('span').html(' ('+($('div#groupList_'+id+' td.tdUser').size())+')');
  });

      $('a#createGroupLink').click(function()
      {
        $('div.MainDialogContent').html('');
        $('div.MainDialogContent').html('');
        $('div#createGroupFrom').find('input[name=groupId]').val('0');
        $('div#createGroupFrom').find('input[name=name]').val('');
        showDialogWithContent(json.community.message.createGroup,$('div#createGroupFrom').html(),false);
        $('div.MainDialogContent form.editGroupForm').ajaxForm( {beforeSubmit: validateGroupChange, success:       successGroupChange} );
      });
      
    $('a.editGroupLink').click(function()
      {
        $('div.MainDialogContent').html('');
        var id=$(this).attr('groupid');
        $('div.MainDialogContent').html('');
        $('div#createGroupFrom').find('input[name=groupId]').val(id);
        var groupName=$(this).parent('li').find('a:first').html();
        showDialogWithContent(json.community.message.editGroup,$('div#createGroupFrom').html(),false);
        $('form.editGroupForm input#name').val(groupName);
        $('div.MainDialogContent form.editGroupForm').ajaxForm( {beforeSubmit: validateGroupChange, success:       successGroupChange} );
      });
      
    $('a.groupLink').click(function()
      {
        $('td#userGroupSelected').html('');
        $('td#userMemberSelected').html('');
        $('div.communityMemberList').show();
        $('div.groupList').hide();
        var id=$(this).attr('groupid');
        $('div#groupList_'+ id).show();
        $('div#memberList td.tdUser').show();
        $('td.tdUser input').attr('checked','');
        groupSelected=id;
        $('div#groupList_'+ id+' input').each(function()
          {
            $('div#memberList td.userid_'+$(this).attr('userid')).hide();
          });
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
    $('td#userMemberSelected').html('');
    groupUsersSelected=new Array();
    memberSelected=new Array();
    $('div.groupMemberList input:checked').each(function()
    {
      groupUsersSelected.push($(this).attr('userid'));
    });
    $('div.communityMemberList input:checked').each(function()
    {
      memberSelected.push($(this).attr('userid'));
    });
    if(groupUsersSelected.length>0)
      {
        $('td#userGroupSelected').html(groupUsersSelected.length+' user(s) selected<br/><a href="javascript:;" id="removeUserLink">Remove users From Group</a>');
      }
    if(memberSelected.length>0)
      {
        $('td#userMemberSelected').html(memberSelected.length+' user(s) selected<br/><a href="javascript:;" id="addUserLink">Add users to Group</a>');
      }
    $('a#removeUserLink').click(function()
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
              $('div.groupMemberList input:checked').each(function()
                {
                  $('div#memberList td.userid_'+$(this).attr('userid')).show();
                  $(this).parent('td').remove();
                  init()
                });
              $('td#userGroupSelected').html('');
              $('td#userMemberSelected').html('');
              $('td.tdUser input').attr('checked','');
              init();
            }
          else
            {
              createNotive(jsonResponse[1],4000);
            }
     });

    });
    
    $('a#addUserLink').click(function()
    {
    var users='';
    $.each( memberSelected, function(i, v){
       users+=v+'-';
     });
     $.post(json.global.webroot+'/community/manage', {communityId: json.community.community_id, addUser: 'true', groupId:groupSelected,users:users},
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
              $('div.communityMemberList input:checked').each(function()
                { 
                 datatable[groupSelected].fnAddData( [
                    $(this).parent('td').html()+'<span id="newRow"/>',
                    ] );
                 $('span#newRow').parent('td').addClass('tdUser');
                 $('span#newRow').parent('td').addClass('userid_'+$(this).attr('userid'));
                 $('span#newRow').remove();
                 $(this).parent('td').hide();
                 init()
                });
              $('td.tdUser input').attr('checked','');
              $('td#userGroupSelected').html('');
              $('td#userMemberSelected').html('');
              init();
            }
          else
            {
              createNotive(jsonResponse[1],4000);
            }
     });
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
         var content="<li><a class='groupLink' groupid='"+jsonResponse[2].group_id+"' href='javascript:;'>"+jsonResponse[2].name+"</a> [<a class='editGroupLink' groupid='"+jsonResponse[2].group_id+"' href='javascript:;'>Edit</a>][<a class='deleteGroupLink' groupid='"+jsonResponse[2].group_id+"' href='javascript:;'>Delete</a>]<span/> </li>";
         $('div#groupsList ul').append(content);
         
         var content="<div style='display:none;' class='groupList' id='groupList_"+jsonResponse[2].group_id+"'>";
         content+="<h4>"+$('div.groupMemberList h4:first').html()+"</h4>";
         content+='<table cellpadding="0" cellspacing="0" border="0" class="display dataTable">';
         content+="</table>";
         content+="</div>";
         $('div.groupMemberList').append(content);
         }
       init();
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