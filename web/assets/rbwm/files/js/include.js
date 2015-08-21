var sWOGateway   = "gateway4.whoson.com"; 
var sWOGatewaySSL= "gateway4.whoson.com";
var sWODomain    = "www3.rbwm.gov.uk";
var sWOChatstart = "https://hosted4.whoson.com/chat/chatstart.htm";
var sWODepartment="";sWOSkillNames="";
var sWOLanguage="";
var sWOBackgroundURL="";
var sWOResponse="Y";
var sWOInvite="Y";
var sWOPreselect="";
var sWOUser="";
var sWOPage="";
var sWOCost=0;var sWORevenue=0;
var sWOName="";var sWOCompany="";var sWOEmail="";var sWOTelephone="";
var sWOProtocol=window.location.protocol;
var sWOImage=document.createElement('img');
var sWOChatElement;var sWOSession;var sWOUrl;
sWOImage.border=0;
(function () {
if(sWOUser==""){
	var dt=new Date();var sWOCookie=document.cookie.toString();
	if(sWOCookie.indexOf("whoson")==-1){sWOSession=parseInt(Math.random()*1000)+"-"+dt.getTime();document.cookie="whoson="+sWOSession+";expires=Thu, 31-Dec-2020 00:00:00 GMT; path=/";}
	sWOCookie=document.cookie.toString();
	if(sWOCookie.indexOf('whoson')==-1){sWOSession="";} else {
		var s=sWOCookie.indexOf("whoson=")+"whoson=".length;var e=sWOCookie.indexOf(";",s);
		if(e==-1)e=sWOCookie.length;sWOSession=sWOCookie.substring(s,e);}}
if(sWOProtocol=="https:")sWOGateway=sWOGatewaySSL;if(sWOUser!="")sWOSession=sWOUser;if(sWOProtocol=="file:")sWOProtocol="http:"; })();
function sWOStartChat(){window.open(sWOChatElement.href,"Chat","width=484,height=361");return false;}
function sWOImageLoaded(){if (sWOImage.width==1) {return;}sWOChatElement.href=sWOChatstart;sWOChatElement.target = "_blank";sWOChatElement.appendChild(sWOImage);sWOChatElement.onclick=sWOStartChat;}
function sWOTrackPage(){
	var bd=document.getElementsByTagName('body')[0];
	if(sWOPage=="")sWOPage=escape(window.location);
	sWOUrl=sWOProtocol+"//"+sWOGateway+"/stat.gif?u="+sWOSession+"&d="+sWODomain;
	if(sWODepartment.length>0)sWOUrl+="&t="+escape(sWODepartment);
	sWOUrl+="&p='"+sWOPage+"'&r='"+escape(document.referrer)+"'";
	if(sWOCost!=0)sWOUrl+="&c="+sWOCost;if(sWORevenue!=0)sWOUrl+="&v="+sWORevenue;
	if(sWOName!=""||sWOCompany!=""||sWOEmail!=""||sWOTelephone!="")sWOUrl+="&n="+encodeURIComponent(encodeURIComponent(sWOName))+"|"+sWOCompany+"|"+sWOEmail+"|"+sWOTelephone;
	if(sWOSkillNames!="")sWOUrl+="&sn="+escape(sWOSkillNames);
	if(sWOResponse==""){ if(document.layers){document.write("<layer name=\"WhosOn\" visibility=hide><img src=\""+sWOUrl+"\" height=1 width=1><\/layer>")} else {var d=document.createElement('div'); d.style.cssText = "position:absolute;display:none;"; 
		sWOImage.onload=sWOImageLoaded; sWOImage.src=sWOUrl; d.appendChild(sWOImage); bd.appendChild(d)}}
		else {
		sWOImage.onload=sWOImageLoaded;sWOChatElement=document.getElementById('whoson_chat_link');
		if(! sWOChatElement){ sWOChatElement=document.createElement('a');sWOChatElement.id='whoson_chat_link'; var insertBefore=null; 
		var scriptAr= document.body.getElementsByTagName('script'); for (var i=0; i < scriptAr.length; i++) { if(typeof(scriptAr[i].src) != 'undefined' && scriptAr[i].src.indexOf('include.js?domain='+sWODomain)>0){insertBefore = scriptAr[i]} } if (insertBefore != null) { insertBefore.parentNode.insertBefore(sWOChatElement, insertBefore) } else {bd.appendChild(sWOChatElement, bd)}}
        sWOUrl+="&response=g";sWOChatstart+="?domain="+sWODomain;if(sWOLanguage.length>0)sWOChatstart+="&lang="+sWOLanguage;
		if(sWOBackgroundURL!="")sWOChatstart+="&bg="+sWOBackgroundURL;if(sWODepartment.length>0)sWOChatstart+="&dept="+escape(sWODepartment);if(sWOPreselect.length>0)sWOChatstart+="&select="+sWOPreselect;
		if(sWOSkillNames!="")sWOChatstart+="&x-requestedskills="+escape(sWOSkillNames);
		sWOChatstart+='&timestamp='+(new Date()).getTime();
		sWOUrl+='&timestamp='+(new Date()).getTime();
		if(sWOSession!=''){sWOChatstart+='&session='+sWOSession;}
		sWOImage.src = sWOUrl;	
	}
	if(sWOInvite=="Y"){ var sWO={}; sWO.i = function()  {if (typeof(woAfterLoad) == 'function') {woAfterLoad(); woAfterLoad = function() {};}} 
        if (typeof(sWOInvite)=='undefined'||sWOInvite=='') {return;} var iog = document.createElement('script'); iog.type = 'text/javascript'; iog.async = true; iog.onload = sWO.i;
            iog.onreadystatechange = function () { if (this.readyState == 'loaded' || this.readyState == 'complete') sWO.i(); };
            iog.src=sWOUrl=sWOProtocol+"//"+sWOGateway+"/invite.js?domain="+sWODomain; var s = document.getElementsByTagName('body')[0]; s.appendChild(iog, s); 
	}
}
