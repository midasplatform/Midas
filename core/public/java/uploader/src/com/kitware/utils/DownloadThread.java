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
  private int currItem = 0;
  private int currFolder = 0;
  private String baseURL;
  private String[] itemIds, folderIds;
  private File dest, currentDir;
  private boolean paused, first;
  private FileOutputStream fileStream = null;

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
    this.first = true;
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
      if(currItem == 0)
      this.parentUI.setProgressIndeterminate(true);
      Utility.log(Utility.LOG_LEVEL.DEBUG, "[CLIENT] " + this.getClass().getName() + " started");
      for(int i = currFolder; i < folderIds.length; i++)
        {
        if(!folderIds[i].trim().equals(""))
          {
          this.downloadFolderRecursive(folderIds[i], this.getFolderName(folderIds[i]), dest);
          }
        if(this.paused)
          {
          return;
          }
        else
          {
          this.parentUI.markTopLevelDownloadComplete();
          currFolder++;
          }
        }
      for(int i = currItem; i < itemIds.length; i++)
        {
        if(!itemIds[i].trim().equals(""))
          {
          this.downloadItem(itemIds[i], dest);
          }
        if(this.paused)
          {
          return;
          }
        else
          {
          this.parentUI.markTopLevelDownloadComplete();
          currItem++;
          }
        }
      JOptionPane.showMessageDialog(this.parentUI, "Your download has finished.",
          "Done", JOptionPane.INFORMATION_MESSAGE);
      this.parentUI.progressBar.setValue(100);
      }
    catch (JavaUploaderException e)
      {
      JOptionPane.showMessageDialog(this.parentUI, e.getMessage(),
          "Download failed", JOptionPane.ERROR_MESSAGE);
      Utility.log(Utility.LOG_LEVEL.ERROR, "[CLIENT] DownloadThread failed", e);
      }
    }

  public int getCurrentFolder()
    {
    return this.currFolder;
    }

  public int getCurrentItem()
    {
    return this.currItem;
    }

  public void setCurrentFolder(int i)
    {
    this.currFolder = i;
    }

  public void setCurrentItem(int i)
    {
    this.currItem = i;
    }

  /**
   * Helper method to get the http response as a string.  Don't use for large responses,
   * just smallish text ones.
   * @return
   */
  private String getResponseText() throws IOException
    {
    InputStream respStream = conn.getInputStream();
    String resp = "";
    int len;
    byte[] buf = new byte[1024];
    while((len = respStream.read(buf, 0, 1024)) != -1)
      {
      resp += new String(buf, 0, len);
      }
    return resp;    
    }

  /**
   * Given the id of the folder, return its name
   * @param folderId
   * @return
   * @throws JavaUploaderException
   */
  private String getFolderName(String folderId) throws JavaUploaderException
    {
    String url = this.baseURL + "folder/getname?id=" + folderId;
    try
      {
      URL urlObj = Utility.buildURL("GetFolderName", url);
      conn = (HttpURLConnection) urlObj.openConnection();
      conn.setUseCaches(false);
      conn.setRequestMethod("GET");
      conn.setRequestProperty("Connection", "close");
      conn.setRequestProperty("Host", urlObj.getHost());

      if (conn.getResponseCode() != 200)
        {
        throw new JavaUploaderException("Exception occurred on server when requesting folder name for id="+folderId);
        }
      
      String name = this.getResponseText().trim();
      conn.disconnect();
      return name;
      }
    catch (IOException e)
      {
      conn.disconnect();
      throw new JavaUploaderException(e);
      }
    }

  /**
   * Download a folder recursively into the destination directory
   * @param folderId
   * @param name
   * @param directory
   */
  private void downloadFolderRecursive(String folderId, String name, File directory) throws JavaUploaderException
    {
    String url = this.baseURL + "folder/javachildren?id=" + folderId;
    this.parentUI.setFileNameLabel(name);
    this.parentUI.resetCurrentDownload(-1);

    File newDir = new File(directory, name);
    if(!newDir.exists())
      {
      if(!newDir.mkdir())
        {
        throw new JavaUploaderException("Could not create directory: "+newDir.getAbsolutePath());
        }
      }

    try
      {
      URL urlObj = Utility.buildURL("GetFolderChildren", url);
      conn = (HttpURLConnection) urlObj.openConnection();
      conn.setUseCaches(false);
      conn.setRequestMethod("GET");
      conn.setRequestProperty("Connection", "close");
      conn.setRequestProperty("Host", urlObj.getHost());

      if (conn.getResponseCode() != 200)
        {
        throw new JavaUploaderException("Exception occurred on server when requesting children for id="+folderId);
        }
      
      String[] resp = this.getResponseText().split("\n");

      for(String line : resp)
        {
        line = line.trim();
        if(line.equals(""))
          {
          continue;
          }
        String[] tokens = line.split(" ", 3);

        if(tokens[0].equals("f")) //folder
          {
          this.downloadFolderRecursive(tokens[1], tokens[2], newDir);
          }
        else //item
          {
          this.downloadItem(tokens[1], newDir);
          }
        }
      
      }
    catch (IOException e)
      {
      throw new JavaUploaderException(e);
      }
    finally
      {
      conn.disconnect();
      }
    }

  /**
   * Download an item into the specified directory
   * @param itemId The id of the item to download
   * @param directory The directory to download the item into
   */
  private void downloadItem(String itemId, File directory) throws JavaUploaderException
    {
    if(first)
      {
      this.parentUI.setProgressIndeterminate(false);
      first = false;
      }
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
      this.parentUI.setFileNameLabel(name);
      this.parentUI.resetCurrentDownload(-1);
      if (new File(directory, name).exists())
        {
        // skip the file if it has already been fully written
        conn.disconnect();
        this.parentUI.increaseDownloadProgress(new File(directory, name).length());
        return;
        }

      long size = conn.getContentLengthLong();
      if (size == -1)
        {
        // If this item is being ZipStreamed, we cannot resume, and must redownload it all (happens if head revision has > 1 bitstream)
        append = false;
        }
      else
        {
        this.parentUI.resetCurrentDownload(size);
        this.parentUI.increaseDownloadProgress(offset);
        }

      responseStream = new DataInputStream(conn.getInputStream());
      fileStream = new FileOutputStream(toWrite, append);
      byte[] buf = new byte[1048576];
      int len;
      while ((len = responseStream.read(buf, 0, buf.length)) != -1)
        {
        fileStream.write(buf, 0, len);
        this.parentUI.increaseDownloadProgress((long)len);
        }

      fileStream.close();
      // Final step: move the file to its completed name
      if(!this.paused)
        {
        toWrite.renameTo(new File(directory, name));
        }
      }
    catch (IOException e)
      {
      this.parentUI.setEnableResumeButton(true);
      this.parentUI.setEnableUploadButton(false);
      this.parentUI.setEnableStopButton(false);

      if(this.paused)
        {
        JOptionPane.showMessageDialog(this.parentUI, "Download paused. " +
            "Press the Resume button to continue.",
            "Connection problem", JOptionPane.INFORMATION_MESSAGE);
        }
      else
        {
        this.paused = true;
        Utility.log(Utility.LOG_LEVEL.WARNING, "[CLIENT] Catch IOException:"
            + IOEXCEPTION_ERROR_WRITING_REQUEST_BODY_TO_SERVER
            + " => Enable Resume");
        JOptionPane.showMessageDialog(this.parentUI, "Error communicating with the server. " +
            "Check your connection, then hit the Resume button.",
            "Connection problem", JOptionPane.WARNING_MESSAGE);
        }
      }
    finally
      {
      try
        {
        conn.disconnect();
        if(responseStream != null)
          {
          responseStream.close();
          }
        if(fileStream != null)
          {
          fileStream.close();
          }
        }
      catch(Exception e)
        {
        }
      }
    }
  }
