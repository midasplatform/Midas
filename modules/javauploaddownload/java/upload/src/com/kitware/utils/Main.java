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

import java.awt.Color;
import java.awt.Container;
import java.awt.GridLayout;
import java.awt.event.ActionEvent;
import java.io.File;
import java.net.URL;
import java.security.AccessControlException;

import javax.swing.BorderFactory;
import javax.swing.Box;
import javax.swing.BoxLayout;
import javax.swing.JApplet;
import javax.swing.JButton;
import javax.swing.JCheckBox;
import javax.swing.JFileChooser;
import javax.swing.JLabel;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.JProgressBar;
import javax.swing.JScrollPane;
import javax.swing.JTable;
import javax.swing.UIManager;
import javax.swing.table.TableColumn;

import com.kitware.utils.exception.JavaUploaderException;

public class Main extends JApplet {
    private static final long serialVersionUID = 2238283629688547425L;

    // GUI elements
    JTable table;
    JButton uploadDownloadButton, uploadDirButton, resumeButton, stopButton;
    TableColumn sizeColumn;
    JProgressBar progressBar;
    JLabel fileCountLabel, fileNameLabel, fileSizeLabel, bytesTransferredLabel, totalSizeLabel, totalTransferredLabel;
    JCheckBox revOnCollisionCheckbox;
    private Color appletBackgroundColor = new Color(225, 225, 225);

    private final static String FILECOUNT_LABEL_TITLE = "File #: ";
    private final static String FILENAME_LABEL_TITLE = "Name: ";
    private final static String FILESIZE_LABEL_TITLE = "Size: ";
    private final static String BYTE_TRANSFERRED_LABEL_TITLE = "Transferred: ";
    private final static String TOTAL_SIZE_LABEL_TITLE = "Total size: ";
    private final static String TOTAL_TRANSFERRED_LABEL_TITLE = "Total transferred: ";

    // File upload
    private File downloadDest;
    private File[] files;
    private long[] fileLengths;
    private int index = 0;
    private long lastTopLevelDownloadOffset = 0;
    private long transferredBytes = 0;
    private long totalSize = 0;
    private long totalTransferred = 0;
    private UploadThread uploadThread = null;
    private DownloadThread downloadThread = null;

    private String baseURL, apiURL, getUploadUniqueIdentifierBaseURL, onSuccessRedirectURL;
    private String uploadFileBaseURL, uploadFileURL, folderIds, itemIds;
    private String getUploadFileOffsetBaseURL, getUploadFileOffsetURL;
    private String sessionId, uploadUniqueIdentifier = null;
    private String parentItem = "";
    private boolean revisionUpload = false;
    private URL onSuccessRedirectURLObj;
    boolean onSuccessfulUploadRedirectEnable = true;
    boolean download = false;
    boolean directory = false;

    private String[] fileExtensions;

    /**
     * Called when the download button is pressed. Prompts the user for a
     * destination directory, then starts the download thread.
     *
     * @param evt
     */
    public void downloadButtonActionPerformed(ActionEvent evt) {
        progressBar.setValue(0);

        try {
            JFileChooser chooser = new JFileChooser();
            chooser.setDialogTitle("Choose a download destination");
            chooser.setFileSelectionMode(JFileChooser.DIRECTORIES_ONLY);
            chooser.setAcceptAllFileFilterUsed(false);
            chooser.setApproveButtonText("Download");
            int returnVal = chooser.showOpenDialog(this);
            if (returnVal == JFileChooser.APPROVE_OPTION) {
                downloadDest = chooser.getSelectedFile();

                // button setup
                uploadDownloadButton.setEnabled(false);
                if (uploadDirButton != null) {
                    uploadDirButton.setEnabled(false);
                }
                stopButton.setEnabled(true);
                resumeButton.setEnabled(false);

                // initialize upload details
                transferredBytes = 0;

                // progress bar setup
                progressBar.setMinimum(0);
                progressBar.setMaximum(100);

                downloadThread = new DownloadThread(this, folderIds, itemIds);
                downloadThread.start();
            }
        } catch (AccessControlException e) {
            Utility.log(Utility.LOG_LEVEL.WARNING, "JAR certificate may be corrupted", e);
        }
    }

    public String getApiURL() {
        return apiURL;
    }

    // Helper method for getting the parameters from the webpage.
    private void getAppletParameters() throws JavaUploaderException {
        String loglevel = getParameter("loglevel");
        if (loglevel != null) {
            Utility.EFFECTIVE_LOG_LEVEL = Utility.LOG_LEVEL.valueOf(loglevel);
        }
        Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] EFFECTIVE_LOG_LEVEL:" + Utility.EFFECTIVE_LOG_LEVEL);

        String downloadMode = getParameter("downloadMode");
        if (downloadMode != null) {
            download = true;
        } else {
            String directoryMode = getParameter("directoryMode");
            if (directoryMode != null) {
                directory = true;
            }
        }
        Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] DOWNLOAD MODE ON");

        String onSuccessfulUploadRedirectEnableStr = getParameter("onSuccessfulUploadRedirectEnable");
        if (onSuccessfulUploadRedirectEnableStr != null) {
            onSuccessfulUploadRedirectEnable = Boolean.parseBoolean(onSuccessfulUploadRedirectEnableStr);
        }

        sessionId = getParameter("sessionId");
        Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] sessionId:" + sessionId);

        baseURL = getParameter("baseURL");
        Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] baseURL:" + baseURL);

        // applet background color
        String background = "";
        try {
            background = "0x" + getParameter("background");
            Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] background:" + background);
            appletBackgroundColor = Color.decode(background);
        } catch (NumberFormatException e) {
            throw new JavaUploaderException(
                    "'background' applet parameter should either be a decimal, octal or hexidecimal integer:"
                            + background, e);
        } catch (NullPointerException e) {
            Utility.log(Utility.LOG_LEVEL.WARNING,
                    "[CLIENT] 'background' applet parameter unspecified: Rollback to default");
        }

        if (download) {
            totalSize = Long.parseLong(getParameter("totalSize"));
            folderIds = getParameter("folderIds");
            itemIds = getParameter("itemIds");
        } else {
            apiURL = getParameter("apiURL");
            // destURL is the address that this applet writes bytes to while
            // uploading a file
            uploadFileBaseURL = baseURL + getParameter("uploadFileBaseURL") + sessionId;
            Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] uploadFileBaseURL:" + uploadFileBaseURL);

            onSuccessRedirectURL = baseURL + getParameter("onSuccessRedirectURL");
            Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] onSuccessRedirectURL:" + onSuccessRedirectURL);

            // idURL generates connectionIDs. This is useful for resuming broken
            // downloads
            getUploadUniqueIdentifierBaseURL = baseURL + getParameter("uploadUniqueIdentifierURL") + sessionId;
            Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] getUploadUniqueIdentifierBaseURL:"
                    + getUploadUniqueIdentifierBaseURL);

            // offsetURL tells us how much of a file was received in the event
            // of a broken download
            getUploadFileOffsetBaseURL = baseURL + getParameter("getUploadFileOffsetBaseURL") + sessionId;
            Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] getUploadFileOffsetBaseURL:" + getUploadFileOffsetBaseURL);

            onSuccessRedirectURLObj = Utility.buildURL("onSuccessRedirect", onSuccessRedirectURL);

            try {
                fileExtensions = getParameter("fileextensions").split(",");
            } catch (NullPointerException e) {
                Utility.log(Utility.LOG_LEVEL.WARNING,
                        "[CLIENT] 'fileextensions' applet parameter unspecified: Rollback to default");
            }

            String uploadType = getParameter("uploadType");
            revisionUpload = uploadType != null && uploadType.equals("revision");
            if (revisionUpload) {
                parentItem = getParameter("parentItem");
                // We don't use the base url here (we don't redirect to the
                // upload controller)
                onSuccessRedirectURL = getParameter("onSuccessRedirectURL");
                onSuccessRedirectURLObj = Utility.buildURL("onSuccessRedirect", onSuccessRedirectURL);
            }
        }
    }

    public String getBaseURL() {
        return baseURL;
    }

    public File getDownloadDest() {
        return downloadDest;
    }

    public long getFileLength(int index) {
        return fileLengths[index];
    }

    public File[] getFiles() {
        return files;
    }

    public String getGetUploadUniqueIdentifierBaseURL() {
        return getUploadUniqueIdentifierBaseURL;
    }

    /**
     * Get the parent item in the case of a revision upload
     *
     * @return the parent item id
     */
    public String getParentItem() {
        return parentItem;
    }

    public String getUploadFileBaseURL() {
        return uploadFileBaseURL;
    }

    public String getUploadFileURL() {
        return uploadFileURL;
    }

    public String getUploadUniqueIdentifier() {
        return uploadUniqueIdentifier;
    }

    public void increaseOverallProgress(long amount) {
        totalTransferred += amount;
        transferredBytes += amount;
        int totalProgress = (int) (100.0 * totalTransferred / totalSize);
        progressBar.setValue(totalProgress);
        bytesTransferredLabel.setText(BYTE_TRANSFERRED_LABEL_TITLE + Utility.bytesToString(transferredBytes));
        if (totalTransferredLabel != null) {
            totalTransferredLabel.setText(TOTAL_TRANSFERRED_LABEL_TITLE + Utility.bytesToString(totalTransferred));
            bytesTransferredLabel.setText(BYTE_TRANSFERRED_LABEL_TITLE + Utility.bytesToString(transferredBytes));
        } else {
            bytesTransferredLabel.setText(BYTE_TRANSFERRED_LABEL_TITLE + Utility.bytesToString(totalTransferred));
        }
    }

    public void increaseUploadProgress(int index, int value) {
        transferredBytes += value;
        int progress = (int) (100.0 * transferredBytes / fileLengths[index]);
        progressBar.setValue(progress);
    }

    @Override
    public void init() {
        try {
            getAppletParameters();
        } catch (JavaUploaderException e) {
            Utility.log(Utility.LOG_LEVEL.FATAL, "[CLIENT] Applet initialization failed", e);
        }

        try {
            final boolean downloadMode = download;
            javax.swing.SwingUtilities.invokeAndWait(new Runnable() {
                @Override
                public void run() {
                    initComponentsCommon();
                    if (downloadMode) {
                        initComponentsDownload();
                    } else {
                        initComponentsUpload();
                    }
                }
            });
        } catch (Exception e) {
            Utility.log(Utility.LOG_LEVEL.FATAL, "[CLIENT] Failed to build GUI");
        }
    }

    /**
     * Initialize the view components common to both upload and download mode
     */
    private void initComponentsCommon() {
        // Set applet to native system L&F
        try {
            UIManager.setLookAndFeel(UIManager.getSystemLookAndFeelClassName());
        } catch (Exception e) {
            Utility.log(Utility.LOG_LEVEL.WARNING, "[CLIENT] Failed to set applet 'look&feel'", e);
        }
        progressBar = new JProgressBar();

        // info labels
        fileNameLabel = new JLabel(FILENAME_LABEL_TITLE);
        fileSizeLabel = new JLabel(FILESIZE_LABEL_TITLE + "0 bytes");
        fileCountLabel = new JLabel(FILECOUNT_LABEL_TITLE);
        bytesTransferredLabel = new JLabel(BYTE_TRANSFERRED_LABEL_TITLE + "0 bytes");

        stopButton = new JButton("Pause");
        stopButton.setEnabled(false);

        resumeButton = new JButton("Resume");
        resumeButton.setEnabled(false);
    }

    private void initComponentsDownload() {
        // Get the main pane to add content to.
        Container pane = getContentPane();
        pane.setLayout(new BoxLayout(pane, BoxLayout.Y_AXIS));

        JPanel buttonPanel = new JPanel();
        buttonPanel.setLayout(new BoxLayout(buttonPanel, BoxLayout.X_AXIS));
        buttonPanel.setBorder(BorderFactory.createEmptyBorder(0, 0, 0, 0));

        // upload button
        uploadDownloadButton = new JButton("Download");
        uploadDownloadButton.addActionListener(new java.awt.event.ActionListener() {
            @Override
            public void actionPerformed(ActionEvent evt) {
                downloadButtonActionPerformed(evt);
            }
        });

        buttonPanel.add(uploadDownloadButton);
        buttonPanel.add(Box.createHorizontalGlue());

        resumeButton.addActionListener(new java.awt.event.ActionListener() {
            @Override
            public void actionPerformed(ActionEvent evt) {
                resumeDownload(evt);
            }
        });
        buttonPanel.add(resumeButton);

        stopButton.addActionListener(new java.awt.event.ActionListener() {
            @Override
            public void actionPerformed(ActionEvent evt) {
                pauseDownload(evt);
            }
        });
        buttonPanel.add(stopButton);

        totalSizeLabel = new JLabel(TOTAL_SIZE_LABEL_TITLE + Utility.bytesToString(totalSize));
        totalTransferredLabel = new JLabel(TOTAL_TRANSFERRED_LABEL_TITLE + "0 bytes");

        pane.add(buttonPanel);
        pane.add(Box.createVerticalStrut(15));

        JPanel labelPanel = new JPanel();
        labelPanel.setLayout(new BoxLayout(labelPanel, BoxLayout.Y_AXIS));
        labelPanel.setBorder(BorderFactory.createEmptyBorder(0, 0, 0, 0));
        labelPanel.add(fileNameLabel);
        labelPanel.add(fileSizeLabel);
        labelPanel.add(bytesTransferredLabel);
        labelPanel.add(Box.createVerticalStrut(15));
        labelPanel.add(totalSizeLabel);
        labelPanel.add(totalTransferredLabel);
        JScrollPane scrollPane = new JScrollPane(labelPanel);
        scrollPane.setBorder(BorderFactory.createEmptyBorder(0, 0, 0, 0));
        pane.add(scrollPane);

        JPanel progressBarPanel = new JPanel(new GridLayout(1, 1));
        progressBarPanel.setBorder(BorderFactory.createEmptyBorder(0, 0, 0, 0));
        progressBarPanel.add(progressBar);

        // background color
        pane.setBackground(appletBackgroundColor);
        buttonPanel.setBackground(appletBackgroundColor);
        scrollPane.setBackground(appletBackgroundColor);
        progressBarPanel.setBackground(appletBackgroundColor);
        labelPanel.setBackground(appletBackgroundColor);

        // Always set the table background colour as White.
        // May change this if required, only would require alot of Params!
        scrollPane.getViewport().setBackground(Color.white);

        pane.add(progressBarPanel);

        uploadDownloadButton.requestFocus();
    }

    private void initComponentsUpload() {
        // Get the main pane to add content to.
        Container pane = getContentPane();
        pane.setLayout(new BoxLayout(pane, BoxLayout.Y_AXIS));

        JPanel buttonPanel = new JPanel();
        buttonPanel.setLayout(new BoxLayout(buttonPanel, BoxLayout.X_AXIS));
        buttonPanel.setBorder(BorderFactory.createEmptyBorder(0, 0, 0, 0));

        // new revision on name collision checkbox
        JPanel revOnCollisionPanel = new JPanel();
        revOnCollisionPanel.setBackground(Color.white);
        revOnCollisionPanel.setLayout(new BoxLayout(revOnCollisionPanel, BoxLayout.X_AXIS));
        revOnCollisionPanel.setBorder(BorderFactory.createEmptyBorder(0, 0, 0, 0));
        revOnCollisionCheckbox = new JCheckBox("Upload new revision if item name already exists");
        revOnCollisionCheckbox.setBackground(Color.white);
        revOnCollisionCheckbox.setSelected(false);
        revOnCollisionPanel.add(revOnCollisionCheckbox);
        revOnCollisionPanel.add(Box.createHorizontalGlue());

        // upload button
        uploadDownloadButton = new JButton("Upload Files");
        if (!directory) {
            uploadDownloadButton.addActionListener(new java.awt.event.ActionListener() {
                @Override
                public void actionPerformed(ActionEvent evt) {
                    uploadFileButtonActionPerformed(evt);
                }
            });
            buttonPanel.add(uploadDownloadButton);
        }

        uploadDirButton = new JButton("Upload Folder");
        if (directory) {
            uploadDirButton.addActionListener(new java.awt.event.ActionListener() {
                @Override
                public void actionPerformed(ActionEvent evt) {
                    uploadFolderButtonActionPerformed(evt);
                }
            });
            buttonPanel.add(uploadDirButton);
        }

        buttonPanel.add(Box.createHorizontalGlue());

        // resume button
        resumeButton.addActionListener(new java.awt.event.ActionListener() {
            @Override
            public void actionPerformed(ActionEvent evt) {
                resumeButtonActionPerformed(evt);
            }
        });
        buttonPanel.add(resumeButton);

        stopButton.addActionListener(new java.awt.event.ActionListener() {
            @Override
            public void actionPerformed(ActionEvent evt) {
                stopButtonActionPerformed(evt);
            }
        });
        buttonPanel.add(stopButton);

        if (!isRevisionUpload()) {
            pane.add(revOnCollisionPanel);
            pane.add(Box.createVerticalStrut(10));
        }
        pane.add(buttonPanel);
        pane.add(Box.createVerticalStrut(15));

        JPanel labelPanel = new JPanel();
        labelPanel.setLayout(new BoxLayout(labelPanel, BoxLayout.Y_AXIS));
        labelPanel.setBorder(BorderFactory.createEmptyBorder(0, 0, 0, 0));
        labelPanel.add(fileCountLabel);
        labelPanel.add(fileNameLabel);
        labelPanel.add(fileSizeLabel);
        labelPanel.add(bytesTransferredLabel);
        JScrollPane scrollPane = new JScrollPane(labelPanel);
        scrollPane.setBorder(BorderFactory.createEmptyBorder(0, 0, 0, 0));
        pane.add(scrollPane);

        JPanel progressBarPanel = new JPanel(new GridLayout(1, 1));
        progressBarPanel.setBorder(BorderFactory.createEmptyBorder(0, 0, 0, 0));

        progressBarPanel.add(progressBar);

        // background color
        pane.setBackground(appletBackgroundColor);
        buttonPanel.setBackground(appletBackgroundColor);
        scrollPane.setBackground(appletBackgroundColor);
        progressBarPanel.setBackground(appletBackgroundColor);
        labelPanel.setBackground(appletBackgroundColor);

        // Always set the table background colour as White.
        // May change this if required, only would require alot of Params!
        scrollPane.getViewport().setBackground(Color.white);

        pane.add(progressBarPanel);
    }

    /**
     * Are we uploading a revision?
     *
     * @return True if uploading a new revision, otherwise false
     */
    public boolean isRevisionUpload() {
        return revisionUpload;
    }

    public void markTopLevelDownloadComplete() {
        lastTopLevelDownloadOffset = totalTransferred;
    }

    public void onSuccessfulUpload() {
        uploadDownloadButton.setEnabled(true);
        stopButton.setEnabled(false);
        if (onSuccessfulUploadRedirectEnable) {
            getAppletContext().showDocument(onSuccessRedirectURLObj);
        }
    }

    public void pauseDownload(ActionEvent evt) {
        stopButton.setEnabled(false);
        downloadThread.forceClose();
        resumeButton.setEnabled(true);
    }

    public void reset() {
        uploadUniqueIdentifier = null;
        progressBar.setValue(0);
    }

    public void resetCurrentDownload(long size) {
        if (size < 0) {
            fileSizeLabel.setText(FILESIZE_LABEL_TITLE + "-");
            bytesTransferredLabel.setText(BYTE_TRANSFERRED_LABEL_TITLE + "-");
        } else {
            fileSizeLabel.setText(FILESIZE_LABEL_TITLE + Utility.bytesToString(size));
            bytesTransferredLabel.setText(BYTE_TRANSFERRED_LABEL_TITLE + "0 bytes");
        }
        transferredBytes = 0;
    }

    public void resumeButtonActionPerformed(ActionEvent evt) {
        Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] resume button clicked");
        setEnableResumeButton(false);
        setEnableStopButton(true);

        try {
            Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] Query server using:" + getUploadFileOffsetURL);
            long offset = Long.valueOf(Utility.queryHttpServer(getUploadFileOffsetURL));
            Utility.log(Utility.LOG_LEVEL.DEBUG, "[SERVER] offset:" + offset);
            uploadThread = new UploadThread(this);
            uploadThread.setUploadOffset(offset);
            uploadThread.setStartIndex(index);
            uploadThread.start();
        } catch (JavaUploaderException e) {
            setEnableResumeButton(true);
            setEnableStopButton(false);
            JOptionPane.showMessageDialog(this,
                    "Retry later - Connection with remote server impossible (open Java Console for more informations)",
                    "Resume upload failed", JOptionPane.ERROR_MESSAGE);
            Utility.log(Utility.LOG_LEVEL.ERROR,
                    "[CLIENT] Resume upload failed - Connection with remote server impossible", e);
        }
    }

    public void resumeDownload(ActionEvent evt) {
        Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] resume button clicked");
        setEnableResumeButton(false);
        totalTransferred = lastTopLevelDownloadOffset;
        increaseOverallProgress(0);

        int currItem = 0;
        int currFolder = 0;
        if (downloadThread != null) {
            currItem = downloadThread.getCurrentItem();
            currFolder = downloadThread.getCurrentFolder();
        }
        downloadThread = new DownloadThread(this, folderIds, itemIds);
        downloadThread.setCurrentItem(currItem);
        downloadThread.setCurrentFolder(currFolder);
        downloadThread.start();
        setEnableStopButton(true);

    }

    public boolean revOnCollision() {
        if (revisionUpload) {
            return false;
        }
        return revOnCollisionCheckbox.isSelected();
    }

    public void setByteUploadedLabel(long uploadedByte, long fileSize) {
        bytesTransferredLabel.setText("Transferred: " + Utility.bytesToString(uploadedByte));
    }

    public void setEnableResumeButton(boolean value) {
        resumeButton.setEnabled(value);
    }

    public void setEnableStopButton(boolean value) {
        stopButton.setEnabled(value);
    }

    public void setEnableUploadButton(boolean value) {
        uploadDownloadButton.setEnabled(value);
        if (uploadDirButton != null) {
            uploadDirButton.setEnabled(value);
        }
    }

    public void setFileCountLabel(int i, int n) {
        fileCountLabel.setText(FILECOUNT_LABEL_TITLE + i + " of " + n);
    }

    public void setFileNameLabel(String value) {
        fileNameLabel.setText(FILENAME_LABEL_TITLE + value);
    }

    public void setFileSizeLabel(long size) {
        if (size > 0) {
            fileSizeLabel.setText(FILESIZE_LABEL_TITLE + Utility.bytesToString(size));
        } else {
            fileSizeLabel.setText(FILESIZE_LABEL_TITLE + "Calculating...");
        }
    }

    public void setIndex(int index) {
        this.index = index;
    }

    public void setProgressIndeterminate(boolean value) {
        // this.progressBar.setIndeterminate(value);
    }

    public void setTotalSize(long size) {
        totalSize = size;
    }

    public void setUploadFileURL(String value) {
        uploadFileURL = value;
    }

    public void setUploadProgress(int index, long value) {
        transferredBytes = value;
        increaseUploadProgress(index, 0);
    }

    public void setUploadUniqueIdentifier(String value) {
        uploadUniqueIdentifier = value;
        getUploadFileOffsetURL = getUploadFileOffsetBaseURL + "?uploadUniqueIdentifier=" + uploadUniqueIdentifier;
    }

    public void stopButtonActionPerformed(ActionEvent evt) {
        Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] stop button clicked");
        stopButton.setEnabled(false);
        resumeButton.setEnabled(true);
        uploadThread.forceClose();
    }

    /**
     * Called when the upload files button is pressed. Prompts the user for
     * files to upload, then kicks off the upload thread.
     *
     * @param evt
     */
    public void uploadFileButtonActionPerformed(ActionEvent evt) {
        try {
            JFileChooser chooser = new JFileChooser();
            progressBar.setValue(0);
            if (fileExtensions != null) {
                UploaderFileFilter filter = new UploaderFileFilter();
                for (int i = 1; i < fileExtensions.length; i++) {
                    filter.addExtension(fileExtensions[i]);
                }
                filter.setDescription(fileExtensions[0]);
                chooser.addChoosableFileFilter(filter);
            } else {
                chooser.setFileFilter(chooser.getAcceptAllFileFilter());
            }
            chooser.setFileSelectionMode(JFileChooser.FILES_ONLY);
            chooser.setMultiSelectionEnabled(true);
            chooser.setDialogTitle("Select a file to upload");
            int returnVal = chooser.showOpenDialog(this);
            if (returnVal == JFileChooser.APPROVE_OPTION) {
                files = chooser.getSelectedFiles();
                fileLengths = new long[files.length];

                for (int i = 0; i < files.length; i++) {
                    fileLengths[i] = files[i].length();
                }

                // button setup
                uploadDownloadButton.setEnabled(false);
                stopButton.setEnabled(true);
                resumeButton.setEnabled(false);

                // initialize upload details
                transferredBytes = 0;

                // progress bar setup
                progressBar.setMinimum(0);
                progressBar.setMaximum(100);

                uploadThread = new UploadThread(this);
                uploadThread.start();
            }
        } catch (AccessControlException e) {
            Utility.log(Utility.LOG_LEVEL.WARNING, "JAR certificate may be corrupted", e);
        }
    }

    /**
     * Called when the upload folder button is pressed. Prompts the user for a
     * dir to upload, then kicks off the upload thread
     */
    public void uploadFolderButtonActionPerformed(ActionEvent evt) {
        try {
            progressBar.setValue(0);
            JFileChooser chooser = new JFileChooser();
            chooser.setFileSelectionMode(JFileChooser.DIRECTORIES_ONLY);
            chooser.setAcceptAllFileFilterUsed(false);
            chooser.setDialogTitle("Select a directory to upload");

            int returnVal = chooser.showOpenDialog(null);
            if (returnVal == JFileChooser.APPROVE_OPTION) {
                files = new File[] { chooser.getSelectedFile() };
                fileLengths = new long[] { 0 };

                // button setup
                uploadDownloadButton.setEnabled(false);
                uploadDirButton.setEnabled(false);
                stopButton.setEnabled(false);
                resumeButton.setEnabled(false);

                // initialize upload details
                transferredBytes = 0;

                // progress bar setup
                progressBar.setMinimum(0);
                progressBar.setMaximum(100);

                uploadThread = new UploadThread(this);
                uploadThread.start();
            }
        } catch (AccessControlException e) {
            Utility.log(Utility.LOG_LEVEL.WARNING, "JAR certificate may be corrupted", e);
        }
    }
}
