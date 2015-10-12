// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};

/**
 * Show a jGrowl notice in the top right of the visible screen.
 * @param text The text to display
 * @param delay Time in milliseconds to display the notice
 * @param state (optional) Set to either "error" or "warning" to display special state
 */
midas.createNotice = function (text, delay, state) {
    'use strict';
    var extraClasses = '';
    if (state == 'error') {
        extraClasses += ' growlError';
    }
    else if (state == 'warning') {
        extraClasses += ' growlWarning';
    }
    else { // state is ok
        extraClasses += ' growlOk';
    }
    midas.createGrowl(false, text, delay, extraClasses);
};

/**
 * @deprecated use midas.createNotice
 */
function createNotice(text, delay, state) {
    'use strict';
    midas.createNotice(text, delay, state);
}

midas.createGrowl = function (persistent, text, delay, extraClasses) {
    'use strict';
    // Use the last visible jGrowl qtip as our positioning target
    var target = $('.qtip.jgrowl:visible:last');

    // Create your jGrowl qTip...
    $(document.body).qtip({
        // Any content config you want here really.... go wild!
        content: {
            text: '<span class="' + extraClasses + '">' + text + '</span>'
        },
        position: {
            my: 'top right', // Not really important...
            at: (target.length ? 'bottom' : 'top') + ' right', // If target is window use 'top right' instead of 'bottom right'
            target: target.length ? target : $(document.body), // Use our target declared above
            adjust: { // show at the top of the visible page, or just below header
                y: Math.max($(window).scrollTop() + 10, $('div.Wrapper').position().top),
                x: -25 // 25px from the right of the screen
            }
        },
        show: {
            event: false, // Don't show it on a regular event
            ready: true, // Show it when ready (rendered)
            effect: function () {
                $(this).stop(0, 1).fadeIn(400);
            }, // Matches the hide effect
            delay: 0, // Needed to prevent positioning issues

            // Custom option for use with the .get()/.set() API, awesome!
            persistent: persistent
        },
        hide: {
            event: false, // Don't hide it on a regular event
            effect: function (api) {
                // Do a regular fadeOut, but add some spice!
                $(this).stop(0, 1).fadeOut(400).queue(function () {
                    // Destroy this tooltip after fading out
                    api.destroy();
                    // Update positions
                    midas.updateGrowls();
                });
            }
        },
        style: {
            classes: 'jgrowl ui-tooltip-dark ui-tooltip-rounded',
            tip: false // No tips for this one (optional of course)
        },
        events: {
            render: function (event, api) {
                // Trigger the timer (below) on render
                timerGrowl.call(api.elements.tooltip, event, delay);
            }
        }
    })
        .removeData('qtip');
};

// Make it a window property see we can call it outside via updateGrowls() at any point
midas.updateGrowls = function () {
    'use strict';
    // Loop over each jGrowl qTip
    var each = $('.qtip.jgrowl:not(:animated)');
    each.each(function (i) {
        var api = $(this).data('qtip');

        // Set the target option directly to prevent reposition() from being called twice.
        api.options.position.target = !i ? $(document.body) : each.eq(i - 1);
        api.set('position.at', (!i ? 'top' : 'bottom') + ' right');
    });
};

function timerGrowl(event, delay) {
    'use strict';
    var api = $(this).data('qtip');

    // If persistent is set to true, don't do anything.
    if (api.get('show.persistent') === true) {
        return;
    }

    // Otherwise, start/clear the timer depending on event type
    clearTimeout(api.timer);
    if (event.type !== 'mouseover') {
        api.timerGrowl = setTimeout(api.hide, delay);
    }
}

$(document).delegate('.qtip.jgrowl', 'mouseover mouseout', timerGrowl);
