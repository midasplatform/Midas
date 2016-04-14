// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global ajaxWebApi */
/* global json */

var midas = midas || {};

$(document).ready(function () {
    'use strict';

    var activeNotification = null;
    var activeSpecNotifications = {};

    //:~ Server API interfaces

    /**
     * Interface to server side midas REST API.
     * @param method The HTTP method {'POST'|'PUT'|'DELETE'|'GET'}
     * @param resourceId (Required for all except POST)
     * @param args an object containing key value pairs for the resource object for PUT calls (Optional except for PUT)
     * @param sCb success callback, passed the return value (Optional)
     * @param eCb error callback, passed the return value (Optional)
     * @param cCb complete callback, passed the return value (Optional)
     */
    function midasRest(httpMethod, path, resourceId, args, sCb, eCb, cCb) {
        var url = json.global.webroot + path;
        if (resourceId) {
            url += '/' + resourceId;
        }
        url += '?useSession=true';

        var restCall = {
            url: url,
            type: httpMethod,
            dataType: 'json',
            success: function (retVal) {
                if (sCb) { sCb(retVal); }
            },
            error: function (retVal) {
                if (eCb) {
                    eCb(retVal);
                } else {
                    midas.createNotice(retVal.message, 3000, 'error');
                }
            },
            complete: function (retVal) {
                if (cCb) { cCb(retVal); }
            },
            log: $('<p></p>')
        };
        if (args) {
            restCall.data = args;
        }
        $.ajax(restCall);
    }

    /**
     * Interface to server side AggregateMetricSpec REST API.
     * @param method The HTTP method {'POST'|'PUT'|'DELETE'|'GET'}
     * @param aggregateMetricSpecId (Required for all except POST)
     * @param args an object containing key value pairs for the AggregateMetricSpec object for PUT calls (Optional except for PUT)
     * @param sCb success callback, passed the return value (Optional)
     * @param eCb error callback, passed the return value (Optional)
     * @param cCb complete callback, passed the return value (Optional)
     */
    function aggregatemetricspecRest(method, aggregateMetricSpecId, args, sCb, eCb, cCb) {
        var path = '/rest/tracker/aggregatemetricspec';
        midasRest(method, path, aggregateMetricSpecId, args, sCb, eCb, cCb);
    }

    /**
     * Interface to server side AggregateMetricNotification REST API.
     * @param method The HTTP method {'POST'|'PUT'|'DELETE'|'GET'}
     * @param aggregateMetricNotificationId (Required for all except POST)
     * @param args an object containing key value pairs for the AggregateMetricNotification object for PUT calls (Optional except for PUT)
     * @param sCb success callback, passed the return value (Optional)
     * @param eCb error callback, passed the return value (Optional)
     * @param cCb complete callback, passed the return value (Optional)
     */
    function aggregatemetricnotificationRest(method, aggregateMetricNotificationId, args, sCb, eCb, cCb) {
        var path = '/rest/tracker/aggregatemetricnotification';
        midasRest(method, path, aggregateMetricNotificationId, args, sCb, eCb, cCb);
    }

    /**
     * Additional wrapper around ajaxWebApi.ajax to provide some defaults.
     * @param string jsonMethod the midas json method
     * @param string httpMethod The HTTP method {'POST'|'PUT'|'DELETE'|'GET'}
     * @param string args The args to pass to the API call
     * @param function successCb success callback, passed the return value (Optional)
     */
     function callAjaxWebApi(jsonMethod, httpMethod, args, successCb) {
        ajaxWebApi.ajax({
            method: jsonMethod,
            type: httpMethod,
            args: args,
            success: function (retVal) {
                if (successCb) { successCb(retVal); }
            },
            error: function (retVal) {
                midas.createNotice(retVal.message, 3000, 'error');
            },
            complete: function () {},
            log: $('<p></p>')
        });
    }

    //:~ Utility functions

    /**
     * Helper function to assign correct row classes to spec table.
     * @param string tableId the DOM id of the table.
     */
    function addClassesToTableRows(tableId) {
        $('#' + tableId + ' tbody tr').each(function (ind, elem) {
            $(this).removeClass('even odd').addClass(ind % 2 ? 'odd' : 'even');
        });
    }

    /**
     * Utility function to search if a given value exists as an option on the select
     * with the passed in id.
     * @param id the id of the select element
     * @param value the value sought as an option of the select
     */
    function selectValueFound(id, value) {
        var found = false;
        var options = document.getElementById(id).options;
        for (var i = 0; i < options.length; i++) {
            if (value === options[i].value) {
                found = true;
            }
        }
        return found;
    }

    //:~ Aggregate Metric Specs main panel

    /**
     * Reset and display the spec details panel, defaults to create mode.
     * @param {bool} true if edit mode, false (the default) for create mode
     */
    function showDetailsPanel(editMode) {
        clearSpecInputs();
        $('div#aggregateMetricSpecCreateEdit').show();
        $('#aggregateMetricSpecSaveLoading').hide();
        $('div#aggregateMetricSpecSaveState input').prop('disabled', false);
        if (editMode) {
            $('.aggregateMetricSpecCreate').hide();
            $('.aggregateMetricSpecComposition input').prop('disabled', true);
            $('.aggregateMetricSpecComposition select').prop('disabled', true);
            $('.aggregateMetricSpecEdit').show();
        } else {
            $('.aggregateMetricSpecComposition input').prop('disabled', false);
            $('.aggregateMetricSpecComposition select').prop('disabled', false);
            $('.aggregateMetricSpecCreate').show();
            $('.aggregateMetricSpecEdit').hide();
        }
    }

    /** Remove highlight from active row. */
    function unhighlightActiveRow() {
        $('#aggregateMetricSpecListTable tbody tr').each(function (ind, elem) {
            $(this).removeClass('activeRow');
        });
        $('#aggregateMetricSpecCreateEdit').hide();
        $('#aggregateMetricUserAlerts').hide();
    }

    /**
     * Highlight the row which houses the current action in the Aggregate Metric Spec table.
     * @param element actionLink the anchor element that was clicked.
     */
    function activateRow(actionLink) {
        var aggregateMetricSpecId = actionLink.data('aggregate_metric_spec_id');
        $('#aggregateMetricSpecEditId').val(aggregateMetricSpecId);
        unhighlightActiveRow();
        actionLink.closest('tr').addClass('activeRow');
    }

    /**
     * Add a row to the aggregateMetricSpec table for the passed in aggregateMetricSpec.
     * @param object aggregateMetricSpec with AggregateMetricSpecDao key value pairs
     */
    function addToSpecTable(aggregateMetricSpec) {

        function createActionLink(qtip, actionClass, imgPath, label) {
            var actionLink = '  <a qtip="'+qtip+'" class="actionLink aggregateMetricSpecAction '+actionClass+'" data-aggregate_metric_spec_id="' + aggregateMetricSpec.aggregate_metric_spec_id+'">';
            actionLink += '  <img class="actionLink" alt="" src="' + json.global.coreWebroot + imgPath +'" /> '+label+'</a>';
            return actionLink;
        }

        var row = '<tr class="aggregateMetricSpecRow"><td class="specName">' + aggregateMetricSpec.name + '</td><td><span class="actionsList">';
        row += createActionLink('Edit aggregate metric spec', 'editAggregateMetricSpec', '/public/images/icons/edit.png', 'Edit');
        row += createActionLink('Edit user notifications', 'editAggregateMetricSpecNotificationUsers', '/public/images/icons/email_error.png', 'Alerts');
        row += createActionLink('Remove aggregate metric spec', 'removeAggregateMetricSpec', '/public/images/icons/close.png', 'Delete');
        row += '</tr>';
        $('#aggregateMetricSpecListTable tbody').append(row);
        addClassesToTableRows('aggregateMetricSpecListTable');
    }

    /**
     * Update an existing aggregateMetricSpec in the spec table, with the properties
     * of the passed in aggergateMetricSpec, currently only updates the name.
     * @param aggregateMetricSpec object with AggregateMetricSpecDao key value pairs
     */
    function updateSpecInTable(aggregateMetricSpec) {
        var amsId = aggregateMetricSpec.aggregate_metric_spec_id;
        var name = aggregateMetricSpec.name;
        // Get the row of this spec in the table.
        var row = $("td").find("[data-aggregate_metric_spec_id='" + amsId + "']").first().closest('tr');
        // Get the first cell of that row and output the spec's name.
        $('td:first', row).text(name);
    }

    // Aggregate Metric Specs main panel handlers

    /**
     * Handler for Delete action, delete an Aggregate Metric Spec on the server
     * and remove it from the table. The handler is tied
     * to a static parent as the links can be dynamically generated through
     * creation of new aggregate metric specs.
     */
    $('#aggregateMetricSpecListTable').on('click', 'a.removeAggregateMetricSpec', function(){
        activateRow($(this));
        var specName = $(this).closest('tr').find('td.specName').html();
        var deleteConfirm = '<div id="amsDeleteConfirm" class="aggregateMetricSpecContent">';
        deleteConfirm += '<div id="toDeleteSpecName">Delete '+specName+'</div>';
        deleteConfirm += '</div>';
        $('#amsDeleteConfirmSaveState').show();
        $('#aggregateMetricSpecContent').prepend(deleteConfirm);
        $('#amsDeleteConfirm').css("height", $('#aggregateMetricSpecContent').height());
        $('#amsDeleteConfirm').css("width", $('#aggregateMetricSpecContent').width());

        var aggregateMetricSpecId = $(this).data('aggregate_metric_spec_id');
        var row = $(this).closest('tr');
        var sCb = function (data) {
            row.remove();
            $('#aggregateMetricDeleteLoading').hide();
            $('#amsDeleteConfirmSaveState input').prop('disabled', false);
            $('#amsDeleteConfirmSaveState').hide();
            addClassesToTableRows('aggregateMetricSpecListTable');
        };

        /** Handler for Confirm delete action, deletes the Spec and removes the deletion warning. */
        $('input#amsDeleteConfirmDelete').off('click').on('click', function() {
            $('#amsDeleteConfirm').remove();
            $('#amsDeleteConfirmSaveState input').prop('disabled', true);
            $('#aggregateMetricDeleteLoading').show();
            aggregatemetricspecRest('DELETE', aggregateMetricSpecId, null, sCb, null, null);
        });
    });

    /** Handler for Cancel delete action, removes the deletion warning. */
    $('input#amsDeleteCancelDelete').on('click', function() {
        $('#amsDeleteConfirm').remove();
        $('#amsDeleteConfirmSaveState').hide();
    });

    /** Handler for Add action, open the details panel in Create state. */
    $('div#addAggregateMetricSpec').click(function () {
        $('#amsDeleteConfirm').remove();
        unhighlightActiveRow();
        showDetailsPanel();
    });

    /**
     * Handler for Edit action, open the details panel in Edit state after
     * loading the details of the Aggregate Metric Spec.  The handler is tied
     * to a static parent as the links can be dynamically generated through
     * creation of new aggregate metric specs.
     */
    $('#aggregateMetricSpecListTable').on('click', 'a.editAggregateMetricSpec', function() {
        activateRow($(this));
        var aggregateMetricSpecId = $(this).data('aggregate_metric_spec_id');
        showDetailsPanel(true);
        $('#aggregateMetricSpecSaveLoading').show();
        var successCallback = function (aggregateMetricSpec) {
            populateSpecInputs(aggregateMetricSpec);
        }
        aggregatemetricspecRest('GET', aggregateMetricSpecId, null, successCallback);
    });

    /** Handler for Alerts button, show the panel to edit the alerted users. */
    $('#aggregateMetricSpecListTable').on('click', 'a.editAggregateMetricSpecNotificationUsers', function() {
        activateRow($(this));
        unhighlightActiveAlertRow();
        var amsName = $('td:first', $(this).closest('tr')).text();
        $('#aggregateMetricUserAlerts').show();
        var aggregateMetricSpecId = $(this).data('aggregate_metric_spec_id');
        $('#aggregateMetricUserAlertsSpecName').text(amsName);
        $('#aggregateMetricUserAlertsBranch').text('');
        $('#aggregateMetricSpecAlertValue').val('');
        $('#aggregateMetricSpecAlertComparison').val('');
        $('#aggregateMetricUserAlertsSpec').val('');
        $('#aggregateMetricSpecAlertedUsers').find('tr:gt(0)').remove();
        $('#aggregateMetricNotificationsTable').find('tr:gt(0)').remove();
        $('#aggregateMetricNotificationRemoveLoading').show();
        $('#addAlertUserSearch').val('Start typing a name or email address...');
        $('#addAlertUserSearchValue').val('init');
        var successCallback = function (aggregateMetricSpec) {
            $('#aggregateMetricUserAlertsSpecName').text(aggregateMetricSpec.name);
            $('#aggregateMetricUserAlertsSpec').val(aggregateMetricSpec.spec);

            var jsonMethod = 'midas.tracker.aggregatemetricspecnotifications.list';
            var args = 'aggregateMetricSpecId=' + aggregateMetricSpecId;
            callAjaxWebApi(jsonMethod, 'GET', args, function (retVal) {
                activeSpecNotifications = {};
                for (var notifInd = 0; notifInd < retVal.data.length; notifInd++) {
                    var notification = retVal.data[notifInd];
                    addToNotificationTable(notification.notification);
                    activeSpecNotifications[notification.notification.aggregate_metric_notification_id] = notification;
                }
                addClassesToTableRows('aggregateMetricNotificationsTable');
                $('#aggregateMetricNotificationRemoveLoading').hide();
            });
        };
        aggregatemetricspecRest('GET', aggregateMetricSpecId, null, successCallback);
    });

    //:~ Spec Details Panel

    /**
     * Save an aggregateMetricSpec, either as a new Dao or update an existing one,
     * depending on whether aggregateMetricSpecId is passed, will perform validation
     * on input fields before saving, and will update or add the spec to the spec table.
     * @param aggregateMetricSpecId if passed the id of an existing spec to update
     */
    function saveAggregateMetricSpec(aggregateMetricSpecId) {
        $('div#aggregateMetricSpecSaveState input').prop('disabled', true);
        $('#aggregateMetricSpecValidationError').text('');
        var specValues = getSpecInputsValues();

        // Validate the inputs.
        // Order matters, so return after the first invalid field found.
        var requiredFields = [
            {'value': specValues.aggregateMetricSpec.name, 'name': 'Name'},
            {'value': specValues.specInputs.metricName, 'name': 'Metric name'},
            {'value': specValues.specInputs.aggregateMetric, 'name': 'Aggregate metric'},
            {'value': specValues.specInputs.param, 'name': 'Param (percentile)'}
        ];
        for (var i = 0; i < requiredFields.length; i++) {
            var value = requiredFields[i].value;
            var name = requiredFields[i].name;
            if (!value || value === '') {
                $('#aggregateMetricSpecValidationError').text(name + ' is a required field');
                $('div#aggregateMetricSpecSaveState input').prop('disabled', false);
                return;
            }
        }
        var param = specValues.specInputs.param;
        if (!$.isNumeric(param) || param < 0 || param > 100) {
            $('#aggregateMetricSpecValidationError').text('Param (percentile) must be >= 0 and <= 100');
            $('div#aggregateMetricSpecSaveState input').prop('disabled', false);
            return;
        }

        // Save the AMS on the server.
        $('#aggregateMetricSpecSaveLoading').show();
        var successCallback = function (aggregateMetricSpec) {
            if (aggregateMetricSpecId) {
                updateSpecInTable(aggregateMetricSpec);
            } else {
                addToSpecTable(aggregateMetricSpec);
            }
            $('div#aggregateMetricSpecCreateEdit').hide();
            unhighlightActiveRow();
        }
        var method = aggregateMetricSpecId ? 'PUT' : 'POST';
        aggregateMetricSpecId = aggregateMetricSpecId ? aggregateMetricSpecId : null;
        aggregatemetricspecRest(method, aggregateMetricSpecId, specValues.aggregateMetricSpec, successCallback);
    }

    /** Clear all spec inputs of any value. */
    function clearSpecInputs() {
        $('.amsField').val('');
        $('#aggregateMetricSpecValidationError').text('');
        $('#aggregateMetricSpecSpec').val('');
        $('#aggregateMetricSpecMetricName option:disabled').attr('selected', 'selected');
    }

    /** Update display of disabled composite spec input from individual elements. */
    function updateSpec() {
        var metricName = $('#aggregateMetricSpecMetricName').val();
        var metric = $('#aggregateMetricSpecAggregateMetric').val();
        var param = $('#aggregateMetricSpecParam').val();
        $('#aggregateMetricSpecSpec').val(metric + "('" + metricName + "', " + param + ")");
    }

    /**
     * Parse individual elements from composite spec string.
     * @param spec string containing the aggregeate metric spec
     */
    function parseMetricSpec(spec) {
        // Expected to be like:
        // percentile('Optimal distance', 95)
        var specParts = /(.*)\('(.*)',\s*(.*)\)/.exec(spec);
        var specParts = {
            'metricName': specParts[2],
            'metric': specParts[1],
            'param': specParts[3]
        };
        return specParts;
    }

    /**
     * Return an object with the current input values from the spec details panel,
     * namespaced as 'aggregateMetricSpec' for those key-values directly
     * from the AggregateMetricSpecDao and 'specInputs' for those input
     * values contributing to the value of aggregateMetricSpec.spec.
     *
     * @return {object} with namespaces 'aggregateMetricSpec' and 'specInputs'
     */
    function getSpecInputsValues() {
        var specValues = {
            'aggregateMetricSpec': {
                'producer_id': $('#producerId').val(),
                'name': $('#aggregateMetricSpecName').val(),
                'description': $('#aggregateMetricSpecDescription').val(),
                'spec': $('#aggregateMetricSpecSpec').val(),
            },
            'specInputs': {
                'metricName': $('#aggregateMetricSpecMetricName').val(),
                'aggregateMetric': $('#aggregateMetricSpecAggregateMetric').val(),
                'param': $('#aggregateMetricSpecParam').val()
            }
        };
        return specValues;
    }

    /**
     * Populate the input elements with the values from the passed in aggregateMetricSpec,
     * including some validation in case the passed in spec was created out of data that is
     * no longer valid.
     * @param aggregateMetricSpec object with AggregateMetricSpecDao key value pairs
     */
    function populateSpecInputs(aggregateMetricSpec) {
        $('#aggregateMetricSpecEditId').val(aggregateMetricSpec.aggregate_metric_spec_id);
        $('#aggregateMetricSpecName').val(aggregateMetricSpec.name);
        $('#aggregateMetricSpecDescription').val(aggregateMetricSpec.description);
        $('#aggregateMetricSpecSpec').val(aggregateMetricSpec.spec);
        var specParts = parseMetricSpec(aggregateMetricSpec.spec);
        $('#aggregateMetricSpecParam').val(specParts.param);
        $('#aggregateMetricSpecAggregateMetric').val(specParts.metric);
        var metricNameFound = selectValueFound('aggregateMetricSpecMetricName', specParts.metricName);
        if (!metricNameFound) {
            $('#aggregateMetricSpecValidationError').text("Loaded metric name '"+specParts.metricName+"' is invalid");
        } else {
            $('#aggregateMetricSpecMetricName').val(specParts.metricName);
        }
        $('#aggregateMetricSpecSaveLoading').hide();
    }

    // Spec Details panel handlers

    /** Handler for the metric name select. */
    $('select#aggregateMetricSpecMetricName').change(function () {
        updateSpec();
    });

    /** Handler for aggregate metric select. */
    $('select#aggregateMetricSpecAggregateMetric').change( function () {
        updateSpec();
    });

    /** Handler for aggregate metric param change. */
    $('input#aggregateMetricSpecParam').on('keyup change', function () {
        updateSpec();
    });

    // Spec Details panel button handlers

    /**
     * Handler for Update button:
     *     update the spec,
     *     adjust its name in the spec listing table,
     *     hide the details panel.
     */
    $('input#aggregateMetricSpecUpdate').click(function () {
        saveAggregateMetricSpec($('#aggregateMetricSpecEditId').val());
    });

    /**
     * Handler for Create button:
     *     create the new spec,
     *     add it to the spec listing table,
     *     hide the details panel.
     */
    $('input#aggregateMetricSpecCreate').click(function () {
        saveAggregateMetricSpec();
    });

    /** Handler for Spec Details Cancel button, hide the details panel. */
    $('input#aggregateMetricSpecCancel').click(function () {
        $('div#aggregateMetricSpecCreateEdit').hide();
        unhighlightActiveRow();
    });

    //:~ Notification/Alerts panel

    /**
     * Add a row to the notifications table for a given spec, based on the
     * passed in AggregateMetricNotification object.
     * @param aggregateMetricNotification object with AggregateMetricNotificationDao key value pairs
     */
    function addToNotificationTable(notification) {

        var notificationId = notification.aggregate_metric_notification_id;
        function createActionLink(qtip, actionClass, imgPath, label) {
            var actionLink = '  <a qtip="'+qtip+'" class="actionLink notificationAction '+actionClass+'" data-aggregate_metric_notification_id="' + notificationId+'">';
            actionLink += '  <img class="actionLink" alt="" src="' + json.global.coreWebroot + imgPath +'" /> '+label+'</a>';
            return actionLink;
        }

        var row = '<tr id=><td id="amnBranch'+notificationId+'" class="aggregateMetricNotificationBranch">' + notification.branch + '</td>';
        row += '<td id="amnComparison'+notificationId+'" class="aggregateMetricNotificationComparison">' + notification.comparison + '</td>';
        row += '<td id="amnValue'+notificationId+'" class="aggregateMetricNotificationValue">' + notification.value + '</td><td><span class="actionsList">';
        row += createActionLink('Edit notification', 'editAggregateMetricNotification', '/public/images/icons/edit.png', 'Edit');
        row += createActionLink('Edit users', 'editAggregateMetricNotificationUsers', '/public/images/icons/email_error.png', 'Users');
        row += createActionLink('Remove notification', 'removeAggregateMetricNotification', '/public/images/icons/close.png', 'Delete');
        row += '</tr>';
        $('#aggregateMetricNotificationsTable tbody').append(row);
     }

    /** Remove highlight from active notification row. */
    function unhighlightActiveAlertRow() {
        $('#aggregateMetricNotificationsTable tbody tr').each(function (ind, elem) {
            $(this).removeClass('activeRow');
        });
        $('#aggregateMetricNotificationEdit').hide();
        $('#aggregateMetricNotificationUsers').hide();
        $('#aggregateMetricNotificationCancel').show();
    }

    /**
     * Highlight the alert row which houses the current action in the Notifications table,
     * sets the activeNotification closure variable to the alert in the row.
     * @param element actionLink the anchor element that was clicked.
     */
    function activateAlertRow(actionLink) {
        unhighlightActiveAlertRow();
        $('#aggregateMetricNotificationValidationError').text('');
        actionLink.closest('tr').addClass('activeRow');
        $('#aggregateMetricNotificationCancel').hide();
        var notificationId = actionLink.data('aggregate_metric_notification_id');
        activeNotification = activeSpecNotifications[notificationId];
    }

    /**
     * Validates the current notification input fields, whether for an edited
     * notification or a new one.
     * @return bool indicating whether validation succeeded.
     */
    function validateNotification() {
        var branch = $('#aggregateMetricNotificationBranch').val();
        if (branch === null || branch === '') {
            $('#aggregateMetricNotificationValidationError').text('branch cannot be empty');
            return false;
        }
        var comparison = $('#aggregateMetricNotificationComparison').val();
        if (comparison === null || comparison === '') {
            $('#aggregateMetricNotificationValidationError').text('comparison cannot be empty');
            return false;
        }
        var value = $('#aggregateMetricNotificationValue').val();
        if (value === null || value === '' || !$.isNumeric(value)) {
            $('#aggregateMetricNotificationValidationError').text('value must be numeric');
            return false;
        }
        $('#aggregateMetricNotificationValidationError').text('');
        return true;
    }

    /**
     * Deactivate the current notification and hide any notification details panels.
     */
    function hideNotificationDetailPanel() {
        $('.editAlert').hide();
        $('.createAlert').hide();
        $('#aggregateMetricNotificationSaveLoading').hide();
        $('div#aggregateMetricNotificationSaveState input').prop('disabled', false);
        unhighlightActiveAlertRow();
        $('#aggregateMetricNotificationEdit').hide();
        $('#aggregateMetricNotificationCancel').show();
    }

    // Notification panel handlers
    // Notifications and Alerts are considered equivalent, "Alert" is shorter

    /**
     * Handler for edit notification users action, activates the notification
     * and loads any users tied to the notification to display them in the
     * users table.
     * The handler is tied to a static parent as the links can be
     * dynamically generated through creation of new alerts.
     */
     $('#aggregateMetricNotificationsTable').on('click', 'a.editAggregateMetricNotificationUsers', function() {
        activateAlertRow($(this));
        $('#aggregateMetricNotificationUsers').show();
        $('#aggregateMetricSpecAlertedUsers').find('tr:gt(0)').remove();
        for(var userInd = 0; userInd < activeNotification.users.length; userInd += 1) {
            var user = activeNotification.users[userInd];
            addToAlertedUsersTable(user.firstname +' '+user.lastname, user.user_id);
        }
        addClassesToTableRows('aggregateMetricSpecAlertedUsers');
    });

    /**
     * Handler for edit notification action, activates the notification
     * and loads the notification into fields such that it can be edited.
     * The handler is tied to a static parent as the links can be
     * dynamically generated through creation of new alerts.
     */
     $('#aggregateMetricNotificationsTable').on('click', 'a.editAggregateMetricNotification', function() {
        activateAlertRow($(this));
        $('#aggregateMetricNotificationEdit').show();
        $('#aggregateMetricNotificationBranch').val(activeNotification.notification.branch);
        $('#aggregateMetricNotificationValue').val(activeNotification.notification.value);
        $('#aggregateMetricNotificationComparison').val(activeNotification.notification.comparison);
        $('#aggregateMetricNotificationCreate').hide();
        $('#aggregateMetricNotificationUpdate').show();
        $('.editAlert').show();
        $('.createAlert').hide();
    });

    /**
     * Handler for Remove notification action, delete the notification and
     * any associated alerted users.
     * The handler is tied to a static parent as the links can be
     * dynamically generated through creation of new alerts.
     */
    $('#aggregateMetricNotificationsTable').on('click', 'a.removeAggregateMetricNotification', function() {
        hideNotificationDetailPanel();
        var row = $(this).closest('tr');
        activateAlertRow($(this));
        var notificationId = $(this).data('aggregate_metric_notification_id');
        $('#aggregateMetricNotificationRemoveLoading').show();
        aggregatemetricnotificationRest('DELETE', notificationId, {}, function (retVal) {
            row.remove();
            $('#aggregateMetricNotificationRemoveLoading').hide();
            delete activeSpecNotifications[notificationId];
            unhighlightActiveAlertRow();
            addClassesToTableRows('aggregateMetricNotificationsTable');
        });
    });

    /**
     * Handler for adding a new notification to the active Spec.
     */
    $('#addAggregateMetricNotification').click(function () {
        unhighlightActiveAlertRow();
        $('#aggregateMetricNotificationUpdate').hide();
        $('#aggregateMetricNotificationBranch').val('');
        $('#aggregateMetricNotificationComparison').val('');
        $('#aggregateMetricNotificationValue').val('');
        $('#aggregateMetricNotificationCreate').show();
        $('#aggregateMetricNotificationEdit').show();
        $('div#aggregateMetricNotificationSaveState input').prop('disabled', false);
        $('#aggregateMetricNotificationValidationError').text('');
        $('#aggregateMetricNotificationSaveState').show();
        $('#aggregateMetricNotificationCancel').hide();
        $('.editAlert').hide();
        $('.createAlert').show();
    });

    // Notification panel button handlers

    /**
     * Handler for Cancel button:
     *     hide any notification detail panel and de-activate any notification row.
     */
    $('#aggregateMetricNotificationCancel').click(function () {
        $('#aggregateMetricUserAlerts').hide();
        unhighlightActiveAlertRow();
        unhighlightActiveRow();
        hideNotificationDetailPanel();
    });

    /**
     * Update the active notification with the input fields.
     */
    $('input#aggregateMetricNotificationUpdate').click(function () {
        if (validateNotification()) {
            $('div#aggregateMetricNotificationSaveState input').prop('disabled', true);
            $('#aggregateMetricNotificationSaveLoading').show();
            var notificationUpdates = {
                branch: $('#aggregateMetricNotificationBranch').val(),
                comparison: $('#aggregateMetricNotificationComparison').val(),
                value: $('#aggregateMetricNotificationValue').val()
            };
            var notificationId = activeNotification.notification.aggregate_metric_notification_id;
            aggregatemetricnotificationRest('PUT', notificationId, notificationUpdates, function (retVal) {
                activeSpecNotifications[notificationId].notification = retVal;
                $('#aggregateMetricNotificationsTable td#amnBranch'+notificationId).html(retVal.branch);
                $('#aggregateMetricNotificationsTable td#amnValue'+notificationId).html(retVal.value);
                $('#aggregateMetricNotificationsTable td#amnComparison'+notificationId).html(retVal.comparison);
                hideNotificationDetailPanel();
            });
        }
    });

    /**
     * Cancel editing or creating a notification.
     */
    $('#aggregateMetricNotificationSaveCancel').click(function () {
        hideNotificationDetailPanel();
    });

    /**
     * Create a new notification with the input fields tied to the active Spec.
     */
    $('input#aggregateMetricNotificationCreate').click(function () {
        if (validateNotification()) {
            $('div#aggregateMetricNotificationSaveState input').prop('disabled', true);
            var notificationProperties = {
                aggregate_metric_spec_id: $('#aggregateMetricSpecEditId').val(),
                branch: $('#aggregateMetricNotificationBranch').val(),
                comparison: $('#aggregateMetricNotificationComparison').val(),
                value: $('#aggregateMetricNotificationValue').val()
            };
            $('#aggregateMetricNotificationSaveLoading').show();
            aggregatemetricnotificationRest('POST', null, notificationProperties, function (retVal) {
                hideNotificationDetailPanel();
                addToNotificationTable(retVal);
                activeSpecNotifications[retVal.aggregate_metric_notification_id] = {
                    notification: retVal,
                    users: []
                };
                addClassesToTableRows('aggregateMetricNotificationsTable');
            });
        }
    });

    //:~ Alerted Users panel

    /**
     * Add a row to the alertedUsers table for the passed in user.
     * @param string userName
     * @param string userId
     */
    function addToAlertedUsersTable(userName, userId) {

        function createActionLink(qtip, actionClass, imgPath, label) {
            var actionLink = '  <a qtip="'+qtip+'" class="actionLink alertedUsersAction '+actionClass+'" data-user_id="' + userId+'">';
            actionLink += '  <img class="actionLink" alt="" src="' + json.global.coreWebroot + imgPath +'" /> '+label+'</a>';
            return actionLink;
        }

        var row = '<tr><td class="userName">' + userName + '</td><td><span class="actionsList">';
        row += createActionLink('Remove user from alerts', 'removeAlertedUser', '/public/images/icons/close.png', 'Remove alert');
        row += '</tr>';
        $('#aggregateMetricSpecAlertedUsers tbody').append(row);
    }

    // Alerted Users panel handlers

    /**
     * Handler for Remove alerted user action, delete the user from being
     * alerted.  The handler is tied to a static parent as the links can be
     * dynamically generated through creation of new alerts.
     */
    $('#aggregateMetricSpecAlertedUsers').on('click', 'a.removeAlertedUser', function() {
        var row = $(this).closest('tr');
        row.addClass('activeRow');
        $('#aggregateMetricSpecAlertsLoading').show();
        var userId = $(this).data('user_id');
        var jsonMethod = 'midas.tracker.aggregatemetricspecnotifieduser.delete';
        var args = 'aggregateMetricNotificationId=' + activeNotification.notification.aggregate_metric_notification_id + '&userId=' + userId;
        callAjaxWebApi(jsonMethod, 'POST', args, function (retVal) {
            if (retVal.data && retVal.data.user_id == userId) {
                row.remove();
                addClassesToTableRows('aggregateMetricSpecAlertedUsers');
                $('#aggregateMetricSpecAlertsLoading').hide();
            } else {
                midas.createNotice('Unexpected return value, check error console', 3000, 'error');
                console.error(retVal);
            }
        });
    });

    // Live search for users

    $.widget('custom.catcomplete', $.ui.autocomplete, {
        _renderMenu: function (ul, items) {
            'use strict';
            var self = this,
                currentCategory = '',
                userIds = {};

            $('#aggregateMetricSpecAlertedUsers .actionsList').children('a').each(function (ind, elem) {
                var userId = $(elem).data('user_id');
                userIds[userId] = userId;
            });

            $.each(items, function (index, item) {
                if (userIds[item.userid]) {
                    // Don't show a user in the list if they are already alerted.
                    return;
                }
                if (item.category != currentCategory) {
                    ul.append('<li class="search-category">' + item.category + '</li>');
                    currentCategory = item.category;
                }
                self._renderItemData(ul, item);
            });
        }
    });

    var alertUserSearchCache = {},
        lastAlertUserShareXhr;
    $('#addAlertUserSearch').catcomplete({
        minLength: 2,
        delay: 10,
        source: function (request, response) {
            'use strict';
            var term = request.term;
            if (term in alertUserSearchCache) {
                response(alertUserSearchCache[term]);
                return;
            }
            $('#aggregateMetricSpecAlertsLoading').show();

            lastAlertUserShareXhr = $.getJSON($('.webroot').val() + '/search/live?userSearch=true&allowEmail',
                request, function (data, status, xhr) {
                    $('#aggregateMetricSpecAlertsLoading').hide();
                    alertUserSearchCache[term] = data;
                    if (xhr === lastAlertUserShareXhr) {
                        response(data);
                    }
                });
        }, // end source
        select: function (event, ui) {
            'use strict';
            $('#aggregateMetricSpecAlertsLoading').show();
            var userId = ui.item.userid;
            var userName = ui.item.value;
            var jsonMethod = 'midas.tracker.aggregatemetricspecnotifieduser.create';
            var args = 'aggregateMetricNotificationId=' + activeNotification.notification.aggregate_metric_notification_id + '&userId=' + userId;
            callAjaxWebApi(jsonMethod, 'POST', args, function (retVal) {
                if (retVal.data && retVal.data.user_id == userId) {
                    activeNotification.users.push(retVal.data);
                    addToAlertedUsersTable(userName, userId);
                    addClassesToTableRows('aggregateMetricSpecAlertedUsers');
                    $('#addAlertUserSearchValue').val('init');
                    $('#aggregateMetricSpecAlertsLoading').hide();
                } else {
                    midas.createNotice('Unexpected return value, check error console', 3000, 'error');
                    console.error(retVal);
                }
            });
        } // end select
    });

    $('#addAlertUserSearch').focus(function () {
        'use strict';
        if ($('#addAlertUserSearchValue').val() == 'init') {
            $('#addAlertUserSearchValue').val($(this).val());
            $(this).val('');
        }
    }).focusout(function () {
        'use strict';
        if ($(this).val() == '') {
            $(this).val($('#addAlertUserSearchValue').val());
            $('#addAlertUserSearchValue').val('init');
        }
    });

    // Alerted Users panel button handlers

    /** Handler for Alerts Users Done button, hide the alerts panel. */
    $('input#aggregateMetricSpecUserAlertsDone').click(function () {
        hideNotificationDetailPanel();
    });

    // Initialize the dialog now that it is loaded.

    // Parse json content
    // jQuery 1.8 has weird bugs when using .html() here, use the old-style innerHTML here
    var trackerJson = $.parseJSON($('div.trackerJsonContent')[0].innerHTML);

    // Create table rows.
    for (var amsInd = 0; amsInd < trackerJson.aggregateMetricSpecs.length; amsInd++) {
        addToSpecTable(trackerJson.aggregateMetricSpecs[amsInd]);
    }
    // Initialize the table row classess.
    addClassesToTableRows('aggregateMetricSpecListTable');
});
