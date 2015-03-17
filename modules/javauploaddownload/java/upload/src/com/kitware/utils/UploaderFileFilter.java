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

import java.io.File;
import java.util.Enumeration;
import java.util.Hashtable;

import javax.swing.filechooser.FileFilter;

public class UploaderFileFilter extends FileFilter {
    private Hashtable<String, FileFilter> filters = null;
    private String description = null;
    private String fullDescription = null;
    private boolean useExtensionsInDescription = true;

    public UploaderFileFilter() {
        filters = new Hashtable<String, FileFilter>();
    }

    @Override
    public boolean accept(File f) {
        if (f != null) {
            if (f.isDirectory()) {
                return true; // Accept directories. Change when code will allow.
            }
            String extension = getExtension(f);
            if (extension != null && filters.get(getExtension(f)) != null) {
                return true;
            }
        }
        return false;
    }

    public void addExtension(String extension) {
        if (filters == null) {
            filters = new Hashtable<String, FileFilter>(5);
        }
        filters.put(extension.toLowerCase(), this);
        fullDescription = null;
    }

    @Override
    public String getDescription() {
        if (fullDescription == null) {
            if (description == null || isExtensionListInDescription()) {
                fullDescription = description == null ? "(" : description + " (";
                // build the description from the extension list
                Enumeration<String> extensions = filters.keys();
                if (extensions != null) {
                    fullDescription += "." + extensions.nextElement();
                    while (extensions.hasMoreElements()) {
                        fullDescription += ", ." + extensions.nextElement();
                    }
                }
                fullDescription += ")";
            } else {
                fullDescription = description;
            }
        }
        return fullDescription;
    }

    public String getExtension(File f) {
        if (f != null) {
            String filename = f.getName();
            int i = filename.lastIndexOf('.');
            if (i > 0 && i < filename.length() - 1) {
                return filename.substring(i + 1).toLowerCase();
            }
            ;
        }
        return null;
    }

    public boolean isExtensionListInDescription() {
        return useExtensionsInDescription;
    }

    public void setDescription(String description) {
        this.description = description;
        fullDescription = null;
    }

    public void setExtensionListInDescription(boolean b) {
        useExtensionsInDescription = b;
        fullDescription = null;
    }
}
