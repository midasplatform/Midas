/**
 * This is for storing constants used with the js of midas.
 */
var midas = midas || {}; // idiom for creating a namespace

/**
 * Called when the folder is changed on the upload dialog. This callback is
 * envoked upon an object with 'folderName' and 'folderId' defined.
 */
midas.CALLBACK_CORE_UPLOAD_FOLDER_CHANGED = 'CALLBACK_CORE_UPLOAD_FOLDER_CHANGED';

/**
 * Called when the java upload has been completed. This callback does not
 * pass any parameters.
 */
midas.CALLBACK_CORE_JAVAUPLOAD_LOADED = 'CALLBACK_CORE_JAVAUPLOAD_LOADED';

/**
 * Called when a new revision is set to be uploaded to an item. It is called on
 * both simple uploading and revision uploading. This passes an object with
 * 'files' and optionally 'revision' defined.
 */
midas.CALLBACK_CORE_VALIDATE_UPLOAD = 'CALLBACK_CORE_VALIDATE_UPLOAD';

/**
 * Called when a revision is uploaded. This callback does not pass any
 * parameters.
 */
midas.CALLBACK_CORE_REVISIONUPLOAD_LOADED = 'CALLBACK_CORE_REVISIONUPLOAD_LOADED';

/**
 * Called when the upload total is reset. This callback does not pass any
 * parameters.
 */
midas.CALLBACK_CORE_RESET_UPLOAD_TOTAL = 'CALLBACK_CORE_RESET_UPLOAD_TOTAL';

/**
 * Called when the simple upload dialog is loaded. This callback does not pass
 * any parameters.
 */
midas.CALLBACK_CORE_SIMPLEUPLOAD_LOADED = 'CALLBACK_CORE_SIMPLEUPLOAD_LOADED';
