var ajaxWebApi = {};

/**
 * Wraps the jQuery ajax function with mechanisms to handle
 * authentication to the web API and parsing and logging of the
 * response object, including error handling
 * Parameters:
 *   method: Web API method to call (such as midas.bitstream.list)
 *   [args]: Key=value arguments to the web API method, delimited with &
 *   [success]: Function to be called when this function is finished (one 
 *              arg, the response json object)
 *   [error]: Function to be called if the request fails (one arg, the
 *            response json object)
 *   [complete]: Function to be called when done with the request, whether or
 *               not it was successful
 *   [log]: jQuerified DOM object representing the log area where output will
 *          be written. Default behavior is alert() in error conditions.
 */
ajaxWebApi.ajax = function(params)
{
  if(!params.method)
    {
    alert('ajaxWebApi.ajax: method parameter not set');
    return;
    }

  this._webApiCall(params);
}

/** Internal function.  Do not call directly. */
ajaxWebApi._webApiCall = function(params)
{
  if(!params.args)
    {
    params.args = 'useSession=true';
    }
  else
    {
    params.args += '&useSession=true';
    }


  $.ajax({
    type: 'POST',
    url: json.global.webroot + '/api/json?method=' + params.method,
    data: params.args,
    dataType: 'json',
    success: function(retVal) {
      if(params.complete)
        {
        params.complete();
        }
      if(retVal.stat == 'fail')
        {
        ajaxWebApi.logError(params.method + '?' + params.args + ' failed: ' + retVal.message, params.log);
        if(params.error)
          {
          params.error(retVal);
          }
        return;
        }
      if(params.success)
        {
        params.success(retVal);
        }
      },
    error: function() {
      ajaxWebApi.logError('Ajax call to web API returned an error (' +
          json.global.webroot + '/api/json' + '?' + params.method + '&' + params.args + ')', params.log);
      if(params.complete)
        {
        params.complete();
        }
      }
  });
}

ajaxWebApi.logMessage = function(text, log)
{
  if(log)
    {
    log.append('<span style="color:black;">' + text + '</span><br>');
    }
  else
    {
    alert(text);
    }
}

ajaxWebApi.logError = function(text, log)
{
  if(log)
    {
    log.append('<span style="color:red;">Error: ' + text + '</span><br>');
    }
  else
    {
    alert('Error: ' + text);
    }
}
