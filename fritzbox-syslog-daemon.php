<?
include_once 'SysLogEvent.php';
include_once 'FritzLogCenterEvent.php';
include_once 'FritzLogCenterRepeatedEvent.php';
include_once 'FritzLogEvent.php';
include_once 'FritzLuaLog.php';
include_once 'LogCenter.php';
include_once 'UdpLog.php';

$logVersion=0;

$repeatFilter = "/^(?<message>.*)\s+\[(?<count>[0-9]+) messages since (?<d1>[0-9]{2}).(?<d2>[0-9]{2}).(?<d3>[0-9]{2})\s+(?<ts>[0-9]{2}:[0-9]{2}:[0-9]{2})\]$/";

$logcenter_path = '/var/services/homes/admin/logs'; // Your logs are located on the volume you installed the Log Center package on.
$syslog_port = 516; // my default port is 516

//parse the commandline to get the connectiondetails for your Fritz!Box, the default username is 'stats'.
$options = parseCommandLine('iadqjl:', array("udp::","ignore::"), 'stats');
echo 'pid= ' . getmypid() . PHP_EOL;
var_dump($options);
	
$rundbquery = cmdLineSwitch("d",$options);
$runquery = cmdLineSwitch("q",$options);
$initDb = cmdLineSwitch("i",$options);

if (cmdLineSwitch("j",$options)==TRUE) $logVersion=1;

setAnonymousDb($options);

$filter="";
if(array_key_exists("ignore",$options)) $filter = $options["ignore"];

if(array_key_exists("l",$options)!=TRUE and ($runquery===FALSE or $rundbquery===TRUE))
{
	echo "LogCenter path must be specified" . PHP_EOL;
	die();
}

$logcenter_path = $options["l"];
if(str_ends_with7($options["l"].'/','//'))
{
	$logcenter_path = substr($options["l"],0,strlen($options["l"])-1);
};
//echo $logcenter_path.PHP_EOL;

if(array_key_exists("udp",$options)) $syslog_port = intval($options["udp"]);

$log = new UdpLog("127.0.0.1", $syslog_port);
$log->facility(0)->procid(1)->hostname($fritz_host);

if ($initDb===TRUE)
{
	$ts = new DateTime();
	$log->appname("SysLogDaemon")->info($ts->format('c'),"Database initialization message");
	echo 'Database should be created from syslog packet...' . PHP_EOL;
	sleep(5);
	removeDbInitMarker($logcenter_path,$fritz_host);
	die();
}
if ($rundbquery===TRUE)
{
	$rowcount=-1;
	try
	{
		// sudo php7x ./fritzbox-syslog-daemon.php -d -l=/var/services/homes/admin/logs
		$rowcount = getLogCenterCount($logcenter_path,$fritz_host);
	}
	catch(Exception $e)
	{
		echo 'Failed to open the database to get the record count of the logs table. Insufficient privileges or file not found?'. PHP_EOL;
		echo 'Caught exception: '.  $e->getMessage() . PHP_EOL;
	}
	var_dump($rowcount);
	die();
}

// prepare the session
$fritz = new FritzLuaLog($fritz_host, $fritz_pwd, $fritz_user, $transport);
if ($runquery===TRUE)
{
	if ($fritz->login())
	{
		var_dump($fritz->getlogs($filter));
	}
	else
	{
		echo "Login failed" . PHP_EOL;	
	}
	$fritz = null;
	die();
}

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
			array_push($samples, $fritz->getlogs($filter));
		}
		foreach($samples as $sample)
		{
			$row = $sample->Log[count($sample->Log)-1];
			array_push($ts_samples, $row->ts());
		}
		foreach($samples as $sample)
		{
			$row = $sample->Log[count($sample->Log)-1];
			if ($row->ts()==max($ts_samples))
			{
				foreach ($sample->Log as $row)	
				{	
					$log->appname($row->category())->msgid($row->id())->info($row->tslog(),$row->message());
				}
				
				break;
			}
		}
		sleep(5);
		$existing = getLogCenterTimeStamps($logcenter_path,$fritz_host);
	}
	
	$existing = getLogCenterTail($logcenter_path,$fritz_host);
	echo 'Starting loop...' . PHP_EOL;
	while(TRUE)
	{
		$data = $fritz->getlogs($filter);
		if ($data===FALSE)
		{
			$fritz->login();
		}
		else
		{

		
		$diff = array_udiff( $data->Log, $existing, 'logEventComparison');
		usort($diff,'logEventSortOrder');

		foreach ($diff as $row)	
				{	
					$log->appname($row->category())->msgid($row->id())->info($row->tslog(),$row->message());	
				}
				
			sleep(300);
			
			// now filter out duplicates of repeated messages we captured before
			$repeats = array();
			$repeatKeys = array();
			foreach(getLogCenterTail($logcenter_path,$fritz_host) as $row)
			
			if (preg_match($repeatFilter, $row->message(), $match, PREG_UNMATCHED_AS_NULL)===1)
			{	
				$repeat = new FritzLogCenterRepeatedEvent($logcenter_path,$fritz_host,$match,$row);
				array_push($repeats, $repeat);
				array_push($repeatKeys, $repeat->key());
			}
		
			array_unique($repeatKeys,SORT_STRING);
			$lastRepeats=array_fill_keys($repeatKeys,NULL);
			
			foreach($repeats as $repeat)
			{
				if($lastRepeats[$repeat->key()]===NULL){
					$lastRepeats[$repeat->key()] = $repeat;
				}
				else if ($lastRepeats[$repeat->key()]->count() < $repeat->count()){
					$lastRepeats[$repeat->key()] = $repeat;
				}
			}
			foreach($repeats as $repeat)
			{
				if(array_key_exists($repeat->key(),$lastRepeats)===true && $lastRepeats[$repeat->key()]->count() != $repeat->count()){			
					echo 'message #'.$repeat->count().' superseded by #'.$lastRepeats[$repeat->key()]->count() .PHP_EOL;
					echo 'message #'.$repeat->count().': id '.$repeat->id().' ';
					removeLogCenterRepeatEvent($logcenter_path,$fritz_host, $repeat->id());
					if ($repeat->firstId()!=NULL){
						removeLogCenterRepeatEvent($logcenter_path,$fritz_host, $repeat->firstId());
						echo' firstId:'.$repeat->firstId();
						}
					echo PHP_EOL;
				}
			}
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
function setAnonymousDb($options)
{
	global $dsm;
	$dsm = (TRUE === cmdLineSwitch("a",$options))? 7 : 6;
}
function str_ends_with7(string $haystack, string $needle):bool
{
	if(strlen($needle)>strlen($haystack))return FALSE;
	if(strlen($needle)==strlen($haystack))return $haystack===$needle;
	$hay=substr($haystack,strlen($haystack)-strlen($needle),strlen($needle));
	return $needle===$hay;
}
?>