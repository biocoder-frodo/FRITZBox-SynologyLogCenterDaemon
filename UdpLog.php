<?
class UdpLog 
{
    private $addr;
    private $port;
    private $sock;

    private $facility;
    private $hostname = '-';
    private $appname = '-';
    private $procid = '-';
    private $msgid = '-';

    public function __construct(string $addr, int $port = 514)
    {
        $this->addr = $addr;
        $this->port = $port;
        $this->sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    }

    public function facility(int $facility) : self
    {
        $this->facility = $facility << 2;
        return $this;
    }

    public function hostname(string $hostname) : self
    {
        $this->hostname = $hostname;
        return $this;
    }

    public function appname(string $appname) : self
    {
        $this->appname = $appname;
        return $this;
    }

    public function procid(string $procid) : self
    {
        $this->procid = $procid;
        return $this;
    }

    public function msgid(string $msgid) : self
    {
        $this->msgid = $msgid;
        return $this;
    }

    public function log($level, $timestamp, $message, array $context = array())
    {
       $prival = $this->facility | $level;

    /*   $msg = sprintf("<%d>1 %s %s %s %s %s - \xEF\xBB\xBF%s|%s", $prival, $timestamp,
            $this->hostname, $this->appname, $this->procid, $this->msgid,
            $message, json_encode($context, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));*/

	$msg = sprintf("<%d>1 %s %s %s %s %s - \xEF\xBB\xBF%s", $prival, $timestamp,
            $this->hostname, $this->appname, $this->procid, $this->msgid, $message);

		socket_sendto($this->sock, $msg, strlen($msg), 0, $this->addr, $this->port);
    }

    public function emergency($timestamp, $message, array $context = array())
    {
        $this->log(LOG_EMERG, $timestamp, $message, $context);
    }

    public function alert($timestamp, $message, array $context = array())
    {
        $this->log(LOG_ALERT, $timestamp, $message, $context);
    }

    public function critical($timestamp, $message, array $context = array())
    {
        $this->log(LOG_EMERG, $timestamp, $message, $context);
    }

    public function error($timestamp, $message, array $context = array())
    {
        $this->log(LOG_ERR, $timestamp, $message, $context);
    }

    public function warning($timestamp, $message, array $context = array())
    {
        $this->log(LOG_WARNING, $timestamp, $message, $context);
    }

    public function notice($timestamp, $message, array $context = array())
    {
        $this->log(LOG_NOTICE, $timestamp, $message, $context);
    }

    public function info($timestamp, $message, array $context = array())
    {
        $this->log(LOG_INFO, $timestamp, $message, $context);
    }

    public function debug($timestamp, $message, array $context = array())
    {
        $this->log(LOG_DEBUG, $timestamp, $message, $context);
    }
}
?>