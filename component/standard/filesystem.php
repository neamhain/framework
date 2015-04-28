<?php
    /**
     * Library Filesystem
     * Filesystem processor
     **/
    
    function IsExists($_Target) {
        return is_file($_Target) || is_dir($_Target);
    }

    function Read($_Target) {
        return IsExists($_Target) ? file_get_contents($_Target) : null;
    }

    function Write($_Target, $_Context, $_IsAppend = false) {
        if(is_writable(dirname($_Target))) {
            $_Result = file_put_contents($_Target, $_Context, ($_IsAppend ? FILE_APPEND : 0) | LOCK_EX);
            
            if($_Result) {
                chmod($_Target, 0666);
            }
            
            return $_Result ? true : false;
        }
        
        return null;
    }

    function Append($_Target, $_Context) {
        return Write($_Target, $_Context, true);
    }

    function MakeDirectory($_Target, $_Permission = 0755) {
        if(!IsExists($_Target)) {
            mkdir($_Target, $_Permission);
            chmod($_Target, $_Permission);
        }
    }

    function Remove($_Target) {
        foreach(array_diff(scandir($_Target), [ '.', '..' ]) as $_Item) {
            if(is_dir($_Target . '/' . $_Item)) {
                Remove($_Target . '/' . $_Item);
                
                continue;
            }
            
            unlink($_Target . '/' . $_Item);
        }
        
        return rmdir($_Target);
    }

    function Upload($_Target) {
        $_Directory = Framework::Resolve('files');
        
        MakeDirectory($_Directory);
        
        $_Result = null;
        $_File = $_FILES[$_Target];
        
        if($_File && $_File['error'] === UPLOAD_ERR_OK) {
            $_Extension = Lowercase(array_reverse(explode('.', $_File['name']))[0]);

            if(preg_match('/jpe?g|png|gif/', $_Extension)) {
                $_Path = Random() . '.' . $_Extension;

                while(IsExists($_Directory . '/' . $_Path)) {
                    $_Path = Random() . '.' . $_Extension;
                }

                move_uploaded_file($_File['tmp_name'], $_Directory . '/' . $_Path);

                $_Result = $_Path;
            }
        }
        
        return $_Result ? '/files/' . $_Result : $_Result;
    }
?>