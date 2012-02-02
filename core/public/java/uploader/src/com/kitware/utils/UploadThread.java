package com.kitware.utils;

import java.io.DataOutputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.BufferedReader;
import java.io.IOException;
import java.io.FileNotFoundException;

import java.net.URL;
import java.net.HttpURLConnection;

import javax.swing.JOptionPane;

import com.kitware.utils.exception.JavaUploaderException;

public class UploadThread extends Thread
  {
  private HttpURLConnection conn = null;
  private Main uploader;
  private long uploadOffset = 0;
  private int startIndex = 0;
  private String getUploadUniqueIdentifierBaseURL;
  private String uploadFileBaseURL, uploadFileURL;
  private boolean paused;

  public static String IOEXCEPTION_ERROR_WRITING_REQUEST_BODY_TO_SERVER = "Error writing request body to server";

  private DataOutputStream output = null; 

  public UploadThread(Main uploader)
    {
    Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] "
        + this.getClass().getName() + " initialized");
    this.uploader = uploader;
    this.getUploadUniqueIdentifierBaseURL = this.uploader
        .getGetUploadUniqueIdentifierBaseURL();
    this.uploadFileBaseURL = this.uploader.getUploadFileBaseURL();
    this.paused = false;
    }

  public void setStartIndex(int index)
    {
    this.startIndex = index;
    }

  public void forceClose()
    {
    if (conn != null)
      {
      conn.disconnect();
      this.paused = true;
      }
    }

  public void setUploadOffset(long uploadOffset) throws JavaUploaderException
    {
    if (this.isAlive())
      {
      throw new JavaUploaderException("Failed to set uploadOffset while "
          + this.getClass().getName() + " is running");
      }
    this.uploadOffset = uploadOffset;
    }

  public void run()
    {
    try
      {
      Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] "
          + this.getClass().getName() + " started");
      for (int i = this.startIndex; i < this.uploader.getFiles().length; i++)
        {
        uploader.setIndex(i);
        uploader.setFileCountLabel(i + 1, this.uploader.getFiles().length);
        uploader.setFileSizeLabel(this.uploader.getFileLength(i));
        uploader.setFileNameLabel(this.uploader.getFiles()[i].getName());
        uploadFile(i, this.uploader.getFiles()[i]);
        this.uploadOffset = 0;
        if(this.paused)
          {
          return;
          }
        }
      }
    catch (JavaUploaderException e)
      {
      // "To obtain further information regarding this error, please turn on the Java Console"
      JOptionPane.showMessageDialog(this.uploader, e.getMessage(),
          "Upload failed", JOptionPane.ERROR_MESSAGE);
      Utility.log(Utility.LOG_LEVEL.ERROR, "[CLIENT] UploadThread failed", e);
      }
    }

  private void uploadFile(int i, File file) throws JavaUploaderException
    {
    // generate URLs
    String filename = file.getName().replace(" ", "_");
    String getUploadUniqueIdentifierURL = this.getUploadUniqueIdentifierBaseURL
        + "?filename=" + filename;
    if(uploader.isRevisionUpload())
      {
      getUploadUniqueIdentifierURL += "&revision=true&itemId=" + uploader.getParentItem();
      }

    // retrieve uploadUniqueIdentifier
    if (this.uploader.getUploadUniqueIdentifier() == null)
      {
      Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] Query server using:"
          + getUploadUniqueIdentifierURL);
      this.uploader.setUploadUniqueIdentifier(Utility.queryHttpServer(getUploadUniqueIdentifierURL));
      Utility.log(Utility.LOG_LEVEL.DEBUG, "[SERVER] uploadUniqueIdentifier:"
          + this.uploader.getUploadUniqueIdentifier());
      }
    else
      {
      Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] Re-use existing uploadUniqueIdentifier:"
        + this.uploader.getUploadUniqueIdentifier());
      }

    FileInputStream fileStream = null;
    int finalByteSize = 0;
    try
      {
      fileStream = new FileInputStream(file);
      fileStream.skip(this.uploadOffset);
      uploader.setUploadProgress(i, this.uploadOffset);
      }
    catch (FileNotFoundException e)
      {
      throw new JavaUploaderException("File '" + file.getPath()
          + "' doesn't exist");
      }
    catch (IOException e)
      {
      throw new JavaUploaderException("Failed to read file '" + file.getPath()
          + "'");
      }

    this.uploadFileURL = this.uploadFileBaseURL + "&filename=" + filename
      + "&uploadUniqueIdentifier=" + this.uploader.getUploadUniqueIdentifier() + "&length="
      + uploader.getFileLength(i);
    if(uploader.isRevisionUpload())
      {
      this.uploadFileURL += "&itemId=" + uploader.getParentItem();
      }
    URL uploadFileURLObj = Utility.buildURL("UploadFile", this.uploadFileURL);

    try
      {
      Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] Query server using:" + this.uploadFileURL);
      conn = (HttpURLConnection) uploadFileURLObj.openConnection();
      conn.setDoInput(true); // Allow Inputs
      conn.setDoOutput(true); // Allow Outputs
      conn.setUseCaches(false); // Don't use a cached copy.
      conn.setRequestMethod("PUT"); // Use a PUT method.
      conn.setRequestProperty("Connection", "close");
      conn.setRequestProperty("Host", uploadFileURLObj.getHost());
      conn.setRequestProperty("Content-Type", "application/octet-stream");
      conn.setRequestProperty("Content-Length",
          String.valueOf(uploader.getFileLength(i) - this.uploadOffset));
      conn.setChunkedStreamingMode(1048576);

      output = new DataOutputStream(conn.getOutputStream());

      int maxBufferSize = 1048576;
      long bytesWritten = this.uploadOffset;
      long fileSize = uploader.getFileLength(i);
      long bytesAvailable = fileSize;
      int bufferSize = (int) Math.min(bytesAvailable, maxBufferSize);
      byte buffer[] = new byte[bufferSize];
      fileStream.read(buffer, 0, bufferSize);
      while (bytesAvailable > 0 && bytesWritten < fileSize)
        {
        Utility.log(Utility.LOG_LEVEL.LOG, "[CLIENT] Read " + bufferSize
            + " bytes from file");
        output.write(buffer, 0, bufferSize);
        Utility.log(Utility.LOG_LEVEL.LOG, "[CLIENT] Wrote " + bufferSize
            + " bytes into OutputStream");
        bytesWritten += bufferSize;
        uploader.setByteUploadedLabel(bytesWritten, fileSize);
        if (bufferSize == maxBufferSize)
          {
          uploader.increaseUploadProgress(i, bufferSize);
          }
        else
          {
          finalByteSize = bufferSize;
          }
        bytesAvailable = fileSize - bytesWritten;
        bufferSize = (int) Math.min(bytesAvailable, maxBufferSize);
        fileStream.read(buffer, 0, bufferSize);
        }

      output.flush();
      output.close();

      Utility.log(Utility.LOG_LEVEL.DEBUG,
          "[CLIENT] Wait for server answer ...");

      uploader.increaseUploadProgress(i, finalByteSize); // update GUI
      uploader.reset();
      if (i + 1 == uploader.getFiles().length)
        {
        uploader.onSuccessfulUpload();
        }
      }
    catch (IOException e)
      {
      String message = e.getMessage();
      if (message != null
          && message.equals(IOEXCEPTION_ERROR_WRITING_REQUEST_BODY_TO_SERVER))
        {
        Utility.log(Utility.LOG_LEVEL.WARNING, "[CLIENT] Catch IOException:"
            + IOEXCEPTION_ERROR_WRITING_REQUEST_BODY_TO_SERVER
            + " => Enable ResumeUpload");
        this.uploader.setEnableResumeButton(true);
        this.uploader.setEnableUploadButton(false);
        this.uploader.setEnableStopButton(false);
        }
      else
        {
        throw new JavaUploaderException(e);
        }
      }
    finally
      {
      if (conn != null)
        {
        InputStream inputStream = null;
        try
          {
          inputStream = conn.getInputStream();
          }
        catch (Exception e)
          {
          inputStream = null;
          }

        InputStream errorInputStream = conn.getErrorStream();

        if (inputStream == null && errorInputStream != null)
          {
          inputStream = errorInputStream;
          }

        if (inputStream != null)
          {
          String msg = Utility.getMessage(new BufferedReader(new InputStreamReader(inputStream)));
          Utility.log(Utility.LOG_LEVEL.DEBUG, "[SERVER] " + msg);
          this.uploader.setUploadStatusLabel(msg);
          try
            {
            inputStream.close();
            }
          catch (IOException e)
            {
            Utility.log(Utility.LOG_LEVEL.ERROR, "[CLIENT] Failed to close ErrorStream", e);
            }
          }
        conn.disconnect();
        }
      }
    }
  }
