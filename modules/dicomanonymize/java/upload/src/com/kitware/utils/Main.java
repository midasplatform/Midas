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

import java.awt.Color;
import java.awt.Container;
import java.awt.GridLayout;
import java.awt.event.ActionEvent;
import java.io.BufferedInputStream;
import java.io.File;
import java.net.URL;
import java.security.AccessControlException;
import java.util.ArrayList;
import java.util.Properties;

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
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;

import org.w3c.dom.Document;

import com.kitware.utils.exception.JavaUploaderException;

public class Main extends JApplet {
    private static final long serialVersionUID = 2238283629688547425L;

    // GUI elements
    JTable table;
    JButton chooseDirButton, resumeButton, stopButton;
    TableColumn sizeColumn;
    public JProgressBar progressBar;
    JLabel fileCountLabel, fileNameLabel, fileSizeLabel, bytesTransferredLabel, totalSizeLabel, totalTransferredLabel;
    JCheckBox anonymizeCheckbox;
    private Color appletBackgroundColor = new Color(225, 225, 225);

    private final static String FILECOUNT_LABEL_TITLE = "File #: ";
    private final static String FILENAME_LABEL_TITLE = "Name: ";
    private final static String FILESIZE_LABEL_TITLE = "Size: ";
    private final static String BYTE_TRANSFERRED_LABEL_TITLE = "Transferred: ";

    // File upload
    private File[] files;
    private ArrayList<Integer> itemIds;
    private int index = 0, total = 0;
    private UploadThread uploadThread = null;
    private Properties daScriptProperties;

    private String baseURL, apiURL, getUploadUniqueIdentifierBaseURL, onSuccessRedirectURL, webroot;
    private String uploadFileBaseURL, uploadFileURL;
    private String getUploadFileOffsetBaseURL, getUploadFileOffsetURL;
    private String sessionId, uploadUniqueIdentifier = null;
    boolean onSuccessfulUploadRedirectEnable = true;

    public String getApiURL() {
        return apiURL;
    }

    // Helper method for getting the parameters from the webpage.
    private void getAppletParameters() throws JavaUploaderException {
        itemIds = new ArrayList<Integer>();

        String loglevel = getParameter("loglevel");
        if (loglevel != null) {
            Utility.EFFECTIVE_LOG_LEVEL = Utility.LOG_LEVEL.valueOf(loglevel);
        }
        Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] EFFECTIVE_LOG_LEVEL:" + Utility.EFFECTIVE_LOG_LEVEL);

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
        webroot = getParameter("webroot");
        apiURL = getParameter("apiURL");
        // destURL is the address that this applet writes bytes to while
        // uploading a file
        uploadFileBaseURL = baseURL + getParameter("uploadFileBaseURL") + sessionId;
        Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] uploadFileBaseURL:" + uploadFileBaseURL);

        onSuccessRedirectURL = webroot + getParameter("onSuccessRedirectURL");
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
    }

    public String getBaseURL() {
        return baseURL;
    }

    public Properties getDAScriptProperties() throws JavaUploaderException {
        if (daScriptProperties == null) {
            String daScriptUrl = getParameter("daScript");
            if (daScriptUrl == null) {
                throw new JavaUploaderException("Must pass a daScript parameter to the applet");
            }
            BufferedInputStream in = null;
            Document xmlDocument;
            try {
                in = new BufferedInputStream(new URL(daScriptUrl).openStream());

                // Build XML document from the input stream
                DocumentBuilderFactory dbf = DocumentBuilderFactory.newInstance();
                dbf.setNamespaceAware(true);
                DocumentBuilder db = dbf.newDocumentBuilder();
                xmlDocument = db.parse(in);
            } catch (Exception e) {
                throw new JavaUploaderException(e.getMessage());
            } finally {
                try {
                    if (in != null) {
                        in.close();
                    }
                } catch (Exception e) {
                }
            }
            if (xmlDocument == null) {
                throw new JavaUploaderException("Could not create properties from DA.script xml file");
            }
            daScriptProperties = Utility.buildDaScriptProperties(xmlDocument);
        }
        return daScriptProperties;
    }

    public File[] getFiles() {
        return files;
    }

    public String getGetUploadUniqueIdentifierBaseURL() {
        return getUploadUniqueIdentifierBaseURL;
    }

    public ArrayList<Integer> getItemIdList() {
        return itemIds;
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

    public String getWebroot() {
        return webroot;
    }

    public void increaseUploadProgress(int index, int value) {
        int progress = (int) (100.0 * ((double) index / (double) total));
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
            javax.swing.SwingUtilities.invokeAndWait(new Runnable() {
                @Override
                public void run() {
                    initComponents();
                }
            });
        } catch (Exception e) {
            Utility.log(Utility.LOG_LEVEL.FATAL, "[CLIENT] Failed to build GUI");
        }
    }

    private void initComponents() {
        // Set applet to native system L&F
        try {
            UIManager.setLookAndFeel(UIManager.getSystemLookAndFeelClassName());
        } catch (Exception e) {
            Utility.log(Utility.LOG_LEVEL.WARNING, "[CLIENT] Failed to set applet L&F", e);
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

        // Get the main pane to add content to.
        Container pane = getContentPane();
        pane.setLayout(new BoxLayout(pane, BoxLayout.Y_AXIS));

        JPanel buttonPanel = new JPanel();
        buttonPanel.setLayout(new BoxLayout(buttonPanel, BoxLayout.X_AXIS));
        buttonPanel.setBorder(BorderFactory.createEmptyBorder(0, 0, 0, 0));

        // new revision on name collision checkbox
        JPanel anonymizePanel = new JPanel();
        anonymizePanel.setBackground(Color.white);
        anonymizePanel.setLayout(new BoxLayout(anonymizePanel, BoxLayout.X_AXIS));
        anonymizePanel.setBorder(BorderFactory.createEmptyBorder(0, 0, 0, 0));
        anonymizeCheckbox = new JCheckBox("Anonymize files before uploading");
        anonymizeCheckbox.setBackground(Color.white);
        anonymizeCheckbox.setSelected(true);
        anonymizePanel.add(anonymizeCheckbox);
        anonymizePanel.add(Box.createHorizontalGlue());

        pane.add(anonymizePanel);
        pane.add(Box.createVerticalStrut(12));

        // upload button
        chooseDirButton = new JButton("Choose Folder");
        chooseDirButton.addActionListener(new java.awt.event.ActionListener() {
            @Override
            public void actionPerformed(ActionEvent evt) {
                uploadFolderButtonActionPerformed(evt);
            }
        });

        buttonPanel.add(chooseDirButton);
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

    public void redirectToItem(int itemId) throws JavaUploaderException {
        chooseDirButton.setEnabled(true);
        stopButton.setEnabled(false);
        if (onSuccessfulUploadRedirectEnable) {
            URL redirectURL = Utility.buildURL("onSuccessRedirect", onSuccessRedirectURL + itemId);
            getAppletContext().showDocument(redirectURL);
        }
    }

    public void reset() {
        uploadUniqueIdentifier = null;
        progressBar.setValue(0);
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
        chooseDirButton.setEnabled(value);
    }

    public void setFileCountLabel(int i, int n) {
        fileCountLabel.setText(FILECOUNT_LABEL_TITLE + i + " of " + n);
        total = n;
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

    public void setUploadFileURL(String value) {
        uploadFileURL = value;
    }

    public void setUploadProgress(int index, long value) {
        increaseUploadProgress(index, 0);
    }

    public void setUploadUniqueIdentifier(String value) {
        uploadUniqueIdentifier = value;
        getUploadFileOffsetURL = getUploadFileOffsetBaseURL + "?uploadUniqueIdentifier=" + uploadUniqueIdentifier;
    }

    public boolean shouldAnonymize() {
        return anonymizeCheckbox.isSelected();
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
    public void uploadFolderButtonActionPerformed(ActionEvent evt) {
        try {
            progressBar.setValue(0);
            JFileChooser chooser = new JFileChooser();
            chooser.setFileSelectionMode(JFileChooser.DIRECTORIES_ONLY);
            chooser.setAcceptAllFileFilterUsed(false);
            chooser.setDialogTitle("Select a directory to upload");

            int returnVal = chooser.showOpenDialog(null);
            if (returnVal == JFileChooser.APPROVE_OPTION) {
                File[] childrenFiles = chooser.getSelectedFile().listFiles();
                ArrayList<File> files = new ArrayList<File>();

                for (File child : childrenFiles) {
                    if (child.isFile()) {
                        files.add(child);
                    }
                }
                this.files = files.toArray(new File[] {});

                // button setup
                chooseDirButton.setEnabled(false);
                stopButton.setEnabled(false);
                resumeButton.setEnabled(false);

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
