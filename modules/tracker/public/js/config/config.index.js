$(window).load(function () {
    $('#configForm').ajaxForm({
        beforeSubmit: function () {
            return true;
        },
        success: function (text) {
            var resp = $.parseJSON(text);
            if(!resp) {
                midas.createNotice('An error occurred, check the log', 3000, 'error');
            }
            else {
                midas.createNotice(resp.message, 3000, resp.status);
            }
        }
    });
});
