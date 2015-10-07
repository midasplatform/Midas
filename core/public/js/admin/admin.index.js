// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.admin = midas.admin || {};

midas.admin.initModulesConfigLinks = function () {
    'use strict';
    $('input.moduleCheckbox').each(function () {
        if ($(this).is(':checked')) {
            $(this).parents('tr').find('td.configLink').show();
        }
        else {
            $(this).parents('tr').find('td.configLink').hide();
        }
    });
};

/** On assetstore add response */
midas.admin.assetstoreAddCallback = function (responseText, statusText, xhr, $form) {
    'use strict';
    $('.assetstoreLoading').hide();
    if (responseText.error) {
        $('.addAssetstoreFormError').html('Error: ' + responseText.error).show();
    }
    else if (responseText.msg) {
        $(document).trigger('hideCluetip');

        if (responseText.assetstore_id) {
            window.location = json.global.webroot + '/admin#tabs-assetstore';
            window.location.reload();
        }
        midas.createNotice(responseText.msg, 4000);
    }
}; // end assetstoreAddCallback

/** On assetstore add submit */
midas.admin.assetstoreSubmit = function (formData, jqForm, options) {
    'use strict';
    // Add the type is the one in the main page (because it's hidden in the assetstore add page)
    var assetstoretype = {};
    assetstoretype.name = 'type';
    assetstoretype.value = $('#importassetstoretype').val();
    formData.push(assetstoretype);
    $('.assetstoreLoading').show();
    $('.addAssetstoreFormError').html('').hide();
}; // end assetstoreBeforeSubmit

/** When the cancel is clicked in the new assetstore window */
midas.admin.newAssetstoreShow = function () {
    'use strict';
    var assetstoretype = $('select#importassetstoretype option:selected').val();
    $('#assetstoretype').find('option:selected').removeAttr('selected');
    $('#assetstoretype').find('option[value=' + assetstoretype + ']').attr('selected', 'selected');
}; // end function newAssetstoreShow

/** When the cancel is clicked in the new assetstore window */
midas.admin.newAssetstoreHide = function () {
    'use strict';
    $(document).trigger('hideCluetip');
};

midas.admin.validateConfig = function (formData, jqForm, options) {};

midas.admin.successConfig = function (responseText, statusText, xhr, form) {
    'use strict';
    var jsonResponse;
    try {
        jsonResponse = $.parseJSON(responseText);
    }
    catch (e) {
        midas.createNotice('An error occured. Please check the logs.', 4000, 'error');
        return false;
    }
    if (jsonResponse === null) {
        midas.createNotice('Error', 4000, 'error');
        return;
    }
    if (jsonResponse[0]) {
        midas.createNotice(jsonResponse[1], 4000);
    }
    else {
        midas.createNotice(jsonResponse[1], 4000, 'error');
    }
};

$(document).ready(function () {
    'use strict';
    midas.admin.tabs = $('#tabsGeneric').tabs({});
    $('#tabsGeneric').show();
    $('img.tabsLoading').hide();

    $('.defaultAssetstoreLink').click(function () {
        $.post(json.global.webroot + '/assetstore/defaultassetstore', {
            submitDefaultAssetstore: true,
            element: $(this).attr('element')
        },
            function (data) {
                var jsonResponse = $.parseJSON(data);
                if (jsonResponse === null) {
                    midas.createNotice('Error', 4000);
                    return;
                }
                midas.createNotice(jsonResponse[1], 1500);
                window.location.replace(json.global.webroot + '/admin#tabs-assetstore');
                window.location.reload();
            }
        );
    });

    $('.moveBitstreamsLink').click(function () {
        var srcId = $(this).attr('element');
        midas.loadDialog('moveBitstreams' + srcId, '/assetstore/movedialog?srcAssetstoreId=' + encodeURIComponent(srcId));
        midas.showDialog('Move bitstreams', false);
    });

    $('.removeAssetstoreLink').click(function () {
        var element = $(this).attr('element');
        var html = '';
        html += 'Do you really want to remove the assetstore? All the items located in it will be deleted. (Can take a while)';
        html += '<br/>';
        html += '<br/>';
        html += '<input style="margin-left:140px;" class="globalButton deleteAssetstoreYes" element="' + element + '" type="button" value="' + json.global.Yes + '"/>';
        html += '<input style="margin-left:50px;" class="globalButton deleteAssetstoreNo" type="button" value="' + json.global.No + '"/>';
        midas.showDialogWithContent('Remove Assetstore', html, false);

        $('input.deleteAssetstoreYes').unbind('click').click(function () {
            $('div.MainDialog').dialog('close');
            midas.ajaxSelectRequest = $.ajax({
                type: 'POST',
                url: json.global.webroot + '/assetstore/delete',
                data: {
                    assetstoreId: element
                },
                success: function (jsonContent) {
                    var jsonResponse = $.parseJSON(jsonContent);
                    midas.createNotice(jsonResponse[1], 1500);
                    if (jsonResponse[0]) {
                        window.location = json.global.webroot + '/admin#tabs-assetstore';
                        window.location.reload();
                    }
                }
            });
        });
        $('input.deleteAssetstoreNo').unbind('click').click(function () {
            $('div.MainDialog').dialog('close');
        });
    });

    $('.editAssetstoreLink').click(function () {
        var element = $(this).attr('element');
        var html = '';
        html += '<form class="genericForm" onsubmit="false;">';
        html += '<label>Name:</label> <input type="text" id="assetstoreName" value="' + $(this).parents('div').find('span.assetstoreName').html() + '"/><br/><br/>';
        html += '<label>Path:</label> <input type="text" id="assetstorePath" value="' + $(this).parents('div').find('span.assetstorePath').html() + '"/>';
        html += '<br/>';
        html += '<br/>';
        html += '<input type="submit" id="assetstoreSubmit" style="float: right;" value="Save"/>';
        html += '</form>';
        html += '<br/>';
        midas.showDialogWithContent('Edit Assetstore', html, false);

        $('input#assetstoreSubmit').unbind('click').click(function () {
            midas.ajaxSelectRequest = $.ajax({
                type: 'POST',
                url: json.global.webroot + '/assetstore/edit',
                data: {
                    assetstoreId: element,
                    assetstoreName: $('input#assetstoreName').val(),
                    assetstorePath: $('input#assetstorePath').val()
                },
                success: function (jsonContent) {
                    var jsonResponse = $.parseJSON(jsonContent);
                    if (jsonResponse[0]) {
                        midas.createNotice(jsonResponse[1], 1500);
                        window.location.replace(json.global.webroot + '/admin#tabs-assetstore');
                        window.location.reload();
                    }
                    else {
                        midas.createNotice(jsonResponse[1], 4000, 'error');
                    }
                }
            });
        });
        $('input.deleteAssetstoreNo').unbind('click').click(function () {
            $('div.MainDialog').dialog('close');
        });
    });

    $('#configForm').ajaxForm({
        beforeSubmit: midas.admin.validateConfig,
        success: midas.admin.successConfig
    });

    $('#assetstoreForm').ajaxForm({
        success: midas.admin.assetstoreAddCallback,
        beforeSubmit: midas.admin.assetstoreSubmit,
        dataType: 'json'
    });

    $('a.load-newassetstore').cluetip({
        cluetipClass: 'jtip',
        activation: 'click',
        local: true,
        cursor: 'pointer',
        arrows: true,
        closeText: 'Hide',
        closePosition: 'title',
        sticky: true,
        onShow: midas.admin.newAssetstoreShow
    });

    $('input.moduleCheckbox').change(function () {
        if ($(this).is(':checked')) {
            modulevalue = 1;
            var dependencies = $(this).attr('dependencies');
            dependencies = dependencies.split(',');
            $.each(dependencies, function (i, l) {
                if (l != '') {
                    if (!$('input[module=' + l + ']').is(':checked')) {
                        $.post(json.global.webroot + '/admin/index', {
                            submitModule: true,
                            modulename: l,
                            modulevalue: modulevalue
                        });
                        midas.createNotice('Dependancy: Enabling module ' + l, 1500);
                    }
                    $('input[module=' + l + ']').attr('checked', true);
                }
            });
        }
        else {
            var modulevalue = 0;
            var moduleDependencies = [];
            $.each($('input[dependencies=' + $(this).attr('module') + ']:checked'), function () {
                moduleDependencies.push($(this).attr('module'));
            });
            $.each($('input[dependencies*=",' + $(this).attr('module') + '"]:checked'), function () {
                moduleDependencies.push($(this).attr('module'));
            });
            $.each($('input[dependencies*="' + $(this).attr('module') + ',"]:checked'), function () {
                moduleDependencies.push($(this).attr('module'));
            });
            var found = false;
            var mainModule = $(this).attr('module');

            $.each(moduleDependencies, function (i, l) {
                if (l != '') {
                    found = true;
                    midas.createNotice('Dependency: The module ' + l + ' requires ' + mainModule + '. You must disable it first.', 4000, 'warning');
                }
            });
            if (found) {
                $(this).attr('checked', true);
                return;
            }
        }

        $.post(json.global.webroot + '/admin/index', {
            submitModule: true,
            modulename: $(this).attr('module'),
            modulevalue: modulevalue
        },
            function (data) {
                var jsonResponse = $.parseJSON(data);
                if (jsonResponse === null) {
                    midas.createNotice('Error', 4000);
                    return;
                }
                midas.createNotice(jsonResponse[1], 3500);
                midas.admin.initModulesConfigLinks();
            });
    });

    $('a.moduleVisibleCategoryLink').click(function () {
        if ($(this).prev('span').html() == '&gt;') {
            $(this).prev('span').html('v');
            $('.' + $(this).html() + 'VisibleElement').show();
        }
        else {
            $(this).prev('span').html('>');
            $('.' + $(this).html() + 'VisibleElement').hide();
        }
    });

    $('a.moduleHiddenCategoryLink').click(function () {
        if ($(this).prev('span').html() == '&gt;') {
            $(this).prev('span').html('v');
            $('.' + $(this).html() + 'HiddenElement').show();
        }
        else {
            $(this).prev('span').html('>');
            $('.' + $(this).html() + 'HiddenElement').hide();
        }
    });
    midas.admin.initModulesConfigLinks();
});
