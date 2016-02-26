// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global ajaxWebApi */
/* global json */

var midas = midas || {};

$(document).ready(function () {
    'use strict';

    function aggregatemetricspecRest(method, aggregateMetricSpecId, args, sCb, eCb, cCb) {
        var url = json.global.webroot + '/rest/tracker/aggregatemetricspec/';
        if (aggregateMetricSpecId) {
            url += aggregateMetricSpecId;
        }
        url += '?useSession=true',

        $.ajax({
            url: json.global.webroot + '/rest/tracker/aggregatemetricspec/'+aggregateMetricSpecId+'?useSession=true',
            type: method,
            success: function (retVal) {
                if (sCb) { sCb(retVal); }
            },
            error: function (retVal) {
                if (eCb) { eCb(retVal); }
            },
            complete: function (retVal) {
                if (cCb) { cCb(retVal); }
            },
            log: $('<p></p>')
        });
    }

     /** Clear all spec fields of any value. */
    function clearSpecFields() {
        $('.amsField').val('');
        // TODO selects are probably wrong
    }

    /** Update disabled composite spec from individual elements. */
    function updateSpec() {
        var metricName = $('#aggregateMetricSpecMetricName').val();
        var metric = $('#aggregateMetricSpecAggregateMetric').val();
        var param = $('#aggregateMetricSpecParam').val();
        $('#aggregateMetricSpecSpec').val(metric + "('" + metricName + "', " + param + ")");
    }

    /** Handler for Delete action. */
    $('a#removeAggregateMetricSpec').click( function () {
      var aggregateMetricSpecId = $(event.target).data('aggregate_metric_spec_id');
      console.log(aggregateMetricSpecId);
      var row = $(event.target).closest('tr');
      var sCb = function (data) {
          // TODO throbber, but where?
          row.remove();
          // TODO adjust rows after for style
      }
      var eCb = function (data) {
          // TODO create a notice
          console.error(data);
      }

      aggregatemetricspecRest('DELETE', aggregateMetricSpecId, null,
          function (data) {
              // TODO throbber, but where?
              row.remove();
              // TODO adjust rows after for style
          },
          function (data) {
              // TODO create a notice
              console.error(data);
          },
          null);

          //sCb, eCb, null);
    /*  $.ajax({
          url: json.global.webroot + '/rest/tracker/aggregatemetricspec/'+aggregateMetricSpecId+'?useSession=true',
          type: 'DELETE',
          success: function (retVal) {
              // TODO throbber, but where?
              row.remove();
              // TODO adjust rows after for style
          },
          error: function (retVal) {
          },
          complete: function () {},
          log: $('<p></p>')
      });*/
      console.log('delete');
    });

    /** Handler for Add action. */
    $('div#addAggregateMetricSpec').click(function () {
        // TODO edit after add results in create cancel save
        clearSpecFields();
        $('div#aggregateMetricSpecCreateEdit').show();
        $('div.aggregateMetricSpecCreate').show();
    });

    $('a#editAggregateMetricSpec').click(function () {
        var aggregateMetricSpecId = $('a#editAggregateMetricSpec').data('aggregate_metric_spec_id');
        // TODO unify ajax call
        $.ajax({
            url: json.global.webroot + '/rest/tracker/aggregatemetricspec/'+aggregateMetricSpecId+'?useSession=true',
            success: function (retVal) {
                clearSpecFields();
                // Set hidden input.
                $('#aggregateMetricSpecEditId').val(aggregateMetricSpecId);
                // TODO loading
                // Set fields with loaded values.
                $('#aggregateMetricSpecName').val(retVal.name);
                $('#aggregateMetricSpecDescription').val(retVal.description);
                // We don't have a metric name, so ??? reset it ???
                //                $('#aggregateMetricSpecMetricName').val(retVal.)>
                // TODO branch will probably break and we don't have it yet.
                // TODO compare against available and check for validity.
                // TODO parse out metric name compare against available and check for validity.
                //$('#aggregateMetricSpecBranch').val(retVal.branch);
                // TODO aggregate metric, percentile, validate
                // TODO percentile/param, parse out
                // TODO for now display resulting metric
                // TODO use the same parsing and the assembly
                $('#aggregateMetricSpecSpec').val(retVal.spec);
                if (retVal.value) {
                    $('#aggregateMetricSpecValue').val(retVal.value);
                }
                if (retVal.comparison) {
                    $('#aggregateMetricSpecComparison').val(retVal.comparison);
                }
                // Display the edit panel.
                $('div#aggregateMetricSpecCreateEdit').show();
                $('div.aggregateMetricSpecEdit').show();
            },
            error: function (retVal) {
                // TODO create a notice
                console.error(retVal);
            },
            complete: function () {},
            log: $('<p></p>')
        });
    });

    /** Handler for Cancel button. */
    $('div.aggregateMetricSpecCancel').click(function () {
        clearSpecFields();
        $('div#aggregateMetricSpecCreateEdit').hide();
        $('div.aggregateMetricSpecCreate').hide();
    });

    /** Handler for Create button. */
    $('div.aggregateMetricSpecCreate').click(function () {
        //TODO Create and Save can both be shown by clicking add then edit
        //TODO disable buttons
        //TODO validate inputs
        //TODO send the response
        //TODO display an error if you get one
        //TODO add the new spec to the table
        //TODO clear the spec fields
        //TODO hide the panel
        //TODO clearSpecFields();
        //TODO throbber
        //$('div#aggregateMetricSpecCreateEdit').hide();
        //$('div.aggregateMetricSpecCreate').hide();
    });

    /** Handler for the metric name select. */
    $('select#aggregateMetricSpecMetricName').change(function () {
        updateSpec();
        // Since the metric name has been set, update the list of branches.
        var producerId = $('#producerId').val();
        var metricName = $('select#aggregateMetricSpecMetricName').val();
        ajaxWebApi.ajax({
            method: 'midas.tracker.branchesformetricname.list',
            args: 'producerId=' + producerId + '&trendMetricName='+metricName,
            success: function (retVal) {
                var branches = retVal.data;
                $.each(branches, function (key, value) {
                    $('#aggregateMetricSpecBranch').append('<option value="">' + value + '</option>');
                });
            },
            error: function (retVal) {
                midas.createNotice(retVal.message, 3000, 'error');
            },
            complete: function () {},
            log: $('<p></p>')
        });

    });

    /** Handler for aggregate metric select. */
    $('select#aggregateMetricSpecAggregateMetric').change( function () {
        updateSpec();
    });

    /** Handler for aggregate metric param change. */
    $('input#aggregateMetricSpecParam').on('keyup change', function () {
        updateSpec();
    });

    // TODO
    // watch the metric name
    // when selected, grab the branch options
    //
    // be careful when editing though an existing one
});
