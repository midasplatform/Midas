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

#-----------------------------------------------------------------------------
#  Find the php executable
find_program(PHP "php" CACHE STRING "PHP executable.")
if(NOT PHP)
 message(FATAL_ERROR "Please set the PHP executable")
endif()

#-----------------------------------------------------------------------------
#  Drop all tables from the testing DB
#  Then re-install the DB, set a Default assetstore, and install all modules
message(STATUS "Setting up database for module testing, please wait...")
execute_process(
  COMMAND ${PHP} ${CMAKE_CURRENT_SOURCE_DIR}/tests/DatabaseSetup.php
  RESULT_VARIABLE databaseSetup_RESULT
  OUTPUT_VARIABLE databaseSetup_OUT
  ERROR_VARIABLE databaseSetup_ERR)

if(NOT databaseSetup_RESULT EQUAL 0)
  message(STATUS "DatabaseSetup output: ${databaseSetup_OUT}")
  message(FATAL_ERROR "DatabaseSetup error: ${databaseSetup_ERR}")
endif()

message(STATUS "Database setup is complete")

#-----------------------------------------------------------------------------
#  This should be at the top level for warning suppression and coverage exclude
configure_file(${CMAKE_CURRENT_SOURCE_DIR}/library/CMake/CTestCustom.cmake.in
  ${CMAKE_CURRENT_BINARY_DIR}/CTestCustom.cmake )



#----------------------------------------------------------------------------
# Setup testing and required parameters for testing

include(CTest)
option( MIDAS_RUN_STYLE_TESTS "Should MIDAS run PHP style checking tests?" ON )
set( SERVER_BIN_DIR ${CMAKE_CURRENT_BINARY_DIR} )
add_subdirectory(tests)
add_subdirectory(core/tests)
add_subdirectory(modules)
