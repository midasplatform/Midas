$(document).ready( function() 
{
   var id = $("input#serversidefilechooser-id").val();
   var errorMessage = $("input#serversidefilechooser-errorMessage").val();
   var destSelector = $("input#serversidefilechooser-destSelector").val();
   var fileFilter = $("input#serversidefilechooser-fileFilter").val();
   var scriptaction = $("input#serversidefilechooser-script").val();  
   
   // Cancel button: hide
   $("#fp-"+id+"-inputButtonCancel").click(function(){
       $("#fp-"+id+"-panel, #fp-"+id+"-background").fadeOut(300);
       return false;
       });
  // Open button: show
   $("#"+id).click(function(){
	    //$('<div />').addClass('lightbox_bg').appendTo('body').show();
       $('.TopDynamicBar').after('<div id="fp-"+id+"-background"></div>');
       $("#fp-"+id+"-panel, #fp-"+id+"-background").fadeIn(300);
        return false;
       });

   $('#fp-'+id+'-fileTree').fileTree(
        {
          root: '', 
          script: scriptaction 
        }, 
        function(file) {
          $("#fp-"+id+"-inputFile").val('' + file);
          },
        function(dir) {
          // Some checks if the root starts with //
          if(dir[0]=='/' && dir[1]=='/')
            {
            dir = dir.substr(1);
            }
          
          $("#fp-"+id+"-inputFile").val('' + dir);
          }
      );

   // Validate button
   $("#fp-"+id+"-inputButtonOK").click(function(){
       // extract file name
       var file = $("#fp-"+id+"-inputFile").val();
       var re = new RegExp(fileFilter);
       if(file.match(re))
         {
         // if destSelector is valid set its value to file
         if( $(destSelector).length !== 0 )
           {
           $(destSelector).val(file); 
           $(destSelector).change(); // mark that it has changed
           $("#fp-"+id+"-panel, #fp-"+id+"-background").fadeOut(300);
           }
         }
       else if(errorMessage)
         {
         alert(errorMessage);
         }
       return false; // important to return false to prevent a form that include
                     // this element to be submitted when the user just want to
                     // select a file.       
     });

});