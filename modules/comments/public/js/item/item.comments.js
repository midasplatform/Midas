var midas = midas || {};
midas.comments = midas.comments || {};

midas.comments.initAddComment = function() {
  $('#commentText').val('');
  $('#commentLengthRemaining').html('1200');
  $('#commentText').focus(function() {
        $('div.addCommentFooter').show();
        $(this).css('height', '50px');
        $('#commentText').autogrow();
        $(this).unbind('focus'); //otherwise causes infinite loop due to autogrow call
    });
    $('#commentText').bind('input', function() {
        var remaining = 1200 - this.value.length;
        $('#commentLengthRemaining').html(remaining);
    });
}

$(document).ready(function() {
    if(json.global.logged == '1') {
        midas.comments.initAddComment();
        $('#addCommentButton').click(function() {
            var comment = $.trim($('#commentText').val());
            if(comment != '') {
                $.post(json.global.webroot+'/comments/comment/add', {
                    itemId: json.item.item_id,
                    comment: comment
                }, function(data) {
                    var resp = $.parseJSON(data);
                    if(resp == null) {
                        createNotice('Error occurred, check the logs', 4000, 'error');
                        return;
                    }
                    createNotice(resp.message, 4000, resp.status);
                    if(resp.status == 'ok') {
                        $('div.addCommentFooter').hide();
                        $('#commentText').css('height', '25px');
                        midas.comments.initAddComment();
                        //todo add the comment to the list or refresh the list
                    }
                });
            }
        });
        $('#addCommentDiv').show();
        $('#postingAsUsername').html(json.modules.comments.username);
    }
});

