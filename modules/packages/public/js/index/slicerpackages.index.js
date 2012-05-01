
function relativeVersionInfoPosition(position)
  {
  return position - $('.version-list').position().top;
  }

function updateVersionInfoPosition(versionLink, versionInfo)
  {
  var firstVersionLinkTop = $('.first-version-link').position().top;
  var currentVersionLinkTop = versionLink.position().top;
  var currentVersionLinkBottom = currentVersionLinkTop + versionLink.outerHeight();

  var deltaCurrentToFirstVersionLink = currentVersionLinkBottom - firstVersionLinkTop;
  var versionInfoHeight = versionInfo.outerHeight();

  var versionInfoTop = relativeVersionInfoPosition(firstVersionLinkTop);
  if (deltaCurrentToFirstVersionLink >= versionInfoHeight)
    {
    versionInfoTop = relativeVersionInfoPosition(currentVersionLinkBottom - versionInfoHeight);
    }
  versionInfo.css(
    { top: versionInfoTop
    , position:'relative'
    })
  }

$(document).ready(function() {

  //
  // Select first item from list
  //

  // Since selector of the form 'div.version-list div.package-download:first-child' failed,
  // let's use a different approach.
  $('div.package-download').filter(':first').find('.version-link').addClass('version-selected first-version-link');


  //
  // Set default value
  //

  var os = json.slicerpackages.os_longname_to_shortname[$.client.os];
  var packageOs = $('div.packageOS.' + os);
  if(packageOs)
    {
    $('div.version-selected').removeClass('version-selected');
    packageOs.next().find('.version-link').addClass('version-selected');

    }

  //
  // Add rounded corners
  //

  $('div.version-info').corner();
  $('div.version-link').corner();


  //
  // Initialize versionInfo
  //

  var selectedVersionLink = $('div.version-selected');
  updateVersionInfoPosition(selectedVersionLink, $('#version-info-' + selectedVersionLink.attr('id')));
  $('#version-info-' + selectedVersionLink.attr('id')).addClass('version-info-selected');


  //
  // Setup event handlers
  //

  $('div.version-link').hover(
    function () {
      if(!$(this).hasClass('version-selected'))
        {
        $(this).addClass('version-highlighted');
        }
    },
    function () {
      $(this).removeClass('version-highlighted');
    });

  $('div.version-link').click(function () {
    if($(this).hasClass('version-selected'))
      {
      return;
      }
    $('div.version-selected').removeClass('version-selected');
    $(this).addClass('version-selected');
    $(this).removeClass('version-highlighted');

    updateVersionInfoPosition($(this), $('#version-info-' + this.id));

    $('div.version-info-selected').removeClass('version-info-selected');

    //$('#version-info-' + this.id).addClass('version-info-selected');
    var clickedVersionLinkId = this.id;
    $('#version-info-' + this.id).show('slide', { direction: 'left'}, 250,
      function() {
        $('#version-info-' + clickedVersionLinkId).addClass('version-info-selected');
        // Since the class 'version-info-selected' already set the dislay style to 'inline',
        // let's make sure to remove the css directly associated with the html element.
        $('#version-info-' + clickedVersionLinkId).css('display', '');
      }
    );

  });

  $('a.download').click(function() {
    $(this).parent().parent().parent().find('.version-cell-md5').show();
  });

});
