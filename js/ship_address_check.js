// JavaScript Document

var flag_Name,flag_Address, flag_City ,flag_Country, flag_State, flag_ZIP, flag_Phone;
flag_Name=0;
flag_Address=0;
flag_City =0;
flag_Country=0;
flag_State=0;
flag_ZIP=0;
flag_email=0;
flag_Phone=0;

function checkinput(id){
	if(i$(id).value.length==0){
		//alert(o.nextSibling.type);
		i$(id+"_img_tips").innerHTML="<img src='images/ico_F.gif' alt='' >";
		i$(id+"_tips").innerHTML=id+" information is required.";
		i$(id+"_tips").display="block";
		//eval("flag_"+id)=0;
		//document.getElementById().nextSibling.style.display=""
		return false;
	}
	else
	{
		i$(id+"_img_tips").innerHTML="<img src='images/ico_T.gif' alt='' >";
		i$(id+"_tips").innerHTML="";
		i$(id+"_tips").display="none";
		//eval("flag_"+id)=1;
		return true;
	}
}
function checkEmail(){

	if (!checkinput("email"))
		return false;
	var id="email";
	var p=/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/;
	if(!p.test(i$("email").value)){
		//alert(o.nextSibling.type);
		i$(id+"_img_tips").innerHTML="<img src='images/ico_F.gif' alt='' >";
		i$(id+"_tips").innerHTML="Email address is incorrect";
		i$(id+"_tips").display="block";
		//eval("flag_"+id)=0;
		//document.getElementById().nextSibling.style.display=""
		return false;
	}
	else
	{
		i$(id+"_img_tips").innerHTML="<img src='images/ico_T.gif' alt='' >";
		i$(id+"_tips").innerHTML="";
		i$(id+"_tips").display="none";
		//eval("flag_"+id)=1;
		return true;
	}	
}
function checkcountry(){


	//document.getElementById().selectedIndex
	var id="country_code";
	//var p=/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/;
	if(i$(id).selectedIndex==0){
		//alert(o.nextSibling.type);
		i$(id+"_img_tips").innerHTML="<img src='images/ico_F.gif' alt='' >";
		i$(id+"_tips").innerHTML="Please select the country you want to ship to";
		i$(id+"_tips").display="block";
		//eval("flag_"+id)=0;
		//document.getElementById().nextSibling.style.display=""
		return false;
	}
	else
	{
		i$(id+"_img_tips").innerHTML="<img src='images/ico_T.gif' alt='' >";
		i$(id+"_tips").innerHTML="";
		i$(id+"_tips").display="none";
		//eval("flag_"+id)=1;
		return true;
	}	
}
function checkAll(){
		
	//alert("flag_Name"+flag_Name+",flag_Address"+flag_Address+",flag_City"+flag_City+",flag_Country"+flag_Country+",flag_State"+flag_State+",flag_ZIP"+flag_ZIP+",flag_Phone"+flag_Phone+",flag_email"+flag_email)	
	var msg ="";
	if (flag_Name==1&&flag_Address==1&&flag_City ==1&&flag_Country==1&&flag_State==1&&flag_ZIP==1&&flag_Phone==1&&flag_email==1)
	{	return true;
		
	}
	else
	{
		
		if (checkinput("Name")){
			
			flag_Name=1;
		}
		else{
			msg = msg + "* Consignee name is required \n";
			flag_Name=0;
		}
		if (checkinput("Address"))
			flag_Address=1;
		else
		{
			msg = msg + "* Receipt Address is required \n";
			flag_Address=0;
		}
		if (checkinput("City"))
			flag_City=1;
		else{
			msg = msg + "* City information is required \n";
			flag_City=0;
		}
		
		
		if (checkinput("State"))
			flag_State=1;
		else{
			msg = msg + "* State/Province/Region information is required \n";
			flag_State=0;
		}

		//alert(checkcountry());
		if (checkcountry())
			flag_Country=1;
		else{
			msg = msg + "* Please select the country you want to ship to\n";
			flag_Country=0;
		}

		if (checkinput("ZIP"))
			flag_ZIP=1;
		else{
			msg = msg + "* ZIP/postcode information is required \n";
			flag_ZIP=0;
		}
			
		if (checkEmail())
			flag_email=1;
		else{
			msg = msg + "* Email information is required \n";
			flag_email=0;
		}

		if (checkinput("Phone"))
			flag_Phone=1;
		else{
			msg = msg + "* Phone number information is required \n";
			flag_Phone=0;
		}
			
	if (flag_Name==1&&flag_Address==1&&flag_City ==1&&flag_Country==1&&flag_State==1&&flag_ZIP==1&&flag_Phone==1&&flag_email==1)
			return true;
	}
	
	alert(msg);
	return false;
	
}
