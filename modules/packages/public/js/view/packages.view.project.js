$(document).ready(function () {
    $('#createApplicationLink').click(function () {
        midas.showDialogWithContent('Create Application', $('#createDialogTemplate').html(), false);
        $('textarea.expanding').autogrow();
    });
});