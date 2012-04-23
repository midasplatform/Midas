var json;
$(document).ready(function(){
    json = jQuery.parseJSON($('div.jsonContent').html());
    jdot = new JSDot("graph", {mode: "drag", json: jQuery.parseJSON($('div.jsonGraph').html())});
    jdot.addEventHandler("graph","selectionchg", jdotClickHandler);
});

function jdotClickHandler(w, s)
  {
  if(w.isNode)console.log(w.name);
	};

