// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

/* global json */

var midas = midas || {};
midas.comments = midas.comments || {};
midas.comments.offset = 0;
midas.comments.total = 0;
midas.comments.PAGE_LIMIT = 10;

/**
 * Init (or re-init) the add comment section. Only call this if the
 * user is logged in.
 */
midas.comments.initAddComment = function () {
    'use strict';
    $('#commentText').val('');
    $('#commentLengthRemaining').html('1200');
    $('#commentText').focus(function () {
        $('div.addCommentFooter').show();
        $(this).css('height', '50px');
        $('#commentText').autogrow();
        $(this).unbind('focus');
    });
    $('#commentText').bind('input', function () {
        var remaining = 1200 - this.value.length;
        $('#commentLengthRemaining').html(remaining);
    });
};

/**
 * Init the comment list. Pass a list of comment dao objects to display
 */
midas.comments.initCommentList = function (comments) {
    'use strict';
    var isAdmin = false;
    var currentUser = 0;
    if (json.modules.comments.user) {
        isAdmin = json.modules.comments.user.admin == '1';
        json.modules.comments.user.user_id;
    }

    $('#existingCommentsList').html('');
    $.each(comments, function () {
        var template = $('#existingCommentTemplate').clone();
        template.attr('id', 'comment_' + this.comment_id);
        template.find('a.commentUserName')
            .html(this.user.firstname + ' ' + this.user.lastname)
            .attr('href', json.global.webroot + '/user/' + this.user.user_id);
        template.find('span.commentDate').html(this.ago).attr('qtip', this.date);
        template.find('span.commentText').html(this.comment.replace(/\n/g, '<br />'));

        if (this.user.thumbnail) {
            template.find('img.commentThumbnail').attr('src', this.user.thumbnail);
        }
        if (isAdmin || currentUser == this.user.user_id) {
            var commentId = this.comment_id;
            template.find('img.deleteCommentIcon').click(function () {
                midas.comments.deleteComment(commentId);
            }).show();
        }
        template.appendTo('#existingCommentsList');
        template.show();
    });
    var bottomMessage = '0 comments on this item';
    if (comments.length > 0) {
        bottomMessage = 'showing comments ' + (midas.comments.offset + 1);
        bottomMessage += '-' + (midas.comments.offset + comments.length);
        bottomMessage += ' of ' + midas.comments.total;
    }
    $('#existingCommentsList').append('<div class="commentsBottomMessage">' + bottomMessage + '</div>');
    $('img[qtip],.commentDate[qtip]').qtip({
        content: {
            attr: 'qtip'
        },
        position: {
            at: 'bottom right',
            my: 'top left',
            viewport: $(window),
            effect: true
        }
    });
    // Conditionally display previous and next page links
    var showNext = (midas.comments.offset + midas.comments.PAGE_LIMIT) < midas.comments.total;
    var showPrev = midas.comments.offset > 0;
    if (showNext) {
        $('#nextComments').show();
    }
    else {
        $('#nextComments').hide();
    }
    if (showPrev) {
        $('#prevComments').show();
    }
    else {
        $('#prevComments').hide();
    }
    if (showNext && showPrev) {
        $('#nextPrevSeparator').show();
    }
    else {
        $('#nextPrevSeparator').hide();
    }
};

/**
 * Requests a page of comments from the server using the current offset
 */
midas.comments.refreshCommentList = function () {
    'use strict';
    $('#refreshingCommentDiv').show();
    $.post(json.global.webroot + '/comments/comment/get', {
        itemId: json.item.item_id,
        limit: midas.comments.PAGE_LIMIT,
        offset: midas.comments.offset
    }, function (data) {
        var resp = $.parseJSON(data);
        if (resp != null && resp.status == 'ok') {
            midas.comments.total = resp.total;
            midas.comments.initCommentList(resp.comments);
        }
        else {
            midas.createNotice('Error refreshing comment list', 4000, 'error');
        }
        $('#refreshingCommentDiv').hide();
    });
};

/**
 * When the user clicks the delete comment icon, this function is called
 * with the id of the comment that they requested to delete
 */
midas.comments.deleteComment = function (commentId) {
    'use strict';
    if (typeof midas.showDialogWithContent == 'function') {
        midas.showDialogWithContent('Delete comment', $('#deleteCommentConfirmation').html(), false);
        $('input.deleteCommentNo').click(function () {
            $('div.MainDialog').dialog('close');
        });
        $('input.deleteCommentYes').click(function () {
            $.post(json.global.webroot + '/comments/comment/delete', {
                commentId: commentId
            }, function (data) {
                var resp = $.parseJSON(data);
                if (resp == null) {
                    midas.createNotice('Error occurred, check the logs', 4000, 'error');
                    return;
                }
                if (resp.status == 'ok') {
                    midas.comments.refreshCommentList();
                    $('div.MainDialog').dialog('close');
                }
                midas.createNotice(resp.message, 4000, resp.status);
            });
        });
    }
    else { // we have not loaded the layout...
        $.post(json.global.webroot + '/comments/comment/delete', {
            commentId: commentId
        }, function (data) {
            var resp = $.parseJSON(data);
            if (resp == null) {
                alert('Error occurred, check the logs');
                return;
            }
            if (resp.status == 'ok') {
                midas.comments.refreshCommentList();
            }
        });
    }
};

/**
 * Render the next page of comments
 */
midas.comments.nextPage = function () {
    'use strict';
    if (midas.comments.offset + midas.comments.PAGE_LIMIT >= midas.comments.total) {
        return;
    }
    midas.comments.offset += midas.comments.PAGE_LIMIT;
    midas.comments.refreshCommentList();
};

/**
 * Render the previous page of comments
 */
midas.comments.previousPage = function () {
    'use strict';
    if (midas.comments.offset <= 0) {
        return;
    }
    midas.comments.offset -= midas.comments.PAGE_LIMIT;
    midas.comments.refreshCommentList();
};

$(document).ready(function () {
    'use strict';
    midas.comments.total = json.modules.comments.total;
    midas.comments.initCommentList(json.modules.comments.comments);
    $('#nextComments').click(midas.comments.nextPage);
    $('#prevComments').click(midas.comments.previousPage);

    if (json.global.logged == '1') {
        midas.comments.initAddComment();
        $('#addCommentButton').click(function () {
            var comment = $.trim($('#commentText').val());
            if (comment != '') {
                $.post(json.global.webroot + '/comments/comment/add', {
                    itemId: json.item.item_id,
                    comment: comment
                }, function (data) {
                    var resp = $.parseJSON(data);
                    if (resp == null) {
                        midas.createNotice('Error occurred, check the logs', 4000, 'error');
                        return;
                    }
                    if (resp.status == 'ok') {
                        $('div.addCommentFooter').hide();
                        $('#commentText').css('height', '25px');
                        midas.comments.initAddComment();
                        midas.comments.refreshCommentList();
                    }
                    midas.createNotice(resp.message, 4000, resp.status);
                });
            }
        });
        $('div.addCommentWrapper').show();
        $('#postingAsUsername').html(json.modules.comments.user.firstname + ' ' +
            json.modules.comments.user.lastname);
    }
    else {
        $('div.loginToComment').show();
        $('#loginToComment').click(function () {
            midas.showOrHideDynamicBar('login');
            midas.loadAjaxDynamicBar('login', '/user/login');
        });
        $('#registerToComment').click(function () {
            midas.showOrHideDynamicBar('register');
            midas.loadAjaxDynamicBar('register', '/user/register');
        });
    }
});
