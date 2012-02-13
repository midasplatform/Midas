var midas = midas || {};
midas.comments = midas.comments || {};

/**
 * Init (or re-init) the add comment section. Only call this if the
 * user is logged in.
 */
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

/**
 * Init the comment list. Pass a list of comment dao objects
 */
midas.comments.initCommentList = function(comments) {
    $.each(comments, function() {
        var template = $('#existingCommentTemplate').clone();
        template.attr('id', 'comment_'+this.comment_id);
        template.find('a.commentUserName')
            .html(this.user.firstname+' '+this.user.lastname)
            .attr('href', json.global.webroot+'/user/'+this.user.user_id);
        template.find('span.commentDate').html(this.ago).attr('qtip', this.date);
        template.find('span.commentText').html(this.comment.replace(/\n/g, '<br />'));

        if(this.user.thumbnail) {
            template.find('img.commentThumbnail').attr('src', this.user.thumbnail);
        }
        template.appendTo('#existingCommentsList');
        template.show();
    });
}

$(document).ready(function() {
    midas.comments.initCommentList(json.modules.comments.comments);

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
                    if(resp.status == 'ok') {
                        $('div.addCommentFooter').hide();
                        $('#commentText').css('height', '25px');
                        midas.comments.initAddComment();
                        //todo add the comment to the list or refresh the list
                    }
                    createNotice(resp.message, 4000, resp.status);
                });
            }
        });
        $('#addCommentDiv').show();
        $('#postingAsUsername').html(json.modules.comments.user.firstname+' '+
                                     json.modules.comments.user.lastname);
    }
});

