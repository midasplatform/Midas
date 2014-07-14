// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

$('img#processButtonImg').show();
$('img#processButtonLoadiing').hide();

$('#blockDashboardLink').click(function () {
    window.location.replace($('.webroot').val() + '/remoteprocessing/index/dashboard');
});
$('#blockManageScheduledLink').click(function () {
    window.location.replace($('.webroot').val() + '/remoteprocessing/job/manage');
});
$('#blockCreateLink').click(function () {
    window.location.replace($('.webroot').val() + '/remoteprocessing/job/init');
});
$('#blockCreateScheduledLink').click(function () {
    window.location.replace($('.webroot').val() + '/remoteprocessing/job/init?scheduled=true');
});
