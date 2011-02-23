var nameValid=false;

$('form.createCommunityForm').submit(function()
{
  return nameValid;
});

$('div.createNameElement input').focusout(function()
{
  $.post($('.webroot').val()+"/community/validentry", {entry: obj.val(), type: "dbcommunityname"},
      function(data){
        if(data.search('true')!=-1)
        {
          alert('name already exists');
          nameValid=false;
        }
        else
        {
          nameValid=true;
        }
      });
});