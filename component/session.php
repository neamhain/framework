<?php
    /**
     * Session
     * Safety session handler
     **/
    
    if(!defined('FRAMEWORK')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
        exit;
    }

    final class Session {
        public $Id = null;
        
        static function Instance() {
            if(!$GLOBALS['FRAMEWORK_SESSION_HANDLER_INSTANCE']) {
                $GLOBALS['FRAMEWORK_SESSION_HANDLER_INSTANCE'] = new self();
            }
            
            return $GLOBALS['FRAMEWORK_SESSION_HANDLER_INSTANCE'];
        }
        
        public function Open($_SavePath, $_SessionId) {
            $this->Id = $_SessionId;
            
            return true;
        }
        
        public function Close() {
            return true;
        }
        
        public function Read($_SessionId) {
            $_Path = Framework::Resolve('session/' . Sha512($_SessionId));
            
            if(IsExists($_Path)) {
                return Read($_Path);
            }
            
            return '';
        }
        
        public function Write($_SessionId, $_SessionData) {
            $_Path = Framework::Resolve('session/' . Sha512($_SessionId));
            
            if(Write($_Path, $_SessionData)) {
                return true;
            }
            
            return false;
        }
        
        public function Destroy($_SessionId) {
            $_Path = Framework::Resolve('session/' . Sha512($_SessionId));
            
            if(IsExists($_Path)) {
                return Remove($_Path) ? true : false;
            }
            
            return true;
        }
        
        public function GarbageCollect($_MaximumLifeTime) {
            $_Path = Framework::Resolve('session');
            
            foreach(array_diff(scandir($_Path), [ '.', '..' ]) as $_Item) {
                if(is_file($_Path . '/' . $_Item) && filemtime($_Path . '/' . $_Item) + $_MaximumLifeTime < time()) {
                    Remove($_Path . '/' . $_Item);
                }
            }
            
            return true;
        }
    }
?>