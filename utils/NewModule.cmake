# This CMake script should be used to create a new Midas module. Requires CMake v2.8.3+
# Call as
#   cmake -P NewModule.cmake modulename
# Where modulename is the name of your module. It should have no spaces in it.
#
if(NOT DEFINED CMAKE_ARGV3)
  message(FATAL_ERROR "Must pass in module name as an argument: cmake -P NewModule.cmake mymodule")
endif()

set(moduleName "${CMAKE_ARGV3}")
set(templateRoot "${CMAKE_CURRENT_LIST_DIR}/moduleTemplate")
string(SUBSTRING "${moduleName}" 0 1  firstChar)
string(SUBSTRING "${moduleName}" 1 -1 restOfString)
string(TOUPPER ${firstChar} firstChar)
string(TOLOWER "${restOfString}" restOfString)
string(TOLOWER "${moduleName}" MN)
set(MN_CAP "${firstChar}${restOfString}")
set(moduleRoot "${CMAKE_CURRENT_LIST_DIR}/../modules/${MN}")

message("Creating skeleton module at ${moduleRoot}")
if(EXISTS "${moduleRoot}")
  message(FATAL_ERROR "File or directory already exists: ${moduleRoot}")
endif()

file(MAKE_DIRECTORY "${moduleRoot}")
file(GLOB_RECURSE templateFiles RELATIVE "${templateRoot}" "${templateRoot}/*")
foreach(templateFile ${templateFiles})
  set(fullPath "${templateRoot}/${templateFile}")
  if(IS_DIRECTORY "${fullPath}")
    file(MAKE_DIRECTORY "${moduleRoot}/${templateFile}")
  else()
    configure_file("${fullPath}" "${moduleRoot}/${templateFile}" @ONLY)
  endif()
endforeach()
message("Done")
