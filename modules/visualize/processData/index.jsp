<%@ page language="java" contentType="text/html; charset=UTF-8"
pageEncoding="UTF-8"%>

<%@ page import="java.io.*"%>
<%@ page import="java.util.*"%>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<%
Runtime r=Runtime.getRuntime();
Process p =null;
String cmd=request.getParameter("pvbatch")+" --use-offscreen-rendering "+request.getRealPath("/")+"processData.py "+request.getParameter("file")+" "+request.getRealPath("/");
try{
p=r.exec(cmd);
InputStreamReader isr=new InputStreamReader(p.getInputStream());
BufferedReader br=new BufferedReader(isr);
String line=null;
while((line=br.readLine())!=null){
out.println(line);
}
p.waitFor();
}
catch(Exception e){
	out.println("PROBLEME !!!");
	out.println(e);
	}

%>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Script-Type" content="text/ecmascript" />
<meta http-equiv="Content-Style-Type" content="text/css" />
</head>
<body>

</body>
</html>