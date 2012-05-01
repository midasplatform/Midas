$(document).ready(function() {
    var msgDiv = $('#messages');
    var createHierarchyLink = $('#createhierarchy');
    createHierarchyLink.click(function() {
	$.getJSON(json.global.webroot + '/slicerpackages/createstructure',
		  function(data) {
		      msgDiv.text(data.msg);
		      if(data.stat) {
			  msgDiv.css('color','green');
		      } else {
			  msgDiv.css('color','red');
		      }
		  });
    });
});