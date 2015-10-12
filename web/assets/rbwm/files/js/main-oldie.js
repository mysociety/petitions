/* PLUGIN JS */

/*! matchMedia() polyfill - Test a CSS media type/query in JS. Authors & copyright (c) 2012: Scott Jehl, Paul Irish, Nicholas Zakas. Dual MIT/BSD license */
window.matchMedia=window.matchMedia||function(e,t){var n=e.documentElement,r=n.firstElementChild||n.firstChild,i=e.createElement("body"),s=e.createElement("div");s.id="mq-test-1";s.style.cssText="position:absolute;top:-100em";i.style.background="none";i.appendChild(s);var o=function(e){s.innerHTML='&shy;<style media="'+e+'"> #mq-test-1 { width: 42px; }</style>';n.insertBefore(i,r);bool=s.offsetWidth===42;n.removeChild(i);return{matches:bool,media:e}},u=function(){var t,r=n.body,i=false;s.style.cssText="position:absolute;font-size:1em;width:1em";if(!r){r=i=e.createElement("body");r.style.background="none"}r.appendChild(s);n.insertBefore(r,n.firstChild);if(i){n.removeChild(r)}else{r.removeChild(s)}t=a=parseFloat(s.offsetWidth);return t},a,f=o("(min-width: 0px)").matches;return function(t){if(f){return o(t)}else{var n=t.match(/\(min\-width[\s]*:[\s]*([\s]*[0-9\.]+)(px|em)[\s]*\)/)&&parseFloat(RegExp.$1)+(RegExp.$2||""),r=t.match(/\(max\-width[\s]*:[\s]*([\s]*[0-9\.]+)(px|em)[\s]*\)/)&&parseFloat(RegExp.$1)+(RegExp.$2||""),i=n===null,s=r===null,l=e.body.offsetWidth,c="em";if(!!n){n=parseFloat(n)*(n.indexOf(c)>-1?a||u():1)}if(!!r){r=parseFloat(r)*(r.indexOf(c)>-1?a||u():1)}bool=(!i||!s)&&(i||l>=n)&&(s||l<=r);return{matches:bool,media:t}}}}(document)

/* MAIN JS */

// Make icons show up in IE8 without requiring a roll-over
function fixIE8Icons() {
    var head = document.getElementsByTagName('head')[0],
        style = document.createElement('style');

    style.type = 'text/css';
    style.styleSheet.cssText = ':before{content:none !important}';
    head.appendChild(style);

    setTimeout(function() {
        head.removeChild(style);
    }, 0);
}

$(document).ready(function() {
    if($("#home-carousel").length != 0){    
    	$("#home-carousel").owlCarousel({
    		autoPlay: 7000,
    		singleItem: true,
    		pagination: false
    	});
	}

});

// On window load...
$(window).bind('load', function() {
    fixIE8Icons();
});