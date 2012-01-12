$(document).ready(function(){
  $('#showLogLink').click(function(){
    $('#hiddenLog').toggle();
  });

  $(document).ready(function() {
    $('#tableResults').dataTable();
    $('#tableXml').dataTable();


  $('.showInDialog').click(function()
    {
      showBigDialogWithContent('Output', '<pre>'+$(this).attr('output').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")+'</pre>',true);
    });

  initMetrics();

  if($('.metric').length != 0)
    {
    $('#metricsWrapper').show();
    }
  processXmlTableColors();
  });

  $('a[elementItem]').hover(function()
    {
    var qtip = $(this).qtip({
           content: {
                text: '<img  src="'+json.global.webroot+'/core/public/images/icons/loading.gif" alt="Loading..." />'
             }
          });
    qtip.qtip('toggle', true);
    $.ajax({
          type: "POST",
          url: json.global.webroot+'/browse/getelementinfo',
          data: {type: 'item', id: $(this).attr('elementItem')},
          success: function(jsonContent){
            qtip.qtip('option', 'content.text', createInfoItem(jsonContent)); // Preferred
          }
        });
    }, function(){

    })
});

  function initMetrics()
    {
    $('#tableXml thead .metric').each(function(){
      var html = '<label for="'+$(this).attr('name')+'">'+$(this).html()+':</label>';
      html += '<div id="'+$(this).attr('name')+'" style="border:0; color:green; font-weight:bold;" />';
      html += '<div style="width:50%;" id="'+$(this).attr('name')+'-slider-range"></div>';
      $('#metricsWrapper').append(html);
      var thisObj = $(this);

      var max = 0;
      $('.'+$(this).attr('name')+'-value').each(function()
        {
        if($(this).html() > max)
          {
          max = $(this).html();
          }
        });

      max = max * (1 + 0.10);

      $( '#'+$(this).attr('name')+'-slider-range' ).slider({
        range: true,
        step: 0.01,
        min: 0.00,
        max: max.toFixed(2),
        values: [ 0.00, max.toFixed(2) ],
        slide: function( event, ui ) {
          $( '#'+thisObj.attr('name')+'' ).html( "<span class='spanmin'>" + (Math.round(ui.values[ 0 ]*100)/100) + "</span> - <span class='spanmax'>" + (Math.round(ui.values[ 1 ]*100)/100)  + "</span>" );
          processXmlTableColors();
        }
      });
      $( '#'+$(this).attr('name')+''  ).html("<span class='spanmin'>0.00</span> - <span class='spanmax'>"+max.toFixed(2)+"</span>" );
    })
    }

  function processXmlTableColors()
    {
    $('#tableXml tbody tr').each(function(){
      var passed = true;
      if($(this).find('.xmlStatus').html() != 'passed')
        {
        passed = false;
        }

     var trObj = $(this);

     $('#tableXml thead .metric').each(function(){
       var name = $(this).attr('name');
       var value = trObj.find('.'+name+'-value').html();
       if(value <= $( '#'+name+' .spanmin').html() || value >= $( '#'+name+' .spanmax').html())
         {
         passed = false;
         }
     });


      if(passed)
        {
        $(this).find('td').css('background-color','#95cbab');
        }
      else
        {
        $(this).find('td').css('background-color','#e39a9a');
        }
    });
    }
  function createInfoItem(jsonContent)
    {
      arrayElement=jQuery.parseJSON(jsonContent);
      var html='';
      if(arrayElement['type']=='community')
        {
        html+='<img class="infoLogo" alt="Data Type" src="'+json.global.coreWebroot+'/public/images/icons/community-big.png" />';
        }
      else if(arrayElement['type']=='folder')
        {
        html+='<img class="infoLogo" alt="Data Type" src="'+json.global.coreWebroot+'/public/images/icons/folder-big.png" />';
        }
      else
        {
        html+='<img class="infoLogo" alt="Data Type" src="'+json.global.coreWebroot+'/public/images/icons/document-big.png" />';
        }
        html+='<span class="infoTitle" >'+sliceFileName(arrayElement['name'],27)+'</span>';
        html+='<table>';
        html+='  <tr>';
        html+='    <td>'+arrayElement.translation.Created+'</td>';
        html+='    <td>'+arrayElement.creation+'</td>';
        html+='  </tr>';
      if(arrayElement['type']=='community')
        {
        html+='  <tr>';
        html+='    <td>Member';
        if(parseInt(arrayElement['members'])>1)
          {
            html+='s';
          }
        html+=     '</td>';
        html+='    <td>'+arrayElement['members']+'</td>';
        html+='  </tr>';
        }
      if(arrayElement['type']=='item')
        {
        html+='  <tr>';
        html+='    <td>'+arrayElement.translation.Uploaded+'</td>';
        html+='    <td><a href="'+json.global.webroot+'/user/'+arrayElement['uploaded']['user_id']+'">'+arrayElement['uploaded']['firstname']+' '+arrayElement['uploaded']['lastname']+'</a></td>';
        html+='  </tr>';
        html+='  <tr>';
        html+='    <td>Revision';
        if(parseInt(arrayElement['revision']['revision'])>1)
          {
            html+='s';
          }
        html+=     '</td>';
        html+='    <td>'+arrayElement['revision']['revision']+'</td>';
        html+='  </tr>';
        html+='  <tr>';
        html+='    <td>'+arrayElement.translation.File;
              if(parseInt(arrayElement['nbitstream'])>1)
          {
            html+='s';
          }
        html+=    '</td>';
        html+='    <td>'+arrayElement['nbitstream']+'</td>';
        html+='  </tr>';

        }

      if(arrayElement['type']=='folder')
        {
        html+='  <tr>';
        html+='    <td colspan="2">';
        html+=arrayElement['teaser'];
        html+=     '</td>';
        html+='  </tr>';
        }
      html+='</table>';
      if(arrayElement['type']=='community'&&arrayElement['privacy']==2)
        {
         html+='<h4>'+arrayElement.translation.Private+'</h4>';
        }

      if(arrayElement['thumbnail']!=undefined&&arrayElement['thumbnail']!='')
        {
        html+='<b>'+json.browse.preview+'</b><br><a href="'+json.global.webroot+'/item/'+arrayElement['item_id']+'"><img class="infoLogo" alt="" src="'+json.global.webroot+'/'+arrayElement['thumbnail']+'" /></a>';
        }

      return html;
    }