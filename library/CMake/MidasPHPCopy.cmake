#=============================================================================
# MIDAS Server
# Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
# All rights reserved.
# More information http://www.kitware.com
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#         http://www.apache.org/licenses/LICENSE-2.0.txt
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#=============================================================================

#----------------------------------------------------------------------------
# Copying the server php files
file(COPY
  ${SOURCE_DIR}/midas
  DESTINATION
  ${BINARY_DIR}
  FILE_PERMISSIONS OWNER_READ OWNER_WRITE OWNER_EXECUTE
  GROUP_READ GROUP_WRITE GROUP_EXECUTE 
  WORLD_READ WORLD_WRITE WORLD_EXECUTE
  DIRECTORY_PERMISSIONS OWNER_READ OWNER_WRITE OWNER_EXECUTE
  GROUP_READ GROUP_WRITE GROUP_EXECUTE 
  WORLD_READ WORLD_WRITE WORLD_EXECUTE)
file(COPY
  ${SOURCE_DIR}/cake
  DESTINATION
  ${BINARY_DIR})
file(COPY
  ${SOURCE_DIR}/docs
  DESTINATION
  ${BINARY_DIR})
file(COPY
  ${SOURCE_DIR}/models
  DESTINATION
  ${BINARY_DIR})
file(COPY
  ${SOURCE_DIR}/vendors
  DESTINATION
  ${BINARY_DIR})
file(COPY
  ${SOURCE_DIR}/index.php
  DESTINATION
  ${BINARY_DIR})
file(COPY
  ${SOURCE_DIR}/crossdomain.xml
  DESTINATION
  ${BINARY_DIR})
file(COPY
  ${SOURCE_DIR}/.htaccess
  DESTINATION
  ${BINARY_DIR})
file(MAKE_DIRECTORY
  ${Midas_BASE_DIRECTORY}/assetstore
  ${Midas_BASE_DIRECTORY}/temp
  ${Midas_BASE_DIRECTORY}/cache
  ${Midas_BASE_DIRECTORY}/backup)
