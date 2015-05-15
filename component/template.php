<?php
    /**
     * Template
     * Hypertext Generator
     **/
    
    if(!defined('FRAMEWORK')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
        exit;
    }

    final class Template {
        static function Interpret($_Target = '', $_Variable = [], $_UseSkeleton = true, $_HtmlUglify = true) {
            $_Template = $_UseSkeleton ? Read(Framework::Resolve('template/skeleton.html', true)) : '{{ body }}';
            $_ResourePath = '/' . basename(Framework::Take('Path')) . '/resource/';
            
            $_Variable = array_merge([
                'Before/Html' => '',
                'Before/Head' => '',
                'After/Head' => '',
                'Before/Body' => '',
                'After/Body' => '',
                'After/Html' => '',
                'Language' => 'ko',
                'Title' => 'Framework',
                'Favorite/Icon' => $_ResourePath . 'favorite.icon.png',
                'Resource' => [
                    'jQuery' => $_ResourePath . 'jquery.min.js',
                    'jQuery/Color' => $_ResourePath . 'jquery.color.min.js',
                    'Chart' => $_ResourePath . 'chartjs/chart.min.js',
                    'Moment' => $_ResourePath . 'moment.min.js',
                    'Semantic/CSS' => $_ResourePath . 'semanticui/dist/semantic.min.css',
                    'Semantic/JS' => $_ResourePath . 'semanticui/dist/semantic.min.js',
                    'Framework' => $_ResourePath . 'framework.js'
                ],
                'Head/Meta' => '',
                'Head/Style' => '',
                'Head/Script' => '',
                'Head/OpenGraph' => '',
                'Head/Others' => '',
                'Body' => '',
                'Page/ID' => defined('FRAMEWORK_VIEW') ? FRAMEWORK_VIEW : 'framework'
            ], $_Variable);
            
            $_Variable['Head/Meta'] = '<meta name="csrf-token" content="' . $_SESSION[Framework::Take('UniqueId')]['__CSRF_TOKEN__'] . '">' . $_Variable['Head/Meta'];
            $_Variable['Head/Meta'] .= '<meta name="subject" content="' . $_Variable['Title'] . '">';
            
            if($_Variable['SEO/Description']) {
                $_Variable['Head/Meta'] .= '<meta name="description" content="' . $_Variable['SEO/Description'] . '">';
            }
            
            if($_Variable['OpenGraph/Type']) {
                $_Variable['Head/OpenGraph'] .= '<meta property="og:type" content="' . $_Variable['OpenGraph/Type'] . '">';
                
                unset($_Variable['OpenGraph/Type']);
            }
            
            if($_Variable['OpenGraph/URL']) {
                $_Variable['Head/OpenGraph'] .= '<meta property="og:url" content="' . $_Variable['OpenGraph/URL'] . '">';
                
                unset($_Variable['OpenGraph/URL']);
            }
            
            if($_Variable['OpenGraph/Image']) {
                $_Variable['Head/OpenGraph'] .= '<meta property="og:image" content="' . $_Variable['OpenGraph/Image'] . '">';
                
                unset($_Variable['OpenGraph/Image']);
            }
            
            if($_Variable['OpenGraph/Video']) {
                $_Variable['Head/OpenGraph'] .= '<meta property="og:video" content="' . $_Variable['OpenGraph/Video'] . '">';
                
                unset($_Variable['OpenGraph/Video']);
            }
            
            if($_Variable['OpenGraph/Title']) {
                $_Variable['Head/OpenGraph'] .= '<meta property="og:title" content="' . $_Variable['OpenGraph/Title'] . '">';
                
                unset($_Variable['OpenGraph/Title']);
            }
            
            if($_Variable['OpenGraph/Image']) {
                $_Variable['Head/OpenGraph'] .= '<meta property="og:image" content="' . $_Variable['OpenGraph/Image'] . '">';
                
                unset($_Variable['OpenGraph/Image']);
            }
            
            if($_Variable['OpenGraph/SiteName']) {
                $_Variable['Head/OpenGraph'] .= '<meta property="og:site_name" content="' . $_Variable['OpenGraph/SiteName'] . '">';
                
                unset($_Variable['OpenGraph/SiteName']);
            }
            
            if($_Variable['OpenGraph/Description']) {
                $_Variable['Head/OpenGraph'] .= '<meta property="og:description" content="' . $_Variable['OpenGraph/Description'] . '">';
                
                unset($_Variable['OpenGraph/Description']);
            }
            
            $_InnerVariable = [];
            
            foreach($_Variable as $_Key => $_Value) {
                if(is_array($_Value)) {
                    foreach($_Value as $_InnerKey => $_InnerValue) {
                        $_InnerVariable[$_Key . '/' . $_InnerKey] = $_InnerValue;
                    }
                }
            }
            
            $_Variable = array_merge($_Variable, $_InnerVariable);
            
            if($_Target) {
                $_Path = Framework::Resolve('template/' . str_replace('-', '.', Lowercase($_Target)) . '.html');
                
                if(!IsExists($_Path)) {
                    if(IsExists(Framework::Resolve('template/' . str_replace('-', '.', Lowercase($_Target)) . '.html', true))) {
                        $_Path = Framework::Resolve('template/' . str_replace('-', '.', Lowercase($_Target)) . '.html', true);
                    } else {
                        throw new Exception('Template ' . $_Target . ' is not exists.');
                    }
                }
                
                $_TargetTemplate = explode("\n", preg_replace('/>\s+</', ">\n<", Read($_Path)));
                $_CompiledTemplate = [];
                
                foreach($_TargetTemplate as $_Line => $_Data) {
                    if(preg_match('/^<(import|load) (src|href|target)=".*">(<\/import>)?$/', $_Data)) {
                        $_Target = preg_replace('/^<(import|load) (src|href|target)="(.*)">(<\/import>)?$/', '$3', $_Data);
                        
                        if(preg_match('/\.js$/', $_Target)) {
                            $_Variable['Head/Script'] .= '<script src="' . $_Target . '" defer></script>';
                            
                            continue;
                        }
                        
                        $_Variable['Head/Style'] .= '<link rel="stylesheet" href="' . $_Target . '">';
                        continue;
                    }
                    
                    $_CompiledTemplate[] = $_Data;
                }
                
                $_Variable['Body'] = implode('', $_CompiledTemplate);
            }
            
            $_Template = static::Bind($_Template, $_Variable, $_HtmlUglify);
            
            return HtmlBeautify($_Template);
        }
        
        static function Bind($_Template, $_Variable = [], $_HtmlUglify = true, $_HtmlBeautify = false) {
            $_Variable = array_merge([], $_Variable);
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
            
            if($_HtmlUglify) {
                $_Template = preg_replace('/>\s+</', '><', $_Template);
            }
            
            return $_HtmlBeautify ? HtmlBeautify($_Template) : $_Template;
        }
    }
?>