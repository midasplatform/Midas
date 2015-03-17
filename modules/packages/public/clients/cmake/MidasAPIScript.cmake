if(NOT DEFINED MIDAS_API_DISPLAY_URL)
  set(MIDAS_API_DISPLAY_URL 0)
endif()

include(CMakeParseArguments)

#
# midas_api_login
#
#   API_URL The url of the Midas Server
#   API_EMAIL The email to use to authenticate to the server
#   API_KEY The default api key to use to authenticate to the server
function(midas_api_login)
  set(options)
  set(oneValueArgs API_URL API_EMAIL API_KEY RESULT_VARNAME)
  set(multiValueArgs)
  cmake_parse_arguments(MY "${options}" "${oneValueArgs}" "${multiValueArgs}" ${ARGN})

  # Sanity check
  set(expected_nonempty_vars API_URL API_EMAIL API_KEY RESULT_VARNAME)
  foreach(var ${expected_nonempty_vars})
    if("${MY_${var}}" STREQUAL "")
      message(FATAL_ERROR "error: ${var} CMake variable is empty !")
    endif()
  endforeach()

  midas_api_escape_for_url(email ${MY_API_EMAIL})
  midas_api_escape_for_url(apikey ${MY_API_KEY})

  set(api_method "midas.login")
  set(params "&appname=Default")
  set(params "${params}&email=${email}")
  set(params "${params}&apikey=${apikey}")
  set(url "${MY_API_URL}/api/json?method=${api_method}${params}")

  if("${MIDAS_API_DISPLAY_URL}")
    message(STATUS "URL: ${url}")
  endif()

  set(login_token_filepath ${CMAKE_CURRENT_BINARY_DIR}/MIDAStoken.txt)
  file(DOWNLOAD ${url} ${login_token_filepath} INACTIVITY_TIMEOUT 120)
  file(READ ${login_token_filepath} resp)
  file(REMOVE ${login_token_filepath})
  string(REGEX REPLACE ".*token\":\"(.*)\".*" "\\1" token ${resp})

  string(LENGTH ${token} tokenlength)
  if(NOT tokenlength EQUAL 40)
    set(token "")
    message(WARNING "Failed to login to Midas Server\n"
                    "  url: ${MY_API_URL}\n"
                    "  email: ${email}\n"
                    "  apikey: ${apikey}\n"
                    "  response: ${resp}")
  endif()
  set(${MY_RESULT_VARNAME} "${token}" PARENT_SCOPE)
endfunction()

function(midas_api_escape_for_url var str)
  string(REPLACE "\\/" "%2F" _tmp "${str}") # Slash
  string(REPLACE " " "%20" _tmp "${_tmp}") # Space
  set(${var} ${_tmp} PARENT_SCOPE)
endfunction()

if(NOT DEFINED MIDAS_API_DISPLAY_URL)
  set(MIDAS_API_DISPLAY_URL 0)
endif()

#
# Uploads a file and create the associated item in the given folder.
#
#   API_URL    The url of the Midas Server
#   API_EMAIL  The email to use to authenticate to the server
#   API_KEY The default api key to use to authenticate to the server
#
#   RESULT_VARNAME Will set the value of ${RESULT_VARNAME} to either "ok" or "fail".

function(midas_api_item_upload)
  include(CMakeParseArguments)
  set(options)
  set(oneValueArgs API_URL API_EMAIL API_KEY FOLDERID ITEM_FILEPATH RESULT_VARNAME)
  set(multiValueArgs)
  cmake_parse_arguments(MY "${options}" "${oneValueArgs}" "${multiValueArgs}" ${ARGN})

  # Sanity check
  _midas_api_expected_nonempty_vars(API_URL API_EMAIL API_KEY FOLDERID RESULT_VARNAME)
  _midas_api_expected_existing_vars(ITEM_FILEPATH)

  get_filename_component(filename "${MY_ITEM_FILEPATH}" NAME)

  midas_api_item_create(
    API_URL ${MY_API_URL}
    API_EMAIL ${MY_API_EMAIL}
    API_KEY ${MY_API_KEY}
    PARENTID ${MY_FOLDERID}
    NAME ${filename}
    RESULT_VARNAME itemid
    )

  file(MD5 ${MY_ITEM_FILEPATH} checksum)

  midas_api_upload_generatetoken(
    API_URL ${MY_API_URL}
    API_EMAIL ${MY_API_EMAIL}
    API_KEY ${MY_API_KEY}
    ITEMID ${itemid}
    FILENAME ${filename}
    CHECKSUM ${checksum}
    RESULT_VARNAME generatetoken
    )

  midas_api_upload_perform(
    API_URL ${MY_API_URL}
    UPLOADTOKEN ${generatetoken}
    FOLDERID ${MY_FOLDERID}
    ITEMID ${itemid}
    ITEM_FILEPATH ${MY_ITEM_FILEPATH}
    RESULT_VARNAME output
    )

  set(${MY_RESULT_VARNAME} ${output} PARENT_SCOPE)
endfunction()



#
# Uploads a file into a given item.
#
#   API_URL    The url of the Midas Server
#   API_EMAIL  The email to use to authenticate to the server
#   API_KEY The default api key to use to authenticate to the server
#
#   RESULT_VARNAME Will set the value of ${RESULT_VARNAME} to either "ok" or "fail".

function(midas_api_upload_perform)
  include(CMakeParseArguments)
  set(options)
  set(oneValueArgs API_URL UPLOADTOKEN FOLDERID ITEMID ITEM_FILEPATH RESULT_VARNAME)
  set(multiValueArgs)
  cmake_parse_arguments(MY "${options}" "${oneValueArgs}" "${multiValueArgs}" ${ARGN})

  # Sanity check
  _midas_api_expected_nonempty_vars(API_URL UPLOADTOKEN FOLDERID ITEMID RESULT_VARNAME)
  _midas_api_expected_existing_vars(ITEM_FILEPATH)

  midas_api_escape_for_url(uploadtoken "${MY_UPLOADTOKEN}")
  get_filename_component(filename "${MY_ITEM_FILEPATH}" NAME)
  midas_api_escape_for_url(filename "${filename}")
  midas_api_escape_for_url(itemid "${MY_ITEMID}")
  midas_api_escape_for_url(folderid "${MY_FOLDERID}")

  midas_api_file_size(${MY_ITEM_FILEPATH} length)

  set(api_method "midas.upload.perform")
  set(params "&uploadtoken=${uploadtoken}")
  set(params "${params}&filename=${filename}")
  set(params "${params}&length=${length}")
  set(params "${params}&itemid=${itemid}")
  set(params "${params}&folderid=${folderid}")
  set(url "${MY_API_URL}/api/json?method=${api_method}${params}")

  if("${MIDAS_API_DISPLAY_URL}")
    message(STATUS "URL: ${url}")
  endif()
  file(UPLOAD ${MY_ITEM_FILEPATH} ${url} INACTIVITY_TIMEOUT 120 STATUS status LOG log SHOW_PROGRESS)

  set(api_call_log ${CMAKE_CURRENT_BINARY_DIR}/${api_method}_response.txt)
  # For some reason, passing directly 'log' to 'midas_api_extract_json_value' return
  # status:0"no error"
  file(WRITE ${api_call_log} ${log})
  file(READ ${api_call_log} response)
  midas_api_extract_json_value(${response} "stat" status)

  if(status STREQUAL "ok")
    file(REMOVE ${api_call_log})
    set(${MY_RESULT_VARNAME} "ok" PARENT_SCOPE)
  else()
    set(${MY_RESULT_VARNAME} "fail" PARENT_SCOPE)
  endif()
endfunction()



#
# Uploads an item to the Midas Server.
#
#   API_URL    The url of the Midas Server
#   API_EMAIL  The email to use to authenticate to the server
#   API_KEY The default api key to use to authenticate to the server
#
#   RESULT_VARNAME  An upload token that can be used to upload a file. If checksum is passed
#                   and the token returned is blank, the server already has this file and there
#                   is no need to upload.
#                   Return "fail" in case of error.

function(midas_api_upload_generatetoken)
  include(CMakeParseArguments)
  set(options)
  set(oneValueArgs API_URL API_EMAIL API_KEY ITEMID FILENAME CHECKSUM RESULT_VARNAME)
  set(multiValueArgs)
  cmake_parse_arguments(MY "${options}" "${oneValueArgs}" "${multiValueArgs}" ${ARGN})

  # Sanity check
  _midas_api_expected_nonempty_vars(API_URL API_EMAIL API_KEY ITEMID FILENAME CHECKSUM RESULT_VARNAME)

  midas_api_login(
    API_URL ${MY_API_URL}
    API_EMAIL ${MY_API_EMAIL}
    API_KEY ${MY_API_KEY}
    RESULT_VARNAME midas_api_token
    )

  midas_api_escape_for_url(itemid "${MY_ITEMID}")
  midas_api_escape_for_url(filename "${MY_FILENAME}")
  midas_api_escape_for_url(checksum "${MY_CHECKSUM}")

  set(api_method "midas.upload.generatetoken")
  set(params "&token=${midas_api_token}")
  set(params "${params}&itemid=${itemid}")
  set(params "${params}&filename=${filename}")
  set(params "${params}&checksum=${checksum}")
  set(url "${MY_API_URL}/api/json?method=${api_method}${params}")

  midas_api_submit_request(${url} ${api_method} "token" token)

  if("${token}" STREQUAL "")
    set(token "fail")
  endif()
  set(${MY_RESULT_VARNAME} "${token}" PARENT_SCOPE)

endfunction()


#
# Create an item on the Midas Server.
#
#   API_URL    The url of the Midas Server
#   API_EMAIL  The email to use to authenticate to the server
#   API_KEY The default api key to use to authenticate to the server
#
#   RESULT_VARNAME  The itemid that was created. Return "fail" in case of error.

function(midas_api_item_create)
  include(CMakeParseArguments)
  set(options)
  set(oneValueArgs API_URL API_EMAIL API_KEY PARENTID NAME RESULT_VARNAME)
  set(multiValueArgs)
  cmake_parse_arguments(MY "${options}" "${oneValueArgs}" "${multiValueArgs}" ${ARGN})

  # Sanity check
  _midas_api_expected_nonempty_vars(API_URL API_EMAIL API_KEY PARENTID NAME RESULT_VARNAME)

  midas_api_login(
    API_URL ${MY_API_URL}
    API_EMAIL ${MY_API_EMAIL}
    API_KEY ${MY_API_KEY}
    RESULT_VARNAME midas_api_token
    )

  midas_api_escape_for_url(parentid "${MY_PARENTID}")
  midas_api_escape_for_url(name "${MY_NAME}")

  set(api_method "midas.item.create")
  set(params "&token=${midas_api_token}")
  set(params "${params}&parentid=${parentid}")
  set(params "${params}&name=${name}")
  set(url "${MY_API_URL}/api/json?method=${api_method}${params}")

  midas_api_submit_request(${url} ${api_method} "item_id" itemid)

  if("${itemid}" STREQUAL "")
    set(itemid "fail")
  endif()

  set(${MY_RESULT_VARNAME} "${itemid}" PARENT_SCOPE)
endfunction()

function(_midas_api_expected_nonempty_vars varlist)
  foreach(var ${varlist})
    if("${MY_${var}}" STREQUAL "")
      message(FATAL_ERROR "error: ${var} CMake variable is empty !")
    endif()
  endforeach()
endfunction()

function(_midas_api_expected_existing_vars varlist)
  foreach(var ${varlist})
    if(NOT EXISTS "${MY_${var}}")
      message(FATAL_ERROR "Variable ${var} is set to an inexistent directory or file ! [${${var}}]")
    endif()
  endforeach()
endfunction()

macro(midas_api_extract_json_value response json_item varname)
  string(REGEX REPLACE ".*{\"${json_item}\":\"([^\"]*)\".*" "\\1" ${varname} ${response})
endmacro()

function(midas_api_submit_request url api_method json_item varname)
  if("${MIDAS_API_DISPLAY_URL}")
    message(STATUS "URL: ${url}")
  endif()
  set(_midas_api_response ${CMAKE_CURRENT_BINARY_DIR}/${api_method}_response.txt)
  file(DOWNLOAD ${url} ${_midas_api_response} INACTIVITY_TIMEOUT 120)
  file(READ ${_midas_api_response} response)
  string(REPLACE "\\" "\\\\" response ${response}) # CMake escape
  file(REMOVE ${_midas_api_response})
  midas_api_extract_json_value(${response} ${json_item} json_item_value)
  set(${varname} ${json_item_value} PARENT_SCOPE)
endfunction()

function(midas_api_file_size filepath varname)

  set(_expected_chunk_size 1048576) # 1 Mb

  function(_read_chunk _file _offset varname)
    file(READ ${_file} _buffer OFFSET ${_offset} LIMIT ${_expected_chunk_size} HEX)
    string(LENGTH "${_buffer}" _current_chunk_size)
    math(EXPR _current_chunk_size "${_current_chunk_size} / 2")
    set(${varname} ${_current_chunk_size} PARENT_SCOPE)
  endfunction()

  set(_offset 0)
  _read_chunk(${filepath} ${_offset} _current_chunk_size)
  set(_offset ${_current_chunk_size})

  while(_current_chunk_size EQUAL ${_expected_chunk_size})
    _read_chunk(${filepath} ${_offset} _current_chunk_size)
    math(EXPR _offset "${_offset} + ${_current_chunk_size}")
  endwhile()

  set(${varname} ${_offset} PARENT_SCOPE)
endfunction()

#
# Api calls specific to the 'packages' module
#

#
# Upload a package
#
# API_URL The URL of the Midas server
# API_EMAIL Username to use for authentication
# API_KEY Default API key to use for authentication
# FILE The local file to upload
# FOLDER_ID Folder to upload into
# APPLICATION_ID Application id that this package corresponds to
# OS Target operating system of the package
# ARCH Target architecture of the package
# PACKAGE_TYPE (zip, NSIS, Bundle, Source, etc)
# NAME Name of the file being uploaded
# RESULT_VARNAME Result variable from this function (will be set to 'ok' in success case)
# [PRODUCT_NAME] Name of the application
# [REVISION] Revision of the repository that package was built against
# [SUBMISSION_TYPE] Submission type (nightly, experimental, etc)
# [CODEBASE] Sub-name of the application
# [RELEASE] Release tag for this package (e.g. 1.2.3)
#
function(midas_api_package_upload)
  include(CMakeParseArguments)
  set(options)
  set(oneValueArgs
    API_URL
    API_EMAIL
    API_KEY
    FILE
    FOLDER_ID
    APPLICATION_ID
    OS
    ARCH
    REVISION
    SUBMISSION_TYPE
    CODEBASE
    PRODUCT_NAME
    PACKAGE_TYPE
    RELEASE
    NAME
    RESULT_VARNAME
  )
  set(multiValueArgs)
  cmake_parse_arguments(MY "${options}" "${oneValueArgs}" "${multiValueArgs}" ${ARGN})

  # Sanity check
  _midas_api_expected_nonempty_vars(API_URL API_EMAIL API_KEY FILE FOLDER_ID OS ARCH PACKAGE_TYPE APPLICATION_ID NAME RESULT_VARNAME)

  midas_api_login(
    API_URL ${MY_API_URL}
    API_EMAIL ${MY_API_EMAIL}
    API_KEY ${MY_API_KEY}
    RESULT_VARNAME midas_api_token
    )

  midas_api_escape_for_url(folderid "${MY_FOLDER_ID}")
  midas_api_escape_for_url(appid "${MY_APPLICATION_ID}")
  midas_api_escape_for_url(name "${MY_NAME}")
  midas_api_escape_for_url(os "${MY_OS}")
  midas_api_escape_for_url(arch "${MY_ARCH}")
  midas_api_escape_for_url(revision "${MY_REVISION}")
  midas_api_escape_for_url(submissiontype "${MY_SUBMISSION_TYPE}")
  midas_api_escape_for_url(packagetype "${MY_PACKAGE_TYPE}")
  midas_api_escape_for_url(productname "${MY_PRODUCT_NAME}")
  midas_api_escape_for_url(codebase "${MY_CODEBASE}")
  midas_api_escape_for_url(release "${MY_RELEASE}")

  set(api_method "midas.packages.package.upload")
  set(params "&token=${midas_api_token}")
  set(params "${params}&folderId=${folderid}")
  set(params "${params}&applicationId=${appid}")
  set(params "${params}&name=${name}")
  set(params "${params}&os=${os}")
  set(params "${params}&arch=${arch}")
  set(params "${params}&revision=${revision}")
  set(params "${params}&submissiontype=${submissiontype}")
  set(params "${params}&packagetype=${packagetype}")
  set(params "${params}&productname=${productname}")
  set(params "${params}&codebase=${codebase}")
  set(params "${params}&release=${release}")
  set(url "${MY_API_URL}/api/json?method=${api_method}${params}")

  if("${MIDAS_API_DISPLAY_URL}")
    message(STATUS "URL: ${url}")
  endif()
  file(UPLOAD ${MY_FILE} ${url} INACTIVITY_TIMEOUT 300 STATUS status LOG log SHOW_PROGRESS)
  set(api_call_log ${CMAKE_CURRENT_BINARY_DIR}/${api_method}_response.txt)
  # For some reason, passing directly 'log' to 'midas_api_extract_json_value' return
  # status:0"no error"
  file(WRITE ${api_call_log} ${log})
  file(READ ${api_call_log} response)
  midas_api_extract_json_value(${response} "stat" stat)

  if("${stat}" STREQUAL "ok")
    set(${MY_RESULT_VARNAME} "ok" PARENT_SCOPE)
  endif()
endfunction()
