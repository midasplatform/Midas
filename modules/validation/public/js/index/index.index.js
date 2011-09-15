  $(document).ready(function() {
    ajaxWebApi.ajax({
        "method" : "midas.validation.test",
        "success": function(data) {
            alert(data.data.foo);
        }
    });
  });