
var altitude = 0;
var meshLiver, meshVenacava, meshStomach;
var selectedObject3D;
var jsonData;
var objects3D = new Array();

$(document).ready(function () {


  // Parse json content
  jsonData = jQuery.parseJSON($('div#datacontent').html());
  init();
  animate();

  // Buttons
  $( "#buttonUp" ).button({
            icons: {
                primary: "ui-icon-carat-1-n"
            },
            text: false
   });
   $( "#buttonDown" ).button({
            icons: {
                primary: "ui-icon-carat-1-s"
            },
            text: false
   });
   $( "#buttonLeft" ).button({
            icons: {
                primary: "ui-icon-carat-1-w"
            },
            text: false
   });
   $( "#buttonRight" ).button({
            icons: {
                primary: "ui-icon-carat-1-e"
            },
            text: false
   });
   $( "#buttonReset" ).button({
            icons: {
                primary: "ui-icon ui-icon-arrow-4-diag"
            },
            text: false
   });
   $( "#buttonPlus" ).button({
            icons: {
                primary: "ui-icon-plus"
            },
            text: false
   });
   $( "#buttonMinus" ).button({
            icons: {
                primary: "ui-icon-minus"
            },
            text: false
   });
});

function showDialogs()
  {
  $('[id^=helpDialog]').dialog({
        autoOpen: false,
        draggable: true,
        modal: false,
        width: 200
    });
   $('#helpDialog1').dialog('close');
   $('#helpDialog2').dialog('close');


  //x and y positions of dialog box
  $('#helpDialog1').dialog('option','position',[0, 30]);
  $('#helpDialog1').dialog('open');

  $('#helpDialog2').dialog('option','position',[window.innerWidth, 30]);
  }

function init()
  {
  initRenderer($('#container'), window.innerHeight, window.innerWidth);
  initCamera(altitude, 25, 0.2);
  camera.position.x = altitude;
  initLights();
  initInteractions();

  var files = new Array();
  var i = 0;
  $.each(jsonData.objects, function(key, value) {
    var callback = function( geometry ) {
      incrementProgressBar();

      var color = new THREE.Color( 0x000000 );
      color.setRGB( value.red , value.green, value.blue );
      var material = new THREE.MeshPhongMaterial(
         {
         color: color.getHex(),
         shading: THREE.SmoothShading,
         opacity: 1.0,
         transparent: true} ) ;
      // material.depthWrite = true;
      // material.depthTest = true;

      var mesh = new THREE.Mesh( geometry, material );
      mesh.scale.x = mesh.scale.y = mesh.scale.z = 1;
      mesh.name = value.name;

      // Apparently the meshes are flipped
      mesh.flipSided = true;

      //  mesh.dynamic = true;
      mesh.geometry.computeFaceNormals();
		  mesh.geometry.computeVertexNormals();
	  	mesh.__dirtyVertices = true;
		  mesh.__dirtyNormals = true;


      objects3D.push( mesh );
      scene.add( objects3D[i] );

      $('#helpDialog1').append('<input type="checkbox" class="objectVisibility" objectid="'+i+'" name="object'+i+'" checked> <label for="object'+i+'">'+mesh.name+'</label><br/>');
      $('.objectVisibility').unbind('change').change(function(){
        objects3D[$(this).attr('objectid')].visible = $(this).is(':checked');
      });

      i++;

      resetCameraPosition(objects3D);
    };

    files.push([jsonData.webroot+"/download/?bitstream="+value.bitstream.bitstream_id, callback]);
  });


  loaderElements(files);
  showDialogs();
  initAxes($('#axesContainer'));
	}


function onWindowResize()
  {
  width = window.innerWidth;
	height = window.innerHeight;
  showDialogs();
  return [width, height];
  }

function render()
  {
  defaultInitRenderer();
  defaultEndRenderer();
  }

function onDocumentMouseDown( event )
  {
  }
