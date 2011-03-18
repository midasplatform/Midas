  var email=false;
  var password=false;
  var firstname=false;
  var lastname=false;
  
  $(document).ready(function() {
    $('#databaseType').change(function()
    {
      $('.dbForm').hide();
      $('#form'+$(this).val()).show();
      $('input[name=type]').val($(this).val());
      $('input[name=submit]').attr('disabled','disabled');
    });
    
    
    $('.dbForm').submit(function()
      {
          email=checkEmail($(this).find('input[name=email]').val());
          firstname=$(this).find('input[name=firstname]').val().length>0;
          lastname=$(this).find('input[name=lastname]').val().length>0;
          password=($(this).find('input[name=userpassword1]').val().length>2&&($(this).find('input[name=userpassword1]').val()==$(this).find('input[name=userpassword2]').val()));
          if(lastname&&firstname&&email&&password)
          {
            return true;
          }
          else
          {
            checkAll($(this).find('input[name=email]'));
            checkAll($(this).find('input[name=firstname]'));
            checkAll($(this).find('input[name=lastname]'));
            checkAll($(this).find('input[name=userpassword1]'));
            checkAll($(this).find('input[name=userpassword2]'));
            return false;
          }
      });
    $('.dbForm input').each(function()
      {
        $(this).after('<span></span>');
      })
    $('.dbForm input').focusout(function()
      {
        var obj=$(this);
        checkAll(obj);
        checkDB($(this));
      });
    
    $('.testConnection').click(function()
    {
      checkDB($(this));
    });
  });
  
  
  function checkDB(obj)
  {
    obj=obj.parents('form');
    obj.find('.testLoading').show();
    obj.find('.testOk').hide();
    obj.find('.testNok').hide();
    obj.find('.testError').html('');
    if(obj.find('[name=host]').val()==''||obj.find('[name=port]').val()=="")
      {
      obj.find('.testNok').show();
      obj.find('.testError').html("Please, set the port and the host.");  
      obj.find('.testLoading').hide();
      return;
      }
    
          $.ajax({
          type: "POST",
          url: json.global.webroot+'/install/testconnexion',
          data: {type: obj.find('[name=type]').val(), host: obj.find('[name=host]').val(), username: obj.find('[name=username]').val(),
            password: obj.find('[name=password]').val(),dbname: obj.find('[name=dbname]').val(),port: obj.find('[name=port]').val()},
          cache:false,
          success: function(jsonContent){
            var testConnexion=jQuery.parseJSON(jsonContent);
            console.log(jsonContent);
            obj.find('.testLoading').hide();
            if(testConnexion[0]==true)
              {
              obj.find('.testOk').show();
              obj.find('.testError').html(testConnexion[1]);  
              obj.find('[name=submit]').removeAttr('disabled');
              }
            else
              {
              obj.find('.testNok').show();
              obj.find('.testError').html(testConnexion[1]);
              }
          }
        }); 
  }
  
  function checkEmail(mailteste)
{
	var reg = new RegExp('^[a-z0-9]+([_|\.|-]{1}[a-z0-9]+)*@[a-z0-9]+([_|\.|-]{1}[a-z0-9]+)*[\.]{1}[a-z]{2,6}$', 'i');

	if(reg.test(mailteste))
	{
		return(true);
	}
	else
	{
		return(false);
	}
}
  
function checkAll(obj)
{
    if(obj.attr('name')=='email')
  {
    if(!checkEmail(obj.val()))
    {
      obj.parent('div').find('span').html('<img alt="" src="'+$('.webroot').val()+'/public/images/icons/nok.png"/> The e-mail is not valid');
      email=false;
    }
    else  
    {
      obj.parent('div').find('span').html('<img alt="" src="'+$('.webroot').val()+'/public/images/icons/ok.png"/>');
      email=true;
    }
  }
  if(obj.attr('name')=='firstname')
  {
    if(obj.val().length<1)
    {
      obj.parent('div').find('span').html('<img alt="" src="'+$('.webroot').val()+'/public/images/icons/nok.png"/> Please set your firstname'); 
      firstname=true;
    }
    else
    {
      obj.parent('div').find('span').html('<img alt="" src="'+$('.webroot').val()+'/public/images/icons/ok.png"/>');
      firstname=false;
    }
  }
  
  if(obj.attr('name')=='lastname')
  {
    if(obj.val().length<1)
    {
      obj.parent('div').find('span').html('<img alt="" src="'+$('.webroot').val()+'/public/images/icons/nok.png"/> Please set your lastname '); 
      lastname=false;
    }
    else
    {
      obj.parent('div').find('span').html('<img alt="" src="'+$('.webroot').val()+'/public/images/icons/ok.png"/>');
      lastname=true;
    }
  }
  if(obj.attr('name')=='userpassword1')
  {
    if(obj.val().length<3)
    {
      obj.parent('div').find('span').html('<img alt="" src="'+$('.webroot').val()+'/public/images/icons/nok.png"/> Password too short'); 
      password=false;
    }
    else
    {
      obj.parent('div').find('span').html('<img alt="" src="'+$('.webroot').val()+'/public/images/icons/ok.png"/>');
    }
  }
  if(obj.attr('name')=='userpassword2')
  {
    if(obj.val().length<3)
    {
      password=false;
    }
    else
    {
      if($('input[name=userpassword1]').val()!=obj.val())
      {
        obj.parent('div').find('span').html('<img alt="" src="'+$('.webroot').val()+'/public/images/icons/nok.png"/> The passwords are not the same');  
        password=false;
      }
      else
      {
        obj.parent('div').find('span').html('<img alt="" src="'+$('.webroot').val()+'/public/images/icons/ok.png"/>');
        password=true;
      }
    }
  }
}