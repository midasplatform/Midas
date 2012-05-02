
var current_slicer_revision = '';
var current_os = '';
var current_arch = '';
//var current_buildtype = '';
var current_packagetype = '';
var current_release = '';

function dataToHtmlTableRows(data, packagetype)
  {
  var webroot = $('.webroot').val();
  var tablecontent = '';
  $.each(data, function (key, val) {
    var productname = val.productname
    var revision = val.revision
    if(packagetype == 'Extension')
      {
      productname = val.productname + ' (' + val.revision + ')'
      revision = val.slicer_revision
      }
    var submissiontype = '';
    if(val.release == '')
      {
      submissiontype = val.submissiontype;
      }

    tablecontent += '<tr>';
    //tablecontent += '  <td class="packagetype ' + packagetype.toLowerCase() + '">' + packagetype + '</td>';
    tablecontent += '  <td class="packagetype ' + packagetype.toLowerCase() + '">';
    tablecontent += '    <a class="external" href="' + webroot + '/download/?items=' + val.item_id + '">' + productname + '</a>&nbsp;';
    tablecontent += '    / <a href="' + webroot + '/statistics/item?id=' + val.item_id + '">Stats</a>';
    tablecontent += '  </td>';
    tablecontent += '  <td class="submissiontype ' + submissiontype + '">' + val.release + '</td>';
    tablecontent += '  <td class="os ' + val.os + '" os="' + json.slicerpackages.os_shortname_to_longname[val.os] + '">' + json.slicerpackages.arch_shortname_to_longname[val.arch] + '</td>';
    tablecontent += '  <td>' + revision + '</td>';
    tablecontent += '</tr>';
  });
  return tablecontent;
  }

function fillDataTable(os, arch, /*buildtype,*/ packagetype, slicer_revision, release)
  {
  if(typeof(os) == 'undefined')
    {
    os = $('span.choice [type="radio"][name="osGroup"]:checked').val();
    }
  if(typeof(arch) == 'undefined')
    {
    arch = $('span.choice [type="radio"][name="archGroup"]:checked').val();
    }
  //if(typeof(buildtype) == 'undefined')
  //  {
  //  buildtype = $('span.choice [type="radio"][name="buildtypeGroup"]:checked').val();
  //  }
  if(typeof(packagetype) == 'undefined')
    {
    packagetype = $('span.choice [type="radio"][name="packagetypeGroup"]:checked').val();
    }
  if(typeof(slicer_revision) == 'undefined')
    {
    slicer_revision = $('[type="text"][name="slicer_revision"]').val();
    }
  if(typeof(release) == 'undefined')
    {
    release = $('option[name="releaseGroup"]:selected').val();
    }
  if(current_os != os || current_arch != arch
      /*|| current_buildtype != buildtype*/ || current_packagetype != packagetype
      || current_slicer_revision != slicer_revision || current_release != release)
    {
    $('#dataTableContent').html("");
    $("#dataTableLoading").show();
    var parameters = '';
    parameters+= 'os=' + os;
    parameters+= '&arch=' + arch;
    if(release == json.slicerpackages.latest_category_text)
      {
      release = '';
      }
    parameters+= '&release=' + release;

    if(packagetype == 'any' || packagetype == 'application')
      {
      var getPackagesParameters = parameters;
      if(slicer_revision != '') { getPackagesParameters+= '&revision=' + slicer_revision; }
      ajaxWebApi.ajax({
        method: 'midas.slicerpackages.package.list',
        args: getPackagesParameters,
        complete: function() {
          $("#dataTableLoading").hide();
          },
        success: function(data) {
          var tablecontent = $('#dataTableContent').html();
          tablecontent += dataToHtmlTableRows(data.data, 'Application');
          $('#dataTableContent').append(tablecontent);
          $('#dataTable').trigger({type:'update', resort:true});
          }
        });
      }

    if(packagetype == 'any' || packagetype == 'extension')
      {
      var getExtensionsParameters = parameters;
      if(slicer_revision != '') { getExtensionsParameters+= '&slicer_revision=' + slicer_revision; }
      ajaxWebApi.ajax({
        method: 'midas.slicerpackages.extension.list',
        args: getExtensionsParameters,
        complete: function() {
          $("#dataTableLoading").hide();
          },
        success: function(data) {
          var tablecontent = $('#dataTableContent').html();
          tablecontent += dataToHtmlTableRows(data.data, 'Extension');
          $('#dataTableContent').html(tablecontent);
          // let the sorting plugin know that we made an update
          $('#dataTable').trigger({type:'update', resort:true});
          }
        });
      }

    current_slicer_revision = slicer_revision;
    current_os = os;
    current_arch = arch;
    //current_buildtype = buildtype;
    current_packagetype = packagetype;
    current_release = release;
    }
  }

$(document).ready(function() {

  $('#dataTable').tablesorter({ // define a custom text extraction function
    textExtraction: function(node) {
      if($(node).hasClass('packagetype'))
        {
        return $(node).find('.external').text();
        }
      if($(node).hasClass('os'))
        {
        return $(node).attr('os') + node.innerHTML;
        }
      return node.innerHTML;
    }
  });

  //
  // Set default value
  //

  var os = json.slicerpackages.os_longname_to_shortname[$.client.os];
  if(json.slicerpackages.requested_os)
    {
    os = json.slicerpackages.requested_os;
    }
  $('span.choice [type="radio"][name="osGroup"][value="' + os + '"]').prop("checked", "checked");

  var arch = json.slicerpackages.arch_longname_to_shortname[$.client.arch];
  if(json.slicerpackages.requested_arch)
    {
    arch = json.slicerpackages.requested_arch;
    }
  $('span.choice [type="radio"][name="archGroup"][value="' + arch + '"]').prop("checked", "checked");

  if(json.slicerpackages.requested_packagetype)
    {
    var packagetype = json.slicerpackages.requested_packagetype;
    $('span.choice [type="radio"][name="packagetypeGroup"][value="' + packagetype + '"]').prop("checked", "checked");
    }

  if(json.slicerpackages.requested_slicer_revision)
    {
    var slicer_revision = json.slicerpackages.requested_slicer_revision;
    $('[type="text"][name="slicer_revision"]').val(slicer_revision);
    }

  if(json.slicerpackages.requested_release)
    {
    var release = json.slicerpackages.requested_release;
    $('option[name="releaseGroup"][value="' + release + '"]').prop("selected", "selected");
    }
  else
    {
    var releaseToSelect = $('option[name="releaseGroup"][value!=any]:first')
    if(releaseToSelect)
      {
      releaseToSelect.prop("selected", "selected");
      }
    }

  //
  // Setup event handlers
  //

  $('#slicerRevisionInput').bind('keypress', function(e) {
    var code = (e.keyCode ? e.keyCode : e.which);
    if(code == 13) { //Enter keycode
      fillDataTable();
      return false;
    }
    });

  $('#releaseForm select').change(function(){ fillDataTable(); });

  $('span.choice [type="radio"]').click(function(){ fillDataTable(); });

  //
  // Retrieve data
  //

  fillDataTable();
  });

