/**
* Add a new event to the window.onload
*
* @param function func	The new function to add to the loading
*/
function addLoadEvent(func)
{
	var oldonload = window.onload;
	if (typeof window.onload != 'function') {
		window.onload = func;
	}
	else {
		window.onload = function() {
			oldonload();
			func();
		}
	}
}

$(document).ready(function() {
  $('.image__caption, .figcaption, .imageCaption').each(function() {
        $(this).width($(this).find('img').width());
    });
    // Remove no-js class
    $('html').removeClass('no-js');
    // Functionality for the show/hide button widget and the row directly beneath it
    var buttonSpanText;
    $('.show-hide-services').parent().parent().parent().parent().parent().next().hide().addClass('row-expand');
    $('.show-hide-services').click(function(e) {
        e.preventDefault();
        buttonSpanText = $(this).find('span');
        if ($(buttonSpanText).hasClass('icon-down-big')) {
            $(buttonSpanText).removeClass('icon-down-big').addClass('icon-up-big');
        } else {
            $(buttonSpanText).removeClass('icon-up-big').addClass('icon-down-big');
        }
        $(this).parent().parent().parent().parent().parent().next().slideToggle(200);
    });
});
