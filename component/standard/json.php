<?php
    /**
     * Library JSON
     * JSON Processor
     **/
    
    if(!defined('FRAMEWORK')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
        exit;
    }
    
    function IsJson($_Json) {
        return json_decode($_Json) && json_last_error() === JSON_ERROR_NONE;
    }
    
    function JsonEncode($_Array, $_Flags = 0) {
        return json_encode($_Array, (JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) & ~$_Flags);
    }

    function JsonDecode($_Json) {
        return json_decode($_Json, true);
    }
?>