
$(document).ready(function() {

  jsonMetadata = jQuery.parseJSON($('div#jsonMetadata').html());
  initElementMetaData();
  $('select, input').change(function(){
    initElementMetaData();
  });
});

function initElementMetaData()
{
  var value = $('select[name=metadatatype]').val();
  var availableTags = new Array();
    $.each( jsonMetadata[value], function(i, l){
     availableTags.push(i);
   });
  $( "input[name=element]" ).autocomplete({
      source: availableTags,
      change: function(){initElementMetaData();}
    });
  initQualifierMetaData();
}

function initQualifierMetaData()
{

  var type = $('select[name=metadatatype]').val();
  var value = $('input[name=element]').val();
  var availableTags = new Array();
  $.each( jsonMetadata[type][value], function(i, l){
     availableTags.push(l.qualifier);
   });
   
  $( "input[name=qualifier]" ).autocomplete({
      source: availableTags,
      change: function(){initElementMetaData();}
    });
}
    