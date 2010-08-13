$(function(){

$(document).pngFix();

$('a[href^=#][href!=#]').click(function(e){
    $('html,body').animate({ 'scrollTop': $($(this).attr('href')).offset().top });
    window.location.hash = 'header'; // Yuck
    e.preventDefault();
});

});
