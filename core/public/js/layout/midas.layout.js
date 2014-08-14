// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var json = json || {};
var midas = midas || {};
var itemselected = false;

// Prevent error if console.log is called
if (typeof console != "object") {
    var console = {
        'log': function () {}
    };
}

// Main calls
$(function () {
    'use strict';
    // Parse json content
    // jQuery 1.8 has weird bugs when using .html() here, use the old-style innerHTML here
    json = $.parseJSON($('div.jsonContent')[0].innerHTML);

    // Preload login page
    if (!json.global.logged) {
        midas.loadAjaxDynamicBar('login', '/user/login');
    }

    // Show log page.
    if (json.global.needToLog) {
        midas.showOrHideDynamicBar('login');
        midas.loadAjaxDynamicBar('login', '/user/login');
        return;
    }

    // Init Dynamic help ---------------
    InitHelpQtip();
    if (json.global.dynamichelpAnimate) {
        TimerQtip();
    }
    else {
        StopTimerQtip();
    }
    // Javascript link ---------------------

    // Starting Guide
    $('a#startingGuideLink').click(function () {
        showStartingGuide();
    });
    if (json.global.startingGuide) {
        showStartingGuide();
    }

    function showStartingGuide() {
        $("#dialogStartingGuide").dialog({
            width: 580,
            title: $("#dialogStartingGuide").attr('title'),
            modal: true
        });
    }

    $('#disableStartingGuide').change(function () {
        var value = 1;
        if ($(this).is(':checked')) {
            value = 0;
        }
        $.post(json.global.webroot + "/user/startingguide", {
            value: value
        });
    });

    $('#blockPersoLink').click(function () {
        window.location = $('.webroot').val() + '/user/userpage/';
    });
    $('#blockExploreLink').click(function () {
        window.location = $('.webroot').val() + '/browse/';
    });
    $('#blockCommunityLink').click(function () {
        window.location = $('.webroot').val() + '/community/';
    });
    $('#blockSettingsLink').click(function () {
        midas.loadAjaxDynamicBar('settings', '/user/settings');
        if ($("div.TopDynamicBar").is(':hidden')) {
            $("div.TopDynamicBar").show('blind', function () {

            });
        }
        $('#dialogStartingGuide').dialog("close");
    });

    // Login
    $("a.loginLink").click(function () {
        midas.showOrHideDynamicBar('login');
        midas.loadAjaxDynamicBar('login', '/user/login');
    });

    // Setting link
    $("li.settingsLink").click(function () {
        if ($("div.TopDynamicBar").is(':hidden')) {
            $("div.TopDynamicBar").show('blind', function () {});
        }
        midas.loadAjaxDynamicBar('settings', '/user/settings');
    });

    // Module link
    $("li.modulesLink").click(function () {
        if ($("div.TopDynamicBar").is(':hidden')) {
            $("div.TopDynamicBar").show('blind', function () {});
        }
        midas.loadAjaxDynamicBar('settings', '/user/settings');
    });

    // Register link
    $("a.registerLink").click(function () {
        midas.showOrHideDynamicBar('register');
        midas.loadAjaxDynamicBar('register', '/user/register');
    });

    // Search Bar -----------------------
    // Live search
    $.widget("custom.catcomplete", $.ui.autocomplete, {
        _renderMenu: function (ul, items) {
            var self = this,
                currentCategory = "";
            $.each(items, function (index, item) {
                if (item.category != currentCategory) {
                    ul.append('<li class="search-category">' + item.category + "</li>");
                    currentCategory = item.category;
                }
                self._renderItemData(ul, item);
            });
        }
    });

    var cache = {},
        lastXhr;
    $("#live_search").catcomplete({
        minLength: 2,
        delay: 10,
        source: function (request, response) {
            var term = request.term;
            if (term in cache) {
                response(cache[term]);
                return;
            }

            $("#searchloading").show();

            lastXhr = $.getJSON($('.webroot').val() + "/search/live", request, function (data, status, xhr) {
                $("#searchloading").hide();
                cache[term] = data;
                if (xhr === lastXhr) {
                    itemselected = false;
                    response(data);
                }
            });
        }, // end source
        select: function (event, ui) {
            itemselected = true;
            if (ui.item.itemid) // if we have an item
            {
                window.location = $('.webroot').val() + '/item/' + ui.item.itemid;
            }
            else if (ui.item.communityid) // if we have a community
            {
                window.location = $('.webroot').val() + '/community/' + ui.item.communityid;
            }
            else if (ui.item.folderid) // if we have a folder
            {
                window.location = $('.webroot').val() + '/folder/' + ui.item.folderid;
            }
            else if (ui.item.userid) // if we have a user
            {
                window.location = $('.webroot').val() + '/user/' + ui.item.userid;
            }
            else {
                window.location = $('.webroot').val() + '/search/' + ui.item.value;
            }
        }
    });

    $('#live_search').focus(function () {
        if ($('#live_search_value').val() == 'init') {
            $('#live_search_value').val($('#live_search').val());
            $('#live_search').val('');
        }
    });

    $('#live_search').focusout(function () {
        if ($('#live_search').val() == '') {
            $('#live_search').val($('#live_search_value').val());
            $('#live_search_value').val('init');
        }
    });

    $('#live_search').keyup(function (e) {
        if (e.keyCode == 13 && !itemselected) // enter key has been pressed
        {
            window.location = $('.webroot').val() + '/search/index?q=' + encodeURI($('#live_search').val());
        }
    });

    // Upload -------------------------------------

    midas.resetUploadButton = function () {
        // init Upload dialog
        if (json.global.logged) {
            var button = $('div.HeaderAction li.uploadFile');
            button.qtip('destroy');
            button.qtip({
                content: {
                    // Set the text to an image HTML string with the correct src URL to the loading image you want to use
                    text: '<img src="' + json.global.webroot + '/core/public/images/icons/loading.gif" alt="Loading..." />',
                    ajax: {
                        url: $('div.HeaderAction li.uploadFile').attr('rel')
                    },
                    title: {
                        text: 'Upload', // Give the tooltip a title using each elements text
                        button: true
                    }
                },
                position: {
                    at: 'bottom center', // Position the tooltip above the link
                    my: 'top right',
                    viewport: $(window), // Keep the tooltip on-screen at all times
                    effect: true // Disable positioning animation
                },
                show: {
                    modal: {
                        on: true,
                        blur: false
                    },
                    event: 'click',
                    solo: true // Only show one tooltip at a time
                },
                hide: {
                    event: false
                },
                style: {
                    classes: 'uploadqtip ui-tooltip-light ui-tooltip-shadow ui-tooltip-rounded'
                }
            });
            $('.uploadqtip').css('z-index:500');
        }
    };
    midas.resetUploadButton();

    // ask the user to log in if we want to upload a file
    var uploadPageLoaded = false;
    $('div.HeaderAction li.uploadFile').click(function () {
        if (json.global.logged) {
            if (!uploadPageLoaded) {
                $('img#uploadAFile').hide();
                $('img#uploadAFileLoading').show();
                uploadPageLoaded = true;
            }
        }
        else {
            midas.createNotice(json.login.contentUploadLogin, 4000);
            $("div.TopDynamicBar").show('blind');
            midas.loadAjaxDynamicBar('login', '/user/login');
        }
    });

    // Style -------------------------------------
    // user menu
    $('#menuUserInfo').click(function () {
        globalAuthAsk(json.global.webroot + '/user/userpage');
    });
    $("div.TopDynamicBar .closeButton").click(function () {
        if (!$("div.TopDynamicBar").is(':hidden')) {
            $("div.TopDynamicBar").hide('blind');
        }
    });

    $('div.TopbarRighta li.first').hover(
        function () {
            $('ul', this).css('display', 'block');
        },
        function () {
            $('ul', this).css('display', 'none');
        });
});

// Javascript utilities ----------------------------------

// asks the user to authenticate
function globalAuthAsk(url) {
    'use strict';
    if (json.global.logged) {
        window.location = url;
    }
    else {
        midas.createNotice(json.login.titleUploadLogin, 4000);
        $("div.TopDynamicBar").show('blind');
        midas.loadAjaxDynamicBar('login', '/user/login');
    }
}

var qtipsHelp = new Array();
var iQtips = 0;

function InitHelpQtip() {
    'use strict';
    if (!json.global.dynamichelp) {
        return;
    }
    if (json.dynamicHelp == undefined) {
        return;
    }
    $.each(json.dynamicHelp, function (index, value) {
        var text = value.text;
        text = text.replace(/&lt;/g, '<');
        text = text.replace(/&gt;/g, '>');
        var tmp = $(value.selector).qtip({
            content: {
                text: text
            },
            position: {
                my: value.my, // Position my top left...
                at: value.at // at the bottom right of...
            }
        });
        qtipsHelp.push(tmp);
    });
}

// Dynamic help sequence
function TimerQtip() {
    'use strict';
    if (!json.global.dynamichelp) {
        return;
    }

    $.each(qtipsHelp, function (index, value) {
        value.qtip('hide');
        value.qtip('disable');
    });

    if (json.global.demomode) {
        $('.loginLink').qtip('enable');
    }

    if (!$('#dialogStartingGuide').is(':hidden')) {
        iQtips = 0;
        setTimeout("TimerQtip()", 1000);
        return;
    }

    qtipsHelp[iQtips].qtip('show');
    if (qtipsHelp.length > iQtips + 1) {
        setTimeout("TimerQtip()", 5000);
    }
    else {
        setTimeout("StopTimerQtip()", 5000);
    }
    iQtips++;
}

function StopTimerQtip() {
    'use strict';
    if (!json.global.dynamichelp) {
        return;
    }
    $.each(qtipsHelp, function (index, value) {
        value.qtip('hide');
        value.qtip('enable');
    });
}
