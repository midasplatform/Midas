/**
 * import.index.js
 * 
 * Page javascript for <midas>/import
 *
 */

/*node browser: true */
/*global $ */
/*global document */
/*global window */

// namespacing
var midas = midas || {};
midas.import = midas.import || {};

// We have four stages: validate, initialize, upload
midas.import.stage = 'validate';
midas.import.formSubmitOptions = {};
midas.import.importSubmitButtonValue = null;
midas.import.uploadid = null;

/** On import serialize */
midas.import.importSerialize = function (form, options) {
    'use strict';
    if (midas.import.stage === 'validate') {
        options.data = {validate: '1'};
    }
};

/** On import submit */
midas.import.importSubmit = function (formData, jqForm, options) {
    'use strict';
    var uploadId = formData[0].value;
    if (midas.import.stage === 'upload') {
        midas.import.checkProgress(uploadId);
    }
};

/** On import success */
midas.import.importCallback = function (responseText, statusText, xhr, form) {
    'use strict';

    if (responseText.stage === 'validate') {
        midas.import.stage = 'initialize';
        $("#progress_status").html('Counting files (this could take some time)');
        midas.import.formSubmitOptions.data = {initialize: '1'};
        $("#importsubmit").html($("#importstop").val());
        $('#importForm').ajaxSubmit(midas.import.formSubmitOptions);
    } else if (responseText.stage === 'initialize') {
        midas.import.stage = 'upload';
        midas.import.formSubmitOptions.data = {totalfiles: responseText.totalfiles};
        $('#importForm').ajaxSubmit(midas.import.formSubmitOptions);
    } else if (responseText.error) {
        midas.import.stage = 'validate';  // goes back to the validate stage
        $("#importsubmit").html(midas.import.importSubmitButtonValue);
        $(".viewNotice").html(responseText.error);
        $(".viewNotice").fadeIn(100).delay(2000).fadeOut(300);
    } else if (responseText.message) {
        midas.import.stage = 'validate';    // goes back to the validate stage
        $("#progress_status").html('Import done');
        $("#progress").progressbar("value", 100);
        $(".viewNotice").html(responseText.message);
        $(".viewNotice").fadeIn(100).delay(2000).fadeOut(300);
        $('#importsubmit').html(midas.import.importSubmitButtonValue);
    }
};

/**
 * Display a stop message within all divs of the class viewNotice
 */
midas.import.displayStopMessage = function (data) {
    'use strict';
    $(".viewNotice").html('Import has been stopped.');
    $(".viewNotice").fadeIn(100).delay(2000).fadeOut(300);
};

/** If the button to start/stop the import has been clicked */
midas.import.startImport = function () {
    'use strict';
    if (midas.import.stage === 'validate') {
        midas.import.formSubmitOptions = {
            success : midas.import.importCallback,
            beforeSerialize: midas.import.importSerialize,
            beforeSubmit: midas.import.importSubmit,
            dataType : 'json'
        };
        $('#importForm').ajaxSubmit(midas.import.formSubmitOptions);
    } else { // stop the import
        midas.import.stage = 'validate'; // goes back to the validate stage
        $.get($('.webroot').val() + '/import/stop?id=' + midas.import.uploadId,
              midas.import.displayStopMessage);
    }
};

/** On assetstore add submit */
midas.import.assetstoreSubmit = function (formData, jqForm, options) {
    'use strict';

    // Add the type is the one in the main page (because it's hidden in the assetstore add page)
    var assetstoretype = {};
    assetstoretype.name = 'assetstoretype';
    assetstoretype.value = $("#importassetstoretype").val();
    formData.push(assetstoretype);
    $(".assetstoreLoading").show();
};

/** On assetstore add sucess */
midas.import.assetstoreAddCallback = function (responseText, statusText, xhr, form) {
    'use strict';
    var newassetstore = {};

    $(".assetstoreLoading").hide();
    if (responseText.error) {
        $(".viewNotice").html(responseText.error);
        $(".viewNotice").fadeIn(100).delay(2000).fadeOut(100);
    } else if (responseText.msg) {
        $(document).trigger('hideCluetip');
        if (responseText.assetstore_id) {
            $("#assetstore").append($("<option></option>")
                                    .attr("value", responseText.assetstore_id)
                                    .text(responseText.assetstore_name)
                                    .attr("selected", "selected"));

            // Add to JSON
            newassetstore.assetstore_id = responseText.assetstore_id;
            newassetstore.name = responseText.assetstore_name;
            newassetstore.type = responseText.assetstore_type;
            midas.import.assetstores.push(newassetstore);
        }

        $(".viewNotice").html(responseText.msg);
        $(".viewNotice").fadeIn(100).delay(2000).fadeOut(100);
    }
};

/** When the cancel is clicked in the new assetstore window */
midas.import.newAssetstoreShow = function () {
    'use strict';
    var assetstoretype = $('select#importassetstoretype option:selected').val();
    $('#assetstoretype option:selected').removeAttr("selected");
    $('#assetstoretype option[value=' + assetstoretype + ']').attr("selected", "selected");
};

/** When the cancel is clicked in the new assetstore window */
midas.import.newAssetstoreHide = function () {
    'use strict';
    $(document).trigger('hideCluetip');
};

/** When the input directory is changed */
midas.import.inputDirectoryChanged = function () {
    'use strict';

    // Set the assetstore name as the basename
    var basename = $('#inputdirectory').val().replace(/^.*[\/\\]/g, '');
    if (basename.length === 0) { // if the last char is / or \
        basename = $('#inputdirectory').val()
            .substr(0, $('#inputdirectory').val().length - 1)
            .replace(/^.*[\/\\]/g, '');
    }
    $("#assetstorename").val(basename);

    // set the input directory as the same
    $("#assetstoreinputdirectory").val($('#inputdirectory').val());
};

/** When the assetstore type list is changed */
midas.import.assetstoretypeChanged = function () {
    'use strict';
    var i,
        assetstoretype = $('select#importassetstoretype option:selected').val();

    // Set the same assetstore type for the new assetstore
    $('#assetstoretype option:selected').removeAttr("selected");
    $('#assetstoretype option[value=' + assetstoretype + ']').attr("selected", "selected");

    // Clean the assetstore list
    $("select#assetstore").find('option:not(:first)').remove();

    for (i = 0; i < midas.import.assetstores.length; i += 1) {
        if (midas.import.assetstores[i].type === assetstoretype) {
            $("select#assetstore").append($("<option></option>")
                                          .attr("value", midas.import.assetstores[i].assetstore_id).
                                          text(midas.import.assetstores[i].name));
        }
    }
};

/**
 * Return a callback for success that timeouts a checkProgress every 3s.
 */
midas.import.makeProgressSuccessCallback = function (id) {
    'use strict';
    var ret = function (html) {

        if (html) {
            if (html.percent !== 'NA') {
                $("#progress").show();
                $("#progress_status").show();

                $("#progress_status").html('Importing files ' + html.current +
                                           '/' + html.max + ' (' + html.percent + '%)');
                $("#progress").progressbar("value", html.percent);
            }
        }
        window.setTimeout("midas.import.checkProgress(" + id + ")", 3000);
    };
    return ret;
};

midas.import.progressFailureCallback = function (XMLHttpRequest, textStatus, errorThrown) {
    'use strict';
    midas.createNotice(textStatus, 4000, 'error');
    midas.createNotice(errorThrown, 4000, 'error');
};

/** Check the progress of the import */
midas.import.checkProgress = function (id) {
    'use strict';

    if (midas.import.stage === 'validate') {
        return false;
    }

    $.ajax({ type: "GET",
             url: $('.webroot').val() + '/import/getprogress?id=' + id,
             dataType: 'json',
             timeout: 10000000000,
             success: midas.import.makeProgressSuccessCallback(id),
             error: midas.import.progressFailureCallback
           });

};

$(document).ready(function () {
    'use strict';

    // Parse json from the view
    midas.import.assetstores = $.parseJSON(midas.import.assetstores);

    // Bind the import submit to start the import
    $('#importsubmit').click(function () {
        midas.import.startImport();
    });

    // Bind the input directory
    $('#inputdirectory').change(midas.import.inputDirectoryChanged);

    // Form for the new assetstore
    $('#assetstoreForm').ajaxForm({ success: midas.import.assetstoreAddCallback,
                                    beforeSubmit: midas.import.assetstoreSubmit,
                                    dataType: 'json'
                                  });

    // Load the window for the new assetstore
    $('a.load-newassetstore').cluetip({ cluetipClass: 'jtip',
                                        activation: 'click',
                                        local: true,
                                        cursor: 'pointer',
                                        arrows: true,
                                        clickOutClose: true,
                                        onShow: midas.import.newAssetstoreShow
                                      });

    $("#progress").progressbar();

    // Not possible to change the type of an assetstore. This is based on a
    // previous choice by the user
    $("#assetstoretype").attr('disabled', 'disabled');

    midas.import.importSubmitButtonValue = $("#importsubmit").html();

    //Init Browser
    $('input[name=importFolder]').val('');
    $('input[name=importFolder]').attr('id', 'destinationId');
    $('input[name=importFolder]').hide();
    $('input[name=importFolder]').before('<input style="margin-left:0px;" id="browseMIDASLink" class="globalButton" type="button" value="Select location" />');
    $('input[name=importFolder]').before('<span style="margin-left:5px;" id="destinationUpload"/>');
    $('#browseMIDASLink').click(function () {
        midas.loadDialog("select", "/browse/movecopy/?selectElement=true");
        midas.showDialog('Browse');
    });
});
