//On page load set the font size
$(document).ready(function () {
    initFontSize();
});

function initFontSize()
{
    // Setup variable for the cookiename, get the correct cookie
    var receivedCookieData = $.cookie("fontsize");

    //Update the visibility safely
    var opts;
    if(opts = document.getElementById('resizeoptions')){
        opts.style.visibility = 'visible';
    }
    //Update the input size
    UpdateInputSize(receivedCookieData);
}

// Get cookie function - Return the value of the cookie
function getCookie(name)
{
    var dc = document.cookie;
    var prefix = name + "=";
    var begin = dc.indexOf("; " + prefix);
    if (begin == -1) {
        begin = dc.indexOf(prefix);
        if (begin != 0) return null;
    } else {
        begin += 2;
    }
    var end = document.cookie.indexOf(";", begin);
    if (end == -1) {
        end = dc.length;
    }
    return unescape(dc.substring(begin + prefix.length, end));
}

function resize(invar)
{
    var newsize = '80%';
    switch (invar) {
        case 1:
            newsize = '80%';
            break;
        case 2:
            newsize = '100%';
            break;
        case 3:
            newsize = '120%';
            break;
    }
    UpdateInputSize(newsize);
    $.cookie("fontsize", newsize, { expires: 7, path: '/' });
    return false;
}


function UpdateInputSize(fontsize) {

    switch (fontsize) {
        case null:
            document['smallFontImage'].src = "/assets/runnymede/images/accessibility/text-small-current.png";
            document['mediumFontImage'].src = "/assets/runnymede/images/accessibility/text-medium.png";
            document['largeFontImage'].src =  "/assets/runnymede/images/accessibility/text-largest.png";
            break;

        case '80%':
            //Update the icon
            document['smallFontImage'].src = "/assets/runnymede/images/accessibility/text-small-current.png";
            document['mediumFontImage'].src = "/assets/runnymede/images/accessibility/text-medium.png";
            document['largeFontImage'].src =  "/assets/runnymede/images/accessibility/text-largest.png";

            //Update the font size for the text input
            $(".toplevelsearch input").css("fontSize", '150%');
            break;

        case '100%':
            document['smallFontImage'].src = "/assets/runnymede/images/accessibility/text-small.png";
            document['mediumFontImage'].src = "/assets/runnymede/images/accessibility/text-medium-current.png";
            document['largeFontImage'].src =  "/assets/runnymede/images/accessibility/text-largest.png";

            //Update the font size for the text input
            $(".toplevelsearch input").css("fontSize", '125%');
            break;

        case '120%':
            document['smallFontImage'].src = "/assets/runnymede/images/accessibility/text-small.png";
            document['mediumFontImage'].src = "/assets/runnymede/images/accessibility/text-medium.png";
            document['largeFontImage'].src =  "/assets/runnymede/images/accessibility/text-largest-current.png";

            //Update the font size for the text input
            $(".toplevelsearch input").css("fontSize", '110%');
            break;
    }
    $('body').attr('style', 'font-size:' + fontsize + ';');
}
