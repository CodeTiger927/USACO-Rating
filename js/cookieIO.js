function writeCookie(name,value,days) {
    var date,expires;
    if(days) {
        date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        expires = "; expires=" + date.toGMTString();
    }else{
        expires = "";
    }
    document.cookie = name + "=" + value + expires + "; path=/";
}

function readCookie(name) {
    var c,ca,nameEQ = name + "=";
    ca = document.cookie.split(';');
    for(var i = 0;i < ca.length;++i) {
        c = ca[i];
        while(c.charAt(0)==' ') {
            c = c.substring(1,c.length);
        }
        if(c.indexOf(nameEQ) == 0) {
       		return c.substring(nameEQ.length,c.length);
        }
    }
    return '';
}

// To help users transition between the two storage methods
function copyOverCookies() {
    var cookies = document.cookie.split(";");
    for(var i = 0;i < cookies.length;i++) {
        if(cookies[i].split("=")[1]) writeRecord(cookies[i].split("=")[0],cookies[i].split("=")[1]);
        writeCookie(cookies[i].split("=")[0],"",-1);
    }
}