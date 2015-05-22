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
        if(is_file($_Target)) {
            return unlink($_Target) ? true : false;
        }
        
        foreach(array_diff(scandir($_Target), [ '.', '..' ]) as $_Item) {
            if(is_dir($_Target . '/' . $_Item)) {
                Remove($_Target . '/' . $_Item);
                
                continue;
            }
            
            unlink($_Target . '/' . $_Item);
        }
        
        return rmdir($_Target) ? true : false;
    }

    function Upload($_Target) {
        $_Files = [];

        if(is_array($_FILES[$_Target]['name'])) {
            foreach($_FILES[$_Target] as $_Key => $_Items) {
                foreach($_Items as $_Index => $_Value) {
                    $_Files[$_Index][Classify($_Key)] = $_Value;
                }
            }
        } else {
            $_Files = [[
                'Name' => $_FILES[$_Target]['name'],
                'Type' => $_FILES[$_Target]['type'],
                'TmpName' => $_FILES[$_Target]['tmp_name'],
                'Error' => $_FILES[$_Target]['error'],
                'Size' => $_FILES[$_Target]['size']
            ]];
        }

        $_Destinations = [];

        foreach($_Files as $_Index => $_File) {
            $_OrdinalSuffix = '';

            switch(($_Index + 1) % 10) {
                case 1: $_OrdinalSuffix = 'st'; break;
                case 2: $_OrdinalSuffix = 'nd'; break;
                case 3: $_OrdinalSuffix = 'rd'; break;
                default: $_OrdinalSuffix = 'th';
            }

            $_OrdinalIndex = $_Index . $_OrdinalSuffix;

            if($_File['Error'] === UPLOAD_ERR_INI_SIZE) {
                continue;
            } else if($_File['Error'] === UPLOAD_ERR_PARTIAL) {
                continue;
            } else if($_File['Error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $_FileType = exif_imagetype($_File['TmpName']) - 1;

            if(!$_FileType || $_FileType > 3) {
                continue;
            }

            $_Extension = [ 'gif', 'jpg', 'png' ][$_FileType];
            $_Destination = 'files/' . sprintf('%s_%s', time(), Random()) . '.' . $_Extension;
            $_Result = move_uploaded_file($_File['TmpName'], Framework::Resolve($_Destination));

            if(!$_Result) {
                continue;
            }

            $_Destinations[] = $_Destination;
        }
        
        if(!$_Destinations[0])  {
            $_Destinations = [''];
        }
        
        return count($_Destinations) > 1 ? $_Destinations : $_Destinations[0];
    }
?>