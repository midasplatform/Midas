// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

$(document).ready(function () {
    $('#createApplicationLink').click(function () {
        midas.showDialogWithContent('Create Application', $('#createDialogTemplate').html(), false);
        $('textarea.expanding').autogrow();
    });
});
