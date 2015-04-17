<?php
    /**
     * Library HTML
     * HTML Processor
     **/
    
    if(!defined('FRAMEWORK')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
        exit;
    }
    
    function HtmlBeautify($_HTML, $_IndentString = '    ') {
        $_Original = explode("\n", Replace($_HTML, [ '/>\s+</' => '><', '<' => "\n<", '>' => ">\n", '/>\s+</' => ">\n<", '>\n</' => "></" ]));
        $_Beautiful = '';
        $_Indentation = 0;

        foreach($_Original as $_Line) {
            $_BeforeLine = &$_Beautiful[count($_Beautiful) - 1];
            
            if(preg_match('/^<\/[a-zA-Z]{1}[0-9a-zA-Z]{0,}>$/', $_Line)) {
                $_Indentation -= 1;
            }

            if(preg_match('/^<\//', $_Line) && preg_match('/^\s*<[a-zA-Z]{1}[0-9a-zA-Z]{0,}/', $_BeforeLine) && !preg_match('/>$/', $_BeforeLine)) {
                $_BeforeLine .= $_Line;
            } else if(strstr($_BeforeLine, '</') === false && !preg_match('/\/>$/', $_BeforeLine) && preg_match('/^\s*<[a-zA-Z]{1}[0-9a-zA-Z]{0,}.*>$/', $_BeforeLine) && preg_match('/^<\/[a-zA-Z]{1}[0-9a-zA-Z]{0,}>$/', $_Line)) {
                $_BeforeLine .= $_Line;
            } else if(preg_match('/^<.*>$/', $_Line)) {
                $_Beautiful[] = str_repeat($_IndentString, $_Indentation) . $_Line;
            } else {
                $_BeforeLine .= $_Line;
            }

            if(preg_match('/^<[a-zA-Z]{1}[0-9a-zA-Z]{0,}.*>$/', $_Line) && !preg_match('/^<(area|base|basefont|br|col|command|embed|frame|hr|img|input|isindex|keygen|link|meta|param|source|track|wbr).*>$/', $_Line) && !preg_match('/^<.*\/>$/', $_Line)) {
                $_Indentation += 1;
            }
        }

        return trim(Replace(implode("\n", $_Beautiful), [ '/>' => '>', ' >' => '>', ' "' => '"', '=""' => '' ]));
    }
?>