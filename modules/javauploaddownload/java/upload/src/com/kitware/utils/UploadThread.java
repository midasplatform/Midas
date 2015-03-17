/*
 * Midas Server
 * Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 * All rights reserved.
 * For more information visit http://www.kitware.com/.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0.txt
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

package com.kitware.utils;

import java.io.BufferedReader;
import java.io.DataOutputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.UnsupportedEncodingException;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;

import javax.swing.JOptionPane;

import org.json.JSONException;
import org.json.JSONObject;

import com.kitware.utils.exception.JavaUploaderException;

public class UploadThread extends Thread {
    private HttpURLConnection conn = null;
    private Main uploader;
    private long uploadOffset = 0;
    private int startIndex = 0, totalFiles, currentFileNumber;
    private String getUploadUniqueIdentifierBaseURL;
    private String uploadFileBaseURL, uploadFileURL, baseURL, apiURL;
    private boolean paused;

    public static String IOEXCEPTION_ERROR_WRITING_REQUEST_BODY_TO_SERVER = "Error writing request body to server";

    private DataOutputStream output = null;

    public UploadThread(Main uploader) {
        Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] " + this.getClass().getName() + " initialized");
        this.uploader = uploader;
        getUploadUniqueIdentifierBaseURL = this.uploader.getGetUploadUniqueIdentifierBaseURL();
        uploadFileBaseURL = this.uploader.getUploadFileBaseURL();
        baseURL = this.uploader.getBaseURL();
        apiURL = this.uploader.getApiURL();
        paused = false;
        currentFileNumber = 0;
    }

    /**
     * Create a new folder on the server with the given name under the given
     * existing parent
     *
     * @param parentId
     *            Id of the parent folder to create folder in
     * @param name
     *            Name of the new child folder
     * @param reuseExisting
     *            If a folder with the same name exists in this location, should
     *            we use the existing one
     * @return The id of the newly created folder (or an existing one in the
     *         case of reuseExisting = true)
     */
    private String createServerFolder(String parentId, String name, boolean reuseExisting)
            throws JavaUploaderException, UnsupportedEncodingException {
        String url = apiURL + "?method=midas.folder.create&useSession";
        url += "&name=" + URLEncoder.encode(name, "ISO-8859-1") + "&parentid=" + parentId;

        if (reuseExisting) {
            url += "&reuseExisting=true";
        }

        try {
            URL urlObj = Utility.buildURL("CreateNewFolder", url);
            conn = (HttpURLConnection) urlObj.openConnection();
            conn.setUseCaches(false);
            conn.setRequestMethod("GET");
            conn.setRequestProperty("Connection", "close");
            conn.setRequestProperty("Host", urlObj.getHost());

            if (conn.getResponseCode() != 200) {
                throw new JavaUploaderException("Exception occurred on server during folder create with parentId="
                        + parentId);
            }

            String resp = getResponseText().trim();
            conn.disconnect();
            return new JSONObject(resp).getJSONObject("data").getString("folder_id");
        } catch (IOException e) {
            conn.disconnect();
            throw new JavaUploaderException(e);
        } catch (JSONException e) {
            throw new JavaUploaderException("Invalid JSON response for folder create (name=" + name + ", parentid="
                    + parentId + "):" + e.getMessage());
        }
    }

    public void forceClose() {
        if (conn != null) {
            conn.disconnect();
            paused = true;
        }
    }

    private String getDestFolder() throws JavaUploaderException {
        String url = baseURL + "javadestinationfolder";
        try {
            URL urlObj = Utility.buildURL("GetDestinationFolder", url);
            conn = (HttpURLConnection) urlObj.openConnection();
            conn.setUseCaches(false);
            conn.setRequestMethod("GET");
            conn.setRequestProperty("Connection", "close");
            conn.setRequestProperty("Host", urlObj.getHost());

            if (conn.getResponseCode() != 200) {
                throw new JavaUploaderException("Exception occurred on server when requesting destination folder id");
            }

            String id = getResponseText().trim();
            conn.disconnect();
            return id;
        } catch (IOException e) {
            conn.disconnect();
            throw new JavaUploaderException(e);
        }
    }

    /**
     * Helper method to get the http response as a string. Don't use for large
     * responses, just smallish text ones.
     *
     * @return
     */
    private String getResponseText() throws IOException {
        InputStream respStream = conn.getInputStream();
        String resp = "";
        int len;
        byte[] buf = new byte[1024];
        while ((len = respStream.read(buf, 0, 1024)) != -1) {
            resp += new String(buf, 0, len);
        }
        return resp;
    }

    /**
     * Query the server to determine if an item with the given name already
     * exists in the parent folder
     *
     * @param parentId
     * @param name
     * @return True if the item with that name already exists in that parent,
     *         false otherwise
     */
    private boolean itemExists(String parentId, String name) throws JavaUploaderException {
        String url = apiURL + "?method=midas.item.exists&useSession";
        url += "&name=" + name + "&parentid=" + parentId;

        try {
            URL urlObj = Utility.buildURL("ItemExists", url);
            conn = (HttpURLConnection) urlObj.openConnection();
            conn.setUseCaches(false);
            conn.setRequestMethod("GET");
            conn.setRequestProperty("Connection", "close");
            conn.setRequestProperty("Host", urlObj.getHost());

            if (conn.getResponseCode() != 200) {
                throw new JavaUploaderException("Exception occurred on server during item exists check with parentId="
                        + parentId + " and name=" + name);
            }

            String resp = getResponseText().trim();
            conn.disconnect();

            return new JSONObject(resp).getJSONObject("data").getBoolean("exists");
        } catch (IOException e) {
            conn.disconnect();
            throw new JavaUploaderException(e);
        } catch (JSONException e) {
            throw new JavaUploaderException("Invalid JSON response for item exists check (name=" + name + ", parentid="
                    + parentId + "):" + e.getMessage());
        }
    }

    @Override
    public void run() {
        try {
            Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] " + this.getClass().getName() + " started");
            String parentId;
            if (uploader.isRevisionUpload()) {
                parentId = uploader.getParentItem();
            } else {
                parentId = getDestFolder();
            }
            if (parentId == null || parentId.equals("")) {
                uploader.setEnableResumeButton(false);
                uploader.setEnableUploadButton(true);
                uploader.setEnableStopButton(false);
                throw new JavaUploaderException("Please choose your destination " + "folder above, then try again.");
            }
            File[] files = uploader.getFiles();
            for (int i = startIndex; i < files.length; i++) {
                if (files[i].isDirectory()) {
                    uploader.setFileSizeLabel(-1);
                    Long[] totalSize = Utility.directorySize(files[i]);
                    uploader.setFileSizeLabel(totalSize[1].longValue());
                    uploader.setTotalSize(totalSize[1].longValue());
                    totalFiles = totalSize[0].intValue();
                    String topId = createServerFolder(parentId, files[i].getName().trim(), true);
                    uploadFolder(files[i], topId);
                    uploader.onSuccessfulUpload();
                } else {
                    uploader.setIndex(i);
                    uploader.setFileCountLabel(i + 1, files.length);
                    uploader.setFileSizeLabel(uploader.getFileLength(i));
                    uploader.setFileNameLabel(files[i].getName());
                    uploadFile(i, files[i], parentId);
                    uploadOffset = 0;
                    if (paused) {
                        return;
                    }
                }
            }
        } catch (JavaUploaderException e) {
            // "To obtain further information regarding this error, please turn on the Java Console"
            JOptionPane.showMessageDialog(uploader, e.getMessage(), "Upload failed", JOptionPane.ERROR_MESSAGE);
            Utility.log(Utility.LOG_LEVEL.ERROR, "[CLIENT] UploadThread failed", e);
        } catch (UnsupportedEncodingException e) {
            JOptionPane.showMessageDialog(uploader, e.getMessage(), "Unsupported URL encoding scheme ISO-8859-1",
                    JOptionPane.ERROR_MESSAGE);
            Utility.log(Utility.LOG_LEVEL.ERROR, "[CLIENT] UploadThread failed", e);
        }
    }

    public void setStartIndex(int index) {
        startIndex = index;
    }

    public void setUploadOffset(long uploadOffset) throws JavaUploaderException {
        if (isAlive()) {
            throw new JavaUploaderException("Failed to set uploadOffset while " + this.getClass().getName()
                    + " is running");
        }
        this.uploadOffset = uploadOffset;
    }

    private void uploadFile(int i, File file, String parentId) throws JavaUploaderException,
    UnsupportedEncodingException {
        // generate URLs
        String filename = file.getName();
        String getUploadUniqueIdentifierURL = getUploadUniqueIdentifierBaseURL + "?filename="
                + URLEncoder.encode(filename, "ISO-8859-1");
        if (uploader.isRevisionUpload()) {
            getUploadUniqueIdentifierURL += "&revision=true&itemId=" + parentId;
        } else {
            getUploadUniqueIdentifierURL += "&parentId=" + parentId;
        }

        // retrieve uploadUniqueIdentifier
        if (uploader.getUploadUniqueIdentifier() == null) {
            Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] Query server using:" + getUploadUniqueIdentifierURL);
            uploader.setUploadUniqueIdentifier(Utility.queryHttpServer(getUploadUniqueIdentifierURL));
            Utility.log(Utility.LOG_LEVEL.DEBUG,
                    "[SERVER] uploadUniqueIdentifier:" + uploader.getUploadUniqueIdentifier());
        } else {
            Utility.log(Utility.LOG_LEVEL.DEBUG,
                    "[CLIENT] Re-use existing uploadUniqueIdentifier:" + uploader.getUploadUniqueIdentifier());
        }

        FileInputStream fileStream = null;
        int finalByteSize = 0;
        try {
            fileStream = new FileInputStream(file);
            fileStream.skip(uploadOffset);
            uploader.setUploadProgress(i, uploadOffset);
        } catch (FileNotFoundException e) {
            throw new JavaUploaderException("File '" + file.getPath() + "' doesn't exist");
        } catch (IOException e) {
            throw new JavaUploaderException("Failed to read file '" + file.getPath() + "'");
        }

        uploadFileURL = uploadFileBaseURL + "&filename=" + URLEncoder.encode(filename, "ISO-8859-1")
                + "&uploadUniqueIdentifier=" + URLEncoder.encode(uploader.getUploadUniqueIdentifier(), "ISO-8859-1")
                + "&length=" + uploader.getFileLength(i);
        if (uploader.isRevisionUpload()) {
            uploadFileURL += "&itemId=" + parentId;
        } else {
            uploadFileURL += uploader.revOnCollision() ? "&newRevision=1" : "&newRevision=0";
            uploadFileURL += "&parentId=" + parentId;
        }
        URL uploadFileURLObj = Utility.buildURL("UploadFile", uploadFileURL);

        try {
            Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] Query server using:" + uploadFileURL);
            conn = (HttpURLConnection) uploadFileURLObj.openConnection();
            conn.setDoInput(true); // Allow Inputs
            conn.setDoOutput(true); // Allow Outputs
            conn.setUseCaches(false); // Don't use a cached copy.
            conn.setRequestMethod("PUT"); // Use a PUT method.
            conn.setRequestProperty("Connection", "close");
            conn.setRequestProperty("Host", uploadFileURLObj.getHost());
            conn.setRequestProperty("Content-Type", "application/octet-stream");
            conn.setRequestProperty("Content-Length", String.valueOf(uploader.getFileLength(i) - uploadOffset));
            conn.setChunkedStreamingMode(1048576);

            output = new DataOutputStream(conn.getOutputStream());

            int maxBufferSize = 1048576;
            long bytesWritten = uploadOffset;
            long fileSize = uploader.getFileLength(i);
            long bytesAvailable = fileSize;
            int bufferSize = (int) Math.min(bytesAvailable, maxBufferSize);
            byte buffer[] = new byte[bufferSize];
            fileStream.read(buffer, 0, bufferSize);
            while (bytesAvailable > 0 && bytesWritten < fileSize) {
                Utility.log(Utility.LOG_LEVEL.LOG, "[CLIENT] Read " + bufferSize + " bytes from file");
                output.write(buffer, 0, bufferSize);
                Utility.log(Utility.LOG_LEVEL.LOG, "[CLIENT] Wrote " + bufferSize + " bytes into OutputStream");
                bytesWritten += bufferSize;
                uploader.setByteUploadedLabel(bytesWritten, fileSize);
                if (bufferSize == maxBufferSize) {
                    uploader.increaseUploadProgress(i, bufferSize);
                } else {
                    finalByteSize = bufferSize;
                }
                bytesAvailable = fileSize - bytesWritten;
                bufferSize = (int) Math.min(bytesAvailable, maxBufferSize);
                fileStream.read(buffer, 0, bufferSize);
            }

            output.flush();
            output.close();

            Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] Wait for server answer ...");

            uploader.increaseUploadProgress(i, finalByteSize); // update GUI
            uploader.reset();
        } catch (IOException e) {
            String message = e.getMessage();
            if (message != null && message.equals(IOEXCEPTION_ERROR_WRITING_REQUEST_BODY_TO_SERVER)) {
                Utility.log(Utility.LOG_LEVEL.WARNING, "[CLIENT] Catch IOException:"
                        + IOEXCEPTION_ERROR_WRITING_REQUEST_BODY_TO_SERVER + " => Enable ResumeUpload");
                uploader.setEnableResumeButton(true);
                uploader.setEnableUploadButton(false);
                uploader.setEnableStopButton(false);
            } else {
                throw new JavaUploaderException(e);
            }
        } finally {
            if (conn != null) {
                InputStream inputStream = null;
                try {
                    inputStream = conn.getInputStream();
                } catch (Exception e) {
                    inputStream = null;
                }

                InputStream errorInputStream = conn.getErrorStream();

                if (inputStream == null && errorInputStream != null) {
                    inputStream = errorInputStream;
                }

                if (inputStream != null) {
                    uploader.setProgressIndeterminate(true);
                    String msg = Utility.getMessage(new BufferedReader(new InputStreamReader(inputStream)));
                    Utility.log(Utility.LOG_LEVEL.DEBUG, "[SERVER] " + msg);
                    if (i + 1 == uploader.getFiles().length) {
                        uploader.onSuccessfulUpload();
                    }
                    uploader.setProgressIndeterminate(false);
                    try {
                        inputStream.close();
                    } catch (IOException e) {
                        Utility.log(Utility.LOG_LEVEL.ERROR, "[CLIENT] Failed to close ErrorStream", e);
                    }
                }
                conn.disconnect();
            }
        }
    }

    private void uploadFolder(File dir, String parentId) throws JavaUploaderException, UnsupportedEncodingException {
        File[] localChildren = dir.listFiles();
        if (localChildren == null) {
            return; // This can happen for weird special directories on windows.
            // Just ignore it.
        }
        for (File f : localChildren) {
            currentFileNumber++;
            uploader.setFileNameLabel(f.getName());
            uploader.setFileCountLabel(currentFileNumber, totalFiles);
            if (f.isDirectory()) {
                String currId = createServerFolder(parentId, f.getName().trim(), true);
                uploadFolder(f, currId);
            } else {
                if (itemExists(parentId, f.getName().trim())) {
                    uploader.increaseOverallProgress(f.length());
                } else {
                    uploadItem(f, parentId);
                }
            }
        }
    }

    private void uploadItem(File file, String parentId) throws JavaUploaderException, UnsupportedEncodingException {
        String getUploadUniqueIdentifierURL = getUploadUniqueIdentifierBaseURL + "?filename="
                + URLEncoder.encode(file.getName(), "ISO-8859-1") + "&parentFolderId=" + parentId;
        uploader.setUploadUniqueIdentifier(Utility.queryHttpServer(getUploadUniqueIdentifierURL));

        FileInputStream fileStream = null;
        long fileSize = file.length();
        try {
            fileStream = new FileInputStream(file);
        } catch (FileNotFoundException e) {
            throw new JavaUploaderException("File '" + file.getPath() + "' doesn't exist");
        }

        uploadFileURL = uploadFileBaseURL + "&filename=" + URLEncoder.encode(file.getName(), "ISO-8859-1")
                + "&uploadUniqueIdentifier=" + URLEncoder.encode(uploader.getUploadUniqueIdentifier(), "ISO-8859-1")
                + "&length=" + fileSize;
        uploadFileURL += uploader.revOnCollision() ? "&newRevision=1" : "&newRevision=0";
        uploadFileURL += "&parentId=" + parentId;
        URL uploadFileURLObj = Utility.buildURL("UploadFile", uploadFileURL);

        try {
            conn = (HttpURLConnection) uploadFileURLObj.openConnection();
            conn.setDoInput(true); // Allow Inputs
            conn.setDoOutput(true); // Allow Outputs
            conn.setUseCaches(false); // Don't use a cached copy.
            conn.setRequestMethod("PUT"); // Use a PUT method.
            conn.setRequestProperty("Connection", "close");
            conn.setRequestProperty("Host", uploadFileURLObj.getHost());
            conn.setRequestProperty("Content-Type", "application/octet-stream");
            conn.setRequestProperty("Content-Length", String.valueOf(fileSize));
            conn.setChunkedStreamingMode(1048576);

            output = new DataOutputStream(conn.getOutputStream());

            byte buffer[] = new byte[1048576];
            int len;
            while ((len = fileStream.read(buffer, 0, 1048576)) != -1) {
                output.write(buffer, 0, len);
                uploader.increaseOverallProgress(len);
            }

            output.flush();
            output.close();
        } catch (IOException e) {
            throw new JavaUploaderException(e);
        } finally {
            try {
                fileStream.close();
            } catch (IOException e) {
            }
            conn.disconnect();
        }
    }
}
