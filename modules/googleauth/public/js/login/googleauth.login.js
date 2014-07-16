(function () {
    var currentUrl = window.encodeURIComponent(window.location.href);

    $.each($('a.googleauth-login'), function () {
        var link = $(this);
        link.attr('href', link.attr('href') + '&state=' + currentUrl);
    });
}) ();
