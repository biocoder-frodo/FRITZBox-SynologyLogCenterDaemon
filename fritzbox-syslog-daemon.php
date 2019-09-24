<?
interface SysLogEvent
{
	public function ts() ;
	
	public function message()  ;
	
	public function category() ;
}

class FritzLogCenterEvent implements SysLogEvent
{
	private $_category;
	private $_message;
	private $_ts;

	public function __construct(int $ts, string $category, string $message)
	{
		$this->_ts = $ts;
		$this->_category = $category;
		$this->_message = $message;
	}
	
	public function ts() : int	{ return $this->_ts; }	
	
	public function message() : string  { return $this->_message; }
	
	public function category() : string { return $this->_category; }
	
}

class FritzLogEvent implements SysLogEvent
{
	private $_id;
	private $_category;
	private $_message;
	private $_ts;
	private $_dt;
	private $_parent;
	
	public function __construct(FritzLog $parent, DateTime $ts, int $category, int $id, string $message)
	{
		$this->_ts = $ts->getTimeStamp();
		$this->_category = $category;
		$this->_id = $id;
		$this->_message = $message;
		$this->_parent = $parent;
		$this->_dt = new DateTime($ts->format('c'),new DateTimeZone($parent->tz()));
	}
	public function tslog() : string
	{
		$this->_dt->setTimeStamp($this->_ts + $this->_parent->offset());
		return $this->_dt->format('c');
	}
	public function ts() : int
	{
		return $this->_ts + $this->_parent->offset();
	}	
	public function id() { return $this->_id; }
	public function message() { return $this->_message; }
	
	public function category() : string
	{
		switch ($this->_category) {
		case 1:
		    return 'System';
		case 2:
		    return 'Internet_Connection';
		case 3:
		    return 'Telephony';
		case 4:
		    return 'Wireless';
		case 5:
		    return 'USB_Devices';
		default:
		    return 'Undefined';
		}		
	}

}

$fritz_host = 'fritz.box';
$fritz_user = 'stats';
$logcenter_path = '/volume2/homes/admin/logs/'; // Your logs are located on the volume you installed the Log Center package on.
$syslog_port = 516; // my default port is 516

$shortopts  = "";
$shortopts .= "p::";  // Optional value
$shortopts .= "f::"; // Optional value
$shortopts .= "u::"; // Optional value
$shortopts .= "l:"; // Required value

$options = getopt($shortopts, array("udp::"));
if ($options===FALSE)
{
	echo "getopt failed" . PHP_EOL;
	die();
}

if(array_key_exists("l",$options)!=TRUE)
{
	echo "LogCenter path must be specified" . PHP_EOL;
	die();
}
if(array_key_exists("p",$options))
{
	$pwdfile = $options["p"];
}
else
{
	$pwdfile = __DIR__ .'/'.$fritz_user . '.pwdfile';
}
$fritz_pwd = file_get_contents($pwdfile);
if($fritz_pwd===FALSE)
{
	echo "Password file not found" . PHP_EOL;
	die();	
}
$logcenter_path = $options["l"];

if(array_key_exists("f",$options)) $fritz_host = $options["f"];
if(array_key_exists("u",$options)) $fritz_user = $options["u"];
if(array_key_exists("udp",$options)) $syslog_port = intval($options["udp"]);

$fritz = new FritzLuaLog($fritz_host, $fritz_pwd, $fritz_user,'http');

$log = new UdpLog("127.0.0.1", $syslog_port);
$log->facility(0)->procid(1)->hostname($fritz_host);

if ($fritz->login())
{
	$existing = getLogCenterTimeStamps($logcenter_path,$fritz_host);

	if (count($existing)==0)
	{
		$samples = array();
		$ts_samples =array();
		for($i=0;$i<20;$i++)
		{
			if($i==0)sleep(5);
			array_push($samples, $fritz->getlogs());
		}
		foreach($samples as $sample)
		{
			$row = $sample->Log[count($sample->Log)-1];
			array_push($ts_samples, $row->ts());
		  	//echo $row->tslog() . ' ' . $row->ts() .' ' .$row->category() .' ' . $row->id() .' ' . $row->message() .PHP_EOL;  
		}
		foreach($samples as $sample)
		{
			$row = $sample->Log[count($sample->Log)-1];
			if ($row->ts()==max($ts_samples))
			{
				foreach ($sample->Log as $row)	
				{	
					$log->appname($row->category())->msgid($row->id())->info($row->tslog(),$row->message());
					//echo $row->tslog() . ' ' . $row->ts() .' ' .$row->category() .' ' . $row->id() .' ' . $row->message() .PHP_EOL;  
					//var_dump($row);	
				}
				
				break;
			}
		}
		sleep(5);
		$existing = getLogCenterTimeStamps($logcenter_path,$fritz_host);
	}
	
	$existing = getLogCenterTail($logcenter_path,$fritz_host);
	
	while(TRUE)
	{
		$data = $fritz->getlogs();
		if ($data===FALSE)
		{
			$fritz->login();
		}
		else
		{
		//foreach ($data->Log as $row)	
		//{	
			//$log->appname($row->category)->msgid($row->id)->info($row->ts,$row->message);
			//echo $row->tslog() . ' ' . $row->ts() .' ' .$row->category() .' ' . $row->id() .' ' . $row->message() .PHP_EOL;  
			//var_dump($row);	
		//}
		//var_dump($existing);
		
		$diff = array_udiff( $data->Log, $existing, 'logEventComparison');
		usort($diff,'logEventSortOrder');
		
		//var_dump($diff);
		foreach ($diff as $row)	
				{	
					$log->appname($row->category())->msgid($row->id())->info($row->tslog(),$row->message());
					//echo $row->tslog() . ' ' . $row->ts() .' ' .$row->category() .' ' . $row->id() .' ' . $row->message() .PHP_EOL;  
					//var_dump($row);	
				}
		//foreach ($data->Log as $row)	
		//{	
			//$log->appname($row->category)->msgid($row->id)->info($row->ts,$row->message);
			//echo $row->tslog() . ' ' . $row->ts() .' ' .$row->category() .' ' . $row->id() .' ' . $row->message() .PHP_EOL;  
			//var_dump($row);	
		//}
		//var_dump($existing);
				
			sleep(300);
		}
		$existing = getLogCenterTail($logcenter_path,$fritz_host);
	}
	
}
else
{
	echo "Login failed" . PHP_EOL;	
}
$fritz = null;
$log=null;

function getLogCenterTail(string $path, string $host, int $limit=400, int $fritz_limit=400)
{
	$last_ts = array();
	$tail = array();
	$prev_ts=0;
	$valsw=0;
	
	$dbh = getLogCenterDb($path, $host);
	$query =  "select * FROM logs order by utcsec desc, id desc limit " . $fritz_limit;
	//echo($query . PHP_EOL);
	
	$rows = $dbh->query($query);
	if($rows===false)
	{
		echo 'LogCenter table empty.' . PHP_EOL;
	}
	else
	{
		$rows = $rows->fetchall();		
		if (count($rows)>0)
		{
			foreach ($rows as $row)
			{
			//	var_dump($row);
				$ts = (int)$row['utcsec'];
				if($ts<>$prev_ts)
				{
					array_push($last_ts, $ts);
					$prev_ts = $ts;
					$valsw=$valsw+1;
				}
				else
				{
					array_push($last_ts, $prev_ts);
				}
				if ($valsw==$limit) break;			    
			}
		}
		
		$query =  "select * FROM logs where utcsec>=" .$prev_ts ." order by utcsec desc, id desc limit " . $fritz_limit;
		//echo($query . PHP_EOL);
		$rows = $dbh->query($query);
		$rows = $rows->fetchall();		
		if (count($rows)>0)
		{
			foreach ($rows as $row)
			{
				array_push($tail, new FritzLogCenterEvent((int)$row['utcsec'], (string)$row['prog'], (string)$row['msg']));
			}
		}
	}
	$last_ts=null;
	$dbh = null;
	return $tail;
}
function logEventComparison(SysLogEvent $rte, SysLogEvent $dbe)
{
	$db = $dbe->category().$dbe->ts().$dbe->message();
	$rt = $rte->category().$rte->ts().$rte->message();
	return strcmp($db,$rt);
}
function logEventSortOrder(SysLogEvent $a, SysLogEvent $b)
{
	return ($a->ts() - $b->ts());
}

function getLogCenterTimeStamps(string $path, string $host, int $limit=10)
{
	$last_ts = array();
	$prev_ts=0;
	$valsw=0;
	
	$dbh = getLogCenterDb($path, $host);
	$query =  "select * FROM logs order by utcsec desc, id desc limit 399";
	
	$rows = $dbh->query($query);
	if($rows===false)
	{
		echo 'LogCenter table empty.' . PHP_EOL;
	}
	else
	{
		$rows = $rows->fetchall();		
		if (count($rows)>0)
		{
			foreach ($rows as $row)
			{
			//	var_dump($row);
				$ts = (int)$row['utcsec'];
				if($ts<>$prev_ts)
				{
					array_push($last_ts, $ts);
					$prev_ts = $ts;
					$valsw=$valsw+1;
				}
				else
				{
					array_push($last_ts, $prev_ts);
				}
				if ($valsw==$limit) break;
			    
			}
		}
	}
	$dbh = null;
	return $last_ts;
}

function getLogCenterDb(string $path, string $host)
{
	$options = [
			    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			    PDO::ATTR_EMULATE_PREPARES   => false,
	];

	$dir = 'sqlite:'.$path.$host.'/SYNOSYSLOGDB_'.$host.'.DB';
	
	try
	{
		$pdo  = new PDO($dir);
		return $pdo;
		
	} catch (\PDOException $e)
	{
	     throw new \PDOException($e->getMessage(), (int)$e->getCode());
	}
}

class FritzLuaLog
{
	private $sessionid;
	private $host;
	private $user;
	private $password;
	private $protocol;
	private $loginURI;
	private $ch;
	private $debug;
	
	public function __construct(string $host,string $password, string $user = "",string $protocol = 'http', bool $with_debug_output = false)
	{			
		$this->sid = '0000000000000000';
		$this->host = $host;
		$this->user = $user;
		$this->password = $password;
		$this->protocol = $protocol;
		$this->loginURI = $this->protocol . '://' . $this->host . '/login_sid.lua';
		$this->debug = $with_debug_output;
		try // for testing on Windows - idc this fails
		{
			$this->ch = curl_init();
		}
		//php 7
		catch (\Throwable $ex)
		{
			echo 'CURL not present'.PHP_EOL;
		}
	}
	function __destruct()
	{
		$this->logout();
		print_r(" closing CURL session ");
		curl_close ($this->ch);
		print_r(" complete" . PHP_EOL);
	}
	public function login() : bool
	{	 
		if($this->debug) print_r('Login URI ' . $this->loginURI . PHP_EOL);
		
		// Get Challenge-String
		$tmp = simplexml_load_string(file_get_contents($this->loginURI));
		if ($tmp->BlockTime > 0)
		{
			print_r('Asked to wait for ' . $tmp->BlockTime . ' seconds'. PHP_EOL);
			sleep(intval($tmp->BlockTime)+1);
			$tmp = simplexml_load_string(file_get_contents($this->loginURI));
		}
		if ($this->debug)
		{
			print_r('Got challenge: ' . $tmp->asXML() . PHP_EOL);
		}
		$challenge = $tmp->Challenge;
		
		// Get SID
		$challenge_str = $challenge . '-' . $this->password;
		$md_str = md5(iconv("UTF-8", "UTF-16LE",  $challenge_str));
		$response = $challenge . '-' . $md_str;
		$tmp = simplexml_load_string(file_get_contents($this->loginURI . '?user=' . $this->user . '&response=' . $response));
		if ($this->debug)
		{print_r(' Got session ID: ' . $tmp->asXML() . PHP_EOL);
		}
		$this->sid = (string)$tmp->SID;
		$result = $this->sid ==='0000000000000000';
		if ($result===TRUE) echo 'Please come back in '. $tmp->BlockTime . ' seconds..' . PHP_EOL;
		return !$result;
	}
	
	public function parselogs(object $json) : FritzLog
	{
		$result = new FritzLog(date_default_timezone_get());
		
		if($this->debug)print_r('JSON result =' . var_dump($json));
		foreach($json->data->log as $row)
		{
			$first=$first+1;
			$datetime= str_replace('.','-',substr($row[0],0,6).'20'.substr($row[0],-2)) . ' ' . $row[1] . '.000'; // this will be bad in 2099, idc tbh
			$dt = new DateTime($datetime, new DateTimeZone(date_default_timezone_get()));			

			$result->add($dt,(int)$row[4],(int)$row[3], $row[2]); 
		
		}		
		return $result;
	}
	
	public function getlogs() 
	{
		
		$postvars = ["xhr1" => "1",
					"sid" => $this->sid,
					"lang" => "en",
					"page" => "log",
					"xhrId" => "all",	
					];
		
		
		curl_setopt($this->ch, CURLOPT_URL, $this->protocol . '://' . $this->host . '/data.lua');
		curl_setopt($this->ch, CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($postvars));
		
		// Receive server response ...
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		$first=0;
		$response = curl_exec($this->ch);
		$json = json_decode($response);
		if ($this->sid===$json->sid)
		{
			return $this->parselogs($json);
		}
		else
		{
			return FALSE;
		}
	}
	
	public function logout()
	{
		if ($this->sid!=='0000000000000000')
		{
			echo 'Destroying session '. $this->sid ;
			// Logout
			$tmp = simplexml_load_string(file_get_contents($this->loginURI.'?logout=1&sid=' . $this->sid));
			if ($this->debug)
			{
				print_r(' Logout: ' . $tmp->asXML() . PHP_EOL);
			}
			$this->sid = (string)$tmp->SID;
		}
		return $sid ==='0000000000000000' ? TRUE: FALSE;
	}
}
class FritzLog
{
	public $Log;
	private $_tz;
	private $_offset;
	
	public function __construct(string $tzname)
	{
		$this->_tz = $tzname;
		$this->Log = array();
		$this->_offset=0;
	}

	public function tz() : string
	{
		return $this->_tz;
	}
	public function offset() : int
	{
		return $this->_offset;
	}
	public function setOffset(int $value) 
	{
		return $this->_offset = $value;
	}
	
	public function add(DateTime $ts, int $category, int $id, string $message)
	{
		array_push($this->Log, new FritzLogEvent($this, $ts, $category, $id, $message));
	}
	
	public function getTimeStamps()
	{
		$last_ts = array();
		if (count((array)$this->Log)>0)
		{
			foreach ((array)$this->Log as $row)
			{
				
			//	var_dump($row);
				$ts = (int)$row->ts();				
				if($ts<>$prev_ts)
				{
					array_push($last_ts, $ts);
					$prev_ts = $ts;
					$valsw=$valsw+1;
				}
				else
				{
					array_push($last_ts, $prev_ts);
				}
				if ($valsw==$limit) break;
			    
			}
		}
		foreach($last_ts as $ts)
		{echo ($ts).PHP_EOL;}
		return $last_ts;
	}
}

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