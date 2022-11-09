// JavaScript Document highlignt the words
 function MarkHighLight(obj,hlWords,cssClass){
        hlWords=AnalyzeHighLightWords(hlWords);
        if(obj==null || hlWords.length==0)
            return;
        if(cssClass==null)
            cssClass="highlight";
        MarkHighLightCore(obj,hlWords);

        function MarkHighLightCore(obj,keyWords){
            var re=new RegExp(keyWords, "i"); 
            for(var i=0; i<obj.childNodes.length; i++){
                var childObj=obj.childNodes[i];
                if(childObj.nodeType==3){
                    if(childObj.data.search(re)==-1)continue; 
                    var reResult=new RegExp("("+keyWords+")", "gi"); 
                    var objResult=document.createElement("span");
                    objResult.innerHTML=childObj.data.replace(reResult,"<font class='"+cssClass+"'>$1</font>");                     
                    obj.replaceChild(objResult,childObj);      
                  
                }else if(childObj.nodeType==1){
                    MarkHighLightCore(childObj,keyWords);
                }
            }
        }        

        function AnalyzeHighLightWords(hlWords)
        {
            if(hlWords==null) return "";
            hlWords=hlWords.replace(/\s+/g,"|").replace(/\|+/g,"|");            
            hlWords=hlWords.replace(/(^\|*)|(\|*$)/g, "");
            if(hlWords.length==0) return "";
            var wordsArr=hlWords.split("|"); 
            if(wordsArr.length>1){
                var resultArr=BubbleSort(wordsArr);
                var result="";
                for(var i=0;i<resultArr.length;i++){
                    result=result+"|"+resultArr[i];
                }                
                return result.replace(/(^\|*)|(\|*$)/g, "");

            }else{
                return hlWords;
            } 
        }    
    }
        function BubbleSort(arr){        
            var temp, exchange;    
            for(var i=0;i<arr.length;i++){            
                exchange=false;                
                for(var j=arr.length-2;j>=i;j--){                
                    if((arr[j+1].length)>(arr[j]).length){                    
                        temp=arr[j+1]; arr[j+1]=arr[j]; arr[j]=temp;
                        exchange=true;
                    }
                }                
                if(!exchange)break;
            }
            return arr;            
        }
function MarkX(obj,keyWords,keyword){
    var arr = new Array();
    for(var start=0; start<keyWords.length;start++){
        for(var length=1; start+length<=keyWords.length;length++){
            arr.push(keyWords.substr(start,length))
        }
    }
    var s=arr.join("|");
    MarkHighLight(obj, s,keyword);
}
    function MarkHighLightDemo(){
        var txtObj=document.getElementById("txtInput");
        var divObj=document.getElementById("ArticleWrapper");
        MarkX(divObj,txtObj.value,'keyword');

    }