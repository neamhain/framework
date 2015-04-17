<?php
    /**
     * Framework
     * Modulize Processor
     * 
     * Version 0.1.0
     * Licensed under the MIT
     * 
     * contact@cichol.com
     * http://www.cichol.com/
     **/
    
    final class Framework {
        public $Configuration = null;
        private $IsInstalled = null;
        private $Setting = null;
        
        public function __construct() {
            define('FRAMEWORK', true);
            define('FRAMEWORK_PATH' , __DIR__);
            
            session_name('FRAMEWORK_SESSION');
            session_start();
            
            foreach(scandir(FRAMEWORK_PATH . '/component/standard') as $_Target) {
                $_Target = FRAMEWORK_PATH . '/component/standard/' . $_Target;

                if(!is_file($_Target)) {
                    continue;
                }

                require_once $_Target;
            }
            
            require_once 'component/library.php';
            require_once 'component/database.php';
            require_once 'component/module.php';
            require_once 'component/template.php';
            
            $this->Configuration = [];
            $this->IsInstalled = false;
        }
        
        static function Instance() {
            if(!$GLOBALS['FRAMEWORK_INSTANCE']) {
                $GLOBALS['FRAMEWORK_INSTANCE'] = new self();
            }
            
            return $GLOBALS['FRAMEWORK_INSTANCE'];
        }
        
        static function Take($_Name) {
            if($_Name === 'Path') {
                return FRAMEWORK_PATH;
            } else if($_Name === 'Criteria') {
                return FRAMEWORK_CRITERIA;
            }
            
            return static::Instance()->Configuration[$_Name];
        }
        
        static function Save($_Name, $_Value) {
            static::Instance()->Configuration[$_Name] = $_Value;
            
            Write(
                static::Resolve('configuration.php'),
                sprintf(
                    implode("\n", [
                        '<?php',
                        '    if(!defined("FRAMEWORK")) {',
                        '        header($_SERVER["SERVER_PROTOCOL"] . " 401 Unauthorized");',
                        '        exit;',
                        '    }',
                        '    ',
                        '    $_Configuration = "%s";',
                        '?>'
                    ]),
                    Flatten(static::Instance()->Configuration)
                )
            );
        }
        
        static function Resolve($_Target, $_IsPath = false) {
            return static::Take($_IsPath ? 'Path' : 'Criteria') . '/' . $_Target;
        }
        
        public function Initialize($_Setting = []) {
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            
            $this->Setting = array_merge([
                'Criteria' => dirname(__DIR__),
                'Mainstream' => ''
            ], $_Setting);
            
            define('FRAMEWORK_CRITERIA' , $this->Setting['Criteria']);
            
            if(IsExists(static::Resolve('configuration.php'))) {
                require_once static::Resolve('configuration.php');
                
                $this->Configuration = Enlarge($_Configuration);
                
                $_Session = &$_SESSION[static::Take('UniqueId')];
                $_SessionOwnerAgent = &$_Session['__OWNER_AGENT__'];
                    
                if(!isset($_SessionOwnerAgent)) {
                    $_SessionOwnerAgent = $_SERVER['HTTP_USER_AGENT'];
                } else if($_SessionOwnerAgent !== $_SERVER['HTTP_USER_AGENT']) {
                    $_Session = [];
                }
                
                if(Uppercase($_SERVER["REQUEST_SCHEME"]) === 'HTTPS' || $_SERVER['HTTPS']) {
                    if(!isset($_COOKIE['SECURE_SESSION'])) {
                        $_Session['__SECURE_SESSION__'] = Random();
                        
                        setcookie('SECURE_SESSION', $_Session['__SECURE_SESSION__'], 0, '/', $_SERVER['HTTP_HOST'], true, true);
                    } else if($_COOKIE['SECURE_SESSION'] !== $_Session['__SECURE_SESSION__']) {
                        $_Session = [];
                        $_Session['__SECURE_SESSION__'] = Random();
                        
                        setcookie('SECURE_SESSION', $_Session['__SECURE_SESSION__'], 0, '/', $_SERVER['HTTP_HOST'], true, true);
                    }
                }
                
                $_CsrfToken = &$_Session['__CSRF_TOKEN__'];
                
                if(!isset($_CsrfToken)) {
                    $_CsrfToken = Random();
                }
                
                $this->IsInstalled = true;
            }
            
            ob_start();
        }
        
        public function Process() {
            $_Errors = ob_get_clean();
            
            ob_start();
            
            if(preg_match('/\/index\.php/', $_SERVER['REQUEST_URI'])) {
                return Location(str_replace('//', '/', preg_replace('/\/index\.php/', '/', $_SERVER['REQUEST_URI'])));
            }
            
            if($_SERVER['PATH_INFO']) {
                $_RequestPath = explode('/', preg_replace('/^\//', '', $_SERVER['PATH_INFO']));
                $_Module = Module::Resolve(array_shift($_RequestPath));
                
                if($_Module === null) {
                    $_RequestPath = explode('/', preg_replace('/^\//', '', $_SERVER['PATH_INFO']));
                    $_Module = Module::Instance($this->Setting['Mainstream']);
                }
                
                if(!$this->IsInstalled) {
                    $_RequestPath = explode('/', preg_replace('/^\//', '', $_SERVER['PATH_INFO']));
                    $_Module = Module::Instance('Install');
                }
                
                if(method_exists($_Module, 'Model') && method_exists($_Module, 'View') && method_exists($_Module, 'Controller')) {
                    $_RequestVerb = [ 'GET' => 'View', 'POST' => 'Controller' ][$_SERVER['REQUEST_METHOD']];
                    $_RequestMethod = array_shift($_RequestPath);
                    
                    define('FRAMEWORK_VIEW', preg_replace('/^-+|-+$/', '', preg_replace('/[^a-z]+/', '-', $_RequestMethod)));
                    
                    if(method_exists($_Module->__get($_RequestVerb), Classify($_RequestMethod))) {
                        call_user_func_array([$_Module->__get($_RequestVerb), Classify($_RequestMethod)], $_RequestPath);
                    } else {
                        $_Module->Process(Classify($_RequestMethod));
                    }
                } else {
                    $_Method = [$_Module, Classify($_SERVER['REQUEST_METHOD']) . Classify(array_shift($_RequestPath))];
                    
                    if(method_exists(...$_Method)) {
                        call_user_func_array($_Method, $_RequestPath);
                    } else {
                        throw new Exception('Module  "' . get_class($_Module) . '" do not have method  "' . $_Method[1] . '"');
                    }
                }
            } else {
                define('FRAMEWORK_VIEW', $_GET['view'] ? preg_replace('/^-+|-+$/', '', preg_replace('/[^a-z]+/', '-', $_GET['view'])) : 'index');
                
                Module::Instance($_Mainstream = $this->IsInstalled ? $this->Setting['Mainstream'] : 'Install')->Process();
            }
            
            $_Data = ob_get_clean();
            
            if(IsJson($_Data)) {
                ContentType('application/json');
                
                echo $_Data;
                exit;
            }
            
            $_Data = Template::Bind($_Data, [
                'Elapsed/Time' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4),
                'Database/Time' => round($GLOBALS['FRAMEWORK_DATABASE_ELAPSED_TIME'], 4)
            ], false, false);
            
            echo $_Errors, $_Data;
            exit;
        }
    }
?>