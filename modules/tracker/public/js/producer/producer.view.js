$(document).ready(function () {
    $('a.editProducerInfo').click(function () {
        midas.loadDialog('editProducer', '/tracker/producer/edit?producerId='+json.tracker.producer.producer_id);
        midas.showDialog('Edit Producer Information', false, {width: 495});
    });
    
    $('input.selectTrend').click(function () {
        $('#selectedTrendCount').html('('+$('input.selectTrend:checked').length+')');
        $('a.visualizeSelected').unbind('click').click(function () {
            var trends = '';
            $.each($('input.selectTrend:checked'), function (idx, checkbox) {
                trends += $(checkbox).attr('element')+',';
            });
            if(trends != '') {
                window.location = json.global.webroot+'/tracker/trend/view?trendId='+trends;
            }
        });
    });
});
