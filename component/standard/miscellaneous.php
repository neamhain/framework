<?php
    /**
     * Library Miscellaneous
     * Miscellaneous functions
     **/
    
    if(!defined('FRAMEWORK')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
        exit;
    }
    
    function Flatten($_Data) {
        return base64_encode(gzcompress(serialize($_Data), 9));
    }
    
    function Enlarge($_Data) {
        return unserialize(gzuncompress(base64_decode($_Data)));
    }

    function Sendmail($_From, $_To, $_Subject, $_Context, $_AdditionalHeaders = []) {
        $_To['Name'] = '=?UTF-8?B?' . base64_encode($_To['Name']) . '?=';
        $_From['Name'] = '=?UTF-8?B?' . base64_encode($_From['Name']) . '?=';
        $_Subject = '=?UTF-8?B?' . base64_encode($_Subject) . '?=';
        
        $_Headers = array_merge([
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/html; charset=utf-8',
            'From' => $_From['Name'] . ' <' . $_From['Mail'] . '>',
            'Reply-To' => $_From['Name'] . ' <' . $_From['Mail'] . '>',
            'Return-Path' => $_From['Name'] . ' <' . $_From['Mail'] . '>',
            'X-Priority' => '1 (Highest)',
            'X-MSMail-Priority' => 'High',
            'Importance' => 'High'
        ], $_AdditionalHeaders);
        
        $_HeadersText = [];
        
        foreach($_Headers as $_Key => $_Value) {
            $_HeadersText[] = $_Key . ': ' . $_Value;
        }
        
        $_Headers = implode("\n", $_HeadersText);
        
        return mail($_To['Name'] . ' <' . $_To['Mail'] . '>', $_Subject, $_Context, $_Headers);
    }

    function SendmailSMTP($_SMTPSetting, $_From, $_To, $_Subject, $_Context, $_AdditionalHeaders = []) {
        Import(Framework::Resolve('component/phpmailer/class.phpmailer.php', true));
        Import(Framework::Resolve('component/phpmailer/class.smtp.php', true));
        
        $_Mailer = new PHPMailer;
        
        $_Mailer->CharSet = 'UTF-8';
        $_Mailer->isSMTP();
        $_Mailer->Host = $_SMTPSetting['Host'];
        $_Mailer->SMTPAuth = true;
        $_Mailer->Username = $_SMTPSetting['Username'];
        $_Mailer->Password = $_SMTPSetting['Password'];
        $_Mailer->SMTPSecure = Lowercase($_SMTPSetting['Secure']);
        $_Mailer->Port = $_SMTPSetting['Port'];
        $_Mailer->setFrom($_From['Mail'], $_From['Name']);
        $_Mailer->addReplyTo($_From['Mail'], $_From['Name']);
        $_Mailer->addBCC($_From['Mail'], $_From['Name']);
        $_Mailer->addAddress($_To['Mail'], $_To['Name']);
        $_Mailer->Subject = $_Subject;
        $_Mailer->msgHTML($_Context);
        $_Mailer->XMailer = '';
        $_Mailer->Priority = 1;
        $_Mailer->AddCustomHeader('X-MSMail-Priority', 'High');
        $_Mailer->AddCustomHeader('Importance', 'High');
        
        foreach($_AdditionalHeaders as $_Key => $_Value) {
            $_Mailer->AddCustomHeader($_Key, $_Value);
        }
        
        return $_Mailer->send() ? true : false;
    }
    
    function ParseURL($_URL) {
        $_EncodedURL = preg_replace_callback('%[^:/@?&=#]+%usD', function($_Matches) {
            return urlencode($_Matches[0]);
        }, $_URL);
        
        $_Parts = parse_url($_EncodedURL);
        
        if($_Parts === false) {
            return null;
        }
        
        foreach($_Parts as $_Key => $_Value) {
            $_Parts[$_Key] = urldecode($_Value);
        }
        
        return $_Parts;
    }
?>