// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.tracker = midas.tracker || {};

midas.tracker.validateEditForm = function () {
    return true;
};

midas.tracker.editSuccess = function (retVal) {
    var resp = $.parseJSON(retVal);
    if (!resp) {
        midas.createNotice('An error occurred on the server', 3000, 'error');
        return;
    }
    midas.createNotice(resp.message, 3000, resp.status);

    if (resp.status == 'ok') {
        $('div.MainDialog').dialog('close');
        $('span.executable').html(resp.producer.executable_name);
        $('span.repository').html(resp.producer.repository);
        $('span.description').html(resp.producer.description);
    }
};

$('textarea.description').autogrow();
$('form.editProducerForm').ajaxForm({
    beforeSubmit: midas.tracker.validateEditForm,
    success: midas.tracker.editSuccess
});

midas.tracker.qtipContent = "Enter the URL that revision values in this producer will link to. " +
    "Put <b>%revision</b> in the URL, and that will be expanded to the value of the revision." +
    "<p><b>Example:</b> https:// github.com/myuser/myproject/commit/%revision</p>";

$.fn.qtip.zindex = 16000; // show qtip on top of the dialog instead of under it
$('input[name="revisionUrl"]').qtip({
    content: midas.tracker.qtipContent
});
