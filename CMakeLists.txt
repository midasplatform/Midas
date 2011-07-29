project( Midas )

#-----------------------------------------------------------------------------
cmake_minimum_required( VERSION 2.6 )

#-----------------------------------------------------------------------------
#  Set Midas version information
set( Midas_VERSION_MAJOR "3" )
set( Midas_VERSION_MINOR "0" )
include( library/CMake/kwsysDateStamp.cmake )
set( Midas_VERSION_BUILD
  ${KWSYS_DATE_STAMP_YEAR}${KWSYS_DATE_STAMP_MONTH}${KWSYS_DATE_STAMP_DAY} )
set( Midas_VERSION
  "${Midas_VERSION_MAJOR}.${Midas_VERSION_MINOR}.${Midas_VERSION_BUILD}" )

set( PHP "php" CACHE STRING  "PHP executable.")

EXECUTE_PROCESS( COMMAND ${PHP} RESULT_VARIABLE ret_var)
if(NOT ${ret_var} EQUAL 0)
  message(FATAL_ERROR "Please set the PHP executable")
endif()



#-----------------------------------------------------------------------------
#  This should be at the top level for warning suppression
configure_file(${CMAKE_CURRENT_SOURCE_DIR}/library/CMake/CTestCustom.cmake.in
  ${CMAKE_CURRENT_BINARY_DIR}/CTestCustom.cmake )



#----------------------------------------------------------------------------
# Setup testing and required parameters for testing

include(CTest)
set( SERVER_BIN_DIR ${CMAKE_CURRENT_BINARY_DIR} )
add_subdirectory(tests)
add_subdirectory(core/tests)
add_subdirectory(modules)
