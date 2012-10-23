$(document).ready(function () {
    $('a.editProducerInfo').click(function () {
        midas.loadDialog('editProducer', '/tracker/producer/edit?producerId='+json.tracker.producer.producer_id);
        midas.showDialog('Edit Producer Information', false, {width: 495});
    });
    
    $('input.selectTrend').click(function () {
        var checked = $('input.selectTrend:checked');
        if(checked.length == 2) {
            $('a.visualizeDualAxis').show().unbind('click').click(function () {
                window.location = json.global.webroot+'/tracker/trend/view?trendId='+$(checked[0]).attr('element')
                  +'&rightTrendId='+$(checked[1]).attr('element');
            });
        }
        else {
            $('a.visualizeDualAxis').unbind('click').hide();
        }
        if(checked.length >= 2) {
            $('a.visualizeSelected').show().unbind('click').click(function () {
                var trends = '';
                $.each(checked, function(idx, checkbox) {
                    trends += $(checkbox).attr('element')+',';
                });
                window.location = json.global.webroot+'/tracker/trend/view?trendId='+trends;
            });
            $('span.selectedTrendCount').html(checked.length);
        }
        else {
            $('a.visualizeSelected').unbind('click').hide();
        }
    });
});
