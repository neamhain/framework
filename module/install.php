<?php
    /**
     * Install
     * Superlative class of Install
     **/
    
    if(!defined('FRAMEWORK')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
        exit;
    }

    class Install extends Module {
        public function Process() {
            if(version_compare(PHP_VERSION, '5.6.0') < 0) {
                echo 'Framework is works on PHP 5.6.0 or higher version.';
                
                return;
            }
            
            if(!function_exists('mcrypt_encrypt')) {
                echo 'Framework need mcrypt library for cryptography.';
                
                return;
            }
            
            if(!is_readable(Framework::Take('Criteria')) || !is_writable(Framework::Take('Criteria'))) {
                echo 'Framework need reading and writing permission at criteria path.';
                
                return;
            }
            
            MakeDirectory(Framework::Resolve('files'), 0707);
            MakeDirectory(Framework::Resolve('module'), 0707);
            MakeDirectory(Framework::Resolve('resource'), 0707);
            MakeDirectory(Framework::Resolve('resource/source'), 0707);
            MakeDirectory(Framework::Resolve('template'), 0707);
            MakeDirectory(Framework::Resolve('template/source'), 0707);
            
            if(!IsExists(Framework::Resolve('module/.htaccess'))) {
                Write(Framework::Resolve('module/.htaccess'), 'Require all denied');
            }
            
            if(!IsExists(Framework::Resolve('template/.htaccess'))) {
                Write(Framework::Resolve('template/.htaccess'), 'Require all denied');
            }
            
            if(!IsExists(Framework::Resolve('.htaccess'))) {
                Write(
                    Framework::Resolve('.htaccess'),
                    implode("\n", [
                        '<IfModule mod_rewrite.c>',
                        '    RewriteEngine On',
                        '    RewriteBase /',
                        '    ',
                        '    RewriteCond $1 !^(index\.php|framework|resource|template|files)',
                        '    RewriteCond %{REQUEST_FILENAME} !-f',
                        '    RewriteCond %{REQUEST_FILENAME} !-d',
                        '    RewriteRule ^(.*)$ ./index.php/$1 [L]',
                        '</IfModule>'
                    ])
                );
            }
            
            if(!IsExists(Framework::Resolve('.gitignore'))) {
                Write(
                    Framework::Resolve('.gitignore'),
                    implode("\n", [
                        '# DIRECTORIES',
                        '/files',
                        '',
                        '# FILES',
                        '/prepros.cfg',
                        '/configuration.php'
                    ])
                );
            }
            
            $_Configuration = [
                'UniqueId' => Random(),
                'HashSalt' => Random(),
                'AesKey' => mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_DEV_URANDOM),
                'AesIv' => mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_DEV_URANDOM)
            ];
            
            foreach($_Configuration as $_Key => $_Value) {
                Framework::Save($_Key, $_Value);
            }
            
            Location('/');
        }
    }
?>