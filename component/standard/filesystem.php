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

    function Write($_Target, $_Context) {
        return is_writable(dirname($_Target)) ? file_put_contents($_Target, $_Context, LOCK_EX) : null;
    }

    function Append($_Target, $_Context) {
        return file_put_contents($_Target, $_Context, FILE_APPEND | LOCK_EX);
    }

    function MakeDirectory($_Target, $_Permission = 0755) {
        if(!IsExists($_Target)) {
            mkdir($_Target, $_Permission);
            chmod($_Target, $_Permission);
        }
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