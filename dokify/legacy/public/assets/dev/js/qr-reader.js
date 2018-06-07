define(function(){var a=0;function b(){var c=this,d=new Instascan.Scanner({video:document.getElementById("video-scanner")});d.addListener("scan",function(e){$.ajax({url:e,type:"post",success:function(g){switch(g.status){case 1:c.checkinAccepted();
break;case 22:c.checkoutConfirmed();break;default:c.checkinRejected();}},error:function(){c.checkinRejected();}});});try{Instascan.Camera.getCameras().then(function(e){if(e.length>0){d.start(e[0]);
}else{c.cameraError();}});}catch(f){c.cameraError();}c.listenForInactivity();}b.prototype.restartInactivityTime=function(){a=0;};b.prototype.listenForInactivity=function(){var c=this;
c.restartInactivityTime();$(document).ready(function(){var d=setInterval(c.inactivityTimerIncrement,60000);$(this).mousemove(function(f){c.restartInactivityTime();
});$(this).keypress(function(f){c.restartInactivityTime();});});};b.prototype.inactivityTimerIncrement=function(){a=a+1;if(a>5){window.location.reload();
}};b.prototype.cameraError=function(){$("#no-cam").show();};b.prototype.checkinAccepted=function(){$("#checkin-accepted").show().delay(2000).fadeOut();
this.restartInactivityTime();};b.prototype.checkinRejected=function(){$("#checkin-rejected").show().delay(2000).fadeOut();this.restartInactivityTime();
};b.prototype.checkoutConfirmed=function(){$("#checkout-confirmed").show().delay(2000).fadeOut();this.restartInactivityTime();};b.init=function(){new b(this);
};return b;});