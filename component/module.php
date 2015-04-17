<?php
    /**
     * Module
     * Abstract Layer of Modules
     **/
    
    if(!defined('FRAMEWORK')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
        exit;
    }

    abstract class Module {
        public $Initialized = null;
        
        static function Resolve($_Target) {
            $_Uppercase = Uppercase($_Target);
            $_Lowercase = Lowercase($_Target);
            
            if(preg_match('/(Model|View|Controller)$/', $_Target)) {
                $_Uppercase = Uppercase(preg_replace('/(Model|View|Controller)$/', '_$1', $_Target));
                $_Lowercase = Lowercase(preg_replace('/(Model|View|Controller)$/', '.$1', $_Target));
            }
            
            $_Path = Framework::Resolve('module/' . $_Lowercase . '.php');
            
            if(!IsExists($_Path)) {
                if(IsExists(Framework::Resolve('module/' . $_Lowercase . '.php', true))) {
                    $_Path = Framework::Resolve('module/' . $_Lowercase . '.php', true);
                } else if(!class_exists($_Target)) {
                    return null;
                }
            }
            
            if(!$GLOBALS['FRAMEWORK_MODULE_' . $_Uppercase . '_INSTANCE']) {
                if(!class_exists($_Target)) {
                    Import($_Path);
                }
                
                $GLOBALS['FRAMEWORK_MODULE_' . $_Uppercase . '_INSTANCE'] = new $_Target();
            }
            
            return $GLOBALS['FRAMEWORK_MODULE_' . $_Uppercase . '_INSTANCE'];
        }
        
        static function Instance($_Target) {
            $_Instance = static::Resolve($_Target);
            
            if($_Instance === null) {
                throw new Exception('Module ' . $_Target . ' is not exists.');
            }
            
            $_Uppercase = Uppercase($_Target);
            $_Lowercase = Lowercase($_Target);
            $_Superlative = Uppercase(preg_replace('/(Model|View|Controller)$/', '', $_Target));
            
            if(!$GLOBALS['FRAMEWORK_MODULE_' . $_Superlative . '_INSTANCE']->Initialized) {
                $GLOBALS['FRAMEWORK_MODULE_' . $_Superlative . '_INSTANCE']->Initialize();
                
                $GLOBALS['FRAMEWORK_MODULE_' . $_Superlative . '_INSTANCE']->Initialized = true;
            }
            
            return $_Instance;
        }
        
        public function __consturct() {
            if($this->Type === 'Controller' && !CsrfTokenVerify($this->Post['CsrfToken'])) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
                exit;
            }
        }
        
        public function &__get($_Name) {
            if($_Name === 'Name') {
                return preg_replace('/(Model|View|Controller)$/', '', static::class);
            }
            
            if($_Name === 'Type') {
                if(preg_match('/Model$/', static::class)) {
                    return 'Model';
                } else if(preg_match('/View/', static::class)) {
                    return 'View';
                } else if(preg_match('/Controller$/', static::class)) {
                    return 'Controller';
                } else {
                    return 'Superlative';
                }
            }
            
            if($_Name === 'Get') {
                $_Get = &$_GET;
                
                return $_Get;
            }
            
            if($_Name === 'Post') {
                $_Post = &$_POST;
                
                return $_Post;
            }
            
            if($_Name === 'Session') {
                $_Session = &$_SESSION[Framework::Take('UniqueId')];
                
                return $_Session;
            }
            
            if($_Name === 'CsrfToken') {
                $_CsrfToken = &$_SESSION[Framework::Take('UniqueId')]['__CSRF_TOKEN__'];
                
                return $_CsrfToken;
            }
            
            return null;
        }
        
        public function Initialize() {
            return true;
        }
    }

    trait MVC {
        public function &__get($_Name) {
            if($_Name === 'Model') {
                return static::Instance($this->Name . 'Model');
            }
            
            if($_Name === 'View') {
                return static::Instance($this->Name . 'View');
            }
            
            if($_Name === 'Controller') {
                return static::Instance($this->Name . 'Controller');
            }
            
            return parent::__get($_Name);
        }
        
        public function Model($_Target, $_Arguments = []) {
            return call_user_func_array([$this->Model, Classify($_Target)], $_Arguments);
        }
        
        public function View($_Target, $_Arguments = []) {
            $_QueryString = [];
            
            foreach($_Arguments as $_Key => $_Value) {
                $_QueryString[] = $_Value;
            }
            
            $_QueryString = count($_QueryString) > 0 ? '/' . implode('/', $_QueryString) : '';
            
            $this->Session['Post'] = array_merge($this->Session['Post'] ? $this->Session['Post'] : [], $this->Post);
            
            foreach($this->Session['Post'] as $_Key => &$_Value) {
                if($_Key === 'context') {
                    $_Value = HtmlPurify($_Value);
                }
            }
            
            Location('/' . Dasherize($_Target) . $_QueryString);
        }
        
        public function Controller($_Target, $_Arguments = []) {
            return call_user_func_array([$this->Controller, Classify($_Target)], $_Arguments);
        }
        
        public function Process() {
            if($this->Type === 'Superlative') {
                if($this->Get['ctrl']) {
                    return call_user_func_array([$this->Controller, Classify($this->Get['ctrl'])], []);
                }
                
                return call_user_func_array([$this->View, Classify($this->Get['view'] ? $this->Get['view'] : 'index')], []);
            }
        }
    }

    trait RESTful {
        public function &__get($_Name) {
            if($_Name === 'Request') {
                $_ContentType = Lowercase(explode(';', $_SERVER['CONTENT_TYPE'], 2)[0]);
                
                if($_ContentType === 'multipart/form-data') {
                    $_RawContext = JsonEncode($this->Post, JSON_NUMERIC_CHECK);
                } else {
                    $_RawContext = file_get_contents('php://input');
                }
                
                if(IsJson($_RawContext)) {
                    return JsonDecode($_RawContext);
                } else if($_ContentType === 'application/x-www-form-urlencoded') {
                    parse_str($_RawContext, $_Context);
                    
                    return $_Context;
                }
                
                return $_RawContext;
            }
            
            return parent::__get($_Name);
        }
        
        public function Process() {
            
        }
    }
?>