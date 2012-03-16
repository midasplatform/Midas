// load a dialog (ajax)
function loadDialog (name, url) {
    if($('.DialogContentPage').val()!=name) {
        $('.DialogContentPage').val(name);
        $('div.MainDialogContent').html("");
        $("div.MainDialogLoading").show();
        $.ajax({
            url: $('.webroot').val() + url,
            //contentType: "application/x-www-form-urlencoded;charset=UTF-8",
            success: function (data) {
                $('div.MainDialogContent').html(data);
                $('div.MainDialogLoading').hide();
                $('.dialogTitle').hide();
            }
        });
    }
}

/**
 * Show a static dialog.
 * To override default dialog() options, use the opts argument
 * @param title The title of the dialog
 * @param button Boolean: whether to show an Ok button or not
 * @param opts An object that will override any default options to jQuery dialog function
 */
function showDialog (title, button, opts) {
    var options = {
        resizable: false,
        width: 450,
        minHeight: 0,
        draggable: true,
        title: title,
        zIndex: 15100,
        modal: true
    };
    for(var attrname in opts) {
        options[attrname] = opts[attrname]; //override defaults if set
    }
    if(button) {
        options.buttons = {
            "Ok": function() {
                $(this).dialog("close");
            }
        };
    }
    var x = ($(window).width() - options.width) / 2;
    var y = Math.min(150, $(window).scrollTop() + 150);
    options.position = [x, y];
    $('div.MainDialog').dialog(options);
}

// show a dialog with a width of 700px
function showBigDialog (title, button) {
    showDialog(title, button, {width: 700});
}

// showDialogWithContent
function showDialogWithContent (title, content, button, opts) {
    $('.DialogContentPage').val('');
    $('div.MainDialogContent').html(content);
    $('div.MainDialogLoading').hide();
    showDialog(title, button, opts);
}

// showBigDialogWithContent
function showBigDialogWithContent (title, content, button) {
    $('.DialogContentPage').val('');
    $('div.MainDialogContent').html(content);
    $("div.MainDialogLoading").hide();
    showBigDialog(title, button);
}

// load the content of the black top bar
function loadAjaxDynamicBar(name,url)
{
    // If we don't have the top dynamic content div, just use a dialog
    if(!$('div.TopDynamicContent').length) {
        loadDialog(name, url);
        return;
    }

    if($('.DynamicContentPage').val() != name) {
        $('.DynamicContentPage').val(name);
        $('div.TopDynamicContent').fadeOut('slow', function() {
            $('div.TopDynamicContent').html("");
            $("div.TopDynamicLoading").show();

            $.ajax({
                url: $('.webroot').val()+url,
                contentType: "application/x-www-form-urlencoded;charset=UTF-8",
                success: function(data) {
                    $("div.TopDynamicLoading").hide();
                    $('div.TopDynamicContent').hide();
                    $('div.TopDynamicContent').html(data);
                    $('div.TopDynamicContent').fadeIn("slow");
                }
            });
        });
    }
}

// show or hide the bar
function showOrHideDynamicBar(name)
{
    // If we don't have the top dynamic content div, just use a dialog
    if(!$('div.TopDynamicContent').length) {
        showDialog(name);
        return;
    }
    if($("div.TopDynamicBar").is(':hidden')) {
        $("div.TopDynamicBar").show('blind', function() {
            $('#email').focus();
        });
    } else if($('.DynamicContentPage').val() == name) {
        $("div.TopDynamicBar").hide('blind');
    }
}
