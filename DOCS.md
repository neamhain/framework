# Framework documentation

## Getting started

## Installation

## How to make a module
```php
<?php
    if(!defined('FRAMEWORK')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
        exit;
    }
    
    class Foobar extends Module {
        public function Initialize() {
            // Codes for module to initialize
        }
        
        public function Process() {
            // Default entry point of the module
        }
    }
?>
```

### MVC pattern

#### module/foobar.php
```php
<?php
    if(!defined('FRAMEWORK')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
        exit;
    }
    
    class Foobar extends Module {
        use MVC;
    }
?>
```

#### module/foobar.view.php
```php
<?php
    if(!defined('FRAMEWORK')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
        exit;
    }
    
    class FoobarView extends Foobar {
        
    }
?>
```


### RESTful pattern
```php
<?php
    if(!defined('FRAMEWORK')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
        exit;
    }
    
    class Foobar extends Module {
        use RESTful;
        
        public function Get() {
            // GET / HTTP/1.1
        }
        
        public function PostSignIn() {
            // POST /sign-in HTTP/1.1
        }
    }
?>
```

## How to use database in module
```php
<?php
    if(!defined('FRAMEWORK')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
        exit;
    }
    
    class FoobarController extends Foobar {
        const Success = [
            'Code' => 400,
            'Message' => 'Success'
        ];
        
        const Failure = [
            'Code' => 400,
            'Message' => 'Failure'
        ];
        
        public function SignIn() {
            $_DB = Database::Instance('localhost', 'username', 'password', 'database');
            
            $_DB->Define('User', [
                'Name' => [
                    'Type' => 'String'
                ],
                'Password => [
                    'Type' => 'Password'
                ]
            ]);
            
            $_IsExists = $_DB->IsExists('User', [ 'Name' => $this->Post['Name'] ]);
            
            if(!_IsExists) {
                echo JsonEncode(self::Failure);
                
                return;
            }
            
            $_User = $_DB->Select('User', [ 'Name' => $this->Post['Name'] ]);
            
            if(!PasswordVerify($_User['Password'], $this->Post['Password'])) {
                echo JsonEncode(self::Failure);
                
                return;
            }
            
            $this->Session['IsLogged'] = true;
            $this->Session['User'] = $_User;
            
            echo JsonEncode(self::Success);
        }
    }
?>
```

## Default equipped libraries
- [PHPMailer](https://github.com/PHPMailer/PHPMailer)
- [jQuery](https://github.com/jquery/jquery)
- [Semantic UI](https://github.com/Semantic-Org/Semantic-UI)
- [Chart.js](https://github.com/nnnick/Chart.js)
- [moment.js](https://github.com/moment/moment)