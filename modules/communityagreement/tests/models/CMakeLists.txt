add_subdirectory( base )

#Style
add_midas_style_test( StyleCommunityagreementModelsBase ${CMAKE_SOURCE_DIR}/modules/communityagreement/models/base/ )
add_midas_style_test( StyleCommunityagreementModelsPdo ${CMAKE_SOURCE_DIR}/modules/communityagreement/models/pdo/ )
add_midas_style_test( StyleCommunityagreementModelsDao ${CMAKE_SOURCE_DIR}/modules/communityagreement/models/dao/ )