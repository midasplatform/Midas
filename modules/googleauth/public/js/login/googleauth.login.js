(function () {
    'use strict';
    $.each($('a.googleauth-login'), function () {
        var link = $(this);
        link.attr('href', link.attr('href') + window.encodeURIComponent(' ' + window.location.href));
    });
})();
