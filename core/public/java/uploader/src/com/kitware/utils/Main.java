package com.kitware.utils;

import java.awt.Color;
import java.awt.Container;
import java.awt.Dimension;
import java.awt.FlowLayout;
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

public class Main extends JApplet
  {
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
  private long currentSize = 0;
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

  private String[] fileExtensions;

  public void init()
    {
    try
      {
      this.getAppletParameters();
      }
    catch (JavaUploaderException e)
      {
      Utility.log(Utility.LOG_LEVEL.FATAL,
        "[CLIENT] Applet initialization failed", e);
      }

    try
      {
      final boolean downloadMode = this.download;
      javax.swing.SwingUtilities.invokeAndWait(new Runnable()
        {
        public void run()
          {
          initComponentsCommon();
          if(downloadMode)
            {
            initComponentsDownload();
            }
          else
            {
            initComponentsUpload();
            }
          }
        });
      }
    catch (Exception e)
      {
      Utility.log(Utility.LOG_LEVEL.FATAL, "[CLIENT] Failed to build GUI");
      }
    }

  /**
   * Initialize the view components common to both upload and download mode
   */
  private void initComponentsCommon()
    {
    // Set applet to native system L&F
    try
      {
      UIManager.setLookAndFeel(UIManager.getSystemLookAndFeelClassName());
      }
    catch (Exception e)
      {
      Utility.log(Utility.LOG_LEVEL.WARNING,
        "[CLIENT] Failed to set applet 'look&feel'", e);
      }
    progressBar = new JProgressBar();
    progressBar.setStringPainted(true);

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

  private void initComponentsDownload()
    {
 // Get the main pane to add content to.
    Container pane = getContentPane();
    pane.setLayout(new BoxLayout(pane, BoxLayout.Y_AXIS));

    JPanel buttonPanel = new JPanel();
    buttonPanel.setLayout(new BoxLayout(buttonPanel, BoxLayout.X_AXIS));
    buttonPanel.setBorder(BorderFactory.createEmptyBorder(0, 0, 0, 0));
    
    // upload button
    uploadDownloadButton = new JButton("Download");
    uploadDownloadButton.addActionListener(new java.awt.event.ActionListener()
      {
      public void actionPerformed(ActionEvent evt)
        {
        downloadButtonActionPerformed(evt);
        }
      });

    buttonPanel.add(uploadDownloadButton);
    buttonPanel.add(Box.createHorizontalGlue());
    
    resumeButton.addActionListener(new java.awt.event.ActionListener()
      {
      public void actionPerformed(ActionEvent evt)
        {
        resumeDownload(evt);
        }
      });
    buttonPanel.add(resumeButton);

    stopButton.addActionListener(new java.awt.event.ActionListener()
      {
        public void actionPerformed(ActionEvent evt)
          {
          pauseDownload(evt);
          }
      });
    buttonPanel.add(stopButton);

    totalSizeLabel = new JLabel(TOTAL_SIZE_LABEL_TITLE + Utility.bytesToString(this.totalSize));
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

  private void initComponentsUpload()
    {
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
    uploadDownloadButton.addActionListener(new java.awt.event.ActionListener()
      {
      public void actionPerformed(ActionEvent evt)
        {
        uploadFileButtonActionPerformed(evt);
        }
      });

    uploadDirButton = new JButton("Upload Folder");
    uploadDirButton.addActionListener(new java.awt.event.ActionListener()
      {
      public void actionPerformed(ActionEvent evt)
        {
        uploadFolderButtonActionPerformed(evt);
        }
      });

    buttonPanel.add(uploadDownloadButton);
    if(!this.isRevisionUpload())
      {
      buttonPanel.add(uploadDirButton);
      }
    buttonPanel.add(Box.createHorizontalGlue());

    // resume button
    resumeButton.addActionListener(new java.awt.event.ActionListener()
      {
      public void actionPerformed(ActionEvent evt)
        {
        resumeButtonActionPerformed(evt);
        }
      });
    buttonPanel.add(resumeButton);

    stopButton.addActionListener(new java.awt.event.ActionListener()
      {
        public void actionPerformed(ActionEvent evt)
          {
          stopButtonActionPerformed(evt);
          }
      });
    buttonPanel.add(stopButton);

    if(!this.isRevisionUpload())
      {
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

  // Helper method for getting the parameters from the webpage.
  private void getAppletParameters() throws JavaUploaderException
    {
    String loglevel = getParameter("loglevel");
    if (loglevel != null)
      {
      Utility.EFFECTIVE_LOG_LEVEL = Utility.LOG_LEVEL.valueOf(loglevel);
      }
    Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] EFFECTIVE_LOG_LEVEL:"
        + Utility.EFFECTIVE_LOG_LEVEL);

    String downloadMode = getParameter("downloadMode");
    if (downloadMode != null)
      {
      this.download = true;
      }
    Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] DOWNLOAD MODE ON");

    String onSuccessfulUploadRedirectEnableStr = getParameter("onSuccessfulUploadRedirectEnable");
    if (onSuccessfulUploadRedirectEnableStr != null)
      {
      this.onSuccessfulUploadRedirectEnable = Boolean
          .parseBoolean(onSuccessfulUploadRedirectEnableStr);
      }

    this.sessionId = getParameter("sessionId");
    Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] sessionId:" + this.sessionId);

    this.baseURL = getParameter("baseURL");
    Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] baseURL:" + baseURL);

    // applet background color
    String background = "";
    try
      {
      background = "0x" + getParameter("background");
      Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] background:" + background);
      appletBackgroundColor = Color.decode(background);
      }
    catch (NumberFormatException e)
      {
      throw new JavaUploaderException(
          "'background' applet parameter should either be a decimal, octal or hexidecimal integer:"
              + background, e);
      }
    catch (NullPointerException e)
      {
      Utility.log(Utility.LOG_LEVEL.WARNING,
        "[CLIENT] 'background' applet parameter unspecified: Rollback to default");
      }

    if (this.download)
      {
      this.totalSize = Long.parseLong(getParameter("totalSize"));
      this.folderIds = getParameter("folderIds");
      this.itemIds = getParameter("itemIds");
      }
    else
      {
      this.apiURL = getParameter("apiURL");
      // destURL is the address that this applet writes bytes to while
      // uploading a file
      this.uploadFileBaseURL = baseURL + getParameter("uploadFileBaseURL")
          + this.sessionId;
      Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] uploadFileBaseURL:"
          + this.uploadFileBaseURL);
  
      this.onSuccessRedirectURL = baseURL + getParameter("onSuccessRedirectURL");
      Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] onSuccessRedirectURL:"
          + this.onSuccessRedirectURL);
  
      // idURL generates connectionIDs. This is useful for resuming broken
      // downloads
      this.getUploadUniqueIdentifierBaseURL = baseURL
          + getParameter("uploadUniqueIdentifierURL") + this.sessionId;
      Utility.log(Utility.LOG_LEVEL.DEBUG,
          "[CLIENT] getUploadUniqueIdentifierBaseURL:"
              + this.getUploadUniqueIdentifierBaseURL);
  
      // offsetURL tells us how much of a file was received in the event
      // of a broken download
      this.getUploadFileOffsetBaseURL = baseURL
          + getParameter("getUploadFileOffsetBaseURL") + this.sessionId;
      Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] getUploadFileOffsetBaseURL:"
          + this.getUploadFileOffsetBaseURL);
  
      this.onSuccessRedirectURLObj = Utility.buildURL("onSuccessRedirect", this.onSuccessRedirectURL);
 
      try
        {
        this.fileExtensions = getParameter("fileextensions").split(",");
        }
      catch (NullPointerException e)
        {
        Utility.log(Utility.LOG_LEVEL.WARNING,
          "[CLIENT] 'fileextensions' applet parameter unspecified: Rollback to default");
        }

      String uploadType = getParameter("uploadType");
      this.revisionUpload = uploadType != null && uploadType.equals("revision");
      if (this.revisionUpload)
        {
        this.parentItem = getParameter("parentItem");
        // We don't use the base url here (we don't redirect to the upload controller)
        this.onSuccessRedirectURL = getParameter("onSuccessRedirectURL");
        this.onSuccessRedirectURLObj = Utility.buildURL("onSuccessRedirect", this.onSuccessRedirectURL);
        }
      }
    }

  public File[] getFiles()
    {
    return this.files;
    }

  public long getFileLength(int index)
    {
    return this.fileLengths[index];
    }

  public String getBaseURL()
    {
    return baseURL;
    }

  public String getApiURL()
    {
    return apiURL;
    }

  public String getGetUploadUniqueIdentifierBaseURL()
    {
    return getUploadUniqueIdentifierBaseURL;
    }

  public String getUploadFileBaseURL()
    {
    return uploadFileBaseURL;
    }

  public String getUploadFileURL()
    {
    return uploadFileURL;
    }

  public void setUploadFileURL(String value)
    {
    this.uploadFileURL = value;
    }

  public String getUploadUniqueIdentifier()
    {
    return uploadUniqueIdentifier;
    }

  public void setUploadUniqueIdentifier(String value)
    {
    this.uploadUniqueIdentifier = value;
    this.getUploadFileOffsetURL = this.getUploadFileOffsetBaseURL
        + "?uploadUniqueIdentifier=" + this.uploadUniqueIdentifier;
    }

  public void setEnableResumeButton(boolean value)
    {
    this.resumeButton.setEnabled(value);
    }

  public void setEnableStopButton(boolean value)
    {
    this.stopButton.setEnabled(value);
    }

  public void setEnableUploadButton(boolean value)
    {
    this.uploadDownloadButton.setEnabled(value);
    }

  public void setByteUploadedLabel(long uploadedByte, long fileSize)
    {
    bytesTransferredLabel.setText("Transferred: "
        + Utility.bytesToString(uploadedByte));
    }

  public void setFileNameLabel(String value)
    {
    this.fileNameLabel.setText(FILENAME_LABEL_TITLE + value);
    }

  public void setFileCountLabel(int i, int n)
    {
    this.fileCountLabel.setText(FILECOUNT_LABEL_TITLE + i + " of " + n);
    }

  public void setFileSizeLabel(long size)
    {
    if(size > 0)
      {
      this.fileSizeLabel.setText(FILESIZE_LABEL_TITLE
          + Utility.bytesToString(size));
      }
    else
      {
      this.fileSizeLabel.setText(FILESIZE_LABEL_TITLE + "Calculating...");
      }
    }

  public void setUploadProgress(int index, long value)
    {
    this.transferredBytes = value;
    increaseUploadProgress(index, 0);
    }

  public void setIndex(int index)
    {
    this.index = index;
    }

  public void increaseUploadProgress(int index, int value)
    {
    this.transferredBytes += value;
    int progress = (int) (100.0 * (double) this.transferredBytes / (double) this.fileLengths[index]);
    this.progressBar.setValue(progress);
    }

  public void increaseOverallProgress(long amount)
    {
    this.totalTransferred += amount;
    this.transferredBytes += amount;
    int totalProgress = (int) (100.0 * (double) this.totalTransferred / (double) this.totalSize);
    this.progressBar.setValue(totalProgress);
    bytesTransferredLabel.setText(BYTE_TRANSFERRED_LABEL_TITLE
        + Utility.bytesToString(this.transferredBytes));
    if(totalTransferredLabel != null)
      {
      totalTransferredLabel.setText(TOTAL_TRANSFERRED_LABEL_TITLE
          + Utility.bytesToString(this.totalTransferred));
      bytesTransferredLabel.setText(BYTE_TRANSFERRED_LABEL_TITLE
          + Utility.bytesToString(this.transferredBytes));
      }
    else
      {
      bytesTransferredLabel.setText(BYTE_TRANSFERRED_LABEL_TITLE
          + Utility.bytesToString(this.totalTransferred));
      }
    }

  public void resetCurrentDownload(long size)
    {
    if(size < 0)
      {
      this.fileSizeLabel.setText(FILESIZE_LABEL_TITLE + "-");
      this.bytesTransferredLabel.setText(BYTE_TRANSFERRED_LABEL_TITLE + "-");
      }
    else
      {
      this.fileSizeLabel.setText(FILESIZE_LABEL_TITLE + Utility.bytesToString(size));
      this.bytesTransferredLabel.setText(BYTE_TRANSFERRED_LABEL_TITLE + "0 bytes");
      }
    this.currentSize = size;
    this.transferredBytes = 0;
    }

  public void setProgressIndeterminate(boolean value)
    {
    this.progressBar.setIndeterminate(value);
    }

  public void onSuccessfulUpload()
    {
    this.uploadDownloadButton.setEnabled(true);
    this.stopButton.setEnabled(false);
    if (this.onSuccessfulUploadRedirectEnable)
      {
      this.getAppletContext().showDocument(this.onSuccessRedirectURLObj);
      }
    }

  public void reset()
    {
    this.uploadUniqueIdentifier = null;
    this.progressBar.setValue(0);
    }

  /**
   * Called when the download button is pressed. Prompts the user for a destination
   * directory, then starts the download thread.
   * @param evt
   */
  public void downloadButtonActionPerformed(ActionEvent evt)
    {
    progressBar.setValue(0);

    try
      {
      JFileChooser chooser = new JFileChooser();
      chooser.setDialogTitle("Choose a download destination");
      chooser.setFileSelectionMode(JFileChooser.DIRECTORIES_ONLY);
      chooser.setAcceptAllFileFilterUsed(false);

      int returnVal = chooser.showSaveDialog(null);
      if (returnVal == JFileChooser.APPROVE_OPTION)
        {
        this.downloadDest = chooser.getSelectedFile();

        // button setup
        this.uploadDownloadButton.setEnabled(false);
        this.stopButton.setEnabled(true);
        this.resumeButton.setEnabled(false);

        // initialize upload details
        this.transferredBytes = 0;

        // progress bar setup
        this.progressBar.setMinimum(0);
        this.progressBar.setMaximum(100);

        this.downloadThread = new DownloadThread(this, this.folderIds, this.itemIds);
        this.downloadThread.start();
        }
      }
    catch (AccessControlException e)
      {
      Utility.log(Utility.LOG_LEVEL.WARNING,
          "JAR certificate may be corrupted", e);
      }
    }

  /**
   * Called when the upload folder button is pressed.  Prompts the user for a dir to
   * upload, then kicks off the upload thread
   */
  public void uploadFolderButtonActionPerformed(ActionEvent evt)
    {
    try
      {
      progressBar.setValue(0);
      JFileChooser chooser = new JFileChooser();
      chooser.setFileSelectionMode(JFileChooser.DIRECTORIES_ONLY);
      chooser.setAcceptAllFileFilterUsed(false);
      chooser.setDialogTitle("Select a directory to upload");

      int returnVal = chooser.showOpenDialog(null);
      if (returnVal == JFileChooser.APPROVE_OPTION)
        {
        this.files = new File[] {chooser.getSelectedFile()};
        this.fileLengths = new long[] {0};

        // button setup
        this.uploadDownloadButton.setEnabled(false);
        this.uploadDirButton.setEnabled(false);
        this.stopButton.setEnabled(false);
        this.resumeButton.setEnabled(false);

        // initialize upload details
        this.transferredBytes = 0;

        // progress bar setup
        this.progressBar.setMinimum(0);
        this.progressBar.setMaximum(100);

        this.uploadThread = new UploadThread(this);
        this.uploadThread.start();
        }
      }
    catch (AccessControlException e)
      {
      Utility.log(Utility.LOG_LEVEL.WARNING,
          "JAR certificate may be corrupted", e);
      }
    }
  
  /**
   * Called when the upload files button is pressed. Prompts the user for files to upload,
   * then kicks off the upload thread.
   * @param evt
   */
  public void uploadFileButtonActionPerformed(ActionEvent evt)
    {
    try
      {
      JFileChooser chooser = new JFileChooser();
      progressBar.setValue(0);
      if (fileExtensions != null)
        {
        UploaderFileFilter filter = new UploaderFileFilter();
        for (int i = 1; i < fileExtensions.length; i++)
          {
          filter.addExtension(fileExtensions[i]);
          }
        filter.setDescription(fileExtensions[0]);
        chooser.addChoosableFileFilter(filter);
        }
      else
        {
        chooser.setFileFilter(chooser.getAcceptAllFileFilter());
        }
      chooser.setFileSelectionMode(JFileChooser.FILES_ONLY);
      chooser.setMultiSelectionEnabled(true);
      chooser.setDialogTitle("Select a file to upload");
      int returnVal = chooser.showOpenDialog(null);
      if (returnVal == JFileChooser.APPROVE_OPTION)
        {
        this.files = chooser.getSelectedFiles();
        this.fileLengths = new long[files.length];

        for (int i = 0; i < this.files.length; i++)
          {
          this.fileLengths[i] = this.files[i].length();
          }

        // button setup
        this.uploadDownloadButton.setEnabled(false);
        this.stopButton.setEnabled(true);
        this.resumeButton.setEnabled(false);

        // initialize upload details
        this.transferredBytes = 0;

        // progress bar setup
        this.progressBar.setMinimum(0);
        this.progressBar.setMaximum(100);

        this.uploadThread = new UploadThread(this);
        this.uploadThread.start();
        }
      }
    catch (AccessControlException e)
      {
      Utility.log(Utility.LOG_LEVEL.WARNING,
          "JAR certificate may be corrupted", e);
      }
    }

  public void stopButtonActionPerformed(ActionEvent evt)
    {
    Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] stop button clicked");
    stopButton.setEnabled(false);
    resumeButton.setEnabled(true);
    uploadThread.forceClose();
    }

  public void pauseDownload(ActionEvent evt)
    {
    stopButton.setEnabled(false);
    downloadThread.forceClose();
    resumeButton.setEnabled(true);
    }

  public void resumeDownload(ActionEvent evt)
    {
    Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] resume button clicked");
    this.setEnableResumeButton(false);
    this.totalTransferred = this.lastTopLevelDownloadOffset;
    this.increaseOverallProgress(0);

    int currItem = 0;
    int currFolder = 0;
    if(this.downloadThread != null)
      {
      currItem = this.downloadThread.getCurrentItem();
      currFolder = this.downloadThread.getCurrentFolder();
      }
    this.downloadThread = new DownloadThread(this, this.folderIds, this.itemIds);
    this.downloadThread.setCurrentItem(currItem);
    this.downloadThread.setCurrentFolder(currFolder);
    this.downloadThread.start();
    this.setEnableStopButton(true);
    
    }

  public void markTopLevelDownloadComplete()
    {
    this.lastTopLevelDownloadOffset = this.totalTransferred;
    }

  public void resumeButtonActionPerformed(ActionEvent evt)
    {
    Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] resume button clicked");
    this.setEnableResumeButton(false);
    this.setEnableStopButton(true);

    try
      {
      Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] Query server using:"
          + this.getUploadFileOffsetURL);
      long offset = Long.valueOf(Utility
          .queryHttpServer(this.getUploadFileOffsetURL));
      Utility.log(Utility.LOG_LEVEL.DEBUG, "[SERVER] offset:" + offset);
      this.uploadThread = new UploadThread(this);
      this.uploadThread.setUploadOffset(offset);
      this.uploadThread.setStartIndex(this.index);
      this.uploadThread.start();
      }
    catch (JavaUploaderException e)
      {
      this.setEnableResumeButton(true);
      this.setEnableStopButton(false);
      JOptionPane.showMessageDialog(this,
        "Retry later - Connection with remote server impossible (open Java Console for more informations)",
        "Resume upload failed", JOptionPane.ERROR_MESSAGE);
      Utility.log(Utility.LOG_LEVEL.ERROR,
        "[CLIENT] Resume upload failed - Connection with remote server impossible", e);
      }
    }

  /**
   * Are we uploading a revision?
   * 
   * @return True if uploading a new revision, otherwise false
   */
  public boolean isRevisionUpload()
    {
    return this.revisionUpload;
    }

  /**
   * Get the parent item in the case of a revision upload
   * @return the parent item id
   */
  public String getParentItem()
    {
    return this.parentItem;
    }

  public boolean revOnCollision()
    {
    if(this.revisionUpload)
      {
      return false;
      }
    return this.revOnCollisionCheckbox.isSelected();
    }

  public File getDownloadDest()
    {
    return this.downloadDest;
    }

  public void setTotalSize(long size)
    {
    this.totalSize = size;
    }
  }
