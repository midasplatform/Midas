var disableElementSize=true;

$(document).ready(function() {
    
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
