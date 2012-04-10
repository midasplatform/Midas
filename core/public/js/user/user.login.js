var valid=false;
var prepare=true;

$('form#loginForm').submit(function () {  
    validLoginForm();
    prepare=true;
    $('input[name=previousuri]').val(json.global.currentUri);
    return valid;
});  

$('a#forgotPasswordLink').click(function () {
    midas.loadDialog("forgotpassword","/user/recoverpassword");
    midas.showDialog("Recover Password");
});

$('form#loginForm input[name=submit]').click(function () {  
    $("form#loginForm div.loginError img").show();
    $("form#loginForm div.loginError span").hide();
});  

$("a.registerLink").unbind('click').click(function(){;
    midas.showOrHideDynamicBar('register');
    midas.loadAjaxDynamicBar('register','/user/register');
});

function validLoginForm() {
    $.ajax({
        url: $('.webroot').val()+"/user/validentry",
        async:false,
        type: "POST",
        data: {entry: $('input[name=email]').val(),password:$('input[name=password]').val(), type: "login"},
        success: function (data) {
            $("form#loginForm div.loginError img").hide();
            if(data.search('true') != -1) {
                valid=true;
            }
            else {
                valid=false; 
            }
            midas.createNotice($("form#loginForm div.loginError span").html(), 8000, 'error');
        }
    }
});
