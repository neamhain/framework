<?php
    /**
     * Library Type
     * Variable type and value checker
     **/
    
    if(!defined('FRAMEWORK')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
        exit;
    }
    
    function IsBoolean(...$_Arguments) {
        $_Count = count($_Arguments);
        
        if($_Count === 1) {
            $_Target = $_Arguments[0];
            
            return gettype($_Target) === 'boolean';
        } else if($_Count > 1) {
            foreach($_Arguments as &$_Target) {
                $_Target = IsBoolean($_Target);
            }
            
            return OperatorAnd($_Target);
        }
        
        return false;
    }

    function IsNumber(...$_Arguments) {
        $_Count = count($_Arguments);
        
        if($_Count === 1) {
            $_Target = $_Arguments[0];
            
            return IsInteger($_Target) || IsFloat($_Target);
        } else if($_Count > 1) {
            foreach($_Arguments as &$_Target) {
                $_Target = IsInteger($_Target);
            }
            
            return OperatorAnd($_Target);
        }
        
        return false;
    }

    function IsPositive(...$_Arguments) {
        $_Count = count($_Arguments);
        
        if($_Count === 1) {
            $_Target = $_Arguments[0];
            
            return IsNumber($_Target) && $_Target > 0;
        } else if($_Count > 1) {
            foreach($_Arguments as &$_Target) {
                $_Target = IsInteger($_Target);
            }
            
            return OperatorAnd($_Target);
        }
        
        return false;
    }

    function IsNegative(...$_Arguments) {
        $_Count = count($_Arguments);
        
        if($_Count === 1) {
            $_Target = $_Arguments[0];
            
            return IsNumber($_Target) && $_Target < 0;
        } else if($_Count > 1) {
            foreach($_Arguments as &$_Target) {
                $_Target = IsInteger($_Target);
            }
            
            return OperatorAnd($_Target);
        }
        
        return false;
    }

    function IsZero(...$_Arguments) {
        $_Count = count($_Arguments);
        
        if($_Count === 1) {
            $_Target = $_Arguments[0];
            
            return IsNumber($_Target) && $_Target === 0;
        } else if($_Count > 1) {
            foreach($_Arguments as &$_Target) {
                $_Target = IsInteger($_Target);
            }
            
            return OperatorAnd($_Target);
        }
        
        return false;
    }

    function IsInteger(...$_Arguments) {
        $_Count = count($_Arguments);
        
        if($_Count === 1) {
            $_Target = $_Arguments[0];
            
            return gettype($_Target) === 'integer';
        } else if($_Count > 1) {
            foreach($_Arguments as &$_Target) {
                $_Target = IsInteger($_Target);
            }
            
            return OperatorAnd($_Target);
        }
        
        return false;
    }

    function IsNatural(...$_Arguments) {
        $_Count = count($_Arguments);
        
        if($_Count === 1) {
            $_Target = $_Arguments[0];
            
            return IsInteger($_Target) && IsPositive($_Target);
        } else if($_Count > 1) {
            foreach($_Arguments as &$_Target) {
                $_Target = IsNatural($_Target);
            }
            
            return OperatorAnd($_Target);
        }
        
        return false;
    }

    function IsOdd(...$_Arguments) {
        $_Count = count($_Arguments);
        
        if($_Count === 1) {
            $_Target = $_Arguments[0];
            
            return IsInteger($_Target) && $_Target % 2 !== 0;
        } else if($_Count > 1) {
            foreach($_Arguments as &$_Target) {
                $_Target = IsNatural($_Target);
            }
            
            return OperatorAnd($_Target);
        }
        
        return false;
    }

    function IsEven(...$_Arguments) {
        $_Count = count($_Arguments);
        
        if($_Count === 1) {
            $_Target = $_Arguments[0];
            
            return !IsOdd($_Target);
        } else if($_Count > 1) {
            foreach($_Arguments as &$_Target) {
                $_Target = IsNatural($_Target);
            }
            
            return OperatorAnd($_Target);
        }
        
        return false;
    }

    function IsFloat(...$_Arguments) {
        $_Count = count($_Arguments);
        
        if($_Count === 1) {
            $_Target = $_Arguments[0];
            
            return gettype($_Target) === 'double';
        } else if($_Count > 1) {
            foreach($_Arguments as &$_Target) {
                $_Target = IsFloat($_Target);
            }
            
            return OperatorAnd($_Target);
        }
        
        return false;
    }

    function IsNumeric(...$_Arguments) {
        $_Count = count($_Arguments);
        
        if($_Count === 1) {
            $_Target = $_Arguments[0];
            
            return floatval($_Target) === $_Target + 0;
        } else if($_Count > 1) {
            foreach($_Arguments as &$_Target) {
                $_Target = IsNumeric($_Target);
            }
            
            return OperatorAnd($_Target);
        }
        
        return false;
    }

    function IsString(...$_Arguments) {
        $_Count = count($_Arguments);
        
        if($_Count === 1) {
            $_Target = $_Arguments[0];
            
            return gettype($_Target) === 'string';
        } else if($_Count > 1) {
            foreach($_Arguments as &$_Target) {
                $_Target = IsString($_Target);
            }
            
            return OperatorAnd($_Target);
        }
        
        return false;
    }

    function IsArray(...$_Arguments) {
        $_Count = count($_Arguments);
        
        if($_Count === 1) {
            $_Target = $_Arguments[0];
            
            return gettype($_Target) === 'array';
        } else if($_Count > 1) {
            foreach($_Arguments as &$_Target) {
                $_Target = IsArray($_Target);
            }
            
            return OperatorAnd($_Target);
        }
        
        return false;
    }

    function IsObject(...$_Arguments) {
        $_Count = count($_Arguments);
        
        if($_Count === 1) {
            $_Target = $_Arguments[0];
            
            return gettype($_Target) === 'object';
        } else if($_Count > 1) {
            foreach($_Arguments as &$_Target) {
                $_Target = IsObject($_Target);
            }
            
            return OperatorAnd($_Target);
        }
        
        return false;
    }

    function IsNull(...$_Arguments) {
        $_Count = count($_Arguments);
        
        if($_Count === 1) {
            $_Target = $_Arguments[0];
            
            return gettype($_Target) === 'NULL';
        } else if($_Count > 1) {
            foreach($_Arguments as &$_Target) {
                $_Target = IsNull($_Target);
            }
            
            return OperatorAnd($_Target);
        }
        
        return false;
    }

    function IsScalar(...$_Arguments) {
        $_Count = count($_Arguments);
        
        if($_Count === 1) {
            $_Target = $_Arguments[0];
            
            return IsBoolean($_Target) || IsNumber($_Target) || IsString($_Target);
        } else if($_Count > 1) {
            foreach($_Arguments as &$_Target) {
                $_Target = IsNull($_Target);
            }
            
            return OperatorAnd($_Target);
        }
        
        return false;
    }

    function IsEmpty(...$_Arguments) {
        $_Count = count($_Arguments);
        
        if($_Count === 1) {
            $_Target = $_Arguments[0];
            
            return IsNull($_Target) || IsBoolean($_Target) && $_Target === false || IsZero($_Target) || IsArray($_Target) && count($_Target) === 0 || IsString($_Target) && strlen($_Target) === 0;
        } else if($_Count > 1) {
            foreach($_Arguments as &$_Target) {
                $_Target = IsNull($_Target);
            }
            
            return OperatorAnd($_Target);
        }
        
        return false;
    }
?>