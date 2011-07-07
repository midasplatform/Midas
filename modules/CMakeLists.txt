file(GLOB sub-dir "${CMAKE_SOURCE_DIR}/modules/*")

foreach(dir ${sub-dir})
  if(IS_DIRECTORY "${dir}/tests")
    add_subdirectory( ${dir}/tests )
  endif()
endforeach()
