<?php
    /**
     * Library String
     * String enhancer
     **/
    
    if(!defined('FRAMEWORK')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
        exit;
    }
    
    function Uppercase($_String) {
        return strtoupper($_String);
    }

    function Lowercase($_String) {
        return strtolower($_String);
    }

    function Camelize($_String) {
        return Classify('-' . $_String);
    }

    function Dasherize($_String, $_Underscore = false) {
        return preg_replace('/^(-|_)/', '', Lowercase(preg_replace('/([A-Z])/', ($_Underscore ? '_' : '-') . '$1', $_String)));
    }

    function Normalize($_String) {
        return ucfirst(Lowercase(preg_replace('/-|_/', ' ', $_String)));
    }

    function Classify($_String) {
        return preg_replace('/\s+/', '', ucwords(Lowercase(preg_replace('/-|_/', ' ', $_String))));
    }

    function Replace($_String, $_Dictionary) {
        $_Replaced = $_String;
        
        foreach($_Dictionary as $_Search => $_Replacement) {
            if(preg_match('/^\//', $_Search) && preg_match('/\/$/', $_Search)) {
                $_Replaced = preg_replace($_Search, $_Replacement, $_Replaced);
                
                continue;
            }
            
            $_Replaced = str_replace($_Search, $_Replacement, $_Replaced);
        }
        
        return $_Replaced;
    }
?>