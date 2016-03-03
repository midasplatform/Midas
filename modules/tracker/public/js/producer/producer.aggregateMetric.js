// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global ajaxWebApi */
/* global json */

var midas = midas || {};

$(document).ready(function () {
    'use strict';

    function addClassesToSpecTableRows() {
        $('#aggreagteMetricSpecListTable tbody tr').each(function (ind, elem) {
            // Set the first (0th) row to be odd, 'even' and 'odd' are switched from their
            // expected places.
            $(this).removeClass('even odd').addClass(ind % 2 ? 'even' : 'odd');
        });
    }

    // Initialize the table rows.
    addClassesToSpecTableRows();

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
        var url = json.global.webroot + '/rest/tracker/aggregatemetricspec';
        if (aggregateMetricSpecId) {
            url += '/' + aggregateMetricSpecId;
        }
        url += '?useSession=true';

        var restCall = {
            url: url,
            type: method,
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
     * Displays the loading image, updates the branch list based on
     * the current metric name, then hides the loading image.
     * @param successCallback callback on success, not passed any value
     */
    function updateBranchList(successCallback) {
        var producerId = $('#producerId').val();
        var metricName = $('select#aggregateMetricSpecMetricName').val();
        $('img#aggregateMetricSpecSaveLoading').show();
        ajaxWebApi.ajax({
            method: 'midas.tracker.branchesformetricname.list',
            args: 'producerId=' + producerId + '&trendMetricName='+metricName,
            success: function (retVal) {
                $('#aggregateMetricSpecBranch').find('option').remove();
                var branches = retVal.data;
                $.each(branches, function (key, value) {
                    $('#aggregateMetricSpecBranch').append('<option value="'+value+'">' + value + '</option>');
                });
                $('img#aggregateMetricSpecSaveLoading').hide();
                if (successCallback) { successCallback(); }
            },
            error: function (retVal) {
                midas.createNotice(retVal.message, 3000, 'error');
            },
            complete: function () {},
            log: $('<p></p>')
        });
    }

    /** Clear all spec inputs of any value. */
    function clearSpecInputs() {
        $('.amsField').val('');
        $('#aggregateMetricSpecValidationError').text('');
        $('#aggregateMetricSpecSpec').val('');
        $('#aggregateMetricSpecMetricName option:disabled').attr('selected', 'selected');
        $('#aggregateMetricSpecComparison option:disabled').attr('selected', 'selected');
        // Remove branches, add the placeholder.
        $('#aggregateMetricSpecBranch').find('option').remove();
        $('#aggregateMetricSpecBranch').append('<option disabled selected value>  -- select a branch -- </option>');
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
     * Reset and display the spec details panel, defaults to create mode.
     * @param {bool} true if edit mode, false (the default) for create mode
     */
    function showDetailsPanel(editMode) {
        clearSpecInputs();
        $('div#aggregateMetricSpecCreateEdit').show();
        $('img#aggregateMetricSpecSaveLoading').hide();
        $('div#aggregateMetricSpecSaveState input').prop('disabled', false);
        if (editMode) {
            $('.aggregateMetricSpecCreate').hide();
            $('.aggregateMetricSpecEdit').show();
        } else {
            $('.aggregateMetricSpecCreate').show();
            $('.aggregateMetricSpecEdit').hide();
        }
    }

    /**
     * Add a row to the aggregateMetricSpec table for the passed in aggregateMetricSpec.
     * @param aggregateMetricSpec object with AggregateMetricSpecDao key value pairs
     */
    function addToSpecTable(aggregateMetricSpec) {
        var row = '<tr><td class="specName">' + aggregateMetricSpec.name + '</td>';
        row += '<td><span class="actionsList">';
        row += '  <a qtip="Edit aggregate metric spec" class="tableActions aggregateMetricSpecAction editAggregateMetricSpec" data-aggregate_metric_spec_id="' + aggregateMetricSpec.aggregate_metric_spec_id+'">';
        row += '  <img class="tableActions" alt="" src="' + json.global.coreWebroot + '/public/images/icons/edit.png" /> Edit</a>';
        row += '  <a qtip="Remove aggregate metric spec" class="tableActions aggregateMetricSpecAction removeAggregateMetricSpec" data-aggregate_metric_spec_id="' + aggregateMetricSpec.aggregate_metric_spec_id + '">';
        row += '    <img class="tableActions" alt="" src="' + json.global.coreWebroot + '/public/images/icons/close.png" /> Delete</a>';
        row += '</tr>';
        $('#aggreagteMetricSpecListTable tbody').append(row);
        addClassesToSpecTableRows();
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

    /**
     * Return an object with of the current input values from the spec details panel,
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
                'branch': $('#aggregateMetricSpecBranch').val(),
                'spec': $('#aggregateMetricSpecSpec').val(),
                'value': $('#aggregateMetricSpecValue').val(),
                'comparison': $('#aggregateMetricSpecComparison').val()
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

    /**
     * Populate the input elements with the values from the passed in aggregateMetricSpec,
     * including some validation in case the passed in spec was created out of data that is
     * no longer valid, e.g. a branch name that no longer has scalars tied to the metric_name.
     * @param aggregateMetricSpec object with AggregateMetricSpecDao key value pairs
     */
    function populateSpecInputs(aggregateMetricSpec) {
        $('#aggregateMetricSpecEditId').val(aggregateMetricSpec.aggregate_metric_spec_id);
        $('#aggregateMetricSpecName').val(aggregateMetricSpec.name);
        $('#aggregateMetricSpecDescription').val(aggregateMetricSpec.description);
        $('#aggregateMetricSpecSpec').val(aggregateMetricSpec.spec);
        // Skip value if it is 0 and comparison is empty, this was added as a DB
        // default and wouldn't be allowed by the UI validation logic.
        if (aggregateMetricSpec.value &&
            (aggregateMetricSpec.value != 0 || aggregateMetricSpec.comparison)) {
            $('#aggregateMetricSpecValue').val(aggregateMetricSpec.value);
        }
        if (aggregateMetricSpec.comparison) {
            $('#aggregateMetricSpecComparison').val(aggregateMetricSpec.comparison);
        }
        var specParts = parseMetricSpec(aggregateMetricSpec.spec);
        $('#aggregateMetricSpecParam').val(specParts.param);
        $('#aggregateMetricSpecAggregateMetric').val(specParts.metric);
        var metricNameFound = selectValueFound('aggregateMetricSpecMetricName', specParts.metricName);
        if (!metricNameFound) {
            // Don't set the branch name because the metric name is invalid,
            // and that determines the set of possible branches.
            $('#aggregateMetricSpecValidationError').text("Loaded metric name '"+specParts.metricName+"' is invalid");
            $('img#aggregateMetricSpecSaveLoading').hide();
        } else {
            $('#aggregateMetricSpecMetricName').val(specParts.metricName);
            // Don't need to hide the loading image because updateBranchList will.
            var successCallback = function () {
                var branchNameFound = selectValueFound('aggregateMetricSpecBranch', aggregateMetricSpec.branch);
                if (!branchNameFound) {
                    $('#aggregateMetricSpecValidationError').text("Loaded branch '"+aggregateMetricSpec.branch+"' is invalid");
                } else {
                    $('#aggregateMetricSpecBranch').val(aggregateMetricSpec.branch);
                }
            };
            updateBranchList(successCallback);
        }
    }

    /**
     * Handler for Delete action, delete an Aggregate Metric Spec on the server
     * and remove it from the table. The handler is tied
     * to a static parent as the links can be dynamically generated through
     * creation of new aggregate metric specs.
     */
    $('#aggreagteMetricSpecListTable').on('click', 'a.removeAggregateMetricSpec', function(){
        var aggregateMetricSpecId = $(event.target).data('aggregate_metric_spec_id');
        var row = $(event.target).closest('tr');
        var sCb = function (data) {
            row.remove();
            $('#aggregateMetricSpecDeleteLoading').hide();
            addClassesToSpecTableRows();
        };
        $('#aggregateMetricSpecDeleteLoading').show();
        aggregatemetricspecRest('DELETE', aggregateMetricSpecId, null, sCb, null, null);
    });

    /** Handler for Add action, open the details panel in Create state. */
    $('div#addAggregateMetricSpec').click(function () {
        showDetailsPanel();
    });

    /**
     * Handler for Edit action, open the details panel in Edit state after
     * loading the details of the Aggregate Metric Spec.  The handler is tied
     * to a static parent as the links can be dynamically generated through
     * creation of new aggregate metric specs.
     */
    $('#aggreagteMetricSpecListTable').on('click', 'a.editAggregateMetricSpec', function(){
        var aggregateMetricSpecId = $(event.target).data('aggregate_metric_spec_id');
        showDetailsPanel(true);
        $('img#aggregateMetricSpecSaveLoading').show();
        var successCallback = function (aggregateMetricSpec) {
            populateSpecInputs(aggregateMetricSpec);
        }
        aggregatemetricspecRest('GET', aggregateMetricSpecId, null, successCallback);
    });

    /** Handler for Cancel button, hide the details panel. */
    $('input#aggregateMetricSpecCancel').click(function () {
        $('div#aggregateMetricSpecCreateEdit').hide();
    });

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
            {'value': specValues.aggregateMetricSpec.branch, 'name': 'Branch'},
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
        // Comparison and valid must be empty or not together.
        var comparisonEmpty = (!specValues.aggregateMetricSpec.comparison || specValues.aggregateMetricSpec.comparison === '');
        var valueEmpty = (!specValues.aggregateMetricSpec.value || specValues.aggregateMetricSpec.value === '');
        if (comparisonEmpty !== valueEmpty) {
            $('#aggregateMetricSpecValidationError').text('Comparison and value must be set together');
            $('div#aggregateMetricSpecSaveState input').prop('disabled', false);
            return;
        } else if (!valueEmpty && !$.isNumeric(specValues.aggregateMetricSpec.value)) {
            // The case where comparison and value are provided.
            $('#aggregateMetricSpecValidationError').text('Value must be numeric');
            $('div#aggregateMetricSpecSaveState input').prop('disabled', false);
            return;
        }

        // Save the AMS on the server.
        $('img#aggregateMetricSpecSaveLoading').show();
        var successCallback = function (aggregateMetricSpec) {
            if (aggregateMetricSpecId) {
                updateSpecInTable(aggregateMetricSpec);
            } else {
                addToSpecTable(aggregateMetricSpec);
            }
            $('div#aggregateMetricSpecCreateEdit').hide();
        }
        var method = aggregateMetricSpecId ? 'PUT' : 'POST';
        aggregateMetricSpecId = aggregateMetricSpecId ? aggregateMetricSpecId : null;
        aggregatemetricspecRest(method, aggregateMetricSpecId, specValues.aggregateMetricSpec, successCallback);
    }

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

    /** Handler for the metric name select. */
    $('select#aggregateMetricSpecMetricName').change(function () {
        updateSpec();
        updateBranchList();
    });

    /** Handler for aggregate metric select. */
    $('select#aggregateMetricSpecAggregateMetric').change( function () {
        updateSpec();
    });

    /** Handler for aggregate metric param change. */
    $('input#aggregateMetricSpecParam').on('keyup change', function () {
        updateSpec();
    });
});
