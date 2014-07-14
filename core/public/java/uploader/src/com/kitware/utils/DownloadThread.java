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

import java.io.DataInputStream;
import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.URL;

import javax.swing.JOptionPane;

import com.kitware.utils.exception.JavaUploaderException;

public class DownloadThread extends Thread {
    private HttpURLConnection conn = null;
    private Main parentUI;
    private int currItem = 0;
    private int currFolder = 0;
    private String baseURL;
    private String[] itemIds, folderIds;
    private File dest;
    private boolean paused, first;
    private FileOutputStream fileStream = null;

    public static String IOEXCEPTION_ERROR_WRITING_REQUEST_BODY_TO_SERVER = "Error writing request body to server";

    private DataInputStream responseStream = null;

    public DownloadThread(Main parentUI, String folderIds, String itemIds) {
        Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] " + this.getClass().getName() + " initialized");
        this.parentUI = parentUI;
        baseURL = this.parentUI.getBaseURL();
        dest = this.parentUI.getDownloadDest();
        this.folderIds = folderIds.split(",");
        this.itemIds = itemIds.split(",");
        paused = false;
        first = true;
    }

    /**
     * Download a folder recursively into the destination directory
     *
     * @param folderId
     * @param name
     * @param directory
     */
    private void downloadFolderRecursive(String folderId, String name, File directory) throws JavaUploaderException {
        String url = baseURL + "folder/javachildren?id=" + folderId;
        parentUI.setFileNameLabel(name);
        parentUI.resetCurrentDownload(-1);

        File newDir = new File(directory, name);
        if (!newDir.exists()) {
            if (!newDir.mkdir()) {
                throw new JavaUploaderException("Could not create directory: " + newDir.getAbsolutePath());
            }
        }

        try {
            URL urlObj = Utility.buildURL("GetFolderChildren", url);
            conn = (HttpURLConnection) urlObj.openConnection();
            conn.setUseCaches(false);
            conn.setRequestMethod("GET");
            conn.setRequestProperty("Connection", "close");
            conn.setRequestProperty("Host", urlObj.getHost());

            if (conn.getResponseCode() != 200) {
                throw new JavaUploaderException("Exception occurred on server when requesting children for id="
                        + folderId);
            }

            String[] resp = getResponseText().split("\n");

            for (String line : resp) {
                line = line.trim();
                if (line.equals("")) {
                    continue;
                }
                String[] tokens = line.split(" ", 3);

                if (tokens[0].equals("f")) // folder
                {
                    downloadFolderRecursive(tokens[1], tokens[2], newDir);
                } else // item
                {
                    downloadItem(tokens[1], newDir);
                }
            }

        } catch (IOException e) {
            throw new JavaUploaderException(e);
        } finally {
            conn.disconnect();
        }
    }

    /**
     * Download an item into the specified directory
     *
     * @param itemId
     *            The id of the item to download
     * @param directory
     *            The directory to download the item into
     */
    private void downloadItem(String itemId, File directory) throws JavaUploaderException {
        if (first) {
            parentUI.setProgressIndeterminate(false);
            first = false;
        }
        // First check if partially downloaded file exists. If so we append to
        // it and pass an offset
        long offset = 0;
        boolean append = false;
        File toWrite = new File(directory, itemId + ".midasdl.part");
        if (toWrite.exists()) {
            offset = toWrite.length();
            append = true;
        }

        String url = baseURL + "download?items=" + itemId + "&offset=" + offset;

        try {
            URL urlObj = Utility.buildURL("DownloadItem", url);

            Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] Query server using:" + url);
            conn = (HttpURLConnection) urlObj.openConnection();
            conn.setDoOutput(true); // Allow Outputs
            conn.setUseCaches(false); // Don't use a cached copy.
            conn.setRequestMethod("GET");
            conn.setRequestProperty("Connection", "close");
            conn.setRequestProperty("Host", urlObj.getHost());
            conn.setChunkedStreamingMode(1048576);

            // This probably means permission failure, so we skip this item
            if (conn.getResponseCode() != 200) {
                conn.disconnect();
                return;
            }

            String name = conn.getHeaderField("Content-Disposition").split("=")[1];
            name = name.replaceAll("\"", ""); // strip quotes from content
            // disposition file name token
            parentUI.setFileNameLabel(name);
            parentUI.resetCurrentDownload(-1);
            if (new File(directory, name).exists()) {
                // skip the file if it has already been fully written
                conn.disconnect();
                parentUI.increaseOverallProgress(new File(directory, name).length());
                return;
            }

            // Do not use conn.getContentLengthLong since it does
            // not exist in Java 6. Hack our own version here.
            String lengthHeader = conn.getHeaderField("Content-Length");
            if (lengthHeader == null) {
                // If this item is being ZipStreamed, we cannot resume, and must
                // redownload it all (happens if head revision has > 1
                // bitstream)
                append = false;
            } else {
                long size = Long.parseLong(lengthHeader);
                parentUI.resetCurrentDownload(size);
                parentUI.increaseOverallProgress(offset);
            }

            responseStream = new DataInputStream(conn.getInputStream());
            fileStream = new FileOutputStream(toWrite, append);
            byte[] buf = new byte[1048576];
            int len;
            while ((len = responseStream.read(buf, 0, buf.length)) != -1) {
                fileStream.write(buf, 0, len);
                parentUI.increaseOverallProgress(len);
            }

            fileStream.close();
            // Final step: move the file to its completed name
            if (!paused) {
                toWrite.renameTo(new File(directory, name));
            }
        } catch (IOException e) {
            parentUI.setEnableResumeButton(true);
            parentUI.setEnableUploadButton(false);
            parentUI.setEnableStopButton(false);

            if (paused) {
                JOptionPane.showMessageDialog(parentUI, "Download paused. " + "Press the Resume button to continue.",
                        "Connection problem", JOptionPane.INFORMATION_MESSAGE);
            } else {
                paused = true;
                Utility.log(Utility.LOG_LEVEL.WARNING, "[CLIENT] Catch IOException:"
                        + IOEXCEPTION_ERROR_WRITING_REQUEST_BODY_TO_SERVER + " => Enable Resume");
                JOptionPane.showMessageDialog(parentUI, "Error communicating with the server. "
                        + "Check your connection, then hit the Resume button.", "Connection problem",
                        JOptionPane.WARNING_MESSAGE);
            }
        } finally {
            try {
                conn.disconnect();
                if (responseStream != null) {
                    responseStream.close();
                }
                if (fileStream != null) {
                    fileStream.close();
                }
            } catch (Exception e) {
            }
        }
    }

    public void forceClose() {
        if (conn != null) {
            conn.disconnect();
            paused = true;
        }
    }

    public int getCurrentFolder() {
        return currFolder;
    }

    public int getCurrentItem() {
        return currItem;
    }

    /**
     * Given the id of the folder, return its name
     *
     * @param folderId
     * @return
     * @throws JavaUploaderException
     */
    private String getFolderName(String folderId) throws JavaUploaderException {
        String url = baseURL + "folder/getname?id=" + folderId;
        try {
            URL urlObj = Utility.buildURL("GetFolderName", url);
            conn = (HttpURLConnection) urlObj.openConnection();
            conn.setUseCaches(false);
            conn.setRequestMethod("GET");
            conn.setRequestProperty("Connection", "close");
            conn.setRequestProperty("Host", urlObj.getHost());

            if (conn.getResponseCode() != 200) {
                throw new JavaUploaderException("Exception occurred on server when requesting folder name for id="
                        + folderId);
            }

            String name = getResponseText().trim();
            conn.disconnect();
            return name;
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

    @Override
    public void run() {
        try {
            if (currItem == 0) {
                parentUI.setProgressIndeterminate(true);
            }
            Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] " + this.getClass().getName() + " started");
            for (int i = currFolder; i < folderIds.length; i++) {
                if (!folderIds[i].trim().equals("")) {
                    downloadFolderRecursive(folderIds[i], getFolderName(folderIds[i]), dest);
                }
                if (paused) {
                    return;
                } else {
                    parentUI.markTopLevelDownloadComplete();
                    currFolder++;
                }
            }
            for (int i = currItem; i < itemIds.length; i++) {
                if (!itemIds[i].trim().equals("")) {
                    downloadItem(itemIds[i], dest);
                }
                if (paused) {
                    return;
                } else {
                    parentUI.markTopLevelDownloadComplete();
                    currItem++;
                }
            }
            JOptionPane.showMessageDialog(parentUI, "Your download has finished.", "Done",
                    JOptionPane.INFORMATION_MESSAGE);
            parentUI.progressBar.setValue(100);
        } catch (JavaUploaderException e) {
            JOptionPane.showMessageDialog(parentUI, e.getMessage(), "Download failed", JOptionPane.ERROR_MESSAGE);
            Utility.log(Utility.LOG_LEVEL.ERROR, "[CLIENT] DownloadThread failed", e);
        }
    }

    public void setCurrentFolder(int i) {
        currFolder = i;
    }

    public void setCurrentItem(int i) {
        currItem = i;
    }
}
