// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

  $(document).ready(function () {
    'use strict';
      $('a.showHideHelp').click(function () {
          $(this).parents('li').next('div').toggle();
      });
  });
