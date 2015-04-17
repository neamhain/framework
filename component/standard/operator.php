<?php
    /**
     * Library Operator
     * Functional version of logical operators
     **/
    
    if(!defined('FRAMEWORK')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
        exit;
    }
    
    function OperatorAnd(...$_Conditions) {
        foreach($_Conditions as $_Condition) {
            if(!$_Condition) {
                return false;
            }
        }
        
        return true;
    }

    function OperatorOr(...$_Conditions) {
        $_Result = false;
        
        foreach($_Conditions as $_Condition) {
            $_Result = $_Result || $_Condition;
        }
        
        return $_Result;
    }
?>