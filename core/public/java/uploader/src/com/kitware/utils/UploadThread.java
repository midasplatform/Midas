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

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

public class UploadThread extends Thread
  {
  private HttpURLConnection conn = null;
  private Main uploader;
  private long uploadOffset = 0;
  private int startIndex = 0, totalFiles, currentFileNumber;
  private String getUploadUniqueIdentifierBaseURL;
  private String uploadFileBaseURL, uploadFileURL, baseURL, apiURL;
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
    this.baseURL = this.uploader.getBaseURL();
    this.apiURL = this.uploader.getApiURL();
    this.paused = false;
    this.currentFileNumber = 0;
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
      File[] files = this.uploader.getFiles();
      for (int i = this.startIndex; i < files.length; i++)
        {
        if(files[i].isDirectory())
          {
          String folderId = this.getDestFolder();
          this.uploader.setFileSizeLabel(-1);
          Long[] totalSize = Utility.directorySize(files[i]);
          this.uploader.setFileSizeLabel(totalSize[1].longValue());
          this.uploader.setTotalSize(totalSize[1].longValue());
          this.totalFiles = totalSize[0].intValue();
          this.uploadFolder(files[i], folderId);
          this.uploader.onSuccessfulUpload();
          }
        else
          {
          uploader.setIndex(i);
          uploader.setFileCountLabel(i + 1, files.length);
          uploader.setFileSizeLabel(this.uploader.getFileLength(i));
          uploader.setFileNameLabel(files[i].getName());
          uploadFile(i, files[i]);
          this.uploadOffset = 0;
          if(this.paused)
            {
            return;
            }
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

  private void uploadFolder(File dir, String parentId) throws JavaUploaderException
    {
    File[] localChildren = dir.listFiles();
    if(localChildren == null)
      {
      return; // This can happen for weird special directories on windows.  Just ignore it.
      }
    for(File f : localChildren)
      {
      this.currentFileNumber++;
      this.uploader.setFileNameLabel(f.getName());
      this.uploader.setFileCountLabel(this.currentFileNumber, this.totalFiles);
      if(f.isDirectory())
        {
        String currId = this.createServerFolder(parentId, f.getName().trim(), true);
        this.uploadFolder(f, currId);
        }
      else
        {
        if(this.itemExists(parentId, f.getName().trim()))
          {
          this.uploader.increaseOverallProgress(f.length());
          }
        else
          {
          this.uploadItem(f, parentId);
          }        
        }
      }
    }

  private void uploadItem(File file, String parentId) throws JavaUploaderException
    {
    String getUploadUniqueIdentifierURL = this.getUploadUniqueIdentifierBaseURL
        + "?filename=" + file.getName()+ "&parentFolderId="+parentId;
    this.uploader.setUploadUniqueIdentifier(Utility.queryHttpServer(getUploadUniqueIdentifierURL));

    FileInputStream fileStream = null;
    int finalByteSize = 0;
    long fileSize = file.length();
    try
      {
      fileStream = new FileInputStream(file);
      }
    catch (FileNotFoundException e)
      {
      throw new JavaUploaderException("File '" + file.getPath() + "' doesn't exist");
      }
    catch (IOException e)
      {
      throw new JavaUploaderException("Failed to read file '" + file.getPath() + "'");
      }

    this.uploadFileURL =
      this.uploadFileBaseURL + "&filename=" + file.getName() + "&uploadUniqueIdentifier=" +
      this.uploader.getUploadUniqueIdentifier() + "&length=" + fileSize;
    this.uploadFileURL += uploader.revOnCollision() ? "&newRevision=1" : "&newRevision=0";
    this.uploadFileURL += "&parentId=" + parentId;
    URL uploadFileURLObj = Utility.buildURL("UploadFile", this.uploadFileURL);

    try
      {
      conn = (HttpURLConnection) uploadFileURLObj.openConnection();
      conn.setDoInput(true); // Allow Inputs
      conn.setDoOutput(true); // Allow Outputs
      conn.setUseCaches(false); // Don't use a cached copy.
      conn.setRequestMethod("PUT"); // Use a PUT method.
      conn.setRequestProperty("Connection", "close");
      conn.setRequestProperty("Host", uploadFileURLObj.getHost());
      conn.setRequestProperty("Content-Type", "application/octet-stream");
      conn.setRequestProperty("Content-Length", String.valueOf(fileSize));
      conn.setChunkedStreamingMode(1048576);

      output = new DataOutputStream(conn.getOutputStream());

      byte buffer[] = new byte[1048576];
      int len;
      while((len = fileStream.read(buffer, 0, 1048576)) != -1)
        {
        output.write(buffer, 0, len);
        this.uploader.increaseOverallProgress(len);
        }

      output.flush();
      output.close();
      }
    catch(IOException e)
      {
      throw new JavaUploaderException(e);
      }
    finally
      {
      try
        {
        fileStream.close();
        }
      catch(IOException e) {}
      conn.disconnect();
      }
    }

  /**
   * Query the server to determine if an item with the given name already exists in the parent folder
   * @param parentId
   * @param name
   * @return True if the item with that name already exists in that parent, false otherwise
   */
  private boolean itemExists(String parentId, String name) throws JavaUploaderException
    {
    String url = this.apiURL + "?method=midas.item.exists&useSession";
    url += "&name="+name+"&parentid="+parentId;

    try
      {
      URL urlObj = Utility.buildURL("ItemExists", url);
      conn = (HttpURLConnection) urlObj.openConnection();
      conn.setUseCaches(false);
      conn.setRequestMethod("GET");
      conn.setRequestProperty("Connection", "close");
      conn.setRequestProperty("Host", urlObj.getHost());

      if (conn.getResponseCode() != 200)
        {
        throw new JavaUploaderException("Exception occurred on server during item exists check with parentId="+parentId+" and name="+name);
        }

      String resp = this.getResponseText().trim();
      conn.disconnect();
 
      return new JSONObject(resp).getJSONObject("data").getBoolean("exists");
      }
    catch (IOException e)
      {
      conn.disconnect();
      throw new JavaUploaderException(e);
      }
    catch (JSONException e)
      {
      throw new JavaUploaderException("Invalid JSON response for item exists check (name="+
        name+", parentid="+parentId+"):" + e.getMessage());
      }
    }

  /**
   * Create a new folder on the server with the given name under the given existing parent
   * @param parentId Id of the parent folder to create folder in
   * @param name Name of the new child folder
   * @param reuseExisting If a folder with the same name exists in this location, should we use the existing one
   * @return The id of the newly created folder (or an existing one in the case of reuseExisting = true)
   */
  private String createServerFolder(String parentId, String name, boolean reuseExisting) throws JavaUploaderException
    {
    String url = this.apiURL + "?method=midas.folder.create&useSession";
    url += "&name="+name+"&parentid="+parentId;
    
    if(reuseExisting)
      {
      url += "&reuseExisting=true";
      }

    try
      {
      URL urlObj = Utility.buildURL("CreateNewFolder", url);
      conn = (HttpURLConnection) urlObj.openConnection();
      conn.setUseCaches(false);
      conn.setRequestMethod("GET");
      conn.setRequestProperty("Connection", "close");
      conn.setRequestProperty("Host", urlObj.getHost());

      if (conn.getResponseCode() != 200)
        {
        throw new JavaUploaderException("Exception occurred on server during folder create with parentId="+parentId);
        }

      String resp = this.getResponseText().trim();
      conn.disconnect();
      return new JSONObject(resp).getJSONObject("data").getString("folder_id");
      }
    catch (IOException e)
      {
      conn.disconnect();
      throw new JavaUploaderException(e);
      }
    catch (JSONException e)
      {
      throw new JavaUploaderException("Invalid JSON response for folder create (name="+
        name+", parentid="+parentId+"):" + e.getMessage());
      }
    }

  private JSONObject getFolderChildren(String folderId) throws JavaUploaderException
    {
    String url = this.apiURL + "?method=midas.folder.children&useSession&id=" + folderId;
    try
      {
      URL urlObj = Utility.buildURL("GetDestinationFolder", url);
      conn = (HttpURLConnection) urlObj.openConnection();
      conn.setUseCaches(false);
      conn.setRequestMethod("GET");
      conn.setRequestProperty("Connection", "close");
      conn.setRequestProperty("Host", urlObj.getHost());

      if (conn.getResponseCode() != 200)
        {
        throw new JavaUploaderException("Exception occurred on server when requesting destination folder id");
        }

      String resp = this.getResponseText().trim();
      conn.disconnect();
      JSONObject json = new JSONObject(resp);
      return json.getJSONObject("data");
      }
    catch (IOException e)
      {
      conn.disconnect();
      throw new JavaUploaderException(e);
      }
    catch (JSONException e)
      {
      throw new JavaUploaderException("Invalid JSON response for folder children (id="+folderId+"):" + e.getMessage());
      }
    }

  private String getDestFolder() throws JavaUploaderException
    {
    String url = this.baseURL + "javadestinationfolder";
    try
      {
      URL urlObj = Utility.buildURL("GetDestinationFolder", url);
      conn = (HttpURLConnection) urlObj.openConnection();
      conn.setUseCaches(false);
      conn.setRequestMethod("GET");
      conn.setRequestProperty("Connection", "close");
      conn.setRequestProperty("Host", urlObj.getHost());

      if (conn.getResponseCode() != 200)
        {
        throw new JavaUploaderException("Exception occurred on server when requesting destination folder id");
        }

      String id = this.getResponseText().trim();
      conn.disconnect();
      return id;
      }
    catch (IOException e)
      {
      conn.disconnect();
      throw new JavaUploaderException(e);
      }
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
    else
      {
      this.uploadFileURL += uploader.revOnCollision() ? "&newRevision=1" : "&newRevision=0";
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
          uploader.setProgressIndeterminate(true);
          String msg = Utility.getMessage(new BufferedReader(new InputStreamReader(inputStream)));
          Utility.log(Utility.LOG_LEVEL.DEBUG, "[SERVER] " + msg);
          if (i + 1 == uploader.getFiles().length)
            {
            uploader.onSuccessfulUpload();
            }
          uploader.setProgressIndeterminate(false);
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
