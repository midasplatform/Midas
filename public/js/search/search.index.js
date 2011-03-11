$(document).ready(function() {
  $('#live_search_value').val($('#live_search').val());
  $('#live_search').val(json.search.keyword); 
});
