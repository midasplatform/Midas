// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global ajaxWebApi */
/* global json */

var midas = midas || {};

$(document).ready(function () {
    'use strict';
// TODO style selects from round

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

    /** TODO */
    function addToSpecTable(aggregateMetricSpec) {
        var row = '<tr><td class="specName">' + aggregateMetricSpec.name + '</td>';
        row += '<td><span class="actionsList">';
        row += '  <a qtip="Edit aggregate metric spec" class="tableActions aggregateMetricSpecAction editAggregateMetricSpec" data-aggregate_metric_spec_id="' + aggregateMetricSpec.aggregate_metric_spec_id+'">';
        row += '  <img class="tableActions" alt="" src="' + json.global.coreWebroot + '/public/images/icons/edit.png" /> Edit</a>';
        row += '  <a qtip="Remove aggregate metric spec" class="tableActions aggregateMetricSpecAction removeAggregateMetricSpec" data-aggregate_metric_spec_id="' + aggregateMetricSpec.aggregate_metric_spec_id + '">';
        row += '    <img class="tableActions" alt="" src="' + json.global.coreWebroot + '/public/images/icons/close.png" /> Delete</a>';
        row += '</tr>';
        // TODO style
        $('#aggreagteMetricSpecListTable tr:last').after(row);
    }

    /**
     * Return an object with of the current input values from the spec details panel,
     * namespaced as 'aggregateMetricSpec' for those key-values directly
     * from the AggregateMetricSpecDao and 'specInputs' for those input
     * values contributing to the value of aggregateMetricSpec.spec.
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

    /** TODO */
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
     * TODO
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
            // TODO CHECK loading invalid and then trying to save, once save handler works.
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

    /** Handler for Delete action. */
    $('#aggreagteMetricSpecListTable').on('click', 'a.removeAggregateMetricSpec', function(){
        var aggregateMetricSpecId = $(event.target).data('aggregate_metric_spec_id');
        console.log(aggregateMetricSpecId);
        var row = $(event.target).closest('tr');
        var sCb = function (data) {
            row.remove();
            // TODO adjust rows after for style
            $('#aggregateMetricSpecDeleteLoading').hide();
        };
        $('#aggregateMetricSpecDeleteLoading').show();
        aggregatemetricspecRest('DELETE', aggregateMetricSpecId, null, sCb, null, null);
    });


    /** Handler for Add action, open the details panel in Create state. */
    $('div#addAggregateMetricSpec').click(function () {
        showDetailsPanel();
        // TODO ensure edit and create don't both show up
    });

    /** TODO */
    $('#aggreagteMetricSpecListTable').on('click', 'a.editAggregateMetricSpec', function(){
        var aggregateMetricSpecId = $(event.target).data('aggregate_metric_spec_id');
        showDetailsPanel(true);
        $('img#aggregateMetricSpecSaveLoading').show();
        var successCallback = function (aggregateMetricSpec) {
            console.log(aggregateMetricSpec);
            populateSpecInputs(aggregateMetricSpec);
        }
        aggregatemetricspecRest('GET', aggregateMetricSpecId, null, successCallback);
    });

    /** Handler for Cancel button, hide the details panel. */
    $('input#aggregateMetricSpecCancel').click(function () {
        $('div#aggregateMetricSpecCreateEdit').hide();
    });

    /**
     * Handler for Create button:
     *     create the new spec,
     *     add it to the spec listing table,
     *     hide the details panel.
     */
    $('input#aggregateMetricSpecCreate').click(function () {
        $('div#aggregateMetricSpecSaveState input').prop('disabled', true);
        $('#aggregateMetricSpecValidationError').text('');
        var specValues = getSpecInputsValues();

        // Validate the inputs.
        var valid = true;
        // TODO order matters here so need to break after the first invalid
        // TODO mucho repeated
        if (!specValues.aggregateMetricSpec.name || specValues.aggregateMetricSpec.name === '') {
            $('#aggregateMetricSpecValidationError').text('Name is a required field');
            valid = false;
        }
        if (!specValues.aggregateMetricSpec.branch || specValues.aggregateMetricSpec.branch === '') {
            $('#aggregateMetricSpecValidationError').text('Branch is a required field');
            valid = false;
        }
        if (!specValues.specInputs.aggregateMetric || specValues.specInputs.aggregateMetric === '') {
            $('#aggregateMetricSpecValidationError').text('Aggregate metric is a required field');
            valid = false;
        }
        if (!specValues.specInputs.metricName || specValues.specInputs.metricName === '') {
            $('#aggregateMetricSpecValidationError').text('Metric name is a required field');
            valid = false;
        }
        // This is percentile centric now, as it is our only metric.
        console.log('here');
        if (!specValues.specInputs.param || specValues.specInputs.param === '') {
            $('#aggregateMetricSpecValidationError').text('Param (percentile) is a required field');
            valid = false;
        } else {
            var param = specValues.specInputs.param;
            console.log(param);
            if (!$.isNumeric(param) || param < 0 || param > 100) {
                $('#aggregateMetricSpecValidationError').text('Param (percentile) must be >= 0 and <= 100');
                valid = false;
            }
        }
        // Comparison and valid must be empty or not together.
        var comparisonEmpty = (!specValues.aggregateMetricSpec.comparison || specValues.aggregateMetricSpec.comparison === '');
        var valueEmpty = (!specValues.aggregateMetricSpec.value || specValues.aggregateMetricSpec.value === '');
        if (comparisonEmpty !== valueEmpty) {
            $('#aggregateMetricSpecValidationError').text('Comparison and value must be set together');
            valid = false;
        } else if (!valueEmpty && !$.isNumeric(specValues.aggregateMetricSpec.value)) {
            // The case where comparison and value are provided.
            $('#aggregateMetricSpecValidationError').text('Value must be numeric');
            valid = false;
        }

        if (!valid) {
            $('div#aggregateMetricSpecSaveState input').prop('disabled', false);
        } else {
            // Create the AMS on the server.
            $('img#aggregateMetricSpecSaveLoading').show();
            var successCallback = function (aggregateMetricSpec) {
                addToSpecTable(aggregateMetricSpec);
                $('div#aggregateMetricSpecCreateEdit').hide();
            }
            aggregatemetricspecRest('POST', null, specValues.aggregateMetricSpec, successCallback);
        }
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

    // TODO save handler for an existing updated spec.
});
