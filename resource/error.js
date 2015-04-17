$(document).ready(function() {
    $('.moment[data-format]').each(function() {
        $(this).text(moment.unix($(this).attr('data-time') ? $(this).attr('data-time') : moment().unix()).format($(this).attr('data-format')));
    });
    
    window.WebFontConfig = {
        google: {
            families: [ 'Inconsolata:400,700:latin,latin-ext' ]
        }
    };
    
    (function() {
        var WebFont = document.createElement('script'), Script = document.getElementsByTagName('script').item(0);
        
        WebFont.src = '//ajax.googleapis.com/ajax/libs/webfont/1/webfont.js';
        WebFont.async = 'true';
        
        Script.parentNode.insertBefore(WebFont, Script);
    })();
});