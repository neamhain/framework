/**
 * Framework
 * JavaScript extension
 **/

'use strict';

window.DebugMode = false;

window.Controller = function(Method, Body) {
    var Deferred = new $.Deferred;
    
    Body = Body ? Body : {};
    Body.CsrfToken = $('meta[name=csrf-token]').attr('content');

    $.post(Method, Body).then(function(Response) {
        Deferred.resolve(Response);
        
        if(DebugMode) {
            Logging('Contoller request "' + Method + '" has succeed.');
        }
    }, function() {
        Deferred.resolve(null);
        
        if(DebugMode) {
            Logging('Contoller request "' + Method + '" has failed.', true);
        }
    });

    return Deferred;
};

window.Logging = function(Message, IsNegative) {
    if(DebugMode) {
        return;
    }
    
    console.log('%c<Framework>', 'font-weight: bold; color: ' + (IsNegative ? 'red' : 'green'), '\t', Message);
}