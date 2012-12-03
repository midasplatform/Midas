var midas = midas || {};
midas.tracker = midas.tracker || {};

midas.tracker.validateEditForm = function () {
    return true;
};

midas.tracker.editSuccess = function (retVal) {
    var resp = $.parseJSON(retVal);
    if(!resp) {
        midas.createNotice('An error occurred on the server', 3000, 'error');
        return;
    }
    midas.createNotice(resp.message, 3000, resp.status);

    if(resp.status == 'ok') {
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
