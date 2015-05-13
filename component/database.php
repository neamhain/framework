<?php
    /**
     * Database
     * Seasoning for MySQLi
     **/
    
    if(!defined('FRAMEWORK')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
        exit;
    }

    final class Database {
        private $Connection = null;
        private $Table = null;
        
        public function __construct(...$_Arguments) {
            $_Tables = [];
            
            if(count($_Arguments) >= 4) {
                $this->Connect(...$_Arguments);
            }
            
            $GLOBALS['FRAMEWORK_DATABASE_ELAPSED_TIME'] = 0;
        }
        
        static function Instance(...$_Arguments) {
            if(!$GLOBALS['FRAMEWORK_DATABASE_INSTANCE']) {
                $GLOBALS['FRAMEWORK_DATABASE_INSTANCE'] = new self(...$_Arguments);
            }
            
            return $GLOBALS['FRAMEWORK_DATABASE_INSTANCE'];
        }
        
        public function Connect($_Hostname, $_Username, $_Password, $_Database) {
            $_Connection = new mysqli($_Hostname, $_Username, $_Password, $_Database);
            
            if($_Connection->connect_error) {
                throw new Exception('Database connection has failure.');
            }
            
            $_Connection->autocommit(false);
            $_Connection->query("SET NAMES utf8mb4");
            
            $this->Connection = $_Connection;
        }
        
        public function Query($_SQL) {
            $GLOBALS['FRAMEWORK_DATABASE_ELAPSED_TIME'] -= microtime(true);
            
            $_Result = $this->Connection->query($_SQL);
            
            $GLOBALS['FRAMEWORK_DATABASE_ELAPSED_TIME'] += microtime(true);
            
            return $_Result;
        }
        
        public function Commit() {
            return $this->Connection->commit();
        }
        
        public function Escape($_String) {
            return $this->Connection->real_escape_string($_String);
        }
        
        public function Tables($_GetStructure) {
            return !$_GetStructure ? array_keys($this->Table) : $this->Table;
        }
        
        // Essential methods
        public function Serial() {
            if(!defined('FRAMEWORK_DATABASE_TABLE_SERIAL')) {
                Framework::Save('DatabaseTableSerial', [
                    'UniqueId' => [
                        'Type' => 'Serial',
                        'Default'=> 1
                    ],
                    'Value' => [
                        'Type' => 'Serial',
                        'Default'=> 1
                    ]
                ]);
                
                $this->Query("CREATE TABLE IF NOT EXISTS `serial` ( `unique_id` BIGINT UNSIGNED NOT NULL DEFAULT '1', `value` BIGINT UNSIGNED NOT NULL DEFAULT '1', PRIMARY KEY( `unique_id` ) )");
            }
            
            $this->Query("INSERT INTO `serial` ( `unique_id`, `value` ) VALUES ( '1', '1' ) ON DUPLICATE KEY UPDATE `value`=`value`+'1'");
            
            $this->Commit();
            
            return intval($this->Query("SELECT * FROM `serial` WHERE `unique_id`='1'")->fetch_assoc()['value']);
        }
        
        public function Define($_Name, $_Structure) {
            if(Framework::Take('DatabaseTable' . $_Name)) {
                $_OriginalStructure = Framework::Take('DatabaseTable' . $_Name);
                
                $_Structure = array_merge([
                    'Serial' => [
                        'Type' => 'Serial'
                    ]
                ], $_Structure, [
                    'RegisterDate' => [
                        'Type' => 'Date',
                        'Default' => time(),
                        'Index' => true
                    ]
                ]);
                
                $_Changed = false;
                
                foreach($_OriginalStructure as $_Field => $_Option) {
                    if(!$_Structure[$_Field]) {
                        $this->Query(sprintf("ALTER TABLE `%s` DROP COLUMN `%s`", Dasherize($_Name, true), Dasherize($_Field, true)));
                        
                        $_Changed = true;
                    }
                }
                
                foreach(array_keys($_Structure) as $_Index => $_Field) {
                    if($_Field === 'Serial' || $_Field === 'RegisterDate') {
                        continue;
                    }
                    
                    $_BeforeField = Dasherize(array_keys($_Structure)[$_Index - 1], true);
                    $_Option = $_Structure[$_Field];
                    $_Indexes = [];
                    $_Fulltexts = [];
                    
                    if($_OriginalStructure[$_Field] === $_Structure[$_Field]) {
                        continue;
                    }
                    
                    if(!is_array($_Option['Type'])) {
                        $_Type = [
                            'Serial' => 'BIGINT UNSIGNED',
                            'String' => "TINYTEXT",
                            'Password' => "CHAR(60)",
                            'Number' => "BIGINT",
                            'Double' => "DOUBLE",
                            'Boolean' => "BOOLEAN",
                            'Date' => "DATETIME",
                            'Context' => "LONGTEXT",
                            'JSON' => "LONGTEXT"
                        ][$_Option['Type']];

                        $_Type = $_Type ? $_Type : $_Option['Type'];

                        $_DefaultValue = [
                            'Serial' => "'0'",
                            'String' => "''",
                            'Password' => "''",
                            'Number' => "'0'",
                            'Double' => "'0.0'",
                            'Boolean' => "TRUE",
                            'Date' => "CURRENT_TIMESTAMP",
                            'Context' => "''",
                            'JSON' => "{}"
                        ][$_Option['Type']];

                        if(isset($_Option['Default'])) {
                            if($_Option['Type'] === 'String') {
                                $_Type = 'VARCHAR(255)';
                            }

                            if(is_bool($_Option['Default'])) {
                                $_DefaultValue = " DEFAULT " . ($_Option['Default'] ? 'TRUE' : 'FALSE');
                            } else if($_Option['Type'] === 'Date' && $_Option['Default'] === 'CURRENT_TIMESTAMP') {
                                $_DefaultValue = " DEFAULT CURRENT_TIMESTAMP";
                            } else {
                                $_DefaultValue = " DEFAULT " . sprintf("'%s'", $_Option['Default']);
                            }
                        } else {
                            $_DefaultValue = " DEFAULT " . ($_DefaultValue ? $_DefaultValue : "''");
                        }
                        
                        if($_Option['Index']) {
                            if($_Option['Type'] === 'String') {
                                $_Type = 'VARCHAR(255)';
                            }
                            
                            $_Indexes[] = $_Field;
                        }
                        
                        if($_Option['Fulltext']) {
                            $_Fulltexts[] = $_Field;
                        }
                    } else {
                        $_Type = "ENUM('" . Lowercase(implode("', '", $_Option['Type'])) . "')";
                        $_DefaultValue = "";
                    }

                    if(!$_OriginalStructure[$_Field]) {
                        $_SQL = "ALTER TABLE `%s` ADD COLUMN `%s` %s NOT NULL%s AFTER `" . $this->Escape($_BeforeField) . "`";
                    } else {
                        $_SQL = "ALTER TABLE `%s` MODIFY COLUMN `%s` %s NOT NULL%s";
                    }
                    
                    $this->Query(sprintf($_SQL, Dasherize($_Name, true), $this->Escape(Dasherize($_Field, true)), $_Type, $_DefaultValue));
                    
                    foreach($_Indexes as $_Index) {
                        $_SQL = "ALTER TABLE `%s` ADD INDEX ( `%s` )";
                        
                        $this->Query(sprintf($_SQL, Dasherize($_Name, true), $this->Escape(Dasherize($_Index, true))));
                    }
                    
                    foreach($_Fulltexts as $_Fulltext) {
                        $_SQL = "ALTER TABLE `%s` ADD FULLTEXT ( `%s` )";
                        
                        $this->Query(sprintf($_SQL, Dasherize($_Name, true), $this->Escape(Dasherize($_Fulltext, true))));
                    }
                    
                    $_Changed = true;
                }
                
                if($_Changed) {
                    Framework::Save('DatabaseTable' . $_Name, $_Structure);
                }
                
                $this->Table[$_Name] = $_Structure;
                
                return;
            }
            
            $_SQL = "CREATE TABLE IF NOT EXISTS `%s` ( %s ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ENGINE=InnoDB";
            
            $_StructureSQL = [
                "`serial_id` BIGINT UNSIGNED NOT NULL"
            ];
            
            $_Indexes = [];
            $_Fulltexts = [];
            
            foreach($_Structure as $_Field => $_Option) {
                if(!is_array($_Option['Type'])) {
                    $_Type = [
                        'Serial' => 'BIGINT UNSIGNED',
                        'String' => "TINYTEXT",
                        'Password' => "CHAR(60)",
                        'Number' => "BIGINT",
                        'Double' => "DOUBLE",
                        'Boolean' => "BOOLEAN",
                        'Date' => "DATETIME",
                        'Context' => "LONGTEXT",
                        'JSON' => "LONGTEXT"
                    ][$_Option['Type']];

                    $_Type = $_Type ? $_Type : $_Option['Type'];

                    $_DefaultValue = [
                        'Serial' => "'0'",
                        'String' => "''",
                        'Password' => "''",
                        'Number' => "'0'",
                        'Double' => "'0.0'",
                        'Boolean' => "TRUE",
                        'Date' => "CURRENT_TIMESTAMP",
                        'Context' => "''",
                        'JSON' => "{}"
                    ][$_Option['Type']];

                    if(isset($_Option['Default'])) {
                        if($_Option['Type'] === 'String') {
                            $_Type = 'VARCHAR(255)';
                        }
                        
                        if(is_bool($_Option['Default'])) {
                            $_DefaultValue = " DEFAULT " . ($_Option['Default'] ? 'TRUE' : 'FALSE');
                        } else if($_Option['Type'] === 'Date' && $_Option['Default'] === 'CURRENT_TIMESTAMP') {
                                $_DefaultValue = " DEFAULT CURRENT_TIMESTAMP";
                        } else {
                            $_DefaultValue = " DEFAULT " . sprintf("'%s'", $_Option['Default']);
                        }
                    } else {
                        $_DefaultValue = " DEFAULT " . ($_DefaultValue ? $_DefaultValue : "''");
                    }
                    
                    if($_Option['Index']) {
                        if($_Option['Type'] === 'String') {
                            $_Type = 'VARCHAR(255)';
                        }
                        
                        $_Indexes[] = $_Field;
                    }
                    
                    if($_Option['Fulltext']) {
                        $_Fulltexts[] = $_Field;
                    }
                } else {
                    $_Type = "ENUM('" . Lowercase(implode("', '", $_Option['Type'])) . "')";
                    $_DefaultValue = "";
                }
                
                $_StructureSQL[] = sprintf("`%s` %s NOT NULL%s", $this->Escape(Dasherize($_Field, true)), $_Type, $_DefaultValue);
            }
            
            $_StructureSQL[] = "`register_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP";
            
            foreach($_Indexes as $_Index) {
                $_StructureSQL[] = sprintf("INDEX ( `%s` )", $this->Escape(Dasherize($_Index, true)));
            }
            
            foreach($_Fulltexts as $_Fulltext) {
                $_StructureSQL[] = sprintf("FULLTEXT ( `%s` )", $this->Escape(Dasherize($_Fulltext, true)));
            }
            
            $_StructureSQL[] = "PRIMARY KEY ( `serial_id` )";
            
            $_Structure = array_merge([
                'Serial' => [
                    'Type' => 'Serial'
                ]
            ], $_Structure, [
                'RegisterDate' => [
                    'Type' => 'Date',
                    'Default' => time(),
                    'Index' => true
                ]
            ]);
            
            Framework::Save('DatabaseTable' . $_Name, $_Structure);
            $this->Query(sprintf($_SQL, Dasherize($_Name, true), implode(', ', $_StructureSQL)));
            
            $this->Table[$_Name] = $_Structure;
        }
        
        public function Fetch($_Name, $_Result) {
            if($_Result === true || $_Result === false) {
                return $_Result;
            }
            
            $_Fetched = [];

            while($_Fetch = $_Result->fetch_assoc()) {
                if($_Fetch['serial_id']) {
                    $_Fetched[] = array_combine(
                        array_keys($this->Table[$_Name]),
                        array_values($_Fetch)
                    );

                    continue;
                }

                $_Fetched[] = $_Fetch;
            }

            foreach($_Fetched as &$_Fetch) {
                foreach($_Fetch as $_Field => &$_Value) {
                    if(!$this->Table[$_Name][$_Field]) {
                        continue;
                    }

                    $_Type = $this->Table[$_Name][$_Field]['Type'];

                    if($_Type === 'Date') {
                        $_Value = strtotime($_Value);
                    } else if($_Type === 'Boolean') {
                        $_Value = $_Value ? true : false;
                    } else if(is_array($_Type)) {
                        $_Value = Classify($_Value);
                    } else if($_Type === 'JSON') {
                        $_Value = JsonDecode($_Value);
                    }
                }
            }

            if(count($_Fetched) === 1) {
                $_Fetched = $_Fetched[0];
            }

            return $_Fetched;
        }
        
        // Basic methods
        public function Select($_Name, $_Detail = []) {
            $_Hash = md5($_Name . serialize($_Detail));
            
            if(!IsExists(Framework::Resolve('cache/' . $_Hash . '.sql'))) {
                $_Fields = [];
                $_WhereClause = "";
                $_OrderClause = "";
                $_LimitClause = "";
                $_GroupByClause = "";

                if(gettype($_Detail) === 'integer' && $_Detail > 0 || is_numeric($_Detail) && intval($_Detail) > 0) {
                    $_Detail = [ 'WHERE' => [ 'Serial' => $_Detail ] ];
                }

                if(is_array($_Detail) && !array_key_exists('FIELDS', $_Detail) && !array_key_exists('WHERE', $_Detail) && !array_key_exists('ORDER', $_Detail) && !array_key_exists('LIMIT', $_Detail) && count($_Detail) > 0) {
                    $_Detail = [ 'WHERE' => $_Detail ];
                }

                if($_Detail['FIELDS']) {
                    foreach($_Detail['FIELDS'] as $_Field) {
                        $_Fields[] = $_Field === 'COUNT(*)' ? $_Field : sprintf("`%s`", $_Field);
                    }
                }

                foreach($_Fields as $_Index => &$_Field) {
                    if(gettype($_Index) === 'string') {
                        $_Field = sprintf("`%s` AS %s", $_Index, $_Field);
                    }
                }

                if(count($_Fields) > 0) {
                    $_Fields = implode(", ", array_values($_Fields));
                } else {
                    $_Fields = "*";
                }

                if($_Detail['WHERE']) {
                    $_WherePieces = [];

                    foreach($_Detail['WHERE'] as $_Field => $_Value) {
                        if(!$this->Table[$_Name][$_Field]) {
                            continue;
                        }
                        
                        $_Type = $this->Table[$_Name][$_Field]['Type'];
                        
                        if($_Type === 'Date') {
                            $_Value = strtotime($_Value);
                        } else if($_Type === 'Boolean') {
                            $_Value = $_Value ? true : false;
                        } else if(is_array($_Type)) {
                            $_Value = Classify($_Value);
                        } else if($_Type === 'JSON') {
                            $_Value = JsonDecode($_Value);
                        }
                        
                        if(preg_match('/^OR\s+.+$/', $_Field)) {
                            if(preg_match('/^%.+%$/', $_Value)) {
                                $_WherePieces[] = sprintf("OR `%s` LIKE '%s'", $this->Escape(Dasherize(preg_replace('/^OR\s+/', '', $_Field), true)), $this->Escape($_Value));
                            } else {
                                $_WherePieces[] = sprintf("OR `%s`='%s'", $this->Escape(Dasherize(preg_replace('/^OR\s+/', '', $_Field), true)), $this->Escape(Dasherize($_Value, true)));
                            }

                        } else if(preg_match('/(!=|<|>|<=|>=)$/', $_Field)) {
                            $_WherePieces[] = sprintf("`%s` %s '%s'", $this->Escape(Dasherize(preg_replace('/(.+)(!=|<|>|<=|>=)$/', '$1', $_Field), true)), preg_replace('/.+(!=|<|>|<=|>=)$/', '$1', $_Field), $this->Escape(Dasherize($_Value, true)));
                        } else if($_Field === 'Serial') {
                            $_WherePieces[] = sprintf("`serial_id`='%s'", $this->Escape(Dasherize($_Value, true)));
                        } else {
                            if(preg_match('/^%.+%$/', $_Value)) {
                                $_WherePieces[] = sprintf("`%s` LIKE '%s'", $this->Escape(Dasherize($_Field, true)), $this->Escape($_Value));
                            } else {
                                $_WherePieces[] = sprintf("`%s`='%s'", $this->Escape(Dasherize($_Field, true)), $this->Escape(Dasherize($_Value, true)));
                            }
                        }
                    }

                    $_WhereClause .= " WHERE " . str_replace("AND OR", "OR", implode(" AND ", $_WherePieces));
                }

                if($_Detail['ORDER']) {
                    $_OrderPieces = [];

                    foreach($_Detail['ORDER'] as $_Field => $_Value) {
                        if($_Field === 'Serial') {
                            $_OrderPieces[] = sprintf("`serial_id` %s", $_Value ? "DESC" : "ASC");
                        } else {
                            $_OrderPieces[] = sprintf("`%s` %s", $this->Escape(Dasherize($_Field, true)), $_Value ? "DESC" : "ASC");
                        }
                    }

                    $_OrderClause .= " ORDER BY " . implode(', ', $_OrderPieces);
                }

                if($_Detail['LIMIT']) {
                    if(is_array($_Detail['LIMIT'])) {
                        $_LimitClause = sprintf(" LIMIT %s, %s", $_Detail['LIMIT'][0], $_Detail['LIMIT'][1]);
                    } else {
                        $_LimitClause = sprintf(" LIMIT %s", $_Detail['LIMIT']);
                    }
                }

                if($_Detail['GROUP BY']) {
                    $_GroupByClause = sprintf(" GROUP BY `%s`", $_Detail['GROUP BY']);
                }
                
                $_SQL = sprintf("SELECT %s FROM `%s`%s%s%s%s", $_Fields, $this->Escape(Dasherize($_Name, true)), $_WhereClause, $_GroupByClause, $_OrderClause, $_LimitClause);
                
                Write(Framework::Resolve('cache/' . $_Hash . '.sql'), $_SQL);
            } else {
                $_SQL = Read(Framework::Resolve('cache/' . $_Hash . '.sql'));
            }
            
            $_Result = $this->Query($_SQL);
            
            if($_Result) {
                $_Fetched = [];
                
                while($_Fetch = $_Result->fetch_assoc()) {
                    if($_Fetch['serial_id']) {
                        $_Fetched[] = array_combine(
                            array_keys($this->Table[$_Name]),
                            array_values($_Fetch)
                        );
                        
                        continue;
                    }
                    
                    $_Fetched[] = $_Fetch;
                }
                
                foreach($_Fetched as &$_Fetch) {
                    foreach($_Fetch as $_Field => &$_Value) {
                        if(!$this->Table[$_Name][$_Field]) {
                            continue;
                        }
                        
                        $_Type = $this->Table[$_Name][$_Field]['Type'];
                        
                        if($_Type === 'Date') {
                            $_Value = strtotime($_Value);
                        } else if($_Type === 'Boolean') {
                            $_Value = $_Value ? true : false;
                        } else if(is_array($_Type)) {
                            $_Value = Classify($_Value);
                        } else if($_Type === 'JSON') {
                            $_Value = JsonDecode($_Value);
                        }
                    }
                }
                
                if(count($_Fetched) === 1) {
                    $_Fetched = $_Fetched[0];
                }
                
                return $_Fetched;
            }
            
            return null;
        }
        
        public function Update($_Name, $_Detail) {
            try {
                $_SetPieces = [];
                
                if(gettype($_Detail['WHERE']) === 'integer' && $_Detail['WHERE'] > 0 || is_numeric($_Detail['WHERE']) && intval($_Detail['WHERE']) > 0) {
                    $_Detail['WHERE'] = [ 'Serial' => $_Detail['WHERE'] ];
                }
                
                foreach($_Detail['SET'] as $_Field => $_Value) {
                    $_Type = $this->Table[$_Name][$_Field]['Type'];
                    
                    if($_Type === 'Password') {
                        $_ValueClause = sprintf("'%s'", Password($_Value));
                    } else if($_Type === 'Date') {
                        $_ValueClause = sprintf("'%s'", date('Y-m-d H:i:s', $_Value));
                    } else if($_Type === 'Boolean') {
                        $_ValueClause = sprintf("%s", $_Value ? "TRUE" : "FALSE");
                    } else if(is_array($_Type)) {
                        $_ValueClause = sprintf("'%s'", Lowercase($_Value));
                    } else if(preg_match('/Number|Double/', $_Type) && preg_match('/^\+/', $_Value)) {
                        $_ValueClause = sprintf("`%s`+'%s'", $this->Escape(Dasherize($_Field, true)), preg_replace('/^\+/', '', $_Value));
                    } else if(preg_match('/Number|Double/', $_Type) && preg_match('/^-/', $_Value)) {
                        $_ValueClause = sprintf("`%s`-'%s'", $this->Escape(Dasherize($_Field, true)), preg_replace('/^-/', '', $_Value));
                    } else if($_Type === 'JSON') {
                        $_ValueClause = JsonEncode($_Value, JSON_NUMERIC_CHECK);
                    } else {
                        $_ValueClause = sprintf("'%s'", $this->Escape($_Value));
                    }
                    
                    $_SetPieces[] = sprintf("`%s`=%s", $this->Escape(Dasherize($_Field, true)), $_ValueClause);
                }
                
                $_SetClause = implode(", ", $_SetPieces);
                
                $_WherePieces = [];
                
                foreach($_Detail['WHERE'] as $_Field => $_Value) {
                    if($_Field === 'Serial') {
                        $_WherePieces[] = sprintf("`serial_id`='%s'", $this->Escape(Dasherize($_Value, true)));
                    } else {
                        $_WherePieces[] = sprintf("`%s`='%s'", $this->Escape(Dasherize($_Field, true)), $this->Escape(Dasherize($_Value, true)));
                    }
                }
                
                $_WhereClause = implode(" AND ", $_WherePieces);
                
                $_Result = $this->Query(sprintf("UPDATE `%s` SET %s WHERE %s", $this->Escape(Dasherize($_Name, true)), $_SetClause, $_WhereClause));
                
                if($_Result === null || !$this->Commit()) {
                    throw new Exception('Database transaction has failure.');
                }
            } catch(Exception $_Exception) {
                $this->Connection->rollback();
                
                return false;
            }
            
            return true;
        }
        
        public function Insert($_Name, $_Detail) {
            try {
                $_FieldsClause = [
                    "`serial_id`"
                ];
                
                $_Serial = !$_Detail['Serial'] ? $this->Serial() : $_Detail['Serial'];
                
                $_ValuesClause = [
                    sprintf("'%s'", $_Serial)
                ];
                
                foreach($_Detail as $_Field => $_Value) {
                    if($_Field === 'Serial') {
                        continue;
                    }
                    
                    $_FieldsClause[] = sprintf("`%s`", $this->Escape(Dasherize($_Field, true)));
                    
                    $_Type = $this->Table[$_Name][$_Field]['Type'];
                    
                    if($_Type === 'Password') {
                        $_ValuesClause[] = sprintf("'%s'", Password($_Value));
                    } else if($_Type === 'Date') {
                        $_ValuesClause[] = sprintf("'%s'", date('Y-m-d H:i:s', $_Value));
                    } else if($_Type === 'Boolean') {
                        $_ValuesClause[] = sprintf("%s", $_Value ? 'TRUE' : 'FALSE');
                    } else if(is_array($_Type)) {
                        $_ValuesClause[] = sprintf("'%s'", Lowercase($_Value));
                    } else if($_Type === 'JSON') {
                        $_ValuesClause[] = JsonEncode($_Value, JSON_NUMERIC_CHECK);
                    } else {
                        $_ValuesClause[] = sprintf("'%s'", $this->Escape($_Value));
                    }
                }
                
                $_FieldsClause = implode(", ", $_FieldsClause);
                $_ValuesClause = implode(", ", $_ValuesClause);
                
                $_Result = $this->Query(sprintf("INSERT INTO `%s` ( %s ) VALUES ( %s )", $this->Escape(Dasherize($_Name, true)), $_FieldsClause, $_ValuesClause));
                
                if(!$_Result || !$this->Commit()) {
                    throw new Exception('Database transaction has failure.');
                }
            } catch(Exception $_Exception) {
                $this->Connection->rollback();
                
                return false;
            }
            
            return $_Serial;
        }
        
        public function Delete($_Name, $_Detail) {
            try {
                $_WherePieces = [];
                
                if(gettype($_Detail) === 'integer' && $_Detail > 0 || is_numeric($_Detail) && intval($_Detail) > 0) {
                    $_Detail = [ 'Serial' => $_Detail ];
                }
                
                foreach($_Detail as $_Field => $_Value) {
                    if($_Field === 'Serial') {
                        $_WherePieces[] = sprintf("`serial_id`='%s'", $this->Escape(Dasherize($_Value, true)));
                    } else {
                        $_WherePieces[] = sprintf("`%s`='%s'", $this->Escape(Dasherize($_Field, true)), $this->Escape(Dasherize($_Value, true)));
                    }
                }
                
                $_WhereClause = implode(" AND ", $_WherePieces);
                
                $_Result = $this->Query(sprintf("DELETE FROM `%s` WHERE %s", $this->Escape(Dasherize($_Name, true)), $_WhereClause));
                
                if(!$_Result || !$this->Commit()) {
                    throw new Exception('Database transaction has failure.');
                }
            } catch(Exception $_Exception) {
                $this->Connection->rollback();
                
                return false;
            }
            
            return true;
        }
        
        // Abstracted methods
        public function Count($_Name, $_Detail = []) {
            if(gettype($_Detail) === 'integer' && $_Detail > 0 || is_numeric($_Detail) && intval($_Detail) > 0) {
                $_Detail = [ 'WHERE' => [ 'Serial' => $_Detail ] ];
            }
            
            if(is_array($_Detail) && !array_key_exists('FIELDS', $_Detail) && !array_key_exists('WHERE', $_Detail) && !array_key_exists('ORDER', $_Detail) && !array_key_exists('LIMIT', $_Detail) && count($_Detail) > 0) {
                $_Detail = [ 'WHERE' => $_Detail ];
            }
            
            return intval($this->Select($_Name, array_merge($_Detail, [
                'FIELDS' => [ 'COUNT(*)' ]
            ]))['COUNT(*)']);
        }
        
        public function IsExists($_Name, $_Detail = []) {
            return $this->Count($_Name, $_Detail) > 0;
        }
        
        public function UpdateOrInsert($_Name, $_Detail) {
            if($this->IsExists($_Name, $_Detail['WHERE'])) {
                return $this->Update($_Name, $_Detail);
            }
            
            return $this->Insert($_Name, $_Detail['SET']);
        }
        
        // Special methods
        public function Truncate($_Name) {
            try {
                return $this->Query("TRUNCATE TABLE `" . $this->Escape(Dasherize($_Name, true)) . "`");
            } catch(Exception $_Exception) {
                $this->Connection->rollback();
                
                return false;
            }
        }
    }
?>