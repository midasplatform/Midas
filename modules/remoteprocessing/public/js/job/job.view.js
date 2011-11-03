$(document).ready(function(){
  $('#showLogLink').click(function(){
    $('#hiddenLog').toggle();
  });

  $(document).ready(function() {
    $('#tableResults').dataTable();
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

      return html;
    }