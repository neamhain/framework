<?php
    /**
     * Library HTTP
     * HTTP Processor
     **/
    
    if(!defined('FRAMEWORK')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
        exit;
    }
    
    function Location($_Destination) {
        header('Location: ' . $_Destination);
        exit;
    }

    function ContentType($_Type) {
        header('Content-Type: ' . $_Type . '; charset=utf-8');
    }
?>