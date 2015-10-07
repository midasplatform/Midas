// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.item = midas.item || {};

midas.item.initElementMetaData = function () {
    'use strict';
    var value = $('select[name=metadatatype]').val();
    var availableTags = [];
    $.each(midas.item.jsonMetadata[value], function (i, l) {
        availableTags.push(i);
    });
    $('input[name=element]').autocomplete({
        source: availableTags,
        change: function () {
            midas.item.initElementMetaData();
        }
    });
    midas.item.initQualifierMetaData();
};

midas.item.initQualifierMetaData = function () {
    'use strict';
    var type = $('select[name=metadatatype]').val();
    var value = $('input[name=element]').val();
    var availableTags = [];
    $.each(midas.item.jsonMetadata[type][value], function (i, l) {
        availableTags.push(l.qualifier);
    });

    $('input[name=qualifier]').autocomplete({
        source: availableTags,
        change: function () {
            midas.item.initElementMetaData();
        }
    });
};

$(document).ready(function () {
    'use strict';
    var text = $('div#jsonMetadata').html().trim();
    if (text == '') {
        return; // no metadata fields, do not perform auto-completion
    }
    else {
        midas.item.jsonMetadata = $.parseJSON(text);
    }
    midas.item.initElementMetaData();
    $('select, input').change(function () {
        midas.item.initElementMetaData();
    });
});
