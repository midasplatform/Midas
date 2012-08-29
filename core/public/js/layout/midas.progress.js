var midas = midas || {};

/**
 * Make an ajax call with progress reporting.
 * @param widget DOM element for the progress bar
 * @param messageContainer DOM element where progress message should display
 * @param url The url of the request
 * @param params The params to query
 * @param success The success function
 */
midas.ajaxWithProgress = function (widget, messageContainer, url, params, onSuccess) {
    $(widget).progressbar({ value: 0 });
    // First we have to create a new progress record on the server
    $.ajax({
        type: 'POST',
        url: json.global.webroot+'/progress/create',
        success: function (data) {
            var progress = $.parseJSON(data);
            params.progressId = progress.progress_id;
            $.post(url, params, function (data) {
                $(widget).progressbar({ value: 100 });
                $(messageContainer).html('');
                onSuccess(data);
            });
            midas._pollProgress(widget, messageContainer, progress.progress_id);
        }
    });
};

/**
 * Internal function for polling progress and updating the progress bar element
 */
midas._pollProgress = function (widget, messageContainer, progressId) {
    $.ajax({
        url: json.global.webroot+'/progress/get?progressId='+progressId,
        success: function (data) {
            var progress = $.parseJSON(data);
            if(progress.progress_id) {
                midas._updateProgress(widget, messageContainer, progress);
                var delayedCall = function () { // scope closure
                    midas._pollProgress(widget, messageContainer, progressId);
                };
                setTimeout(delayedCall, 650);
            }
        }
    });
};

/**
 * Internal function to render the progress in the widget
 */
midas._updateProgress = function (widget, messageContainer, progress) {
    if(progress.maximum > 0) {
        var percent = Math.round(100 * (progress.current / progress.maximum));
        $(widget).progressbar({ value: percent });
        $(messageContainer).html(progress.message);
    } else {
        // TODO somehow render indeterminate state
    }
};
