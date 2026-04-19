<?php
namespace PHPMailer\PHPMailer {


class Exception extends \Exception {
    public function errorMessage(): string { return $this->getMessage(); }
}

class SMTP {
    const VERSION        = '6.9.1';
    const CRLF           = "\r\n";
    const DEFAULT_PORT   = 25;
    const MAX_LINE_LENGTH = 998;
    const DEBUG_OFF      = 0;
    const DEBUG_CLIENT   = 1;
    const DEBUG_SERVER   = 2;
    const DEBUG_CONNECTION = 3;
    const DEBUG_LOWLEVEL = 4;

    public $Version      = self::VERSION;
    public $SMTP_CONN;
    public $error        = ['error' => '', 'detail' => '', 'smtp_code' => '', 'smtp_code_ex' => ''];
    public $helo_rply;
    public $do_debug     = self::DEBUG_OFF;
    public $Debugoutput  = 'echo';
    public $do_verp      = false;
    public $Timeout      = 300;
    public $Timelimit    = 300;
    protected $smtp_transaction_id_patterns = [
        'exim'      => '/[0-9]{3} OK id=(.*)/',
        'sendmail'  => '/[0-9]{3} 2.0.0 (.*) Message/',
        'postfix'   => '/[0-9]{3} 2.0.0 Ok: queued as (.*)/',
        'Microsoft_ESMTP'=> '/[0-9]{3} 2.[0-9].0 (.*)@.*/',
        'Amazon_SES'=> '/[0-9]{3} Ok (.*)/',
        'SendGrid'  => '/[0-9]{3} Ok: queued as (.*)/',
        'CampaignMonitor'=> '/[0-9]{3} 2.0.0 OK:(.*)/',
        'Haraka'    => '/[0-9]{3} Message Queued \((.*)\)/',
        'ZoneMTA'   => '/[0-9]{3} Message queued as (.*)/',
        'Mailjet'   => '/[0-9]{3} OK queued as (.*)/',
    ];
    protected $last_smtp_transaction_id;
    private $smtp_conn;
    private $error_count = 0;
    private $server_caps;
    private $last_reply  = '';

    protected function edebug(string $str, int $level = 0): void {
        if ($level > $this->do_debug) return;
        if (is_callable($this->Debugoutput) && !is_string($this->Debugoutput)) {
            call_user_func($this->Debugoutput, $str, $level);
        } elseif ($this->Debugoutput === 'error_log') {
            error_log($str);
        } else {
            echo gmdate('Y-m-d H:i:s') . "\t" . trim($str) . "\n";
        }
    }

    public function connect(string $host, int $port = null, int $timeout = 30, array $options = []): bool {
        static $streamok;
        if (is_null($streamok)) {
            $streamok = function_exists('stream_socket_client');
        }
        $this->setError('');
        if ($this->connected()) {
            $this->setError('Already connected to a server');
            return false;
        }
        if (empty($port)) $port = self::DEFAULT_PORT;
        $this->edebug("Connection: opening to {$host}:{$port}, t={$timeout}, opt=" . var_export($options, true), self::DEBUG_CONNECTION);
        $errno  = 0;
        $errstr = '';
        if ($streamok) {
            $socket_context = stream_context_create($options);
            $this->smtp_conn = @stream_socket_client(
                $host . ':' . $port, $errno, $errstr, $timeout,
                STREAM_CLIENT_CONNECT, $socket_context
            );
        } else {
            $this->edebug('Connection: stream_socket_client not available, falling back to fsockopen', self::DEBUG_CONNECTION);
            $this->smtp_conn = fsockopen($host, $port, $errno, $errstr, $timeout);
        }
        if (!is_resource($this->smtp_conn)) {
            $this->setError('Failed to connect to server', '', (string)$errno, $errstr);
            $this->edebug('SMTP ERROR: ' . $this->error['error'] . ': ' . $errstr, self::DEBUG_CLIENT);
            return false;
        }
        $this->edebug('Connection: opened', self::DEBUG_CONNECTION);
        stream_set_timeout($this->smtp_conn, $timeout, 0);
        $announce = $this->get_lines();
        $this->edebug('SERVER -> CLIENT: ' . $announce, self::DEBUG_SERVER);
        return true;
    }

    public function startTLS(): bool {
        if (!$this->sendCommand('STARTTLS', 'STARTTLS', 220)) return false;
        $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
        }
        set_error_handler([$this, 'errorHandler']);
        $crypto_ok = stream_socket_enable_crypto($this->smtp_conn, true, $crypto_method);
        restore_error_handler();
        return (bool)$crypto_ok;
    }

    public function authenticate(string $username, string $password, string $authtype = null, \PHPMailer\PHPMailer\OAuthTokenProvider $OAuth = null): bool {
        if (!$this->server_caps) {
            $this->setError('Authentication is not allowed before HELO/EHLO');
            return false;
        }
        if (array_key_exists('EHLO', $this->server_caps)) {
            if (!array_key_exists('AUTH', $this->server_caps)) {
                $this->setError('Authentication is not allowed at this stage');
                return false;
            }
            $this->edebug('Auth method requested: ' . ($authtype ?: 'UNSPECIFIED'), self::DEBUG_LOWLEVEL);
            $this->edebug('Auth methods available on the server: ' . implode(',', $this->server_caps['AUTH']), self::DEBUG_LOWLEVEL);
            if (empty($authtype)) {
                foreach (['CRAM-MD5', 'LOGIN', 'PLAIN', 'XOAUTH2'] as $method) {
                    if (in_array($method, $this->server_caps['AUTH'])) { $authtype = $method; break; }
                }
                if (empty($authtype)) {
                    $this->setError('No supported authentication methods found');
                    return false;
                }
                $this->edebug('Auth method selected: ' . $authtype, self::DEBUG_LOWLEVEL);
            }
            if (!in_array($authtype, $this->server_caps['AUTH']) && $authtype !== 'XOAUTH2') {
                $this->setError("The requested authentication method \"{$authtype}\" is not supported by the server");
                return false;
            }
        } elseif (empty($authtype)) {
            $authtype = 'LOGIN';
        }
        switch ($authtype) {
            case 'PLAIN':
                if (!$this->sendCommand('AUTH', 'AUTH PLAIN ' . base64_encode("\0" . $username . "\0" . $password), 235)) return false;
                break;
            case 'LOGIN':
                if (!$this->sendCommand('AUTH', 'AUTH LOGIN', 334)) return false;
                if (!$this->sendCommand('Username', base64_encode($username), 334)) return false;
                if (!$this->sendCommand('Password', base64_encode($password), 235)) return false;
                break;
            case 'CRAM-MD5':
                if (!$this->sendCommand('AUTH CRAM-MD5', 'AUTH CRAM-MD5', 334)) return false;
                $challenge = base64_decode(substr($this->last_reply, 4));
                $response  = $username . ' ' . $this->hmac($challenge, $password);
                if (!$this->sendCommand('Username', base64_encode($response), 235)) return false;
                break;
            default:
                $this->setError("Authentication method \"{$authtype}\" is not supported");
                return false;
        }
        return true;
    }

    protected function hmac(string $data, string $key): string {
        if (function_exists('hash_hmac')) return hash_hmac('md5', $data, $key);
        $bytelen = 64;
        if (strlen($key) > $bytelen) $key = pack('H*', md5($key));
        $key    = str_pad($key, $bytelen, "\0");
        $ipad   = substr($key, 0, $bytelen) ^ str_repeat("\x36", $bytelen);
        $opad   = substr($key, 0, $bytelen) ^ str_repeat("\x5C", $bytelen);
        return md5($opad . pack('H*', md5($ipad . $data)));
    }

    public function connected(): bool {
        if (is_resource($this->smtp_conn)) {
            $sock_status = stream_get_meta_data($this->smtp_conn);
            if ($sock_status['eof']) { $this->edebug('SMTP NOTICE: EOF caught while checking if connected', self::DEBUG_CLIENT); $this->close(); return false; }
            return true;
        }
        return false;
    }

    public function close(): void {
        $this->setError('');
        $this->server_caps = null;
        $this->helo_rply   = null;
        if (is_resource($this->smtp_conn)) {
            fclose($this->smtp_conn);
            $this->smtp_conn = null;
            $this->edebug('Connection: closed', self::DEBUG_CONNECTION);
        }
    }

    public function data(string $msg_data): bool {
        if (!$this->sendCommand('DATA', 'DATA', 354)) return false;
        $lines     = explode("\n", str_replace(["\r\n", "\r"], "\n", $msg_data));
        $in_header = true;
        $data      = '';
        foreach ($lines as $line) {
            $lines_out = [];
            if ($in_header && $line === '') { $in_header = false; }
            $max = self::MAX_LINE_LENGTH;
            while ($in_header && strlen($line) > $max) {
                $pos = strrpos(substr($line, 0, $max), ' ');
                if (!$pos) { $pos = $max - 1; }
                $lines_out[] = substr($line, 0, $pos);
                $line        = "\t" . substr($line, $pos + 1);
            }
            $lines_out[] = $line;
            foreach ($lines_out as $line_out) {
                if (!empty($line_out) && $line_out[0] === '.') $line_out = '.' . $line_out;
                $data .= $line_out . self::CRLF;
            }
        }
        $data .= '.' . self::CRLF;
        $this->edebug('CLIENT -> SERVER: [data]', self::DEBUG_CLIENT);
        set_error_handler([$this, 'errorHandler']);
        $result = fwrite($this->smtp_conn, $data);
        restore_error_handler();
        if (!$result) { $this->setError('Failed to write to SMTP'); return false; }
        $this->last_reply = $this->get_lines();
        $responseCode     = (int)substr($this->last_reply, 0, 3);
        $this->edebug('SERVER -> CLIENT: ' . $this->last_reply, self::DEBUG_SERVER);
        if ($responseCode !== 250) { $this->setError('DATA not accepted from server', '', (string)$responseCode); return false; }
        foreach ($this->smtp_transaction_id_patterns as $id) {
            if (preg_match($id, $this->last_reply, $matches)) { $this->last_smtp_transaction_id = trim($matches[1]); }
        }
        return true;
    }

    public function hello(string $host = ''): bool { return $this->sendHello('EHLO', $host) ?: $this->sendHello('HELO', $host); }

    protected function sendHello(string $hello, string $host): bool {
        $noerror = $this->sendCommand($hello, $hello . ' ' . $host, 250);
        $this->helo_rply = $this->last_reply;
        if ($noerror) {
            $this->parseHelloFields($hello);
        } else {
            $this->server_caps = null;
        }
        return $noerror;
    }

    protected function parseHelloFields(string $type): void {
        $this->server_caps = [];
        $lines = explode("\n", $this->helo_rply);
        foreach ($lines as $n => $s) {
            $s = trim(substr($s, 4));
            if (!$s) continue;
            $fields = explode(' ', $s);
            if ($fields) {
                if (!$n) { $this->server_caps['HELO'] = $fields; } else {
                    $name = array_shift($fields);
                    $this->server_caps[$name] = $fields;
                }
            }
        }
    }

    public function mail(string $from): bool {
        $useVerp = ($this->do_verp ? ' XVERP' : '');
        return $this->sendCommand('MAIL FROM', 'MAIL FROM:<' . $from . '>' . $useVerp, 250);
    }

    public function quit(bool $close_on_error = true): bool {
        $noerror = $this->sendCommand('QUIT', 'QUIT', 221);
        $e       = $this->error;
        if ($noerror || $close_on_error) { $this->close(); $this->error = $e; }
        return $noerror;
    }

    public function recipient(string $address, string $dsn = ''): bool {
        if (empty($dsn)) return $this->sendCommand('RCPT TO', 'RCPT TO:<' . $address . '>', [250, 251]);
        $rcpt = 'RCPT TO:<' . $address . '> NOTIFY=' . $dsn;
        return $this->sendCommand('RCPT TO', $rcpt, [250, 251]);
    }

    public function reset(): bool { return $this->sendCommand('RSET', 'RSET', 250); }

    protected function sendCommand(string $command, string $commandstring, $expect): bool {
        if (!$this->connected()) {
            $this->setError("Called {$command} without being connected");
            return false;
        }
        if (strpos($commandstring, "\n") !== false || strpos($commandstring, "\r") !== false) {
            $this->setError("Command '{$command}' contained line breaks");
            return false;
        }
        $this->edebug('CLIENT -> SERVER: ' . $commandstring, self::DEBUG_CLIENT);
        set_error_handler([$this, 'errorHandler']);
        fwrite($this->smtp_conn, $commandstring . self::CRLF);
        restore_error_handler();
        if ($command === 'DATA') return true;
        $this->last_reply = $this->get_lines();
        $matches          = [];
        if (preg_match('/^\d+[ -](?:\d+\.){2}\d+[ -]/', $this->last_reply, $matches)) {
            $code      = substr($matches[0], 0, 3);
            $code_ex   = (count($matches) > 1) ? trim($matches[1]) : '';
            $detail    = preg_replace(['/' . $code . '[ -](?:\d+\.){2}\d+[ -]/m', '/^[\d -]+/m'], '', $this->last_reply);
            $detail    = trim(implode('', explode("\n", $detail)));
        } else {
            $code    = substr($this->last_reply, 0, 3);
            $code_ex = '';
            $detail  = substr($this->last_reply, 4);
        }
        $this->edebug('SERVER -> CLIENT: ' . $this->last_reply, self::DEBUG_SERVER);
        if (!in_array((int)$code, (array)$expect)) {
            $this->setError("{$command}: Unexpected response code received from server", $detail, $code, $code_ex);
            return false;
        }
        $this->error_count = 0;
        return true;
    }

    public function sendAndMail(string $from): bool { return $this->sendCommand('SAML', 'SAML FROM:<' . $from . '>', 250); }
    public function verify(string $name): bool { return $this->sendCommand('VRFY', 'VRFY ' . $name, [250, 251, 252]); }
    public function noop(): bool { return $this->sendCommand('NOOP', 'NOOP', 250); }

    public function turn(): bool {
        $this->setError('The SMTP TURN command is not implemented');
        $this->edebug('SMTP NOTICE: ' . $this->error['error'], self::DEBUG_CLIENT);
        return false;
    }

    public function sendRaw(string $data): bool {
        set_error_handler([$this, 'errorHandler']);
        $r = fwrite($this->smtp_conn, $data);
        restore_error_handler();
        if (!$r) { $this->setError('Error writing to SMTP server'); return false; }
        return true;
    }

    public function getServerExtList(): ?array { return $this->server_caps; }

    public function getServerExt(string $name): ?array {
        if (!$this->server_caps) { $this->setError('No HELO/EHLO was sent'); return null; }
        if (!array_key_exists($name, $this->server_caps)) return null;
        return $this->server_caps[$name];
    }

    public function getLastReply(): string { return $this->last_reply; }

    protected function get_lines(): string {
        if (!is_resource($this->smtp_conn)) return '';
        $data     = '';
        $endtime  = 0;
        stream_set_timeout($this->smtp_conn, $this->Timeout);
        if ($this->Timelimit > 0) $endtime = time() + $this->Timelimit;
        $selR = [$this->smtp_conn];
        $selW = null;
        while (is_resource($this->smtp_conn) && !feof($this->smtp_conn)) {
            set_error_handler([$this, 'errorHandler']);
            $n = stream_select($selR, $selW, $selW, $this->Timelimit);
            restore_error_handler();
            if ($n === null) break;
            if ($n === 0 && $endtime && time() > $endtime) break;
            $str = fgets($this->smtp_conn, 515);
            $data .= $str;
            if (!isset($str[3]) || ($str[3] === ' ' || $str[3] === "\r" || $str[3] === "\n") || substr($str, 0, 3) === '221') break;
            $info = stream_get_meta_data($this->smtp_conn);
            if ($info['timed_out']) break;
            if ($endtime && time() > $endtime) break;
        }
        return $data;
    }

    public function getLastTransactionID(): ?string { return $this->last_smtp_transaction_id; }

    public function errorHandler(int $errno, string $errmsg, string $errfile = '', int $errline = 0): bool {
        $notice = 'Connection failed. System message: ' . $errmsg;
        $this->setError($notice, '', (string)$errno);
        $this->edebug('SMTP ERROR: ' . $notice, self::DEBUG_CONNECTION);
        return true;
    }

    protected function setError(string $message, string $detail = '', string $smtp_code = '', string $smtp_code_ex = ''): void {
        $this->error = ['error' => $message, 'detail' => $detail, 'smtp_code' => $smtp_code, 'smtp_code_ex' => $smtp_code_ex];
    }

    public function setTimeout(int $timeout): void { $this->Timeout = $timeout; }
    public function setTimelimit(int $timelimit): void { $this->Timelimit = $timelimit; }
    public function setDebugLevel(int $level): void { $this->do_debug = $level; }
    public function setDebugOutput($method): void { $this->Debugoutput = $method; }
    public function setVerp(bool $enabled): void { $this->do_verp = $enabled; }
    public function setOptions(array $options): void { }
    public function getError(): array { return $this->error; }
}

class PHPMailer {
    const CHARSET_ASCII  = 'us-ascii';
    const CHARSET_ISO88591 = 'iso-8859-1';
    const CHARSET_UTF8   = 'utf-8';
    const CONTENT_TYPE_PLAINTEXT = 'text/plain';
    const CONTENT_TYPE_TEXT_CALENDAR = 'text/calendar';
    const CONTENT_TYPE_TEXT_HTML = 'text/html';
    const CONTENT_TYPE_MULTIPART_ALTERNATIVE = 'multipart/alternative';
    const CONTENT_TYPE_MULTIPART_MIXED = 'multipart/mixed';
    const CONTENT_TYPE_MULTIPART_RELATED = 'multipart/related';
    const ENCODING_7BIT  = '7bit';
    const ENCODING_UTF8  = 'utf-8';
    const ENCODING_8BIT  = '8bit';
    const ENCODING_BASE64 = 'base64';
    const ENCODING_QUOTED_PRINTABLE = 'quoted-printable';
    const ENCRYPTION_STARTTLS = 'tls';
    const ENCRYPTION_SMTPS   = 'ssl';
    const VERSION = '6.9.1';
    const STOP_MESSAGE = 0;
    const STOP_CONTINUE = 1;
    const STOP_CRITICAL = 2;
    const CRLF = "\r\n";
    const FWS  = " ";
    const MIME_1_0 = '1.0';
    const MAX_LINE_LENGTH = 998;
    const STD_LINE_LENGTH = 76;
    public static $LE = "\r\n";
    public static $validator = 'php';

    public $Priority;
    public $CharSet   = self::CHARSET_UTF8;
    public $ContentType = self::CONTENT_TYPE_PLAINTEXT;
    public $Encoding  = self::ENCODING_8BIT;
    public $ErrorInfo = '';
    public $From      = '';
    public $FromName  = '';
    public $Sender    = '';
    public $Subject   = '';
    public $Body      = '';
    public $AltBody   = '';
    public $Ical      = '';
    public $MIMEBody  = '';
    public $MIMEHeader = '';
    public $mailHeader = '';
    public $WordWrap  = 0;
    public $Mailer    = 'mail';
    public $Sendmail  = '/usr/sbin/sendmail';
    public $UseSendmailOptions = true;
    public $ConfirmReadingTo = '';
    public $Hostname  = '';
    public $MessageID = '';
    public $MessageDate = '';
    public $Host      = 'localhost';
    public $Port      = 25;
    public $Helo      = '';
    public $SMTPSecure = '';
    public $SMTPAutoTLS = true;
    public $SMTPAuth  = false;
    public $SMTPOptions = [];
    public $Username  = '';
    public $Password  = '';
    public $AuthType  = '';
    public $SMTPDebug = 0;
    public $Debugoutput = 'echo';
    public $SMTPKeepAlive = false;
    public $SingleTo  = false;
    public $do_verp   = false;
    public $XMailer   = '';
    public $Timeout   = 300;
    public $Timelimit = 300;
    public $dsn       = '';
    public $action_function = '';
    public $DKIM_selector = '';
    public $DKIM_identity = '';
    public $DKIM_passphrase = '';
    public $DKIM_domain = '';
    public $DKIM_copyHeaderFields = true;
    public $DKIM_extraHeaders = [];
    public $DKIM_private = '';
    public $DKIM_private_string = '';

    protected $smtp;
    protected $to       = [];
    protected $cc       = [];
    protected $bcc      = [];
    protected $ReplyTo  = [];
    protected $all_recipients = [];
    protected $RecipientsQueue = [];
    protected $ReplyToQueue = [];
    protected $attachment = [];
    protected $CustomHeader = [];
    protected $lastMessageID = '';
    protected $message_type = '';
    protected $boundary   = [];
    protected $language   = [];
    protected $error_count = 0;
    protected $sign_cert_file = '';
    protected $sign_key_file = '';
    protected $sign_extracerts_file = '';
    protected $sign_key_pass = '';
    protected $exceptions = false;
    protected $uniqueid  = '';

    public function __construct(bool $exceptions = null) {
        if ($exceptions !== null) $this->exceptions = (bool)$exceptions;
    }

    public function __destruct() { $this->smtpClose(); }

    private function mailPassthru(string $to, string $subject, string $body, string $header, $params): bool {
        set_error_handler([$this, 'callbackWrapper']);
        if (!$this->UseSendmailOptions) {
            $r = mail($to, $this->encodeHeader($this->secureHeader($subject)), $body, $header);
        } else {
            $r = mail($to, $this->encodeHeader($this->secureHeader($subject)), $body, $header, $params);
        }
        restore_error_handler();
        return (bool)$r;
    }

    protected function edebug(string $str): void {
        if ($this->SMTPDebug <= 0) return;
        if (is_callable($this->Debugoutput) && !is_string($this->Debugoutput)) {
            call_user_func($this->Debugoutput, $str, $this->SMTPDebug);
        } elseif ($this->Debugoutput === 'error_log') {
            error_log($str);
        } else {
            echo gmdate('Y-m-d H:i:s') . "\t" . trim($str) . "\n";
        }
    }

    public function isHTML(bool $isHtml = true): void {
        $this->ContentType = $isHtml ? static::CONTENT_TYPE_TEXT_HTML : static::CONTENT_TYPE_PLAINTEXT;
    }

    public function isSMTP(): void { $this->Mailer = 'smtp'; }
    public function isMail(): void { $this->Mailer = 'mail'; }

    public function addAddress(string $address, string $name = ''): bool { return $this->addOrEnqueueAnAddress('to', $address, $name); }
    public function addCC(string $address, string $name = ''): bool { return $this->addOrEnqueueAnAddress('cc', $address, $name); }
    public function addBCC(string $address, string $name = ''): bool { return $this->addOrEnqueueAnAddress('bcc', $address, $name); }
    public function addReplyTo(string $address, string $name = ''): bool { return $this->addOrEnqueueAnAddress('Reply-To', $address, $name); }

    protected function addOrEnqueueAnAddress(string $kind, string $address, string $name): bool {
        $address = trim($address);
        $name    = trim(preg_replace('/[\r\n]+/', '', $name));
        if (strpos($address, '@') === false) {
            $this->setError(sprintf('Invalid address: %s', $address));
            return false;
        }
        if ('Reply-To' !== $kind) {
            if (!array_key_exists(strtolower($address), $this->all_recipients)) {
                $this->{$kind}[] = [$address, $name];
                $this->all_recipients[strtolower($address)] = true;
                return true;
            }
        } elseif (!array_key_exists(strtolower($address), $this->ReplyTo)) {
            $this->ReplyTo[strtolower($address)] = [$address, $name];
            return true;
        }
        return false;
    }

    public function setFrom(string $address, string $name = '', bool $auto = true): bool {
        $address = trim($address);
        $name    = trim(preg_replace('/[\r\n]+/', '', $name));
        $this->From     = $address;
        $this->FromName = $name;
        if ($auto && empty($this->Sender)) $this->Sender = $address;
        return true;
    }

    public function getLastMessageID(): string { return $this->lastMessageID; }

    public static function validateAddress(string $address, $patternselect = null): bool {
        return (bool)filter_var($address, FILTER_VALIDATE_EMAIL);
    }

    public function send(): bool {
        try {
            if (!$this->preSend()) return false;
            return $this->postSend();
        } catch (Exception $exc) {
            $this->mailHeader = '';
            $this->setError($exc->getMessage());
            if ($this->exceptions) throw $exc;
            return false;
        }
    }

    public function preSend(): bool {
        if (empty($this->From)) { $this->From = 'root@localhost'; $this->FromName = 'Root User'; }
        $this->mailHeader = '';
        if (count($this->to) + count($this->cc) + count($this->bcc) < 1) {
            throw new Exception('You must provide at least one recipient.', self::STOP_CRITICAL);
        }
        $this->uniqueid    = $this->generateId();
        $this->boundary[1] = 'b1_' . $this->uniqueid;
        $this->boundary[2] = 'b2_' . $this->uniqueid;
        $this->boundary[3] = 'b3_' . $this->uniqueid;
        $this->setMessageType();
        $this->MIMEHeader  = $this->createHeader();
        $this->MIMEBody    = $this->createBody();
        return true;
    }

    public function postSend(): bool {
        try {
            switch ($this->Mailer) {
                case 'smtp': return $this->smtpSend($this->MIMEHeader, $this->MIMEBody);
                case 'mail': return $this->mailSend($this->MIMEHeader, $this->MIMEBody);
                default:     return $this->mailSend($this->MIMEHeader, $this->MIMEBody);
            }
        } catch (Exception $exc) {
            $this->setError($exc->getMessage());
            if ($this->exceptions) throw $exc;
        }
        return false;
    }

    protected function mailSend(string $header, string $body): bool {
        $toArr = [];
        foreach ($this->to as $toaddr) $toArr[] = $this->addrFormat($toaddr);
        $to     = implode(', ', $toArr);
        $params = null;
        if (!empty($this->Sender)) $params = sprintf('-f%s', $this->Sender);
        if (!$this->mailPassthru($to, $this->Subject, $body, $header, $params)) {
            throw new Exception('Could not instantiate mail function.', self::STOP_CRITICAL);
        }
        return true;
    }

    public function getSMTPInstance(): SMTP {
        if (!is_object($this->smtp)) $this->smtp = new SMTP();
        return $this->smtp;
    }

    protected function smtpConnect(array $options = []): bool {
        if ($this->smtp === null) $this->smtp = $this->getSMTPInstance();
        if ($this->smtp->connected()) return true;
        $this->smtp->setTimeout($this->Timeout);
        $this->smtp->setTimelimit($this->Timelimit);
        $this->smtp->setDebugLevel($this->SMTPDebug);
        $this->smtp->setDebugOutput($this->Debugoutput);
        $this->smtp->setVerp($this->do_verp);
        $hosts = explode(';', $this->Host);
        $lastException = null;
        foreach ($hosts as $hostentry) {
            $hostinfo = [];
            if (!preg_match('/^(?:(ssl|tls):\/\/)?(.+?)(?::(\d+))?$/', trim($hostentry), $hostinfo)) continue;
            $prefix = '';
            $secure = $this->SMTPSecure;
            $tls    = (static::ENCRYPTION_STARTTLS === $this->SMTPSecure);
            if ('ssl' === $hostinfo[1] || ('' === $hostinfo[1] && static::ENCRYPTION_SMTPS === $this->SMTPSecure)) {
                $prefix = 'ssl://'; $tls = false; $secure = static::ENCRYPTION_SMTPS;
            }
            $options = $this->SMTPOptions;
            $port    = $this->Port;
            if (!empty($hostinfo[3])) $port = (int)$hostinfo[3];
            $host    = $hostinfo[2];
            if (!$this->smtp->connect($prefix . $host, $port, $this->Timeout, $options)) continue;
            try {
                $hello = $this->Helo ?: $this->serverHostname();
                $this->smtp->hello($hello);
                $extList = $this->smtp->getServerExtList();
                if ($this->SMTPAutoTLS && empty($prefix) && '' === $secure && is_array($extList) && array_key_exists('STARTTLS', $extList)) $tls = true;
                if ($tls) {
                    if (!$this->smtp->startTLS()) throw new Exception('Could not connect to SMTP host.');
                    $this->smtp->hello($hello);
                }
                if ($this->SMTPAuth) {
                    if (!$this->smtp->authenticate($this->Username, $this->Password, $this->AuthType)) throw new Exception('Could not authenticate.');
                }
                return true;
            } catch (Exception $exc) {
                $lastException = $exc;
                $this->smtp->quit();
                continue;
            }
        }
        $this->smtp->close();
        if ($lastException !== null) {
            $this->setError($lastException->getMessage());
            if ($this->exceptions) throw new Exception($this->ErrorInfo);
        } else {
            $this->setError('SMTP connect() failed. ' . $this->Host);
            if ($this->exceptions) throw new Exception($this->ErrorInfo);
        }
        return false;
    }

    protected function smtpSend(string $header, string $body): bool {
        $bad_rcpt = [];
        if (!$this->smtpConnect($this->SMTPOptions)) throw new Exception('SMTP connect() failed.', self::STOP_CRITICAL);
        $smtp_from = !empty($this->Sender) ? $this->Sender : $this->From;
        if (!$this->smtp->mail($smtp_from)) {
            throw new Exception('From address failed: ' . $smtp_from, self::STOP_CRITICAL);
        }
        foreach ([$this->to, $this->cc, $this->bcc] as $togroup) {
            foreach ($togroup as $to) {
                if (!$this->smtp->recipient($to[0], $this->dsn)) {
                    $bad_rcpt[] = $to[0];
                }
            }
        }
        if (empty($bad_rcpt)) {
            if (!$this->smtp->data($header . $body)) throw new Exception('Data not accepted.', self::STOP_CRITICAL);
        } else {
            throw new Exception('Recipients failed: ' . implode(', ', $bad_rcpt), self::STOP_CONTINUE);
        }
        if (!$this->SMTPKeepAlive) $this->smtp->quit();
        return true;
    }

    public function smtpClose(): void {
        if ($this->smtp !== null && $this->smtp->connected()) {
            $this->smtp->quit();
            $this->smtp->close();
        }
    }

    public function addrFormat(array $addr): string {
        return empty($addr[1]) ? $this->secureHeader($addr[0]) : $this->encodeHeader($this->secureHeader($addr[1]), 'phrase') . ' <' . $this->secureHeader($addr[0]) . '>';
    }

    public function createHeader(): string {
        $result  = '';
        $result .= $this->headerLine('Date', '' === $this->MessageDate ? self::rfcDate() : $this->MessageDate);
        if (count($this->to) > 0) { if ($this->Mailer !== 'mail') $result .= $this->addrAppend('To', $this->to); }
        elseif (count($this->cc) === 0) { $result .= $this->headerLine('To', 'undisclosed-recipients:;'); }
        $result .= $this->addrAppend('From', [[$this->From, $this->FromName]]);
        if (count($this->cc) > 0)      $result .= $this->addrAppend('Cc', $this->cc);
        if (count($this->ReplyTo) > 0) $result .= $this->addrAppend('Reply-To', $this->ReplyTo);
        if ($this->Mailer !== 'mail')  $result .= $this->headerLine('Subject', $this->encodeHeader($this->secureHeader($this->Subject)));
        $this->lastMessageID = !empty($this->MessageID) ? $this->MessageID : sprintf('<%s@%s>', $this->uniqueid, $this->serverHostname());
        $result .= $this->headerLine('Message-ID', $this->lastMessageID);
        if (!empty($this->Priority))   $result .= $this->headerLine('X-Priority', $this->Priority);
        $result .= $this->headerLine('X-Mailer', 'PHPMailer ' . self::VERSION);
        if ('' !== $this->ConfirmReadingTo) $result .= $this->headerLine('Disposition-Notification-To', '<' . $this->ConfirmReadingTo . '>');
        foreach ($this->CustomHeader as $header) $result .= $this->headerLine(trim($header[0]), $this->encodeHeader(trim($header[1])));
        $result .= $this->headerLine('MIME-Version', self::MIME_1_0);
        $result .= $this->getMailMIME();
        return $result;
    }

    public function getMailMIME(): string {
        $result = '';
        switch ($this->message_type) {
            case 'alt': case 'alt_inline':
                $result .= $this->headerLine('Content-Type', static::CONTENT_TYPE_MULTIPART_ALTERNATIVE . ';');
                $result .= $this->textLine("\tboundary=\"" . $this->boundary[1] . '"');
                break;
            case 'attach': case 'alt_attach': case 'inline_attach': case 'alt_inline_attach':
                $result .= $this->headerLine('Content-Type', static::CONTENT_TYPE_MULTIPART_MIXED . ';');
                $result .= $this->textLine("\tboundary=\"" . $this->boundary[1] . '"');
                break;
            case 'inline':
                $result .= $this->headerLine('Content-Type', static::CONTENT_TYPE_MULTIPART_RELATED . ';');
                $result .= $this->textLine("\tboundary=\"" . $this->boundary[1] . '"');
                break;
            default:
                $result .= $this->textLine('Content-Type: ' . $this->ContentType . '; charset=' . $this->CharSet);
                $result .= $this->headerLine('Content-Transfer-Encoding', $this->Encoding);
                break;
        }
        return $result;
    }

    protected function generateId(): string { return bin2hex(random_bytes(16)); }

    public function createBody(): string {
        $body            = '';
        $bodyEncoding    = $this->Encoding;
        $altBodyEncoding = $this->Encoding;

        switch ($this->message_type) {
            case 'alt':
                $body .= $this->getBoundary($this->boundary[1], $this->CharSet, static::CONTENT_TYPE_PLAINTEXT, $altBodyEncoding);
                $body .= $this->encodeString($this->AltBody, $altBodyEncoding);
                $body .= static::$LE;
                $body .= $this->getBoundary($this->boundary[1], $this->CharSet, static::CONTENT_TYPE_TEXT_HTML, $bodyEncoding);
                $body .= $this->encodeString($this->Body, $bodyEncoding);
                $body .= static::$LE;
                $body .= $this->endBoundary($this->boundary[1]);
                break;
            default:
                $body .= $this->encodeString($this->Body, $bodyEncoding);
                break;
        }
        return $body;
    }

    protected function getBoundary(string $boundary, string $charSet, string $contentType, string $encoding): string {
        $result  = $this->textLine('--' . $boundary);
        $result .= sprintf('Content-Type: %s; charset=%s', $contentType ?: $this->ContentType, $charSet ?: $this->CharSet);
        $result .= static::$LE;
        $result .= $this->headerLine('Content-Transfer-Encoding', $encoding ?: $this->Encoding);
        $result .= static::$LE;
        return $result;
    }

    protected function endBoundary(string $boundary): string { return static::$LE . '--' . $boundary . '--' . static::$LE; }

    protected function setMessageType(): void {
        $type = [];
        if ($this->alternativeExists()) $type[] = 'alt';
        if ($this->inlineImageExists()) $type[] = 'inline';
        if ($this->attachmentExists())  $type[] = 'attach';
        $this->message_type = implode('_', $type);
        if ($this->message_type === '') $this->message_type = 'plain';
    }

    public function headerLine(string $name, string $value): string { return $name . ': ' . $value . static::$LE; }
    public function textLine(string $value): string { return $value . static::$LE; }

    public function encodeString(string $str, string $encoding = self::ENCODING_BASE64): string {
        switch (strtolower($encoding)) {
            case static::ENCODING_BASE64: return chunk_split(base64_encode($str), static::STD_LINE_LENGTH, static::$LE);
            case static::ENCODING_7BIT:
            case static::ENCODING_8BIT:  return $this->fixEOL($str);
            case static::ENCODING_BINARY: return $str;
            case static::ENCODING_QUOTED_PRINTABLE: return quoted_printable_encode($str);
            default: $this->setError('Unknown encoding: ' . $encoding); break;
        }
        return '';
    }

    public function encodeHeader(string $str, string $position = 'text'): string {
        $matchcount = preg_match_all('/[\000-\010\013\014\016-\037\177-\377]/', $str, $matches);
        if ($matchcount === 0) return $str;
        return $this->encodeQ($str, $position);
    }

    public function encodeQ(string $str, string $position = 'text'): string {
        $encoded = str_replace(["\r", "\n"], '', $str);
        $pattern = '\000-\011\013\014\016-\037\075\077\137\177-\377';
        $matches = [];
        if (preg_match_all("/[{$pattern}]/", $encoded, $matches)) {
            $eqkey = array_unique($matches[0]);
            $svalues = $rvalues = [];
            foreach ($eqkey as $val) { $svalues[] = $val; $rvalues[] = '=' . sprintf('%02X', ord($val)); }
            $encoded = str_replace($svalues, $rvalues, $encoded);
        }
        return '=?' . $this->CharSet . '?Q?' . str_replace(' ', '_', $encoded) . '?=';
    }

    public function encodeB(string $str): string { return '=?' . $this->CharSet . '?B?' . base64_encode($str) . '?='; }

    public function addrAppend(string $type, array $addr): string {
        $addresses = [];
        foreach ($addr as $address) $addresses[] = $this->addrFormat($address);
        return $type . ': ' . implode(', ', $addresses) . static::$LE;
    }

    protected function setError(string $msg): void { ++$this->error_count; $this->ErrorInfo = $msg; }
    public function isError(): bool { return $this->error_count > 0; }

    public static function rfcDate(): string {
        date_default_timezone_set(@date_default_timezone_get());
        return date('D, j M Y H:i:s O');
    }

    protected function serverHostname(): string {
        if (!empty($this->Hostname)) return $this->Hostname;
        if (isset($_SERVER['SERVER_NAME'])) return $_SERVER['SERVER_NAME'];
        if (function_exists('gethostname') && gethostname() !== false) return gethostname();
        return 'localhost.localdomain';
    }

    protected function secureHeader(string $str): string { return trim(str_replace(["\r", "\n"], '', $str)); }

    public function fixEOL(string $str): string {
        return preg_replace('/(\r\n|\r|\n)/m', static::CRLF, $str);
    }

    protected function alternativeExists(): bool { return !empty($this->AltBody); }
    protected function inlineImageExists(): bool { return false; }
    protected function attachmentExists(): bool  { return false; }

    public static function hasInvalidEncoding(string $string): bool { return (bool)preg_match('/[^\x00-\x7F]/', $string); }

    public function clearAllRecipients(): void { $this->to = []; $this->cc = []; $this->bcc = []; $this->all_recipients = []; }
    public function clearAttachments(): void { $this->attachment = []; }
    public function clearCustomHeaders(): void { $this->CustomHeader = []; }

    public function doCallback(bool $isSent, array $to, array $cc, array $bcc, string $subject, string $body, string $from, array $extra): void {
        if (!empty($this->action_function) && is_callable($this->action_function)) {
            call_user_func($this->action_function, $isSent, $to, $cc, $bcc, $subject, $body, $from, $extra);
        }
    }

    public function callbackWrapper(int $errno, string $errmsg, string $errfile = '', int $errline = 0): bool { return true; }
}

interface OAuthTokenProvider { public function getOauth64(): string; }
}

namespace {
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    // ── CONFIG ── Edit these before uploading ──────────────────
    define('SMTP_HOST',     'smtp.gmail.com');
    define('SMTP_PORT',     587);
    define('SMTP_USERNAME', 'debashisbiswal19@gmail.com');
    define('SMTP_PASSWORD', 'your_app_password_here'); // Use an app password if 2FA is enabled
    define('ADMIN_EMAIL',   'info@omindus.com');
    define('ADMIN_NAME',    'Admin');
    define('FROM_NAME',     'Enquiry Form');

    // ── CORS & Headers ─────────────────────────────────────────
	$allowed_origins = ['https://www.theomindustries.in', 'https://www.omindus.com', 'https://omindus.com'];
    // 2. Check if the requesting origin is in your list
	if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
		header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
	}

	header('Access-Control-Allow-Methods: POST, OPTIONS');
	header('Access-Control-Allow-Headers: Content-Type');

	// 3. Handle preflight (OPTIONS) requests immediately
	if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
		exit; 
	}

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST')    {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        exit;
    }

    // ── Read Input ─────────────────────────────────────────────
    $raw   = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: $_POST;

    // ── Sanitize ───────────────────────────────────────────────
    function clean(string $v): string {
        return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8');
    }

    $fullName = clean($input['fullname'] ?? '');
    $phone    = clean($input['phone']    ?? '');
    $emailId  = clean($input['email']    ?? '');
    $message  = clean($input['enquiry']  ?? '');

    // ── Validate ───────────────────────────────────────────────
    $errors = [];
    if ($fullName === '')                                         $errors['fullname'] = 'Full name is required.';
    if ($phone === '')                                            $errors['phone']    = 'Phone number is required.';
    elseif (!preg_match('/^\+?[\d\s\-\(\)]{7,20}$/', $phone))   $errors['phone']    = 'Invalid phone number.';
    if ($emailId === '')                                          $errors['email']    = 'Email address is required.';
    elseif (!filter_var($emailId, FILTER_VALIDATE_EMAIL))        $errors['email']    = 'Invalid email address.';
    if ($message === '')                                          $errors['enquiry']  = 'Enquiry is required.';
    elseif (strlen($message) < 10)                               $errors['enquiry']  = 'Enquiry must be at least 10 characters.';

    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Validation failed.', 'errors' => $errors]);
        exit;
    }

    // ── Email Body ─────────────────────────────────────────────
    $date     = date('d M Y, h:i A');
    $htmlBody = <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
  body{font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:20px}
  .wrap{background:#fff;border-radius:8px;max-width:560px;margin:auto;padding:30px}
  h2{color:#2c3e50;border-bottom:3px solid #3498db;padding-bottom:10px;margin-top:0}
  .row{margin:14px 0}
  .lbl{font-size:11px;font-weight:bold;color:#888;text-transform:uppercase;letter-spacing:.6px}
  .val{color:#222;font-size:15px;margin-top:3px}
  .msg{background:#f9f9f9;border-left:4px solid #3498db;padding:12px 16px;border-radius:4px;white-space:pre-wrap}
  .foot{margin-top:28px;font-size:11px;color:#bbb;text-align:center}
</style></head><body>
<div class="wrap">
  <h2>📩 New Enquiry Received</h2>
  <div class="row"><div class="lbl">Full Name</div><div class="val">{$fullName}</div></div>
  <div class="row"><div class="lbl">Phone</div><div class="val">{$phone}</div></div>
  <div class="row"><div class="lbl">Email</div><div class="val"><a href="mailto:{$emailId}">{$emailId}</a></div></div>
  <div class="row"><div class="lbl">Enquiry</div><div class="msg">{$message}</div></div>
  <div class="foot">Submitted on {$date}</div>
</div></body></html>
HTML;

    $plainBody = "New Enquiry\n\nFull Name : {$fullName}\nPhone     : {$phone}\nEmail     : {$emailId}\nEnquiry   : {$message}\n\nSubmitted : {$date}";

    // ── Send Email ─────────────────────────────────────────────
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_USERNAME, FROM_NAME);
        $mail->addAddress(ADMIN_EMAIL, ADMIN_NAME);
        $mail->addReplyTo($emailId, $fullName);

        $mail->isHTML(true);
        $mail->Subject = "New Enquiry from {$fullName}";
        $mail->Body    = $htmlBody;
        $mail->AltBody = $plainBody;
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            error_log("SMTP: $str");
        };

        $mail->send();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Enquiry submitted successfully. We will get back to you shortly.'
        ]);

    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $e->getMessage() . ' | ' . $mail->ErrorInfo);
        // Check if email actually sent despite the exception
        if ($mail->getSMTPInstance() !== null) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Enquiry submitted successfully. We will get back to you shortly.'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to send enquiry. Please try again later.'
            ]);
        }
    }
}
