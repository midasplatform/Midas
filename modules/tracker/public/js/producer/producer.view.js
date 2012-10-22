$(document).ready(function () {
    $('a.editProducerInfo').click(function () {
        midas.loadDialog('editProducer', '/tracker/producer/edit?producerId='+json.tracker.producer.producer_id);
        midas.showDialog('Edit Producer Information', false, {width: 495});
    });
});
