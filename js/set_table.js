// JavaScript Document
function setTab(m,n){
 var tli=document.getElementById("menu"+m).getElementsByTagName("li");
 var mli=document.getElementById("main"+m).getElementsByTagName("ul");
 for(i=0;i<tli.length;i++){
  //tli[i].className=i==n?str1[n]:str2[n];
  if (i == n ) {
	  tli[i].className = 'pr_'+(n+1)+'_2';
	}else{
	  tli[i].className = 'pr_'+(i+1)+'_1';
	}
  mli[i].style.display=i==n?"block":"none";
 }
};
