$(document).ready(function() {

    $(".faqanswer").hide();
    $('.faqquestion').hover(over);
    $('.faqquestion').css({background: '#efebef url(http://www.salford.gov.uk/t/icon-plus.gif) 670px 9px no-repeat'});

    $(".faqquestion").click(function () {
        $(this).next().toggle(400);
        var currentback = ($(this).css("backgroundImage"));
        var foo = (currentback.indexOf("icon-plus") > 0) ? $(this).css({backgroundImage: 'url("http://www.salford.gov.uk/t/icon-minus.gif")'}) : $(this).css({backgroundImage: 'url("http://www.salford.gov.uk/t/icon-plus.gif")'});
    });

    $("<li class=\"styles\"><strong>Change text size:</strong></li>").appendTo("#accessOptions")
    $("<li><a href=\"#\" rel=\"default\" class=\"styleswitch\"><img src=\"http://www.salford.gov.uk/t/textsize-normal.gif\" alt=\"normal size\" /></a></li><li><a href=\"#\" rel=\"large\" class=\"styleswitch\"><img src=\"http://www.salford.gov.uk/t/textsize-large.gif\" alt=\"large size\" /></a></li><li><a href=\"#\" rel=\"extralarge\" class=\"styleswitch\"><img src=\"http://www.salford.gov.uk/t/textsize-extralarge.gif\" alt=\"extra large size\" /></a></li><li><a href=\"#\" rel=\"highvisibility\" class=\"styleswitch\"><img src=\"http://www.salford.gov.uk/t/textsize-highvisibility.gif\" alt=\"high visibility size and colour\" /></a></li>").appendTo("#accessOptions")
    $("<li><a href=\"http://www.salford.gov.uk/accessibility.htm#textsize\" class=\"small\">(what is this?)</a></li>").appendTo("#accessOptions")
    $("#accessOptions li.changetext").hide()

    var c = readCookie('style');
    if (c) switchStylestyle(c);
  
    $('a[href*=#]').each(function() {
        if ( ( filterPath(this.pathname)!='az.htm' ) && ( filterPath(location.pathname) == filterPath(this.pathname) && location.hostname == this.hostname && this.hash.replace(/#/,'') ) ) {
            var $targetId = $(this.hash), $targetAnchor = $('[name=' + this.hash.slice(1) +']');
            var $target = $targetId.length ? $targetId : $targetAnchor.length ? $targetAnchor : false;
            if ($target) {
                var targetOffset = $target.offset().top;
                $(this).click(function() {
                    $('html, body').animate({scrollTop: targetOffset}, 400);
                    return false;
                });
            }
        }
    });
    
    $("#qt").val("Search for...");

    $("#qt").click(function(){
        if($("#qt").val()=="Search for..."){
            $("#qt").val("");
        }
    });

    $("#qt").focus(function(){
        if($("#qt").val()=="Search for..."){
            $("#qt").val("");
        }
    });

    $("#qt").blur(function(){
        if($("#qt").val()==""){
            $("#qt").val("Search for...");
        }
    });
   
    $('.styleswitch').click(function(){
        switchStylestyle(this.getAttribute("rel"));
        return false;
    });

    var fileTypes = {
        doc: 'http://www.salford.gov.uk/t/icon-word.gif', dot: 'http://www.salford.gov.uk/t/icon-word.gif', csv: 'http://www.salford.gov.uk/t/icon-excel.gif', xls: 'http://www.salford.gov.uk/t/icon-excel.gif', rtf: 'http://www.salford.gov.uk/t/icon-text.gif', mp3: 'http://www.salford.gov.uk/t/icon-mp3.gif', pps: 'http://www.salford.gov.uk/t/icon-powerpoint.gif', ppt: 'http://www.salford.gov.uk/t/icon-powerpoint.gif', zip: 'http://www.salford.gov.uk/t/icon-zip.gif', pdf: 'http://www.salford.gov.uk/t/icon-acrobat.gif', png: 'http://www.salford.gov.uk/t/icon-image.gif', jpg: 'http://www.salford.gov.uk/t/icon-image.gif', gif: 'http://www.salford.gov.uk/t/icon-image.gif', bmp: 'http://www.salford.gov.uk/t/icon-image.gif'
    };

    $('a').each(function() {
    
        var $a = $(this);
        var href = $a.attr('href');
        var children = $a.children("img");
    
        if (href==null) { } else {
    
            if ((href.match(/^http/)) && (! href.match(document.domain)) && (! href.match('news.bbc.co.uk/go/rss')) && (! href.match('www.businesslink.gov.uk/bdotg')) && (! href.match('online.businesslink.gov.uk')) && (! href.match('www.thebusinessdesk.com')) && (! href.match('yourcounciljobs.co.uk/salford')) && (href.indexOf("salford.gov.uk")==-1)) {
                var image = 'external';
            } else {
                var hrefArray = href.split('.');
                var extension = hrefArray[hrefArray.length - 1];
                var image = fileTypes[extension];
            }
    
            if (image) { 
                    if(image=='external') {
                        chkalt = children.attr("alt");
                        if (chkalt) {
                            children.attr("alt", chkalt + " (External site)");
                        } else {
                            $a.append('<img src="http://www.salford.gov.uk/t/icon-external-link.gif" alt="External site" style="padding-left: 4px" \/>');
                        }
                    } else {
                        $a.css({background: 'transparent url("' + image + '") no-repeat right top'});
                        $a.css({paddingRight: '20px'});
                        $a.css({paddingBottom: '1px'});
                    }
            }
    
            if (href.indexOf("mailto:")==0) {
                $a.css({background: 'transparent url("http://www.salford.gov.uk/t/icon-email-small.gif") no-repeat left top'});
                $a.css({paddingLeft: '20px'});
            }
    
        }
    
    });

});

function filterPath(string) {
    return string
    .replace(/^\//,'')
    .replace(/(index|default).[a-zA-Z]{3,4}$/,'')
    .replace(/\/$/,'');
}

function switchStylestyle(styleName) {

    $('link[rel*=style][title]').each(function(i) {
        this.disabled = true;
        if (this.getAttribute('title') == styleName) this.disabled = false;
    });
    
    createCookie('style', styleName, 365);
    
    if(styleName == "highvisibility") {
        $("#directgovImage").attr({ src: "http://www.salford.gov.uk/t/directgov_logo_white-highvis.gif" });
    } else {
        $("#directgovImage").attr({ src: "http://www.salford.gov.uk/t/directgov_logo_white.gif" });
    }

}

function createCookie(name,value,days) {
    if(days){
        var date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        var expires = "; expires="+date.toGMTString();
    }
    else var expires = "";
    document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++){
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name,"",-1);
}

function over(event) {
    $(this).css("cursor", "pointer");
}