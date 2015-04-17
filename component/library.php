<?php
    /**
     * Library
     * Base processing
     **/
    
    if(!defined('FRAMEWORK')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
        exit;
    }
    
    /**
     * Include a file without variable's namespace collision.
     **/
    function Import($_Target) {
        require_once $_Target;
    }
    
    /**
     * Process an error to the template.
     **/
    call_user_func_array(function() {
        error_reporting(~E_ALL);

        function ErrorHandler($_Type, $_Message, $_File, $_Line, $_Context = null, $_Trace = null) {
            $_ResourcePath = '/' . basename(Framework::Take('Path')) . '/resource/';

            $_ErrorFile = explode("\n", htmlspecialchars(Read($_File)));

            foreach($_ErrorFile as $_ErrorLine => &$_Context) {
                if(max(0, $_Line - (is_array($_Trace) ? 5 : 10)) <= $_ErrorLine + 1 && $_ErrorLine + 1 <= min($_Line + (is_array($_Trace) ? 5 : 10), count($_ErrorFile))) {
                    if($_Line === $_ErrorLine + 1) {
                        $_Context = sprintf('<span data-line="%d" class="target">%s</span>', $_ErrorLine + 1, $_Context);
                    } else {
                        $_Context = sprintf('<span data-line="%d">%s</span>', $_ErrorLine + 1, $_Context);
                    }
                } else {
                    $_Context = '';
                }
            }

            $_ErrorFile = sprintf('<code>%s</code>', implode('', $_ErrorFile));
            
            if(is_array($_Trace)) {
                array_shift($_Trace);
                
                foreach($_Trace as $_Index => $_Error) {
                    if(!$_Error['file'] || !$_Error['line']) {
                        continue;
                    }
                    
                    $_TraceErrorFile = explode("\n", htmlspecialchars(Read($_Error['file'])));

                    foreach($_TraceErrorFile as $_ErrorLine => &$_Context) {
                        if(max(0, $_Error['line'] - 5) <= $_ErrorLine + 1 && $_ErrorLine + 1 <= min($_Error['line'] + 5, count($_TraceErrorFile))) {
                            if($_Error['line'] === $_ErrorLine + 1) {
                                $_Context = sprintf('<span data-line="%d" class="target">%s</span>', $_ErrorLine + 1, $_Context);
                            } else {
                                $_Context = sprintf('<span data-line="%d">%s</span>', $_ErrorLine + 1, $_Context);
                            }
                        } else {
                            $_Context = '';
                        }
                    }
                    
                    $_ErrorFile .=
                            '</article>' .
                        '</div>' .
                        '<div class="ui ' . (count($_Trace) - 1 === $_Index ? 'bottom ' : '') . 'attached segment">' .
                            '<h2 class="ui top attached label">' .
                                '<span class="left floated">' .
                                    preg_replace(':^(' . Framework::Take('Criteria') . '|' . Framework::Take('Path') . ')/:', '', $_Error['file']) .
                                '</span>' .
                                '<span class="right floated">around line ' . $_Error['line'] . '</span>' .
                            '</h2>' .
                            '<article>' . sprintf('<code>%s</code>', implode('', $_TraceErrorFile));
                }
            }
            
            function TemplateBind($_Variable) {
                $_Template = Read(Framework::Resolve('template/error.html', true));
                $_InnerVariable = [];
                
                foreach($_Variable as $_Key => $_Value) {
                    if(is_array($_Value)) {
                        foreach($_Value as $_InnerKey => $_InnerValue) {
                            $_InnerVariable[$_Key . '/' . $_InnerKey] = $_InnerValue;
                        }
                    }
                }

                $_Variable = array_merge($_Variable, $_InnerVariable);

                foreach($_Variable as $_Original => $_Replacement) {
                    $_Template = str_replace('{{ ' . Lowercase($_Original) . ' }}', $_Replacement, $_Template);
                }
                
                return $_Template;
            }

            $_ErrorTemplate = TemplateBind([
                'Framework/Resource' => preg_replace('/\/$/', '', $_ResourcePath),
                'Resource' => [
                    'jQuery' => $_ResourcePath . 'jquery.min.js',
                    'Moment' => $_ResourcePath . 'moment.min.js',
                    'Semantic/CSS' => $_ResourcePath . 'semanticui/dist/semantic.min.css',
                    'Semantic/JS' => $_ResourcePath . 'semanticui/dist/semantic.min.js'
                ],
                'Error' => [
                    'Trace' => is_array($_Trace) ? 'top attached ' : '',
                    'Type' => [
                        'Exception' => 'Unexpected Exception',
                        E_ERROR => 'Unexpected Error',
                        E_WARNING => 'Warning',
                        E_PARSE => 'Parse Error',
                        E_CORE_ERROR => 'Unexpected Error',
                        E_CORE_WARNING => 'Warning',
                        E_COMPILE_ERROR => 'Unexpected Error',
                        E_COMPILE_WARNING => 'Warning'
                    ][$_Type],
                    'Message' => $_Message,
                    'File' => preg_replace(':^(' . Framework::Take('Criteria') . '|' . Framework::Take('Path') . ')/:', '', $_File),
                    'File/Context' => $_ErrorFile,
                    'Line' => $_Line,
                    'Context' => $_Context,
                ],
                'Time' => time()
            ], false);
            
            header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');

            echo HtmlBeautify($_ErrorTemplate);
            exit;
        }

        set_error_handler('ErrorHandler', E_ALL & ~E_STRICT & ~E_NOTICE & ~E_DEPRECATED);

        set_exception_handler(function($_Exception) {
            ErrorHandler('Exception', $_Exception->getMessage(), $_Exception->getFile(), $_Exception->getLine(), null, $_Exception->getTrace());
        });

        register_shutdown_function(function() {
            $_Error = error_get_last();
            $_Type = $_Error['type'];
            
            if(OperatorOr(...[
                $_Type === E_ERROR,
                $_Type === E_WARNING,
                $_Type === E_PARSE,
                $_Type === E_CORE_ERROR,
                $_Type === E_CORE_WARNING,
                $_Type === E_COMPILE_ERROR,
                $_Type === E_COMPILE_WARNING
            ])) {
                ErrorHandler($_Type, $_Error['message'], $_Error['file'], $_Error['line']);
            }
        });
    }, []);  
?>