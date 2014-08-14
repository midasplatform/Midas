// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};

// Create the callbacks data structure
midas.callbacks = midas.callbacks || {};

/**
 * Register a callback function from a module
 * @param name The name of the callback
 * @param module The module name registering the callback
 * @param fn The callback function
 */
midas.registerCallback = function (name, module, fn) {
    'use strict';
    if (midas.callbacks[name] == undefined) {
        midas.callbacks[name] = {};
    }
    midas.callbacks[name][module] = fn;
};

/**
 * Perform a callback.
 * @param name The name of the callback to run.
 * @param args A json object that will be passed to the registered callbacks.
 * @return A json object whose keys are the module names and whose values are
 * the return value for that module's registered callback.
 */
midas.doCallback = function (name, args) {
    'use strict';
    if (midas.callbacks[name] == undefined) {
        return {};
    }
    var retVal = {};
    $.each(midas.callbacks[name], function (index, value) {
        retVal[index] = value(args);
    });
    return retVal;
};
