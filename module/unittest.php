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
        private $Result = null;
        
        public function __construct() {
            $this->Result = [];
        }
        
        private function GetJUnit() {
            $_JUnit = '<?xml version="1.0" encoding="UTF-8"?><testsuites tests="' . count($this->Result) . '">';
            
            foreach($this->Result as $_Index => $_Result) {
                if($_Result === true) {
                    $_JUnit .= '<testcase classname="' . static::class . '" name="Case Number ' . $_Index . '" />';
                    
                    continue;
                }
                
                if($_Result === null) {
                    $_JUnit .=
                        '<testcase classname="' . static::class . '" name="Case Number ' . $_Index . '">' .
                            '<skipped />' .
                        '</testcase>';
                    
                    continue;
                }
                
                if(is_array($_Result)) {
                    $_JUnit .=
                        '<testcase classname="' . static::class . '" name="Case Number ' . $_Index . '">' .
                            '<failure type="Response"><![CDATA[' . $_Result['Response'] . ']]></failure>' .
                            '<failure type="Expectation"><![CDATA[' . $_Result['Expectation'] . ']]></failure>' .
                        '</testcase>';
                }
            }
            
            $_JUnit .= '</testsuites>';
            
            return $_JUnit;
        }
        
        public function Assert($_Alpha, $_Beta) {
            $GLOBALS['FRAMEWORK_UNIT_TEST_ELAPSED_TIME'] -= microtime(true);
            $GLOBALS['FRAMEWORK_UNIT_TEST_COUNT'] += 1;
            
            try {
                $this->AssertEquals($_Alpha, JsonEncode($_Beta));
                
                if(!empty($_Beta)) {
                    echo "\033[0;32m" . '[✓] UnitTest Case Number ' . $GLOBALS['FRAMEWORK_UNIT_TEST_COUNT'] . ' Succeed' . "\033[0m\n";
                    
                    $this->Result[] = true;
                } else {
                    echo '[*] UnitTest Case Number ' . $GLOBALS['FRAMEWORK_UNIT_TEST_COUNT'] . ' Passed' . "\n";
                    
                    $this->Result[] = null;
                }
                
                $_Result = true;
            } catch(Exception $_Exception) {
                echo "\033[0;31m" . '[ ] UnitTest Case Number ' . $GLOBALS['FRAMEWORK_UNIT_TEST_COUNT'] . ' Failure' . "\033[0m\n";
                
                $this->Result[] = [
                    'Response' => $_Alpha,
                    'Expectation' => JsonEncode($_Beta)
                ];
                
                var_dump($_Alpha, JsonEncode($_Beta));
                
                $_Result = false;
            }
            
            $GLOBALS['FRAMEWORK_UNIT_TEST_ELAPSED_TIME'] += microtime(true);
            
            Write(Framework::Resolve('report.xml'), $this->GetJUnit());
            
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