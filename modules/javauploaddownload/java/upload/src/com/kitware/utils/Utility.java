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

import java.io.BufferedReader;
import java.io.File;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.PrintStream;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.text.DecimalFormat;

import com.kitware.utils.exception.JavaUploaderException;
import com.kitware.utils.exception.JavaUploaderHttpServerErrorException;
import com.kitware.utils.exception.JavaUploaderNetworkException;
import com.kitware.utils.exception.JavaUploaderQueryHttpServerException;

public class Utility {
    public static enum LOG_LEVEL {
        FATAL, ERROR, WARNING, DEBUG, LOG
    }

    public static URL buildURL(String name, String url) throws JavaUploaderException {
        try {
            return new URL(url.replaceAll(" ", "%20"));
        } catch (MalformedURLException e) {
            throw new JavaUploaderException("Malformed " + name + " URL:" + url, e);
        } catch (java.lang.NullPointerException e) {
            // Do something here for the missing destination
            throw new JavaUploaderException(name + " URL not specified", e);
        }
    }

    /**
     * Convert a number of bytes into a human readable string
     *
     * @param bytes
     *            Numeric bytes (ex: 123899381)
     * @return Human readable version of bytes (ex: 1.4 GB)
     */
    public static String bytesToString(long bytes) {
        if (bytes > 1024 * 1024 * 1024) {
            return new DecimalFormat("#.##").format(bytes / (1024.0 * 1024.0 * 1024.0)) + " GB";
        }
        if (bytes > 1024 * 1024) {
            return new DecimalFormat("#.##").format(bytes / (1024.0 * 1024.0)) + " MB";
        }
        if (bytes > 1024) {
            return new DecimalFormat("#.##").format(bytes / 1024.0) + " KB";
        }
        return bytes + " B";
    }

    /**
     * Get the size of the directory (recursive)
     *
     * @param dir
     * @return an array of Longs of length 2. First element is the total number
     *         of files and folders. Second is the total size in bytes of all
     *         files.
     */
    public static Long[] directorySize(File dir) {
        Long[] size = new Long[] { new Long(0), new Long(0) };
        Utility.directorySize(dir, size);
        return size;
    }

    private static void directorySize(File dir, Long[] size) {
        if (dir.isFile()) {
            size[1] += dir.length();
        } else {
            File[] subFiles = dir.listFiles();

            if (subFiles == null) {
                return;
            }
            for (File file : subFiles) {
                size[0]++;
                Utility.directorySize(file, size);
            }
        }
    };

    public static String getMessage(BufferedReader in) throws JavaUploaderNetworkException,
    JavaUploaderHttpServerErrorException {
        String msg = "", output = "";
        boolean isMultilineCorrectAnswer = false;
        boolean isWrongAnswer = false;
        try {

            while ((msg = in.readLine()) != null) {
                if (msg.startsWith(HTTP_SERVER_ERROR_ANSWER_PREFIX)) {
                    String error = msg.substring(HTTP_SERVER_ERROR_ANSWER_PREFIX.length());
                    throw new JavaUploaderHttpServerErrorException(error);
                } else if (msg.startsWith(HTTP_SERVER_CORRECT_ANSWER_PREFIX)) {
                    output = msg.substring(HTTP_SERVER_CORRECT_ANSWER_PREFIX.length());
                    isMultilineCorrectAnswer = true;
                } else if (isMultilineCorrectAnswer) {
                    output += msg + NEWLINE;
                } else {
                    if (!isWrongAnswer) {
                        isWrongAnswer = true;
                    } else {
                        output += msg + NEWLINE;
                    }
                }
            }
            if (isWrongAnswer && !isMultilineCorrectAnswer) {
                Utility.log(Utility.LOG_LEVEL.ERROR, "Malformed answer: " + output);
                throw new JavaUploaderNetworkException("Malformed answer: " + output);
            }
            return output;
        } catch (IOException e) {
            throw new JavaUploaderNetworkException("Failed to read data from server: " + msg, e);
        }
    }

    public static void log(LOG_LEVEL level, String message) {
        Utility.log(level, message, null);
    }

    public static void log(LOG_LEVEL level, String message, Throwable e) {
        if (Utility.EFFECTIVE_LOG_LEVEL.ordinal() < level.ordinal()) {
            return;
        }
        PrintStream printStream = System.out;
        if (level.ordinal() <= LOG_LEVEL.WARNING.ordinal()) {
            printStream = System.err;
        }

        String logMsg = "[" + level + "]";
        logMsg += message.equals("") ? "" : message;
        if (e != null) {
            logMsg += " - Exception: " + e.getClass().getName()
                    + (e.getMessage() == null ? "" : " - " + e.getMessage());
        }
        printStream.println(logMsg);

        if (e != null) {
            e.printStackTrace();
        }
    }

    public static void log(LOG_LEVEL level, Throwable e) {
        Utility.log(level, "", e);
    }

    public static String queryHttpServer(String queryURL) throws JavaUploaderQueryHttpServerException {
        HttpURLConnection conn = null;
        try {
            URL url = new URL(queryURL);
            conn = (HttpURLConnection) url.openConnection();
            conn.setDoInput(true); // Allow Inputs
            conn.setDoOutput(false); // Allow Outputs
            conn.setUseCaches(false); // Don't use a cached copy.
            conn.setRequestMethod("GET"); // Use a PUT method.
            conn.setRequestProperty("Connection", "close");
            conn.setRequestProperty("Host", url.getHost());
            conn.setRequestProperty("Accept", "text/plain");

            BufferedReader in = new BufferedReader(new InputStreamReader(conn.getInputStream()));
            try {
                return Utility.getMessage(in);
            } catch (JavaUploaderNetworkException e) {
                throw new JavaUploaderQueryHttpServerException("Query using '" + queryURL
                        + "' returns malformed answer", e);
            } finally {
                if (conn != null) {
                    conn.disconnect();
                }
            }
        } catch (MalformedURLException e) {
            throw new JavaUploaderQueryHttpServerException("Malformed queryURL:" + queryURL);
        } catch (IOException e) {
            throw new JavaUploaderQueryHttpServerException("Failed to connect to server using '" + queryURL + "'");
        }
    }

    public static final String NEWLINE = "\r\n";

    public static final String HTTP_SERVER_ERROR_ANSWER_PREFIX = "[ERROR]";

    public static final String HTTP_SERVER_CORRECT_ANSWER_PREFIX = "[OK]";

    public static LOG_LEVEL EFFECTIVE_LOG_LEVEL = LOG_LEVEL.WARNING;
}
