var midas = midas || {};
midas.ratings = midas.ratings || {};

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
        createNotice(data, 4000);
    });
  
}

$(document).ready(function() {
    $('#ratingsAverage').stars({
        disabled: true,
        split: 2
    });

    if(json.global.logged == '1') {
      $('#ratingsUser').stars({
          disabled: false,
          callback: function(ui, type, value) {
              midas.ratings.setRating(value);
          }
      });
      $('#ratingsUser').show();
    }
});

