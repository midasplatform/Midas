/*
 * MIDAS Server
 * Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 * All rights reserved.
 * More information http://www.kitware.com
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
import java.util.ArrayList;

import javax.swing.JOptionPane;

import org.rsna.ctp.stdstages.anonymizer.AnonymizerStatus;
import org.rsna.ctp.stdstages.anonymizer.dicom.DICOMAnonymizer;

import com.kitware.utils.exception.JavaUploaderException;
import com.kitware.utils.exception.JavaUploaderQueryHttpServerException;

public class UploadThread extends Thread {
    private HttpURLConnection conn = null;
    private Main uploader;
    private long uploadOffset = 0;
    private int startIndex = 0;
    private String getUploadUniqueIdentifierBaseURL;
    private String uploadFileBaseURL, uploadFileURL;
    private boolean paused;

    public static String IOEXCEPTION_ERROR_WRITING_REQUEST_BODY_TO_SERVER = "Error writing request body to server";

    private DataOutputStream output = null;

    public UploadThread(Main uploader) {
        Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] " + this.getClass().getName() + " initialized");
        this.uploader = uploader;
        getUploadUniqueIdentifierBaseURL = this.uploader.getGetUploadUniqueIdentifierBaseURL();
        uploadFileBaseURL = this.uploader.getUploadFileBaseURL();
        paused = false;
    }

    /**
     * Run the CTP DICOM anonymizer on the given file and return the output file
     * 
     * @param file
     *            The file to anonymize
     * @return The anonymized file
     */
    private File anonymizeFile(File file) throws JavaUploaderException {
        File outFile = new File(file.getParent(), file.getName() + ".temp_anon");
        uploader.setFileNameLabel("Anonymizing " + file.getName() + "...");

        AnonymizerStatus status = DICOMAnonymizer.anonymize(file, outFile, uploader.getDAScriptProperties(), null,
                null, false, false);

        if (status.isOK()) {
            return outFile;
        } else {
            Utility.log(Utility.LOG_LEVEL.ERROR,
                    "[CLIENT] Anonymization failed on " + file.getName() + ": " + status.getMessage()
                            + ", ignoring file");
            return null;
        }
    }

    public void forceClose() {
        if (conn != null) {
            conn.disconnect();
            paused = true;
        }
    }

    /**
     * Call item merge operation on the server on the given list of items
     * 
     * @param itemIds
     *            An array list of item id's
     * @return The id of the new item
     * @throws JavaUploaderException
     */
    private int mergeItems(ArrayList<Integer> itemIds) throws JavaUploaderException {
        String itemIdList = "";
        for (Integer itemId : itemIds) {
            itemIdList += itemId.toString() + "-";
        }
        if (itemIdList.endsWith("-")) {
            itemIdList = itemIdList.substring(0, itemIdList.length() - 1);
        }

        String url = uploader.getWebroot() + "/item/merge?outputItemId&items=" + itemIdList;
        url += "&name=DICOMUpload_" + System.currentTimeMillis();
        try {
            URL urlObj = Utility.buildURL("MergeDICOMItems", url);
            conn = (HttpURLConnection) urlObj.openConnection();
            conn.setUseCaches(false);
            conn.setRequestMethod("GET");
            conn.setRequestProperty("Connection", "close");
            conn.setRequestProperty("Host", urlObj.getHost());

            if (conn.getResponseCode() != 200) {
                conn.disconnect();
                throw new JavaUploaderException("Exception occurred on server during item merge");
            }

            InputStream respStream = conn.getInputStream();
            String resp = "";
            int len;
            byte[] buf = new byte[1024];
            while ((len = respStream.read(buf, 0, 1024)) != -1) {
                resp += new String(buf, 0, len);
            }
            int itemId = Integer.parseInt(resp.trim());
            conn.disconnect();
            return itemId;
        } catch (IOException e) {
            conn.disconnect();
            throw new JavaUploaderException(e);
        }
    }

    @Override
    public void run() {
        try {
            Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] " + this.getClass().getName() + " started");
            File[] files = uploader.getFiles();

            // 1. Anonymize and upload each file
            for (int i = startIndex; i < files.length; i++) {
                uploader.setIndex(i);
                uploader.setFileCountLabel(i + 1, files.length);

                File toUpload = uploader.shouldAnonymize() ? anonymizeFile(files[i]) : files[i];
                uploadFile(i, toUpload);
                uploadOffset = 0;
                if (paused) {
                    return;
                }
            }
            // 2. Once we have uploaded all the files, we want to merge them
            // into one item
            uploader.setFileNameLabel("Merging items on server...");
            uploader.setUploadProgress(files.length, 0);
            int itemId = mergeItems(uploader.getItemIdList());
            // 3. Once they are merged, we want to call DICOM metadata extractor
            // on the new item
            uploader.setFileNameLabel("Extracting DICOM metadata on server...");
            runMetadataExtraction(itemId);
            uploader.setFileNameLabel("Finished!");
            uploader.redirectToItem(itemId);
        } catch (Exception e) {
            JOptionPane.showMessageDialog(uploader, e.getMessage(), "Upload failed", JOptionPane.ERROR_MESSAGE);
            Utility.log(Utility.LOG_LEVEL.ERROR, "[CLIENT] UploadThread failed", e);
        }
    }

    /**
     * Run DICOM metadata extraction on the server side
     * 
     * @param itemId
     *            The id of the item to run extraction on
     * @throws JavaUploaderException
     */
    private void runMetadataExtraction(int itemId) throws JavaUploaderException {
        String url = uploader.getApiURL() + "midas.dicomextractor.extract&item=" + itemId;

        try {
            URL urlObj = Utility.buildURL("DICOMExtraction", url);
            conn = (HttpURLConnection) urlObj.openConnection();
            conn.setUseCaches(false);
            conn.setRequestMethod("GET");
            conn.setRequestProperty("Connection", "close");
            conn.setRequestProperty("Host", urlObj.getHost());

            if (conn.getResponseCode() != 200) {
                conn.disconnect();
                throw new JavaUploaderException("Exception occurred on server during DICOM metadata extraction");
            }
            conn.disconnect();
        } catch (IOException e) {
            conn.disconnect();
            throw new JavaUploaderException(e);
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

    private void uploadFile(int i, File file) throws JavaUploaderException, UnsupportedEncodingException {
        if (file == null) {
            uploader.setUploadProgress(i, 0);
            return;
        }
        long length = file.length();
        String filename = file.getName();
        String actualFilename = filename.replace(".temp_anon", "");
        uploader.setFileNameLabel("Uploading " + actualFilename);
        uploader.setFileSizeLabel(length);

        String getUploadUniqueIdentifierURL = getUploadUniqueIdentifierBaseURL + "?filename="
                + URLEncoder.encode(actualFilename, "ISO-8859-1");

        // retrieve uploadUniqueIdentifier
        if (uploader.getUploadUniqueIdentifier() == null) {
            Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] Query server using:" + getUploadUniqueIdentifierURL);
            try {
                uploader.setUploadUniqueIdentifier(Utility.queryHttpServer(getUploadUniqueIdentifierURL));
            } catch (JavaUploaderQueryHttpServerException e) {
                if (uploader.shouldAnonymize()) {
                    file.delete();
                }
                uploader.setFileNameLabel("");
                uploader.setFileSizeLabel(-1);
                uploader.chooseDirButton.setEnabled(true);
                uploader.resumeButton.setEnabled(false);
                uploader.stopButton.setEnabled(false);
                throw new JavaUploaderException("Error: you must select an upload destination folder first");
            } catch (Exception e) {
                if (uploader.shouldAnonymize()) {
                    file.delete();
                }
                throw new JavaUploaderException(e);
            }
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

        uploadFileURL = uploadFileBaseURL + "&filename=" + URLEncoder.encode(actualFilename, "ISO-8859-1")
                + "&uploadUniqueIdentifier=" + uploader.getUploadUniqueIdentifier() + "&length=" + length
                + "&newRevision=0";
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
            conn.setRequestProperty("Content-Length", String.valueOf(length - uploadOffset));
            conn.setChunkedStreamingMode(1048576);

            output = new DataOutputStream(conn.getOutputStream());

            int maxBufferSize = 1048576;
            long bytesWritten = uploadOffset;
            long bytesAvailable = length;
            int bufferSize = (int) Math.min(bytesAvailable, maxBufferSize);
            byte buffer[] = new byte[bufferSize];
            fileStream.read(buffer, 0, bufferSize);
            while (bytesAvailable > 0 && bytesWritten < length) {
                Utility.log(Utility.LOG_LEVEL.LOG, "[CLIENT] Read " + bufferSize + " bytes from file");
                output.write(buffer, 0, bufferSize);
                Utility.log(Utility.LOG_LEVEL.LOG, "[CLIENT] Wrote " + bufferSize + " bytes into OutputStream");
                bytesWritten += bufferSize;
                uploader.setByteUploadedLabel(bytesWritten, length);
                if (bufferSize == maxBufferSize) {
                    uploader.increaseUploadProgress(i, bufferSize);
                } else {
                    finalByteSize = bufferSize;
                }
                bytesAvailable = length - bytesWritten;
                bufferSize = (int) Math.min(bytesAvailable, maxBufferSize);
                fileStream.read(buffer, 0, bufferSize);
            }

            output.flush();
            output.close();

            Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] Wait for server answer ...");

            uploader.increaseUploadProgress(i, finalByteSize); // update GUI
            uploader.setUploadUniqueIdentifier(null); // must reset the token or
                                                      // next upload will break
        } catch (IOException e) {
            String message = e.getMessage();
            if (message != null && message.equals(IOEXCEPTION_ERROR_WRITING_REQUEST_BODY_TO_SERVER)) {
                Utility.log(Utility.LOG_LEVEL.WARNING, "[CLIENT] Catch IOException:"
                        + IOEXCEPTION_ERROR_WRITING_REQUEST_BODY_TO_SERVER + " => Enable ResumeUpload");
                uploader.setEnableResumeButton(true);
                uploader.setEnableUploadButton(false);
                uploader.setEnableStopButton(false);
            } else {
                try {
                    fileStream.close();
                } catch (IOException ioe) {
                }
                if (uploader.shouldAnonymize()) {
                    file.delete();
                }
                throw new JavaUploaderException(e);
            }
        } finally {
            try {
                fileStream.close();
            } catch (IOException ioe) {
            }
            if (uploader.shouldAnonymize()) {
                file.delete();
            }

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
                    String itemId = Utility.getMessage(new BufferedReader(new InputStreamReader(inputStream)));
                    uploader.getItemIdList().add(new Integer(itemId)); // append
                                                                       // item
                                                                       // id to
                                                                       // the
                                                                       // list
                                                                       // to
                                                                       // merge
                    Utility.log(Utility.LOG_LEVEL.DEBUG, "[SERVER] " + itemId);

                    try {
                        inputStream.close();
                    } catch (IOException e) {
                        Utility.log(Utility.LOG_LEVEL.ERROR, "[CLIENT] Failed to close response stream", e);
                    }
                }
                conn.disconnect();
            }
        }
    }
}
