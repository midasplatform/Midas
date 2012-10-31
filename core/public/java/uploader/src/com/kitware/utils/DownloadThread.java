package com.kitware.utils;

import java.io.DataInputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.BufferedReader;
import java.io.IOException;
import java.io.FileNotFoundException;

import java.net.URL;
import java.net.HttpURLConnection;

import javax.swing.JOptionPane;

import com.kitware.utils.exception.JavaUploaderException;

public class DownloadThread extends Thread
  {
  private HttpURLConnection conn = null;
  private Main parentUI;
  private long uploadOffset = 0;
  private int startIndex = 0;
  private String baseURL;
  private String[] itemIds, folderIds;
  private File dest, currentDir;
  private boolean paused;

  public static String IOEXCEPTION_ERROR_WRITING_REQUEST_BODY_TO_SERVER = "Error writing request body to server";

  private DataInputStream responseStream = null; 

  public DownloadThread(Main parentUI, String folderIds, String itemIds)
    {
    Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] " + this.getClass().getName() + " initialized");
    this.parentUI = parentUI;
    this.baseURL = this.parentUI.getBaseURL();
    this.dest = this.parentUI.getDownloadDest();
    this.folderIds = folderIds.split(",");
    this.itemIds = itemIds.split(",");
    this.paused = false;
    }

  public void forceClose()
    {
    if (conn != null)
      {
      conn.disconnect();
      this.paused = true;
      }
    }

  public void run()
    {
    try
      {
      Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] " + this.getClass().getName() + " started");
      for(int i = 0; i < folderIds.length; i++)
        {
        if(!folderIds[i].trim().equals(""))
          {
          this.downloadFolderRecursive(folderIds[i], dest);
          }
        }
      for(int i = 0; i < itemIds.length; i++)
        {
        if(!itemIds[i].trim().equals(""))
          {
          this.downloadItem(itemIds[i], dest);
          }
        }
      }
    catch (JavaUploaderException e)
      {
      JOptionPane.showMessageDialog(this.parentUI, e.getMessage(),
          "Download failed", JOptionPane.ERROR_MESSAGE);
      Utility.log(Utility.LOG_LEVEL.ERROR, "[CLIENT] DownloadThread failed", e);
      }
    }

  /**
   * Download a folder recursively into the destination directory
   * @param folderId
   * @param directory
   * @return
   */
  private void downloadFolderRecursive(String folderId, File directory) throws JavaUploaderException
    {
    
    }

  /**
   * Download an item into the specified directory
   * @param itemId The id of the item to download
   * @param directory The directory to download the item into
   */
  private void downloadItem(String itemId, File directory) throws JavaUploaderException
    {
    // First check if partially downloaded file exists.  If so we append to it and pass an offset
    long offset = 0;
    boolean append = false;
    File toWrite = new File(directory, itemId+".midasdl.part"); 
    if(toWrite.exists())
      {
      offset = toWrite.length(); 
      append = true;
      }

    String url = this.baseURL + "download?items=" + itemId + "&offset=" + offset;

    try
      {
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
      if (conn.getResponseCode() != 200)
        {
        conn.disconnect();
        return;
        }

      String name = conn.getHeaderField("Content-Disposition").split("=")[1];
      name = name.replaceAll("\"", ""); //strip quotes from content disposition file name token
      if (new File(directory, name).exists())
        {
        // skip the file if it has already been fully written
        conn.disconnect();
        // TODO increment progress in the parent UI by the length of the file on disk 
        return;
        }

      if (conn.getContentLength() == -1)
        {
        // If this item is being ZipStreamed, we cannot resume, and must redownload it all (happens if head revision has > 1 bitstream)
        append = false;
        }

      responseStream = new DataInputStream(conn.getInputStream());
      FileOutputStream fileStream = new FileOutputStream(toWrite, append);
      byte[] buf = new byte[1048576];
      int len;
      while ((len = responseStream.read(buf, 0, buf.length)) != -1)
        {
        fileStream.write(buf, 0, len);
        // TODO increment parent UI progress by len
        }
      fileStream.close();
      responseStream.close();

      // Final step: move the file to its completed name
      toWrite.renameTo(new File(directory, name)); 
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
        //this.uploader.setEnableResumeButton(true);
        //this.uploader.setEnableUploadButton(false);
        //this.uploader.setEnableStopButton(false);
        }
      else
        {
        throw new JavaUploaderException(e);
        }
      }
    finally
      {
      conn.disconnect();
      }
    }
  }
