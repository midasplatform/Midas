
if(!this.JSON){this.JSON={};}
(function(){function f(n){return n<10?'0'+n:n;}
if(typeof Date.prototype.toJSON!=='function'){Date.prototype.toJSON=function(key){return isFinite(this.valueOf())?this.getUTCFullYear()+'-'+
f(this.getUTCMonth()+1)+'-'+
f(this.getUTCDate())+'T'+
f(this.getUTCHours())+':'+
f(this.getUTCMinutes())+':'+
f(this.getUTCSeconds())+'Z':null;};String.prototype.toJSON=Number.prototype.toJSON=Boolean.prototype.toJSON=function(key){return this.valueOf();};}
var cx=/[\u0000\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,escapable=/[\\\"\x00-\x1f\x7f-\x9f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,gap,indent,meta={'\b':'\\b','\t':'\\t','\n':'\\n','\f':'\\f','\r':'\\r','"':'\\"','\\':'\\\\'},rep;function quote(string){escapable.lastIndex=0;return escapable.test(string)?'"'+string.replace(escapable,function(a){var c=meta[a];return typeof c==='string'?c:'\\u'+('0000'+a.charCodeAt(0).toString(16)).slice(-4);})+'"':'"'+string+'"';}
function str(key,holder){var i,k,v,length,mind=gap,partial,value=holder[key];if(value&&typeof value==='object'&&typeof value.toJSON==='function'){value=value.toJSON(key);}
if(typeof rep==='function'){value=rep.call(holder,key,value);}
switch(typeof value){case'string':return quote(value);case'number':return isFinite(value)?String(value):'null';case'boolean':case'null':return String(value);case'object':if(!value){return'null';}
gap+=indent;partial=[];if(Object.prototype.toString.apply(value)==='[object Array]'){length=value.length;for(i=0;i<length;i+=1){partial[i]=str(i,value)||'null';}
v=partial.length===0?'[]':gap?'[\n'+gap+
partial.join(',\n'+gap)+'\n'+
mind+']':'['+partial.join(',')+']';gap=mind;return v;}
if(rep&&typeof rep==='object'){length=rep.length;for(i=0;i<length;i+=1){k=rep[i];if(typeof k==='string'){v=str(k,value);if(v){partial.push(quote(k)+(gap?': ':':')+v);}}}}else{for(k in value){if(Object.hasOwnProperty.call(value,k)){v=str(k,value);if(v){partial.push(quote(k)+(gap?': ':':')+v);}}}}
v=partial.length===0?'{}':gap?'{\n'+gap+partial.join(',\n'+gap)+'\n'+
mind+'}':'{'+partial.join(',')+'}';gap=mind;return v;}}
if(typeof JSON.stringify!=='function'){JSON.stringify=function(value,replacer,space){var i;gap='';indent='';if(typeof space==='number'){for(i=0;i<space;i+=1){indent+=' ';}}else if(typeof space==='string'){indent=space;}
rep=replacer;if(replacer&&typeof replacer!=='function'&&(typeof replacer!=='object'||typeof replacer.length!=='number')){throw new Error('JSON.stringify');}
return str('',{'':value});};}
if(typeof JSON.parse!=='function'){JSON.parse=function(text,reviver){var j;function walk(holder,key){var k,v,value=holder[key];if(value&&typeof value==='object'){for(k in value){if(Object.hasOwnProperty.call(value,k)){v=walk(value,k);if(v!==undefined){value[k]=v;}else{delete value[k];}}}}
return reviver.call(holder,key,value);}
text=String(text);cx.lastIndex=0;if(cx.test(text)){text=text.replace(cx,function(a){return'\\u'+
('0000'+a.charCodeAt(0).toString(16)).slice(-4);});}
if(/^[\],:{}\s]*$/.test(text.replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g,'@').replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,']').replace(/(?:^|:|,)(?:\s*\[)+/g,''))){j=eval('('+text+')');return typeof reviver==='function'?walk({'':j},''):j;}
throw new SyntaxError('JSON.parse');};}}());var escapeJSONString=(function()
{var escapable=/[\\\"\x00-\x1f\x7f-\x9f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,meta={'\b':'\\b','\t':'\\t','\n':'\\n','\f':'\\f','\r':'\\r','"':'\\"','\\':'\\\\'};return function(string){escapable.lastIndex=0;return escapable.test(string)?'"'+string.replace(escapable,function(a){var c=meta[a];return typeof c==='string'?c:'\\u'+('0000'+a.charCodeAt(0).toString(16)).slice(-4);})+'"':'"'+string+'"';};})();function toJSON(o)
{var marker="$_$jabsorbed$813492";var markerHead;var fixups=[];function removeMarkers()
{var next;while(markerHead)
{next=markerHead[marker].prev;delete markerHead[marker];markerHead=next;}}
var omitCircRefOrDuplicate={};var json;function subObjToJSON(o,p,ref)
{var v=[],fixup,original,parent,circRef,i;if(o===null||o===undefined)
{return"null";}
else if(typeof o==='string')
{return escapeJSONString(o);}
else if(typeof o==='number')
{return o.toString();}
else if(typeof o==='boolean')
{return o.toString();}
else
{if(o[marker])
{fixup=[ref];parent=p;while(parent)
{if(original)
{original.unshift(parent[marker].ref);}
if(parent===o)
{circRef=parent;original=[circRef[marker].ref];}
fixup.unshift(parent[marker].ref);parent=parent[marker].parent;}
if(circRef)
{if(JSONRpcClient.fixupCircRefs)
{fixup.shift();original.shift();fixups.push([fixup,original]);return omitCircRefOrDuplicate;}
else
{removeMarkers();throw new Error("circular reference detected!");}}
else
{if(JSONRpcClient.fixupDuplicates)
{original=[o[marker].ref];parent=o[marker].parent;while(parent)
{original.unshift(parent[marker].ref);parent=parent[marker].parent;}
fixup.shift();original.shift();fixups.push([fixup,original]);return omitCircRefOrDuplicate;}}}
else
{o[marker]={parent:p,prev:markerHead,ref:ref};markerHead=o;}
if(o.constructor===Date)
{if(o.javaClass)
{return'{javaClass: "'+o.javaClass+'", time: '+o.valueOf()+'}';}
else
{return'{javaClass: "java.util.Date", time: '+o.valueOf()+'}';}}
else if(o.constructor===Array)
{for(i=0;i<o.length;i++)
{json=subObjToJSON(o[i],o,i);v.push(json===omitCircRefOrDuplicate?null:json);}
return"["+v.join(", ")+"]";}
else
{for(var attr in o)
{if(attr===marker)
{}
else if(o[attr]===null||o[attr]===undefined)
{v.push("\""+attr+"\": null");}
else if(typeof o[attr]=="function")
{}
else
{json=subObjToJSON(o[attr],o,attr);if(json!==omitCircRefOrDuplicate)
{v.push(escapeJSONString(attr)+": "+json);}}}
return"{"+v.join(", ")+"}";}}}
json=subObjToJSON(o,null,"root");removeMarkers();if(fixups.length)
{return{json:json,fixups:fixups};}
else
{return{json:json};}}
function JSONRpcClient()
{var arg_shift=0,req,_function,methods,self,name,arg0type=(typeof arguments[0]),doListMethods=true;if(arg0type==="function")
{this.readyCB=arguments[0];arg_shift++;}
else if(arguments[0]&&arg0type==="object"&&arguments[0].length)
{this._addMethods(arguments[0]);arg_shift++;doListMethods=false;}
this.serverURL=arguments[arg_shift];this.user=arguments[arg_shift+1];this.pass=arguments[arg_shift+2];this.objectID=0;if(doListMethods)
{this._addMethods(["system.listMethods"]);req=JSONRpcClient._makeRequest(this,"system.listMethods",[]);if(this.readyCB)
{self=this;req.cb=function(result,e)
{if(!e)
{self._addMethods(result);}
self.readyCB(result,e);};}
if(!this.readyCB)
{methods=JSONRpcClient._sendRequest(this,req);this._addMethods(methods);}
else
{JSONRpcClient.async_requests.push(req);JSONRpcClient.kick_async();}}}
JSONRpcClient.prototype.createCallableProxy=function(objectID,javaClass)
{var cp,req,methodNames,name,i;cp=new JSONRPCCallableProxy(objectID,javaClass);for(name in JSONRpcClient.knownClasses[javaClass])
{cp[name]=JSONRpcClient.bind(JSONRpcClient.knownClasses[javaClass][name],cp);}
return cp;};function JSONRPCCallableProxy()
{this.objectID=arguments[0];this.javaClass=arguments[1];this.JSONRPCType="CallableReference";}
JSONRpcClient.knownClasses={};JSONRpcClient.Exception=function(errorObject)
{var m;for(var prop in errorObject)
{if(errorObject.hasOwnProperty(prop))
{this[prop]=errorObject[prop];}}
if(this.trace)
{m=this.trace.match(/^([^:]*)/);if(m)
{this.name=m[0];}}
if(!this.name)
{this.name="JSONRpcClientException";}};JSONRpcClient.Exception.CODE_REMOTE_EXCEPTION=490;JSONRpcClient.Exception.CODE_ERR_CLIENT=550;JSONRpcClient.Exception.CODE_ERR_PARSE=590;JSONRpcClient.Exception.CODE_ERR_NOMETHOD=591;JSONRpcClient.Exception.CODE_ERR_UNMARSHALL=592;JSONRpcClient.Exception.CODE_ERR_MARSHALL=593;JSONRpcClient.Exception.prototype=new Error();JSONRpcClient.Exception.prototype.toString=function(code,msg)
{var str="";if(this.name)
{str+=this.name;}
if(this.message)
{str+=": "+this.message;}
if(str.length==0)
{str="no exception information given";}
return str;};JSONRpcClient.default_ex_handler=function(e)
{var a,str="";for(a in e)
{str+=a+"\t"+e[a]+"\n";}
alert(str);};JSONRpcClient.toplevel_ex_handler=JSONRpcClient.default_ex_handler;JSONRpcClient.profile_async=false;JSONRpcClient.max_req_active=1;JSONRpcClient.requestId=1;JSONRpcClient.fixupCircRefs=true;JSONRpcClient.fixupDuplicates=true;JSONRpcClient.transformDates=false;JSONRpcClient.transformDateWithoutHint=false;JSONRpcClient.javaDateClasses={'java.util.Date':true,'java.sql.Date':true,'java.sql.Time':true,'java.sql.Timestamp':true};JSONRpcClient.bind=function(functionName,context)
{return function(){return functionName.apply(context,arguments);};};JSONRpcClient._createMethod=function(client,methodName)
{var serverMethodCaller=function()
{var args=[],callback;for(var i=0;i<arguments.length;i++)
{args.push(arguments[i]);}
if(typeof args[0]=="function")
{callback=args.shift();}
var req=JSONRpcClient._makeRequest(this,methodName,args,this.objectID,callback);if(!callback)
{return JSONRpcClient._sendRequest(client,req);}
else
{JSONRpcClient.async_requests.push(req);JSONRpcClient.kick_async();return req.requestId;}};return serverMethodCaller;};JSONRpcClient.prototype.createObject=function()
{var args=[],callback=null,constructorName,_args,req;for(var i=0;i<arguments.length;i++)
{args.push(arguments[i]);}
if(typeof args[0]=="function")
{callback=args.shift();}
constructorName=args[0]+".$constructor";_args=args[1];req=JSONRpcClient._makeRequest(this,constructorName,_args,0,callback);if(callback===null)
{return JSONRpcClient._sendRequest(this,req);}
else
{JSONRpcClient.async_requests.push(req);JSONRpcClient.kick_async();return req.requestId;}};JSONRpcClient.CALLABLE_REFERENCE_METHOD_PREFIX=".ref";JSONRpcClient.prototype._addMethods=function(methodNames,dontAdd)
{var name,obj,names,n,method,methods=[],javaClass,tmpNames,startIndex,endIndex;for(var i=0;i<methodNames.length;i++)
{obj=this;names=methodNames[i].split(".");startIndex=methodNames[i].indexOf("[");endIndex=methodNames[i].indexOf("]");if((methodNames[i].substring(0,JSONRpcClient.CALLABLE_REFERENCE_METHOD_PREFIX.length)==JSONRpcClient.CALLABLE_REFERENCE_METHOD_PREFIX)&&(startIndex!=-1)&&(endIndex!=-1)&&(startIndex<endIndex))
{javaClass=methodNames[i].substring(startIndex+1,endIndex);}
else
{for(n=0;n<names.length-1;n++)
{name=names[n];if(obj[name])
{obj=obj[name];}
else
{obj[name]={};obj=obj[name];}}}
name=names[names.length-1];if(javaClass)
{method=JSONRpcClient._createMethod(this,name);if(!JSONRpcClient.knownClasses[javaClass])
{JSONRpcClient.knownClasses[javaClass]={};}
JSONRpcClient.knownClasses[javaClass][name]=method;}
else
{method=JSONRpcClient._createMethod(this,methodNames[i]);if((!obj[name])&&(!dontAdd))
{obj[name]=JSONRpcClient.bind(method,this);}
methods.push(method);}
javaClass=null;}
return methods;};JSONRpcClient._getCharsetFromHeaders=function(http)
{var contentType,parts,i;try
{contentType=http.getResponseHeader("Content-type");parts=contentType.split(/\s*;\s*/);for(i=0;i<parts.length;i++)
{if(parts[i].substring(0,8)=="charset=")
{return parts[i].substring(8,parts[i].length);}}}
catch(e)
{}
return"UTF-8";};JSONRpcClient.async_requests=[];JSONRpcClient.async_inflight={};JSONRpcClient.async_responses=[];JSONRpcClient.async_timeout=null;JSONRpcClient.num_req_active=0;JSONRpcClient._async_handler=function()
{var res,req;JSONRpcClient.async_timeout=null;while(JSONRpcClient.async_responses.length>0)
{res=JSONRpcClient.async_responses.shift();if(res.canceled)
{continue;}
if(res.profile)
{res.profile.dispatch=new Date();}
try
{res.cb(res.result,res.ex,res.profile);}
catch(e)
{JSONRpcClient.toplevel_ex_handler(e);}}
while(JSONRpcClient.async_requests.length>0&&JSONRpcClient.num_req_active<JSONRpcClient.max_req_active)
{req=JSONRpcClient.async_requests.shift();if(req.canceled)
{continue;}
JSONRpcClient._sendRequest(req.client,req);}};JSONRpcClient.kick_async=function()
{if(!JSONRpcClient.async_timeout)
{JSONRpcClient.async_timeout=setTimeout(JSONRpcClient._async_handler,0);}};JSONRpcClient.cancelRequest=function(requestId)
{if(JSONRpcClient.async_inflight[requestId])
{JSONRpcClient.async_inflight[requestId].canceled=true;return true;}
var i;for(i in JSONRpcClient.async_requests)
{if(JSONRpcClient.async_requests[i].requestId==requestId)
{JSONRpcClient.async_requests[i].canceled=true;return true;}}
for(i in JSONRpcClient.async_responses)
{if(JSONRpcClient.async_responses[i].requestId==requestId)
{JSONRpcClient.async_responses[i].canceled=true;return true;}}
return false;};JSONRpcClient._makeRequest=function(client,methodName,args,objectID,cb)
{var req={};req.client=client;req.requestId=JSONRpcClient.requestId++;var obj="{id:"+req.requestId+",method:";if((objectID)&&(objectID>0))
{obj+="\".obj["+objectID+"]."+methodName+"\"";}
else
{obj+="\""+methodName+"\"";}
if(cb)
{req.cb=cb;}
if(JSONRpcClient.profile_async)
{req.profile={submit:new Date()};}
var j=toJSON(args);obj+=",params:"+j.json;if(j.fixups)
{obj+=",fixups:"+toJSON(j.fixups).json;}
req.data=obj+"}";return req;};JSONRpcClient._sendRequest=function(client,req)
{var http;if(req.profile)
{req.profile.start=new Date();}
http=JSONRpcClient.poolGetHTTPRequest();JSONRpcClient.num_req_active++;http.open("POST",client.serverURL,!!req.cb,client.user,client.pass);try
{http.setRequestHeader("Content-type","text/plain");}
catch(e)
{}
if(req.cb)
{http.onreadystatechange=function()
{var res;if(http.readyState==4)
{http.onreadystatechange=function()
{};res={cb:req.cb,result:null,ex:null};if(req.profile)
{res.profile=req.profile;res.profile.end=new Date();}
else
{res.profile=false;}
try
{res.result=client._handleResponse(http);}
catch(e)
{res.ex=e;}
if(!JSONRpcClient.async_inflight[req.requestId].canceled)
{JSONRpcClient.async_responses.push(res);}
delete JSONRpcClient.async_inflight[req.requestId];JSONRpcClient.kick_async();}};}
else
{http.onreadystatechange=function()
{};}
JSONRpcClient.async_inflight[req.requestId]=req;try
{http.send(req.data);}
catch(e)
{JSONRpcClient.poolReturnHTTPRequest(http);JSONRpcClient.num_req_active--;throw new JSONRpcClient.Exception({code:JSONRpcClient.Exception.CODE_ERR_CLIENT,message:"Connection failed"});}
if(!req.cb)
{delete JSONRpcClient.async_inflight[req.requestId];return client._handleResponse(http);}
return null;};JSONRpcClient.prototype._handleResponse=function(http)
{if(!this.charset)
{this.charset=JSONRpcClient._getCharsetFromHeaders(http);}
var status,statusText,data;try
{status=http.status;statusText=http.statusText;data=http.responseText;}
catch(e)
{JSONRpcClient.poolReturnHTTPRequest(http);JSONRpcClient.num_req_active--;JSONRpcClient.kick_async();throw new JSONRpcClient.Exception({code:JSONRpcClient.Exception.CODE_ERR_CLIENT,message:"Connection failed"});}
JSONRpcClient.poolReturnHTTPRequest(http);JSONRpcClient.num_req_active--;if(status!=200)
{throw new JSONRpcClient.Exception({code:status,message:statusText});};return this.unmarshallResponse(data);};JSONRpcClient.prototype.unmarshallResponse=function(data)
{function applyFixups(obj,fixups)
{function findOriginal(ob,original)
{for(var i=0,j=original.length;i<j;i++)
{ob=ob[original[i]];}
return ob;}
function applyFixup(ob,fixups,value)
{var j=fixups.length-1;for(var i=0;i<j;i++)
{ob=ob[fixups[i]];}
ob[fixups[j]]=value;}
for(var i=0,j=fixups.length;i<j;i++)
{applyFixup(obj,fixups[i][0],findOriginal(obj,fixups[i][1]));}}
function transformDate(obj)
{function hasOnlyProperty(obj,prop)
{var i,count=0;if(obj.hasOwnProperty(prop))
{for(i in obj)
{if(obj.hasOwnProperty(i))
{count++;if(count>1)
{return;}}}
return true;}}
var i,d;if(obj&&typeof obj==='object')
{if((obj.javaClass&&JSONRpcClient.javaDateClasses[obj.javaClass]))
{d=new Date(obj.time);if(obj.javaClass!=='java.util.Date')
{d.javaClass=obj.javaClass;}
return d;}
else if(JSONRpcClient.transformDateWithoutHint&&hasOnlyProperty(obj,'time'))
{return new Date(obj.time);}
else
{for(i in obj)
{if(obj.hasOwnProperty(i))
{obj[i]=transformDate(obj[i]);}}
return obj;}}
else
{return obj;}}
var obj;try
{eval("obj = "+data);}
catch(e)
{throw new JSONRpcClient.Exception({code:550,message:"error parsing result"});}
if(obj.error)
{throw new JSONRpcClient.Exception(obj.error);}
var r=obj.result;var i,tmp;if(r)
{if(r.objectID&&r.JSONRPCType=="CallableReference")
{return this.createCallableProxy(r.objectID,r.javaClass);}
else
{r=JSONRpcClient.extractCallableReferences(this,JSONRpcClient.transformDates?transformDate(r):r);if(obj.fixups)
{applyFixups(r,obj.fixups);}}}
return r;};JSONRpcClient.extractCallableReferences=function(client,root)
{var i,tmp,value;for(i in root)
{if(typeof(root[i])=="object")
{tmp=JSONRpcClient.makeCallableReference(client,root[i]);if(tmp)
{root[i]=tmp;}
else
{tmp=JSONRpcClient.extractCallableReferences(client,root[i]);root[i]=tmp;}}
if(typeof(i)=="object")
{tmp=JSONRpcClient.makeCallableReference(client,i);if(tmp)
{value=root[i];delete root[i];root[tmp]=value;}
else
{tmp=JSONRpcClient.extractCallableReferences(client,i);value=root[i];delete root[i];root[tmp]=value;}}}
return root;};JSONRpcClient.makeCallableReference=function(client,value)
{if(value&&value.objectID&&value.javaClass&&value.JSONRPCType=="CallableReference")
{return client.createCallableProxy(value.objectID,value.javaClass);}
return null;};JSONRpcClient.http_spare=[];JSONRpcClient.http_max_spare=8;JSONRpcClient.poolGetHTTPRequest=function()
{var http=JSONRpcClient.http_spare.pop();if(http)
{return http;}
return JSONRpcClient.getHTTPRequest();};JSONRpcClient.poolReturnHTTPRequest=function(http)
{if(JSONRpcClient.http_spare.length>=JSONRpcClient.http_max_spare)
{delete http;}
else
{JSONRpcClient.http_spare.push(http);}};JSONRpcClient.msxmlNames=["MSXML2.XMLHTTP.6.0","MSXML2.XMLHTTP.3.0","MSXML2.XMLHTTP","MSXML2.XMLHTTP.5.0","MSXML2.XMLHTTP.4.0","Microsoft.XMLHTTP"];JSONRpcClient.getHTTPRequest=function()
{try
{JSONRpcClient.httpObjectName="XMLHttpRequest";return new XMLHttpRequest();}
catch(e)
{}
for(var i=0;i<JSONRpcClient.msxmlNames.length;i++)
{try
{JSONRpcClient.httpObjectName=JSONRpcClient.msxmlNames[i];return new ActiveXObject(JSONRpcClient.msxmlNames[i]);}
catch(e)
{}}
JSONRpcClient.httpObjectName=null;throw new JSONRpcClient.Exception({code:0,message:"Can't create XMLHttpRequest object"});};var flashRenderers=new Object();var jsRenderers=new Object();var javaRenderers=new Object();var jsRendererInteraction=new Object();var paraviewObjects=new Object();var __nb_global_ie_methods__=1;function consumeEvent(event){if(event.preventDefault){event.preventDefault();}else{event.returnValue=false;}
return false;}
function touchInteraction(rendererId,sessionId,viewId,action,event){if(!jsRendererInteraction[rendererId]){jsRendererInteraction[rendererId]={pendingAction:0,lastX:0,lastY:0};}
var rendererInfo=jsRendererInteraction[rendererId];var height=jsRenderers[rendererId].view.height;var width=jsRenderers[rendererId].view.width;event.preventDefault();if(action=='down'){if(rendererInfo.pendingAction==2){paraviewObjects[sessionId].sendEvent('MouseEvent',viewId+' 2 0 '
+rendererInfo.lastX+' '
+rendererInfo.lastY+' 0 0');rendererInfo.pendingAction=0;}else if(rendererInfo.pendingAction==3){paraviewObjects[sessionId].sendEvent('MouseEvent',viewId+' 2 0 0 '
+rendererInfo.lastY+' 1 0');}
rendererInfo.pendingAction=1;rendererInfo.lastX=event.touches[0].pageX/width;rendererInfo.lastY=-event.touches[0].pageY/height;paraviewObjects[sessionId].sendEvent('MouseEvent',viewId+' 0 0 '
+rendererInfo.lastX+' '
+rendererInfo.lastY+' 0 0');}else if(action=='move'&&(rendererInfo.pendingAction==2||rendererInfo.pendingAction==1)&&event.scale==1){rendererInfo.pendingAction=2;rendererInfo.lastX=event.touches[0].pageX/width;rendererInfo.lastY=-event.touches[0].pageY/height;paraviewObjects[sessionId].sendEvent('MouseMove',viewId+' 1 0 '
+rendererInfo.lastX+' '
+rendererInfo.lastY+' 0 0');}else if(action=='move'&&event.scale!=1){if(rendererInfo.pendingAction==2||rendererInfo.pendingAction==1){paraviewObjects[sessionId].sendEvent('MouseEvent',viewId+' 2 0 '
+rendererInfo.lastX+' '
+rendererInfo.lastY+' 0 0');paraviewObjects[sessionId].sendEvent('MouseEvent',viewId+' 0 0 0 0 1 0');}
rendererInfo.pendingAction=3;rendererInfo.lastY=(1-event.scale)/10;paraviewObjects[sessionId].sendEvent('MouseEvent',viewId+' 1 0 0 '+rendererInfo.lastY+' 1 0');}}
function mouseInteraction(rendererId,sessionId,viewId,action,event){consumeEvent(event);jsRendererInteraction.lastRealEvent=event;var width=jsRenderers[rendererId].view.width;var height=jsRenderers[rendererId].view.height;if(action=='down'){if(jsRendererInteraction.needUp){paraviewObjects[sessionId].sendEvent('MouseEvent',viewId+' 2 '+jsRendererInteraction.lastEvent);}
jsRendererInteraction.isDragging=true;jsRendererInteraction.needUp=true;if(navigator.appName.indexOf("Microsoft")==-1){jsRendererInteraction.button=event.button+' ';}else{switch(event.button){case 1:break;jsRendererInteraction.button='0 ';case 4:jsRendererInteraction.button='1 ';break;case 2:jsRendererInteraction.button='2 ';break;}}
jsRendererInteraction.action=" 0 ";jsRendererInteraction.keys="";if(event.ctrlKey){jsRendererInteraction.keys+="1";}else{jsRendererInteraction.keys+="0";}
if(event.shiftKey){jsRendererInteraction.keys+=" 1";}else{jsRendererInteraction.keys+=" 0";}
jsRendererInteraction.x=event.screenX;jsRendererInteraction.y=event.screenY;var docX=0;var docY=0;if(event.pageX==null){var d=(document.documentElement&&document.documentElement.scrollLeft!=null)?document.documentElement:document.body;docX=event.clientX+d.scrollLeft;docY=event.clientY+d.scrollTop;}else{docX=event.pageX;docY=event.pageY;}
jsRendererInteraction.xOrigin=docX-jsRenderers[rendererId].getPageX();jsRendererInteraction.yOrigin=docY-jsRenderers[rendererId].getPageY();var ratio=jsRenderers[rendererId].interactiveRatio;updateRendererSize(sessionId,viewId,width/ratio,height/ratio);}else if(action=='move'){jsRendererInteraction.action=" 1 ";}else if(action=='up'||action=='click'){jsRendererInteraction.isDragging=false;jsRendererInteraction.needUp=false;jsRendererInteraction.action=" 2 ";var mouseInfo=((event.screenX-jsRendererInteraction.x+jsRendererInteraction.xOrigin)/height)+" "+(1-(event.screenY-jsRendererInteraction.y+jsRendererInteraction.yOrigin)/height)+" "+jsRendererInteraction.keys;jsRendererInteraction.lastEvent=jsRendererInteraction.button+mouseInfo;paraviewObjects[sessionId].sendEvent('MouseEvent',viewId+jsRendererInteraction.action+jsRendererInteraction.lastEvent);updateRendererSize(sessionId,viewId,width,height);jsRendererInteraction.scale=1;jsRendererInteraction.button=event.button+' ';jsRendererInteraction.keys="0 0"}
if(jsRendererInteraction.isDragging){var mouseInfoDrag=((event.screenX-jsRendererInteraction.x+jsRendererInteraction.xOrigin)/height)+" "+(1-(event.screenY-jsRendererInteraction.y+jsRendererInteraction.yOrigin)/height)+" "+jsRendererInteraction.keys;var mouseAction='MouseEvent';if(action=='move'){mouseAction='MouseMove';}
jsRendererInteraction.lastEvent=jsRendererInteraction.button+mouseInfoDrag;paraviewObjects[sessionId].sendEvent(mouseAction,viewId+jsRendererInteraction.action+jsRendererInteraction.lastEvent);}
return false;}
function getFlashApplet(appName){if(navigator.appName.indexOf("Microsoft")!=-1){return window[appName];}else{return document[appName];}}
function flexParaWebRendererLoaded(id){getFlashApplet(id).initializeView(flashRenderers[id].sessionId,flashRenderers[id].viewId);getFlashApplet(id).showInfo(false);}
function updateFlexFps(id,fps){flashRenderers[id].fps=fps;}
function updateJavaFps(id,fps){javaRenderers[id].fps=fps;}
function updateRendererSize(sessionId,viewId,sizeWidth,sizeHeight){if(sizeWidth<1){sizeWidth=10;alert("Try to resize server window with invalide size: "+sizeWidth+' x '+sizeHeight)}
if(sizeHeight<1){sizeHeight=10;}
paraviewObjects[sessionId].UpdateViewsSize({size:[sizeWidth,sizeHeight],viewId:viewId});}
function JsonRpcObjectBuilder(methodName,methodArguments){var args=[];if(typeof(methodArguments)!="undefined"){if(typeof(methodArguments)!="object"){args=[methodArguments];}else{args=methodArguments;}}
return{id:1,method:methodName,params:args};}
function executeRemote(paraviewInstance,methodName,methodArguments){try{var reply_string=paraviewInstance.jsonRpcClient.VisualizationsManager.invoke(paraviewInstance.sessionId,JSON.stringify(JsonRpcObjectBuilder(methodName,methodArguments)));var json=JSON.parse(reply_string);if(json.error){throw json.error.message;}
if(json.result.error){throw json.result.error.message;}
if(json.result.result)
return attatchMethods(paraviewInstance,json.result.result);return attatchMethods(paraviewInstance,json.result);}catch(e){if(paraviewInstance.errorListener){if(paraviewInstance.errorListener.manageError(e)){throw(e);}}else{throw(e);}}}
function attatchMethods(paraviewInstance,replyObj){if(replyObj.__jsonclass__=="Proxy"){replyObj._method=function(){var real_params=JsonRpcObjectBuilder(replyObj.__selfid__,JsonRpcObjectBuilder(extractMethodName(arguments),extractArguments(arguments)));return executeRemote(paraviewInstance,"execute_command_on",real_params);}
replyObj._get=function(){var real_params=JsonRpcObjectBuilder(replyObj.__selfid__,JsonRpcObjectBuilder(extractMethodName(arguments),[]));return executeRemote(paraviewInstance,"handle_property",real_params);}
replyObj._set=function(){var real_params=JsonRpcObjectBuilder(replyObj.__selfid__,JsonRpcObjectBuilder(extractMethodName(arguments),extractArguments(arguments)));return executeRemote(paraviewInstance,"handle_property",real_params);}
var index;for(index in replyObj.__properties__){var propertyName=replyObj.__properties__[index];replyObj["get"+propertyName]=appendMethodName(replyObj._get,"get"+propertyName);replyObj["set"+propertyName]=appendMethodName(replyObj._set,"set"+propertyName);}
for(index in replyObj.__methods__){var methodName=replyObj.__methods__[index];replyObj[methodName]=appendMethodName(replyObj._method,methodName);}
replyObj['getLatest']=appendMethodName(function(){return executeRemote(paraviewInstance,"get_last_version",replyObj.__selfid__);},methodName);}
return replyObj;}
function appendMethodName(methodToCall,methodName){return function(){var args=new Array();args.push(methodName);for(var i=0;i<arguments.length;i++){args.push(arguments[i]);}
return methodToCall.apply(this,args);};}
function extractMethodName(args){return args[0];}
function extractArguments(args){if(args.length==2){return args[1];}
else{var resultArgs=new Array();for(var i=1;i<args.length;i++){resultArgs.push(args[i]);}
return resultArgs;}}
function Paraview(coreServiceURL){this.coreServiceURL=coreServiceURL;this.jsonRpcClient=new JSONRpcClient(coreServiceURL+"/json");this.plugins={};this.sessionId='';this.errorListener;}
Paraview.prototype.sendEvent=function(command,content){try{this.jsonRpcClient.VisualizationsManager.forwardWithoutReply(this.sessionId,command,content);}catch(e){if(this.errorListener){if(this.errorListener.manageError(e)){throw(e);}}else{throw(e);}}}
Paraview.prototype.loadFile=function(filename){try{var reply_string=this.jsonRpcClient.VisualizationsManager.loadFile(this.sessionId,filename);var json=JSON.parse(reply_string);if(json.error){throw json.error.message;}
if(json.result.error){throw json.result.error.message;}
if(json.result.result)
return attatchMethods(this,json.result.result);return attatchMethods(this,json.result);}catch(e){if(this.errorListener){if(this.errorListener.manageError(e)){throw(e);}}else{throw(e);}}}
Paraview.prototype.createSession=function(name,comment,settingId){try{if(!settingId)
settingId="default";var _pv_=this;this.sessionId=this.jsonRpcClient.VisualizationsManager.createVisualization(name,comment,settingId);var reply=executeRemote(_pv_,"get_module",[]);var method=function(){return executeRemote(_pv_,"execute_command",JsonRpcObjectBuilder(extractMethodName(arguments),extractArguments(arguments)));}
for(var i=0;i<reply.length;i++){var methodname=reply[i];this[methodname]=appendMethodName(method,methodname);}
paraviewObjects[this.sessionId]=this;}catch(e){if(this.errorListener){if(this.errorListener.manageError(e)){throw(e);}}else{throw(e);}}}
Paraview.prototype.connectToSession=function(sessionId){var _pv_=this;this.sessionId=sessionId;var reply=executeRemote(this,"get_module",[]);var method=function(){return executeRemote(_pv_,"execute_command",JsonRpcObjectBuilder(extractMethodName(arguments),extractArguments(arguments)));}
for(var i=0;i<reply.length;i++){var methodname=reply[i];this[methodname]=appendMethodName(method,methodname);}
paraviewObjects[this.sessionId]=this;}
Paraview.prototype.disconnect=function(){try{this.jsonRpcClient.VisualizationsManager.stopVisualization(this.sessionId);}catch(e){if(this.errorListener){if(this.errorListener.manageError(e)){throw(e);}}else{throw(e);}}}
Paraview.prototype.loadPlugins=function(){var _pv_=this;var reply=executeRemote(_pv_,"get_plugins",[]);for(var index=0;index<reply.length;index++){var pluginName=reply[index];this.plugins[pluginName]=this.getPlugin(pluginName);}}
Paraview.prototype.getPlugin=function(pluginName){try{var _pv_=this;var plugin=executeRemote(_pv_,"get_plugin",[pluginName]);plugin._method=function(){var real_params=JsonRpcObjectBuilder(pluginName,JsonRpcObjectBuilder(extractMethodName(arguments),extractArguments(arguments)));return executeRemote(_pv_,"execute_command_on_plugin",real_params);}
var index;for(index in plugin.__methods__){var methodName=plugin.__methods__[index];plugin[methodName]=appendMethodName(plugin._method,methodName);}
return plugin;}catch(e){if(this.errorListener){if(this.errorListener.manageError(e)){throw(e);}}else{throw(e);}}}
function JavaScriptRenderer(rendererId,coreServiceURL){this.baseURL=coreServiceURL+"/LastPicture";this.sessionId="";this.viewId="";this.nbError=0;this.nbStart=0;this.interactiveRatio=2;this.localTimeStamp=0;this.bgImage=new Image();this.view=new Image();this.view.id=rendererId;this.view.alt="ParaView Renderer";this.bgImage.viewId=rendererId;this.bgImage.otherThis=this;this.lastImageTime=new Date().getTime();this.fps=0;this.nbShow=0;jsRenderers[rendererId]=this;}
JavaScriptRenderer.prototype.getImageURL=function(){var urlTail="";if(this.localTimeStamp<5){urlTail="&nonBlockingRequest";}
return this.baseURL+"?sid="+this.sessionId+"&vid="+this.viewId+"&change="+(this.nbStart)+"-"+(this.localTimeStamp++)+urlTail;}
JavaScriptRenderer.prototype.bindToElementId=function(elementId){document.getElementById(elementId).appendChild(this.view);}
JavaScriptRenderer.prototype.unbindToElementId=function(elementId){document.getElementById(elementId).removeChild(this.view);}
JavaScriptRenderer.prototype.bindToElement=function(element){element.appendChild(this.view);}
JavaScriptRenderer.prototype.unbindToElement=function(element){element.removeChild(this.view);}
JavaScriptRenderer.prototype.init=function(sessionId,viewId){this.sessionId=sessionId;this.viewId=viewId;}
JavaScriptRenderer.prototype.start=function(){if(navigator.appName.indexOf("Microsoft")==-1){this.view.setAttribute("onmousedown","mouseInteraction('"+this.view.id+"','"+this.sessionId+"','"+this.viewId+"','down',event)");this.view.setAttribute("onmouseup","mouseInteraction('"+this.view.id+"','"+this.sessionId+"','"+this.viewId+"','up',event)");this.view.setAttribute("onmousemove","mouseInteraction('"+this.view.id+"','"+this.sessionId+"','"+this.viewId+"','move',event)");this.view.setAttribute("onclick","mouseInteraction('"+this.view.id+"','"+this.sessionId+"','"+this.viewId+"','click',event)");this.view.setAttribute("oncontextmenu","consumeEvent(event)");this.view.setAttribute("ontouchstart","touchInteraction('"+this.view.id+"','"+this.sessionId+"','"+this.viewId+"','down',event);");this.view.setAttribute("ontouchmove","touchInteraction('"+this.view.id+"','"+this.sessionId+"','"+this.viewId+"','move',event);");}else{__nb_global_ie_methods__++;var currentRootMethodName="__ie_mouseinteract_"+window.__nb_global_ie_methods__;eval(currentRootMethodName+" = function (action,event){mouseInteraction('"+this.view.id+"','"+this.sessionId+"','"+this.viewId+"',action,event);}");var currentRootMethod=eval(currentRootMethodName);this.view.attachEvent('onmousedown',function(event){currentRootMethod('down',event);});this.view.attachEvent('onmouseup',function(event){currentRootMethod('up',event);});this.view.attachEvent('onmousemove',function(event){currentRootMethod('move',event);});this.view.attachEvent('onclick',function(event){currentRootMethod('click',event);});this.view.attachEvent('oncontextmenu',function(event){consumeEvent(event);});}
this.localTimeStamp=0;this.nbError=0;this.nbShow=0;this.nbStart++;this.loadImage();}
JavaScriptRenderer.prototype.loadImage=function(){this.bgImage.src=this.getImageURL();this.bgImage.onload=this.show;this.bgImage.onabort=function(e){this.otherThis.nbError++;if(this.otherThis.nbError<10){setTimeout('jsRenderers["'+this.otherThis.view.id+'"].loadImage()',500);}}
this.bgImage.onerror=function(e){this.otherThis.nbError++;if(this.otherThis.nbError<10){setTimeout('jsRenderers["'+this.otherThis.view.id+'"].loadImage()',500);}}}
JavaScriptRenderer.prototype.show=function(){try{this.realWidth=this.width;this.realHeight=this.height;if(navigator.appName.indexOf("Microsoft")!=-1){var previousWidth=document.images[this.viewId].width;var previousHeigth=document.images[this.viewId].height;document.images[this.viewId].src=this.src;document.images[this.viewId].width=previousWidth;document.images[this.viewId].height=previousHeigth;}else{document.images[this.viewId].src=this.src;}
this.otherThis.nbShow++;if(this.otherThis.nbShow%20==0){var newTime=new Date().getTime();this.otherThis.fps=Math.floor(20000/(newTime-this.otherThis.lastImageTime));this.otherThis.lastImageTime=newTime;}
this.otherThis.nbError=0
this.otherThis.loadImage();}catch(e){}}
JavaScriptRenderer.prototype.setSize=function(width,height){this.view.width=width;this.view.height=height;}
JavaScriptRenderer.prototype.getPageX=function(){var location=0;var node=this.view;while(node){location+=node.offsetLeft;node=node.offsetParent;}
return location;}
JavaScriptRenderer.prototype.getPageY=function(){var location=0;var node=this.view;while(node){location+=node.offsetTop;node=node.offsetParent;}
return location;}
function HttpAppletRenderer(rendererId,coreServiceURL){this.baseURL=coreServiceURL;this.sessionId="";this.viewId="";this.fps=0;this.view=document.createElement("applet");this.view.id=rendererId;this.view.code="org.paraview.web.HttpParaWebApplet";this.view.name=rendererId;this.view.archive=coreServiceURL+"/java/ParaWeb.jar";this.view.width="100";this.view.height="100";this.streamUrlParam=document.createElement("param");this.streamUrlParam.name="DOWNLOAD_STREAM_URL";this.streamUrlParam.value=coreServiceURL+"/DownStream";this.upstreamUrlParam=document.createElement("param");this.upstreamUrlParam.name="UP_STREAM_URL";this.upstreamUrlParam.value=coreServiceURL+"/EventStream";this.viewIdParam=document.createElement("param");this.viewIdParam.name="VIEW_ID";this.viewIdParam.value="...viewId...";this.sessionIdParam=document.createElement("param");this.sessionIdParam.name="SESSION_ID";this.sessionIdParam.value="...sessionId...";this.rendererIdParam=document.createElement("param");this.rendererIdParam.name="RENDER_ID";this.rendererIdParam.value=rendererId;this.view.appendChild(this.sessionIdParam);this.view.appendChild(this.streamUrlParam);this.view.appendChild(this.upstreamUrlParam);this.view.appendChild(this.viewIdParam);this.view.appendChild(this.rendererIdParam);if(navigator.appName.indexOf("Microsoft")==-1){this.noPluginInfo=document.createElement("comment");this.noPluginInfo.innerHTML="<center><b><br/>Java is not supported in your browser. <br/> It can be downloaded from <a href='http://java.sun.com/webapps/getjava/BrowserRedirect?host=java.com'>here</a></b></center>";this.view.appendChild(this.noPluginInfo);}
javaRenderers[rendererId]=this;}
HttpAppletRenderer.prototype.bindToElementId=function(elementId){document.getElementById(elementId).appendChild(this.view);}
HttpAppletRenderer.prototype.unbindToElementId=function(elementId){document.getElementById(elementId).removeChild(this.view);}
HttpAppletRenderer.prototype.bindToElement=function(element){element.appendChild(this.view);}
HttpAppletRenderer.prototype.unbindToElement=function(element){element.removeChild(this.view);}
HttpAppletRenderer.prototype.init=function(sessionId,viewId){this.sessionId=sessionId;this.viewId=viewId;this.viewIdParam.value=viewId;this.sessionIdParam.value=sessionId;}
HttpAppletRenderer.prototype.start=function(){paraviewObjects[this.sessionId].sendEvent("Render","");}
HttpAppletRenderer.prototype.setSize=function(width,height){try{this.view.width=width;this.view.height=height;}catch(e){this.unbind(this.containerId);this.view.width=width;this.view.height=height;this.bind(this.containerId);}}
HttpAppletRenderer.prototype.useBrowserConnection=function(useJsonRPC){eval("document."+this.view.name+".setBrowserEventCall("+useJsonRPC+")");}
function JMSAppletRenderer(rendererId,coreServiceURL){var serviceURL=coreServiceURL;this.sessionId="";this.jmsPort="61616";this.viewId="";this.fps=0;this.view=document.createElement("applet");this.view.id=rendererId;this.view.code="org.paraview.web.JmsParaWebApplet";this.view.archive=serviceURL+"/java/JParaWebApplet.jar,"+serviceURL+"/java/activemq-all-5.3.0.jar";this.view.width="100";this.view.height="100";this.urlParam=document.createElement("param");this.urlParam.name="URL";this.urlParam.value="...url...";this.viewIdParam=document.createElement("param");this.viewIdParam.name="VIEW_ID";this.viewIdParam.value="...viewId...";this.sessionIdParam=document.createElement("param");this.sessionIdParam.name="SESSION_ID";this.sessionIdParam.value="...sessionId...";this.view.appendChild(this.urlParam);this.view.appendChild(this.viewIdParam);this.view.appendChild(this.sessionIdParam);if(navigator.appName.indexOf("Microsoft")==-1){this.noPluginInfo=document.createElement("comment");this.noPluginInfo.innerHTML="<center><b><br/>Java is not supported in your browser. <br/> It can be downloaded from <a href='http://java.com/en/download/linux_manual.jsp?host=java.com'>here</a></b></center>";this.view.appendChild(this.noPluginInfo);}else{this.view.alt="<center><b><br/>Java is not supported in your browser. <br/> It can be downloaded from <a href='http://java.sun.com/webapps/getjava/BrowserRedirect?host=java.com'>here</a></b></center>";}}
JMSAppletRenderer.prototype.getURL=function(){return"tcp://"+location.hostname+":"+this.jmsPort;}
JMSAppletRenderer.prototype.bindToElementId=function(elementId){document.getElementById(elementId).appendChild(this.view);}
JMSAppletRenderer.prototype.unbindToElementId=function(elementId){document.getElementById(elementId).removeChild(this.view);}
JMSAppletRenderer.prototype.bindToElement=function(element){element.appendChild(this.view);}
JMSAppletRenderer.prototype.unbindToElement=function(element){element.removeChild(this.view);}
JMSAppletRenderer.prototype.init=function(sessionId,viewId){this.sessionId=sessionId;this.viewId=viewId;this.urlParam.value=this.getURL();this.viewIdParam.value=viewId;this.sessionIdParam.value=sessionId;}
JMSAppletRenderer.prototype.start=function(){}
JMSAppletRenderer.prototype.setSize=function(width,height){this.view.width=width;this.view.height=height;}
function FlashRenderer(rendererId,coreServiceURL){this.serviceURL=coreServiceURL;this.sessionId="";this.viewId="";this.fps=0;this.vars="";this.data=this.serviceURL+"/resources/RenderWindow.swf";this.id=rendererId;this.width="100";this.height="100";flashRenderers[rendererId]=this;}
FlashRenderer.prototype.buildHTML=function(){var htmlTxt="";this.vars="swfid="+this.id+"&sessionId="+this.sessionId+"&viewId="+this.viewId;if(navigator.appName.indexOf("Microsoft")==-1){htmlTxt+="<embed id='"+this.id+"' name='"+this.id+"' FlashVars='"+this.vars+"' src='"+this.data+"' width='"+this.width+"' height='"+this.height+"'/>";}else{htmlTxt+="<object id='"+this.id+"' name='"+this.id+"' classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' width='"+this.width+"' height='"+this.height+"'>";htmlTxt+="<param name='movie' value='"+this.data+"'/>";htmlTxt+="<param name='FlashVars' value='"+this.vars+"'/>";htmlTxt+="</object>";}
return htmlTxt;}
FlashRenderer.prototype.bindToElementId=function(elementId){document.getElementById(elementId).innerHTML=this.buildHTML();this.view=document.getElementById(this.id);}
FlashRenderer.prototype.unbindToElementId=function(elementId){document.getElementById(elementId).removeChild(this.view);}
FlashRenderer.prototype.bindToElement=function(element){element.innerHTML(buildHTML());this.view=document.getElementById(this.id);}
FlashRenderer.prototype.unbindToElement=function(element){element.removeChild(this.view);}
FlashRenderer.prototype.init=function(sessionId,viewId){this.sessionId=sessionId;this.viewId=viewId;}
FlashRenderer.prototype.start=function(){}
FlashRenderer.prototype.showInfo=function(visible){if(getFlashApplet(this.view.id)){getFlashApplet(this.view.id).showInfo(visible);}else{alert("Could not find "+this.view.id+" flash applet. Must be binded and loaded");}}
FlashRenderer.prototype.setSize=function(width,height){if(this.view){this.view.width=width;this.view.height=height;}else{this.width=width;this.height=height;}}