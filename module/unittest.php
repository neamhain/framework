<?php
    /**
     * UnitTest
     * Superlative class of UnitTest
     **/
    
    if(!defined('FRAMEWORK')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
        exit;
    }

    class UnitTest extends Module {
        public function Assert($_Alpha, $_Beta) {
            $GLOBALS['FRAMEWORK_UNIT_TEST_ELAPSED_TIME'] -= microtime(true);
            $GLOBALS['FRAMEWORK_UNIT_TEST_COUNT'] += 1;
            
            try {
                $this->AssertEquals($_Alpha, JsonEncode($_Beta));
                
                echo "\033[0;32m" . '[✓] FRAMEWORK UNIT TEST CASE ' . $GLOBALS['FRAMEWORK_UNIT_TEST_COUNT'] . ' SUCCESS';
                
                $_Result = true;
            } catch(Exception $_Exception) {
                echo "\033[0;31m" . '[ ] FRAMEWORK UNIT TEST CASE ' . $GLOBALS['FRAMEWORK_UNIT_TEST_COUNT'] . ' FAILURE';
                
                $_Result = false;
            }
            
            echo "\033[0m\n";
            
            $GLOBALS['FRAMEWORK_UNIT_TEST_ELAPSED_TIME'] += microtime(true);
            
            return $_Result;
        }
        
        public function OutputCatch($_Target, $_Arguments = []) {
            $_Buffer = ob_get_clean();
            
            ob_start();
            
            call_user_func_array($_Target, $_Arguments);
            
            $_Output = ob_get_clean();
            
            ob_start();
            
            echo $_Buffer;
            
            return $_Output;
        }
        
        public function AssertEquals($_Alpha, $_Beta) {
            if($_Alpha !== $_Beta) {
                throw new Exception('Equals failure !');
            }
            
            return true;
        }
        
        public function AssertNotEquals($_Alpha, $_Beta) {
            if($this->AssertEquals($_Alpha, $_Beta)) {
                throw new Exception('Not equals failure !');
            }
            
            return true;
        }
    }
?>