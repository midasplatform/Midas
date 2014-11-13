// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

$(document).ready(function () {
    'use strict';
    var defaultTest = $('.installHelp').html();
    var helpText = [];
    helpText['name'] = '<h4>Name</h4>Please select the name of your installation of MIDAS';
    helpText['description'] = '<h4>Description</h4>Provide a description for search engines.';
    helpText['lang'] = '<h4>Language</h4>Please select the default language. Currently, English and French are available.';
    helpText['time'] = '<h4>Timezone</h4>Please select the timezone of your server.';
    helpText['assetstore'] = '<h4>Default Assetstore</h4>Please select a default assetstore. An assestore is the location where the uploaded files are stored.';

    $('.installName').hover(function () {
        $('.installHelp').html(helpText['name']);
    }, function () {
        $('.installHelp').html(defaultTest);
    });
    $('.installDescription').hover(function () {
        $('.installHelp').html(helpText['description']);
    }, function () {
        $('.installHelp').html(defaultTest);
    });
    $('.installLang').hover(function () {
        $('.installHelp').html(helpText['lang']);
    }, function () {
        $('.installHelp').html(defaultTest);
    });
    $('.installTimezone').hover(function () {
        $('.installHelp').html(helpText['time']);
    }, function () {
        $('.installHelp').html(defaultTest);
    });
    $('.installAssetstore').hover(function () {
        $('.installHelp').html(helpText['assetstore']);
    }, function () {
        $('.installHelp').html(defaultTest);
    });
});
