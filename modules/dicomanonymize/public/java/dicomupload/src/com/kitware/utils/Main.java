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

public class Main extends JApplet
  {
  private static final long serialVersionUID = 2238283629688547425L;

  // GUI elements
  JTable table;
  JButton chooseDirButton, resumeButton, stopButton;
  TableColumn sizeColumn;
  JProgressBar progressBar;
  JLabel fileCountLabel, fileNameLabel, fileSizeLabel, bytesTransferredLabel,
      totalSizeLabel, totalTransferredLabel;
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

  private String baseURL, apiURL, getUploadUniqueIdentifierBaseURL,
      onSuccessRedirectURL, webroot;
  private String uploadFileBaseURL, uploadFileURL;
  private String getUploadFileOffsetBaseURL, getUploadFileOffsetURL;
  private String sessionId, uploadUniqueIdentifier = null;
  boolean onSuccessfulUploadRedirectEnable = true;

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
    // Set applet to native system L&F
    try
      {
      UIManager.setLookAndFeel(UIManager.getSystemLookAndFeelClassName());
      }
    catch (Exception e)
      {
      Utility.log(Utility.LOG_LEVEL.WARNING, "[CLIENT] Failed to set applet L&F", e);
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
    chooseDirButton.addActionListener(new java.awt.event.ActionListener()
      {
        public void actionPerformed(ActionEvent evt)
          {
          uploadFolderButtonActionPerformed(evt);
          }
      });

    buttonPanel.add(chooseDirButton);
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
    this.itemIds = new ArrayList<Integer>();

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
    Utility
        .log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] sessionId:" + this.sessionId);

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
      Utility.log(Utility.LOG_LEVEL.WARNING, "[CLIENT] 'background' applet parameter unspecified: Rollback to default");
      }
    this.webroot = getParameter("webroot");
    this.apiURL = getParameter("apiURL");
    // destURL is the address that this applet writes bytes to while
    // uploading a file
    this.uploadFileBaseURL = baseURL + getParameter("uploadFileBaseURL")
        + this.sessionId;
    Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] uploadFileBaseURL:"
        + this.uploadFileBaseURL);

    this.onSuccessRedirectURL = this.webroot + getParameter("onSuccessRedirectURL");
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
    }

  public File[] getFiles()
    {
    return this.files;
    }

  public String getWebroot()
    {
    return this.webroot;
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
    this.chooseDirButton.setEnabled(value);
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
    this.total = n;
    }

  public void setFileSizeLabel(long size)
    {
    if (size > 0)
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
    increaseUploadProgress(index, 0);
    }

  public void setIndex(int index)
    {
    this.index = index;
    }

  public void increaseUploadProgress(int index, int value)
    {
    int progress = (int) (100.0 * ((double)index / (double)this.total));
    this.progressBar.setValue(progress);
    }

  public void redirectToItem(int itemId) throws JavaUploaderException
    {
    this.chooseDirButton.setEnabled(true);
    this.stopButton.setEnabled(false);
    if (this.onSuccessfulUploadRedirectEnable)
      {
      URL redirectURL = Utility.buildURL("onSuccessRedirect", this.onSuccessRedirectURL + itemId);
      this.getAppletContext().showDocument(redirectURL);
      }
    }

  public void reset()
    {
    this.uploadUniqueIdentifier = null;
    this.progressBar.setValue(0);
    }

  /**
   * Called when the upload files button is pressed. Prompts the user for files
   * to upload, then kicks off the upload thread.
   * 
   * @param evt
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
        File[] childrenFiles = chooser.getSelectedFile().listFiles();
        ArrayList<File> files = new ArrayList<File>();

        for(File child : childrenFiles)
          {
          if(child.isFile())
            {
            files.add(child);
            }
          }
        this.files = files.toArray(new File[]{});

        // button setup
        this.chooseDirButton.setEnabled(false);
        this.stopButton.setEnabled(false);
        this.resumeButton.setEnabled(false);

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


  public void resumeButtonActionPerformed(ActionEvent evt)
    {
    Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] resume button clicked");
    this.setEnableResumeButton(false);
    this.setEnableStopButton(true);

    try
      {
      Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] Query server using:"
          + this.getUploadFileOffsetURL);
      long offset = Long.valueOf(Utility.queryHttpServer(this.getUploadFileOffsetURL));
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
      JOptionPane.showMessageDialog(this, "Retry later - Connection with remote server impossible (open Java Console for more informations)",
          "Resume upload failed", JOptionPane.ERROR_MESSAGE);
      Utility.log(Utility.LOG_LEVEL.ERROR, "[CLIENT] Resume upload failed - Connection with remote server impossible", e);
      }
    }

  public Properties getDAScriptProperties() throws JavaUploaderException
    {
    if(this.daScriptProperties == null)
      {
      String daScriptUrl = getParameter("daScript");
      if (daScriptUrl == null)
        {
        throw new JavaUploaderException("Must pass a daScript parameter to the applet");
        }
      BufferedInputStream in = null;
      Document xmlDocument;
      try
        {
        in = new BufferedInputStream(new URL(daScriptUrl).openStream());

        // Build XML document from the input stream
        DocumentBuilderFactory dbf = DocumentBuilderFactory.newInstance();
        dbf.setNamespaceAware(true);
        DocumentBuilder db = dbf.newDocumentBuilder();
        xmlDocument = db.parse(in);
        }
      catch(Exception e)
        {
        throw new JavaUploaderException(e.getMessage());
        }
      finally
        {
        try
          {
          if (in != null)
            {
            in.close();
            }
          }
        catch(Exception e)
          {
          }
        }
      if(xmlDocument == null)
        {
        throw new JavaUploaderException("Could not create properties from DA.script xml file");
        }
      this.daScriptProperties = Utility.buildDaScriptProperties(xmlDocument);
      }
    return this.daScriptProperties;
    }

  public ArrayList<Integer> getItemIdList()
    {
    return this.itemIds;
    }

  public boolean shouldAnonymize()
    {
    return this.anonymizeCheckbox.isSelected();
    }
  }
