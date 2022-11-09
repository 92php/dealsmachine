	var down,send;
	var objread,objsend,objkey;
    var readtimer=null,sndtimer=null;
   
	function getFix()
	{
		url=document.URL;
		n1=url.lastIndexOf('/');n2=url.lastIndexOf('//');
		if(n1>n2+1)
			url=url.substr(0,n1+1);
		return url;
	}
	
	
	function initTransfer()
	{
		down=document.createElement("script");
		down.id="oDownload";down.language="javascript";
		document.body.appendChild(down);

		send=document.createElement("script");
		send.id="oUpload";send.language="javascript";
		document.body.appendChild(send);		
	}
	
	function initTransfer2()
	{
		objread=new	MSGOBJ();objsend=new MSGOBJ();objkey=new MSGOBJ();	
	}
	
	function AddTimeStamp(url)
	{
		var ni=url.indexOf("?");
		if(ni==-1)
			return url;
		else
		{
			url=url.replace("?","?ran="+(new Date()).getTime()+"&");
			return url;
		}
	}
	
	function ydSendMsg(url)
	{
		down.src=url;
	}
	
	function ydSendMsg2(url)
	{
		url="iswitch.aspx?a=2&vid="+vid+"&toid="+toid+"&msg="+escape(url);
		if(MM.checked)
			url+="&mm=true";
		send.src=url;
	}
	
	function ydSend(url)
	{
		send.src=url;
	}
	
	function ydClearSend(url)
	{
		send.src="";
		send.src=url;
	}
		
	function syncydSendMsg(url)
	{
		ydimg=document.createElement("img");
		ydimg.src=AddTimeStamp(url);
		document.body.appendChild(ydimg);
		domainSynSendMsg(url);
	}
	
	function domainSynSendMsg(url)
	{
		var bResult=false;
		url=getFix()+AddTimeStamp(url);
		var Http = new ActiveXObject("Microsoft.XMLHTTP");
		try
		{
			Http.open("GET",url,false);
			Http.send();
			bResult=true;
		}
		catch(e)
		{
		}
		Http=null;
		return bResult;
	}
	
	function playSound(type)
	{
		soundeffect.src="";
		var src="";
		if(type==0)//msg
			src="sound/msg.wav";
		else if(type==1)//ondoor
			src="sound/doorbell.wav";		
		else if(type==2)//in site
			src="sound/getin.wav";
		else if(type==3)//left
			src="sound/offline.wav";
		else if(type==4)//left
			src="sound/offtalk.wav";
		soundeffect.src=src;
		lastsoundtime=new Date();		
	}
		
	function MSGOBJ()
	{
		this.object=null;
		this.time=null;
		
		return this;
	}
	
	function msgread2(url,bAsync,msgobj,pfunction,pvalue)
	{
		obj=new ActiveXObject("Microsoft.XMLHTTP");
		if(obj)
		{
			if(bAsync)
			{
				msgobj.object=obj;
				msgobj.object.onreadystatechange=function()
				{
					if(msgobj.object.readyState==4)
					{
						if (msgobj.object.status==200&&pfunction!=null)
						{
							var sResult=msgobj.object.responseText;
							if(sResult!=null&&sResult!="")
								pfunction(sResult);
						}
						msgobj.object=null;
					}
				}
				msgobj.object.open("POST",url,bAsync);
				msgobj.object.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
				
				msgobj.object.send(pvalue);
				msgobj.time=new Date();
				return true;
			}
			else
			{
				obj.open("GET",url,bAsync);
				obj.send();
				if(pfunction!=null)
					pfunction(obj.responseText);
				return true;				
			}
	    }
	    else
			return false;
	}
	
	function msgread(url,bAsync,msgobj,pfunction)
	{
		obj=new ActiveXObject("Microsoft.XMLHTTP")
		if(obj)
		{
			if(bAsync)
			{
				msgobj.object=obj;
				msgobj.object.onreadystatechange=function()
				{
					if(msgobj.object.readyState==4)
					{
						if (msgobj.object.status==200&&pfunction!=null)
						{
							var sResult=msgobj.object.responseText;
							if(sResult!=null&&sResult!="")
								pfunction(sResult);
						}
						msgobj.object=null;
					}
				}
				msgobj.object.open("GET",url,bAsync);
				msgobj.object.send();
				msgobj.time=new Date();
				return true;
			}
			else
			{
				obj.open("GET",url,bAsync);
				obj.send();
				if(pfunction!=null)
					pfunction(obj.responseText);
				return true;				
			}
	    }
	    else
			return false;
	}
	
	function formatdate()
	{
		var d=new Date();
		var s=tw(d.getMonth()+1)+"-"+tw(d.getDate())+" "+tw(d.getHours())+":"+tw(d.getMinutes());
		return s;
	}

	function yd_DownloadFile(fname)
    {
		fname='iswitch.aspx?a=-6&file='+escape(fname);
		window.open(fname);
		return false;
    }
