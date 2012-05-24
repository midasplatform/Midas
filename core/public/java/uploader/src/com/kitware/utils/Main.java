package com.kitware.utils;

import java.awt.Color;
import java.awt.Container;
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
  JButton uploadFileButton, resumeUploadButton, stopUploadButton;
  TableColumn sizeColumn;
  JProgressBar uploadProgressBar;
  JLabel fileCountLabel, fileNameLabel, fileSizeLabel,
      bytesUploadedLabel;
  JCheckBox revOnCollisionCheckbox;
  private Color appletBackgroundColor = new Color(225, 225, 225);

  private final static String FILECOUNT_LABEL_TITLE = "File #: ";
  private final static String FILENAME_LABEL_TITLE = "Name: ";
  private final static String FILESIZE_LABEL_TITLE = "Size: ";
  private final static String BYTE_TRANSFERRED_LABEL_TITLE = "Transferred: ";

  // File upload
  private File[] files;
  private long[] fileLengths;
  private int index = 0;
  private long uploadedBytes = 0;
  private UploadThread uploadThread = null;

  private String getUploadUniqueIdentifierBaseURL, onSuccessRedirectURL;
  private String uploadFileBaseURL, uploadFileURL;
  private String getUploadFileOffsetBaseURL, getUploadFileOffsetURL;
  private String sessionId, uploadUniqueIdentifier = null;
  private String parentItem = "";
  private boolean revisionUpload = false;
  private URL onSuccessRedirectURLObj;
  boolean onSuccessfulUploadRedirectEnable = true;

  private String[] fileExtensions;

  public void init()
    {
    try
      {
      getAppletParameters();
      }
    catch (JavaUploaderException e)
      {
      Utility.log(Utility.LOG_LEVEL.FATAL,
        "[CLIENT] Applet initialization failed", e);
      }

    try
      {
      javax.swing.SwingUtilities.invokeAndWait(new Runnable()
        {
        public void run()
          {
          initComponents();
          }
        });
      }
    catch (Exception e)
      {
      Utility.log(Utility.LOG_LEVEL.FATAL, "[CLIENT] Failed to build GUI");
      }
    }

  private void initComponents()
    {
    // Set the look of the applet
    try
      {
      UIManager.setLookAndFeel(UIManager.getSystemLookAndFeelClassName());
      }
    catch (Exception e)
      {
      Utility.log(Utility.LOG_LEVEL.WARNING,
        "[CLIENT] Failed to set applet 'look&feel'", e);
      }

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
    uploadFileButton = new JButton("Upload");
    uploadFileButton.addActionListener(new java.awt.event.ActionListener()
      {
      public void actionPerformed(ActionEvent evt)
        {
        uploadFileButtonActionPerformed(evt);
        }
      });

    buttonPanel.add(uploadFileButton);

    buttonPanel.add(Box.createHorizontalGlue());

    // resume button
    resumeUploadButton = new JButton("Resume");
    resumeUploadButton.addActionListener(new java.awt.event.ActionListener()
      {
      public void actionPerformed(ActionEvent evt)
        {
        resumeUploadButtonActionPerformed(evt);
        }
      });
    resumeUploadButton.setEnabled(false);
    buttonPanel.add(resumeUploadButton);

    stopUploadButton = new JButton("Stop");
    stopUploadButton.addActionListener(new java.awt.event.ActionListener()
      {
        public void actionPerformed(ActionEvent evt)
          {
          stopUploadButtonActionPerformed(evt);
          }
      });
    stopUploadButton.setEnabled(false);
    buttonPanel.add(stopUploadButton);

    if(!this.isRevisionUpload())
      {
      pane.add(revOnCollisionPanel);
      pane.add(Box.createVerticalStrut(10));
      }
    pane.add(buttonPanel);
    pane.add(Box.createVerticalStrut(15));

    // info labels
    fileNameLabel = new JLabel(FILENAME_LABEL_TITLE);
    fileSizeLabel = new JLabel(FILESIZE_LABEL_TITLE + "0 bytes");
    fileCountLabel = new JLabel(FILECOUNT_LABEL_TITLE);
    bytesUploadedLabel = new JLabel(BYTE_TRANSFERRED_LABEL_TITLE + "0 bytes");

    JPanel labelPanel = new JPanel();
    labelPanel.setLayout(new BoxLayout(labelPanel, BoxLayout.Y_AXIS));
    labelPanel.setBorder(BorderFactory.createEmptyBorder(0, 0, 0, 0));
    labelPanel.add(fileCountLabel);
    labelPanel.add(fileNameLabel);
    labelPanel.add(fileSizeLabel);
    labelPanel.add(bytesUploadedLabel);
    JScrollPane scrollPane = new JScrollPane(labelPanel);
    scrollPane.setBorder(BorderFactory.createEmptyBorder(0, 0, 0, 0));
    pane.add(scrollPane);

    JPanel progressBarPanel = new JPanel(new GridLayout(1, 1));
    progressBarPanel.setBorder(BorderFactory.createEmptyBorder(0, 0, 0, 0));

    uploadProgressBar = new JProgressBar();
    uploadProgressBar.setStringPainted(true);

    progressBarPanel.add(uploadProgressBar);

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

    String onSuccessfulUploadRedirectEnableStr = getParameter("onSuccessfulUploadRedirectEnable");
    if (onSuccessfulUploadRedirectEnableStr != null)
      {
      this.onSuccessfulUploadRedirectEnable = Boolean
          .parseBoolean(onSuccessfulUploadRedirectEnableStr);
      }

    this.sessionId = getParameter("sessionId");
    Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] sessionId:" + this.sessionId);

    String baseURL = getParameter("baseURL");
    Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] baseURL:" + baseURL);

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

  public File[] getFiles()
    {
    return this.files;
    }

  public long getFileLength(int index)
    {
    return this.fileLengths[index];
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
    this.resumeUploadButton.setEnabled(value);
    }

  public void setEnableStopButton(boolean value)
    {
    this.stopUploadButton.setEnabled(value);
    }

  public void setEnableUploadButton(boolean value)
    {
    this.uploadFileButton.setEnabled(value);
    }

  public void setByteUploadedLabel(long uploadedByte, long fileSize)
    {
    bytesUploadedLabel.setText("Transfered: "
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
    this.fileSizeLabel.setText(FILESIZE_LABEL_TITLE
        + Utility.bytesToString(size));
    }

  public void setUploadProgress(int index, long value)
    {
    this.uploadedBytes = value;
    increaseUploadProgress(index, 0);
    }

  public void setIndex(int index)
    {
    this.index = index;
    }

  public void increaseUploadProgress(int index, int value)
    {
    this.uploadedBytes += value;
    int progress = (int) (100.0 * (double) this.uploadedBytes / (double) this.fileLengths[index]);
    this.uploadProgressBar.setValue(progress);
    }

  public void setProgressIndeterminate(boolean value)
    {
    this.uploadProgressBar.setIndeterminate(value);
    }

  public void onSuccessfulUpload()
    {
    this.uploadFileButton.setEnabled(true);
    this.stopUploadButton.setEnabled(false);
    if (this.onSuccessfulUploadRedirectEnable)
      {
      this.getAppletContext().showDocument(this.onSuccessRedirectURLObj);
      }
    }

  public void reset()
    {
    this.uploadUniqueIdentifier = null;
    this.uploadProgressBar.setValue(0);
    }

  public void uploadFileButtonActionPerformed(ActionEvent evt)
    {

    try
      {
      JFileChooser chooser = new JFileChooser();
      uploadProgressBar.setValue(0);
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
        this.uploadFileButton.setEnabled(false);
        this.stopUploadButton.setEnabled(true);
        this.resumeUploadButton.setEnabled(false);

        // initialize upload details
        this.uploadedBytes = 0;

        // progress bar setup
        this.uploadProgressBar.setMinimum(0);
        this.uploadProgressBar.setMaximum(100);

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

  public void stopUploadButtonActionPerformed(ActionEvent evt)
    {
    Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] StopUpload button clicked");
    stopUploadButton.setEnabled(false);
    resumeUploadButton.setEnabled(true);
    uploadThread.forceClose();
    uploadThread.interrupt();
    }

  public void resumeUploadButtonActionPerformed(ActionEvent evt)
    {
    Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] ResumeUpload button clicked");
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
  }
