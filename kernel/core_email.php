<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

if ( defined('CORE_EMAIL_LOADED') )
        die('Scripting error');

define('CORE_EMAIL_LOADED', True);

class mailer
{
        var $msg, $subject, $headers;
        var $addresses, $reply, $from;

        var $tpl_msg = array();

        function mailer()
        {
                $this->reset();
                $this->reply = $this->from = '';
        }

        // Resets all the data (address, template file, etc etc to default
        function reset()
        {
                $this->addresses = array();
                $this->vars = $this->msg = $this->extra_headers = '';
        }

        // Sets an email address to send to
        function email_address($address)
        {
                $this->addresses['to'] = trim($address);
        }

        function cc($address)
        {
                $this->addresses['cc'][] = trim($address);
        }

        function bcc($address)
        {
                $this->addresses['bcc'][] = trim($address);
        }

        function replyto($address)
        {
                $this->reply = trim($address);
        }

        function from($address)
        {
                $this->from = trim($address);
        }

        // set up subject for mail
        function set_subject($subject = '')
        {
                $this->subject = trim(preg_replace('#[\n\r]+#s', '', $subject));
        }

        // set up extra mail headers
        function headers($headers)
        {
                $this->headers .= trim($headers) . "\n";
        }

        function use_template($template, $lang='')
        {
                global $QF_Config;

                if (trim($template) == '')
                {
                        trigger_error('mailer: template is not set', 256);
                }

                if (trim($lang) == '')
                {
                        $lang = $QF_Config['def_lang'];
                }

                if (empty($this->tpl_msg[$lang . $template]))
                {
                        $tpl_file = 'langs/'.$lang.'/mtemplates/'.$template.'.tpl';

                        if (!@file_exists($tpl_file))
                        {
                                $tpl_file = 'langs/'.$QF_Config['def_lang'].'/mtemplates/'.$template.'.tpl';

                                if (!@file_exists($tpl_file))
                                {
                                        trigger_error('mailer: template is missing: '.$tpl_file, 256);
                                }
                        }

                        if (!($fd = @fopen($tpl_file, 'r')))
                        {
                                trigger_error('mailer: template is corrupted: '.$tpl_file, 256);
                        }

                        $this->tpl_msg[$lang . $template] = fread($fd, filesize($tpl_file));
                        fclose($fd);
                }

                $this->msg = $this->tpl_msg[$lang . $template];

                return true;
        }

        // assign variables
        function assign_vars($vars)
        {
                $this->vars = (empty($this->vars)) ? $vars : $this->vars . $vars;
        }

        // Send the mail out to the recipients set previously in var $this->address
        function send()
        {
                global $QF_Config, $lang, $QF_DBase;

            // Escape all quotes, else the eval will fail.
                $this->msg = str_replace ("'", "\'", $this->msg);
                $this->msg = preg_replace('#\{([a-z0-9\-_]*?)\}#is', "' . $\\1 . '", $this->msg);

                // Set vars
                reset ($this->vars);
                while (list($key, $val) = each($this->vars))
                {
                        $$key = $val;
                }

                eval("\$this->msg = '$this->msg';");

                // Clear vars
                reset ($this->vars);
                while (list($key, $val) = each($this->vars))
                {
                        unset($$key);
                }

                // We now try and pull a subject from the email body ... if it exists,
                // do this here because the subject may contain a variable
                $drop_header = '';
                $match = array();
                if (preg_match('#^(Subject:(.*?))$#m', $this->msg, $match))
                {
                        $this->subject = (trim($match[2]) != '') ? trim($match[2]) : (($this->subject != '') ? $this->subject : 'No Subject');
                        $drop_header .= '[\r\n]*?' . preg_quote($match[1], '#');
                }
                else
                {
                        $this->subject = (($this->subject != '') ? $this->subject : 'No Subject');
                }

                if (preg_match('#^(Charset:(.*?))$#m', $this->msg, $match))
                {
                        $this->encoding = (trim($match[2]) != '') ? trim($match[2]) : trim($lang['ENCODING']);
                        $drop_header .= '[\r\n]*?' . preg_quote($match[1], '#');
                }
                else
                {
                        $this->encoding = trim($lang['ENCODING']);
                }

                if ($drop_header != '')
                {
                        $this->msg = trim(preg_replace('#' . $drop_header . '#s', '', $this->msg));
                }

                $to = $this->addresses['to'];

                $cc = (count($this->addresses['cc'])) ? implode(', ', $this->addresses['cc']) : '';
                $bcc = (count($this->addresses['bcc'])) ? implode(', ', $this->addresses['bcc']) : '';


                // Build header
                $this->from = ($this->from != '') ? "From: $this->from\n" : 'From: =?'.$this->encoding.'?B?'.base64_encode($QF_Config['site_name']).'?= <'. $QF_Config['site_mail'] . ">\n";
                $this->subject = '=?'.$this->encoding.'?B?'.base64_encode($this->subject).'?=';
                $this->headers = (($this->reply != '') ? "Reply-to: $this->reply_to\n" : '') . $this->from . "Return-Path: " . $QF_Config['site_mail'] . "\nMessage-ID: <" . md5(uniqid(time())) . "@" . $QF_Config['server_name'] . ">\nMIME-Version: 1.0\nContent-Type: text/plain; charset=" . $this->encoding . "\nContent-Transfer-Encoding: 8bit\nDate: " . date('r', time()) . "\nX-Priority: 3\nX-MSMail-Priority: Normal\nX-Mailer: QuickFox\nX-MimeOLE: QuickFox\n" . $this->headers . (($cc != '') ? "Cc: $cc\n" : '')  . (($bcc != '') ? "Bcc: $bcc\n" : '');

                // Send message ... removed $this->encode() from subject for time being
                        $empty_to_header = ($to == '') ? TRUE : FALSE;
                        $to = ($to == '') ? (($QF_Config['sendmail_fix']) ? ' ' : 'Undisclosed-recipients:;') : $to;

                        $result = $QF_Config['sendmail_smtp']
                            ? $this->MailSMTP($to, $this->subject, preg_replace("#(?<!\r)\n#s", "\n", $this->msg), $this->headers)
                            : mail($to, $this->subject, preg_replace("#(?<!\r)\n#s", "\n", $this->msg), $this->headers);

                        if (!$result && !$QF_Config['sendmail_fix'] && $empty_to_header)
                        {
                                $to = ' ';

                                $sql = "REPLACE INTO {DBKEY}config VALUES ('', 'sendmail_fix', '1')";
                                if (!$QF_DBase->sql_query($sql))
                                {
                                        trigger_error('mailer: Unable to update config table', 256);
                                }

                                $QF_Config['sendmail_fix'] = 1;
                                $result = mail($to, $this->subject, preg_replace("#(?<!\r)\n#s", "\n", $this->msg), $this->headers);
                        }

                // Did it work?
                if (!$result)
                {
                        trigger_error('mailer: Failed sending email: '.$result, 256);
                }

                return true;
        }
        
        function MailSMTP($to, $subject, $message, $headers)
        {
            global $QF_Config;

            $connect = fsockopen ('127.0.0.1', 25, $errno, $errstr, 30); 
            if (!$connect)
                return false;
            fwrite($connect, "HELO 127.0.0.1\r\n"); 
            fwrite($connect, "MAIL FROM: <".$QF_Config['site_mail'].">\n");
            fwrite($connect, "RCPT TO: <$to>\n");
            fwrite($connect, "DATA\r\n"); 
            fwrite($connect, "To: <$to>\n");
            fwrite($connect, "Subject: $subject\n"); 
            $headers = explode("\n", $headers);
            foreach ($headers as $header)
                if ($header) fwrite($connect, $header."\n"); 
            fwrite($connect, "\n\n"); 
            fwrite($connect, $message." \r\n"); 
            fwrite($connect, ".\r\n"); 
            fwrite($connect, "RSET\r\n");
            fwrite($connect, "QUIT\r\n");
            $resp = '';
            while(!feof($connect))
                $resp.= fread($connect, 1024);
            fclose($connect);
            return true;
        }


} // class emailer

?>
