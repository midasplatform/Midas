$(document).ready(function(){
  $("#viewer").iviewer(
      {
      src: $('div#urlImage').html(),
      update_on_resize: false
      });
});
