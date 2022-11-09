
<%
url=trim(request("url"))

if url="" then
%>

    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
    <html>
    <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>no title</title>
    </head>
    
    <body>
    <form action="">
    url<input type="text" name="url" value="" size="100"/>
    </form>
    </body>
    </html>
<%else %>
	<%@LANGUAGE="VBSCRIPT" CODEPAGE="65001"%>
     
    <%Session.CodePage="65001"%>
    
    <%

    Function BytesToBstr(body,Cset) 
        dim objstream 
        set objstream = Server.CreateObject("adodb.stream") 
        objstream.Type = 1 
        objstream.Mode =3 
        objstream.Open 
        objstream.Write body 
        objstream.Position = 0 
        objstream.Type = 2 
        objstream.Charset = Cset 
        BytesToBstr = objstream.ReadText  
        objstream.Close 
        set objstream = nothing 
    End Function 
    
    function getHTTPPage(url) 
        dim Http 
        set Http=Server.CreateObject("Microsoft.XMLHTTP")
        Http.open "GET",url,false 
        Http.send() 
        if Http.readystate<>4 then  
        exit function 
        end if 
        
        getHTTPPage=BytesToBstr(Http.responseBody,"utf-8") 
    
        set http=nothing 
        if err.number<>0 then err.Clear  
    end function
    
    
    
    
    Set objStream = Server.CreateObject("ADODB.Stream") 
    
    'url="http://"&request.ServerVariables("HTTP_HOST")&"/product_show.asp?id="&id
    content=getHTTPPage(url)
    'content=toUTF8(content)
    
    response.Write(content)
    response.End()
    
    With objStream 
      .Open 
      .Charset = "utf-8" 
      .Position = objStream.Size 
      .WriteText=content 
      .SaveToFile server.mappath("index.htm"),2  
      .Close 
    End With 
    
    
    


Set objStream = Nothing	

%>
<%end if%>