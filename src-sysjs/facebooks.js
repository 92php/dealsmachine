//277682888966553
window.fbAsyncInit = function() {
    FB.init({ appId: '174797055973289', status: true, cookie: true, oauth: true, xfbml: true });
};
(function() {
    var e = document.createElement('script');
    e.type = 'text/javascript';
    e.src = 'https://connect.facebook.net/en_US/all.js';
    e.async = true;
    document.getElementById('fb-root').appendChild(e);
} ());

function facebook_signup() {
  
       FB.login(function(response) { 
        if (response.authResponse) { 
            var fbuid = response.authResponse.userID;
            var fbtoken = response.authResponse.accessToken;
                FB.api({
                    method: 'fql.query',
                    query: 'select first_name,last_name,email from user where uid=' + fbuid
                },
                    function(rep){
                        var fname = rep[0].first_name;
                        var lname = rep[0].last_name;
                        var fbEmail = rep[0].email;
                        dinoLoginForFB(fbuid,fbtoken,fname,lname,fbEmail,0,1);
                    }
                );
            } else { 
                return;
            } 
        }, {scope:'email'}); 	

}


function dinoLoginForFB(fbuid,fbtoken,fname,lname,fbEmail,fstats,status){
    if(status==1)
    {
        var postData = "FTextUserEmail="+fbEmail+"&ebID="+fbuid+"&firstname="+fname+"&lastname="+lname+"&FTextUserPwd2="+parseInt(Math.random()*1000000);
        postData = postData + "&ebToken=" + fbtoken + "&ebStats=" + fstats + "&ebUid=" + fbuid + "&actions=facebookLogin";
        $.ajax({
            type:'post',
            url:'fb_eb_login.php',
            data:postData,
            error:function(XMLHttpRequest, textStatus, errorThrown){
                        //alert("AJAX Login Error."+textStatus+"-"+errorThrown,26,0,fbEmail);
                    },
            success:function(result){
                window.location.href = 'http://www.bestafford.com/' + js_cur_lang_url + 'm-users-a-order_list.htm';
            }
        });	
    }
}

