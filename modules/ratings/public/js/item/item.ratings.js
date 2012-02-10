var midas = midas || {};
midas.ratings = midas.ratings || {};

/**
 * Display the aggregate rating information including star visualization
 */
midas.ratings.renderAggregate = function(average, total) {
    if(average != null && average != '') {
        average = Math.round(average*100)/100;
        $('#averageValue').html(average);
        var starSelect = Math.round(average * 2) - 1;
        $('#ratingsAverage').stars('selectID', starSelect);
    }
    else {
        $('#averageValue').html('0.00');
        $('#ratingsAverage').stars('selectID', -1);
    }
    $('#voteTotal').html(total);
}

/**
 * Set the rating of the current item for the currently logged user
 * to the given value, which should be a number 0-5. Passing 0 means
 * the existing rating should be removed for the user.
 */
midas.ratings.setRating = function(value) {
    $.post(json.global.webroot+'/ratings/rating/rateitem', {
        itemId: json.item.item_id,
        rating: value
    }, function(data) {
        var resp = $.parseJSON(data);
        createNotice(resp.message, 3000, resp.status);
        midas.ratings.renderAggregate(resp.average, resp.total);
    });
  
}

$(document).ready(function() {
    $('#ratingsAverage').stars({
        disabled: true,
        split: 2
    });
    midas.ratings.renderAggregate(json.modules.ratings.average, json.modules.ratings.total);
    $('#ratingsAverage').show();

    if(json.global.logged == '1') {
      $('#ratingsUser').stars({
          disabled: false,
          callback: function(ui, type, value) {
              midas.ratings.setRating(value);
          }
      });
      $('#ratingsUser').stars('selectID', json.modules.ratings.userRating - 1);
      $('#ratingsUser').show();
    }
});

