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
        private $BinaryMode = null;
        
        public function __construct() {
            define('FRAMEWORK', true);
            define('FRAMEWORK_PATH' , __DIR__);
            
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
            require_once 'component/session.php';
            
            session_name('FRAMEWORK_SESSION');
            
            session_set_save_handler(
                [Session::Instance(), 'Open'],
                [Session::Instance(), 'Close'],
                [Session::Instance(), 'Read'],
                [Session::Instance(), 'Write'],
                [Session::Instance(), 'Destroy'],
                [Session::Instance(), 'GarbageCollect']
            );
            
            $this->Configuration = [];
            $this->IsInstalled = false;
            $this->BinaryMode = false;
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
            $this->Setting = array_merge([
                'Deploy' => 'Production',
                'Criteria' => dirname(__DIR__),
                'Mainstream' => ''
            ], $_Setting);
            
            define('FRAMEWORK_DEPLOY' , $this->Setting['Deploy']);
            define('FRAMEWORK_CRITERIA' , $this->Setting['Criteria']);
            
            session_start();
            
            if(IsExists(static::Resolve('configuration.php'))) {
                require_once static::Resolve('configuration.php');
                
                $this->Configuration = Enlarge($_Configuration);
                
                $_Session = &$_SESSION[static::Take('UniqueId')];
                $_SessionOwnerAgent = &$_Session['__OWNER_AGENT__'];
                
                define('FRAMEWORK_AES_KEY', static::Take('AesKey'));
                define('FRAMEWORK_AES_IV', static::Take('AesIv'));
                define('FRAMEWORK_RSA_PUBLIC_KEY', static::Take('RsaPublicKey'));
                define('FRAMEWORK_RSA_PRIVATE_KEY', static::Take('RsaPrivateKey'));
                    
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
            
            if(!$this->BinaryMode) {
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
            } else {
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
            }
        }
        
        public function BinaryOutput($_File) {
            if(is_array($_File)) {
                $_Origin = implode('', array_keys($_File));
                $_Target = implode('', array_values($_File));
            } else {
                $_Origin = $_Target = $_File;
            }
            
            ob_clean();
            
            $this->BinaryMode = true;
            
            if(preg_match('/MSIE|Trident/', $_SERVER['HTTP_USER_AGENT'])) {
                $_Target = iconv('UTF-8', 'CP949//IGNORE', $_Target);
            }
            
            header('Pragma: public');
            header('Expires: 0');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $_Target . '"');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . filesize($_Origin));
            
            flush();
            
            $_Handle = fopen($_Origin, 'rb');
            
            while(!feof($_Handle)) {
                echo fread($_Handle, 1);
                
                ob_flush();
                flush();
            }
            
            fclose($_Handle);
        }
    }
?>