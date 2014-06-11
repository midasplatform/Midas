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

include( ExternalProject )

set( base "${CMAKE_BINARY_DIR}" )
set_property( DIRECTORY PROPERTY EP_BASE ${base} )

if( CMAKE_BUILD_TYPE )
  set( build_type "${CMAKE_BUILD_TYPE}" )
endif()

set( gen "${CMAKE_GENERATOR}" )

if( NOT GIT_EXECUTABLE )
  find_package( Git REQUIRED )
endif( NOT GIT_EXECUTABLE )

option( GIT_PROTOCOL_HTTP
  "Use HTTP for git access (useful if behind a firewall)" OFF )
if( GIT_PROTOCOL_HTTP )
  set( GIT_PROTOCOL "http" CACHE STRING "Git protocol for file transfer" )
else( GIT_PROTOCOL_HTTP )
  set( GIT_PROTOCOL "git" CACHE STRING "Git protocol for file transfer" )
endif( GIT_PROTOCOL_HTTP )
mark_as_advanced( GIT_PROTOCOL )

##
## Insight
##
set( proj ITK )
ExternalProject_Add( ${proj}
  GIT_REPOSITORY "${GIT_PROTOCOL}://itk.org/ITK.git"
  GIT_TAG "v3.20.0"
  SOURCE_DIR "${CMAKE_BINARY_DIR}/ITK"
  BINARY_DIR ITK-Build
  CMAKE_GENERATOR ${gen}
  CMAKE_ARGS
  -Dgit_EXECUTABLE:FILEPATH=${GIT_EXECUTABLE}
  -DCMAKE_CXX_FLAGS:STRING=${CMAKE_CXX_FLAGS}
  -DCMAKE_C_FLAGS:STRING=${CMAKE_C_FLAGS}
  -DCMAKE_EXE_LINKER_FLAGS:STRING=${CMAKE_EXE_LINKER_FLAGS}
  -DCMAKE_SHARED_LINKER_FLAGS:STRING=${CMAKE_SHARED_LINKER_FLAGS}
  -DCMAKE_BUILD_TYPE:STRING=Release
  -DBUILD_SHARED_LIBS:BOOL=OFF
  -DBUILD_EXAMPLES:BOOL=OFF
  -DBUILD_TESTING:BOOL=OFF
  INSTALL_COMMAND ""
  )
set( ITK_DIR "${base}/ITK-Build" )

##
## VTK
##
set( proj VTK )
ExternalProject_Add( VTK
  GIT_REPOSITORY "${GIT_PROTOCOL}://vtk.org/VTK.git"
  GIT_TAG "v5.6.1"
  SOURCE_DIR "${CMAKE_BINARY_DIR}/VTK"
  BINARY_DIR VTK-Build
  CMAKE_GENERATOR ${gen}
  CMAKE_ARGS
  -Dgit_EXECUTABLE:FILEPATH=${GIT_EXECUTABLE}
  -DCMAKE_CXX_FLAGS:STRING=${CMAKE_CXX_FLAGS}
  -DCMAKE_C_FLAGS:STRING=${CMAKE_C_FLAGS}
  -DCMAKE_EXE_LINKER_FLAGS:STRING=${CMAKE_EXE_LINKER_FLAGS}
  -DCMAKE_SHARED_LINKER_FLAGS:STRING=${CMAKE_SHARED_LINKER_FLAGS}
  -DCMAKE_BUILD_TYPE:STRING=Release
  -DBUILD_SHARED_LIBS:BOOL=OFF
  -DBUILD_EXAMPLES:BOOL=OFF
  -DBUILD_TESTING:BOOL=OFF
  INSTALL_COMMAND ""
  )
set( VTK_DIR "${base}/VTK-Build" )

set( proj MidasFilters )
ExternalProject_Add( MidasFilters
  GIT_REPOSITORY "${GIT_PROTOCOL}://public.kitware.com/MIDAS/MidasFilters.git"
  SOURCE_DIR "${CMAKE_BINARY_DIR}/MidasFilters"
  BINARY_DIR MidasFilters-Build
  CMAKE_GENERATOR ${gen}
  CMAKE_ARGS
    -Dgit_EXECUTABLE:FILEPATH=${GIT_EXECUTABLE}
    -DCMAKE_CXX_FLAGS:STRING=${CMAKE_CXX_FLAGS}
    -DCMAKE_C_FLAGS:STRING=${CMAKE_C_FLAGS}
    -DCMAKE_EXE_LINKER_FLAGS:STRING=${CMAKE_EXE_LINKER_FLAGS}
    -DCMAKE_SHARED_LINKER_FLAGS:STRING=${CMAKE_SHARED_LINKER_FLAGS}
    -DCMAKE_BUILD_TYPE:STRING=Release
    -DCMAKE_INSTALL_PREFIX:PATH=${CMAKE_BINARY_DIR}/Midas/midas/filters
    -DITK_DIR:PATH=${ITK_DIR}
    -DVTK_DIR:PATH=${VTK_DIR}
  DEPENDS
    "VTK"
    "ITK"
)

set( proj Midas )
ExternalProject_Add( Midas
  DOWNLOAD_COMMAND ""
  SOURCE_DIR "${CMAKE_CURRENT_SOURCE_DIR}"
  BINARY_DIR Midas
  CMAKE_GENERATOR ${gen}
  CMAKE_ARGS
  -DMidas_USE_SUPERBUILD:BOOL=FALSE
  -DMidas_DB_NAME:STRING=${Midas_DB_NAME}
  -DMidas_DB_LOGIN:STRING=${Midas_DB_LOGIN}
  -DMidas_DB_PASS:STRING=${Midas_DB_PASS}
  -DMidas_BASE_DIRECTORY:PATH=${Midas_BASE_DIRECTORY}
  -DMidas_SERVER_URL:STRING=${Midas_SERVER_URL}
  INSTALL_COMMAND ""
  DEPENDS
    "MidasFilters"
)
