// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};

// load a dialog (ajax)
midas.loadDialog = function (name, url) {
    'use strict';
    if ($('.DialogContentPage').val() != name) {
        $('.DialogContentPage').val(name);
        $('div.MainDialogContent').html('');
        $('div.MainDialogLoading').show();
        $.ajax({
            url: $('.webroot').val() + url,
            // contentType: "application/x-www-form-urlencoded;charset=UTF-8",
            success: function (data) {
                $('div.MainDialogContent').html(data);
                $('div.MainDialogLoading').hide();
                $('.dialogTitle').hide();
            }
        });
    }
};

/**
 * Show a static dialog.
 * To override default dialog() options, use the opts argument
 * @param title The title of the dialog
 * @param button Boolean: whether to show an Ok button or not
 * @param opts An object that will override any default options to jQuery dialog function
 */
midas.showDialog = function (title, button, opts) {
    'use strict';
    var options = {
        resizable: false,
        width: 450,
        minHeight: 0,
        draggable: true,
        title: title,
        zIndex: 15100,
        modal: true
    };
    if (button) {
        options.buttons = {
            'Ok': function () {
                $(this).dialog('close');
            }
        };
    }
    else {
        options.buttons = null;
    }
    for (var attrname in opts) {
        options[attrname] = opts[attrname]; // override defaults if set
    }
    var x = ($(window).width() - options.width) / 2;
    var y = Math.min(150, $(window).scrollTop() + 150);
    options.position = [x, y];
    $('div.MainDialog').dialog(options);
};

// show a dialog with a width of 700px
midas.showBigDialog = function (title, button) {
    'use strict';
    midas.showDialog(title, button, {
        width: 700
    });
};

// showDialogWithContent
midas.showDialogWithContent = function (title, content, button, opts) {
    'use strict';
    $('.DialogContentPage').val('');
    $('div.MainDialogContent').html(content);
    $('div.MainDialogLoading').hide();
    midas.showDialog(title, button, opts);
};

// showBigDialogWithContent
midas.showBigDialogWithContent = function (title, content, button) {
    'use strict';
    $('.DialogContentPage').val('');
    $('div.MainDialogContent').html(content);
    $('div.MainDialogLoading').hide();
    midas.showBigDialog(title, button);
};

// load the content of the black top bar
midas.loadAjaxDynamicBar = function (name, url) {
    'use strict';
    // If we don't have the top dynamic content div, just use a dialog
    if (!$('div.TopDynamicContent').length) {
        midas.loadDialog(name, url);
        return;
    }

    if ($('.DynamicContentPage').val() != name) {
        $('.DynamicContentPage').val(name);
        $('div.TopDynamicContent').fadeOut('slow', function () {
            $('div.TopDynamicContent').html('');
            $('div.TopDynamicLoading').show();

            $.ajax({
                url: $('.webroot').val() + url,
                contentType: 'application/x-www-form-urlencoded;charset=UTF-8',
                success: function (data) {
                    $('div.TopDynamicLoading').hide();
                    $('div.TopDynamicContent').hide();
                    $('div.TopDynamicContent').html(data);
                    $('div.TopDynamicContent').fadeIn('slow');
                }
            });
        });
    }
};

// show or hide the bar
midas.showOrHideDynamicBar = function (name) {
    'use strict';
    // If we don't have the top dynamic content div, just use a dialog
    if (!$('div.TopDynamicContent').length) {
        midas.showDialog(name);
        return;
    }
    if ($('div.TopDynamicBar').is(':hidden')) {
        $('div.TopDynamicBar').show('blind', function () {
            $('#email').focus();
        });
    }
    else if ($('.DynamicContentPage').val() == name) {
        $('div.TopDynamicBar').hide('blind');
    }
};
