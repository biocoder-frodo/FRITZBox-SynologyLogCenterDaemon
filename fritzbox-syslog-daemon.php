<?
include_once 'SysLogEvent.php';
include_once 'FritzLogCenterEvent.php';
include_once 'FritzLogEvent.php';
include_once 'FritzLuaLog.php';
include_once 'LogCenter.php';
include_once 'UdpLog.php';

global $dsm;
$dsm=6;

$logcenter_path = '/var/services/homes/admin/logs/'; // Your logs are located on the volume you installed the Log Center package on.
$syslog_port = 516; // my default port is 516

//parse the commandline to get the connectiondetails for your Fritz!Box, the default username is 'stats'.
$options = parseCommandLine('idql:', array("udp::","ignore::"), 'stats');
echo 'pid =' . getmypid() . PHP_EOL;
var_dump($options);
	
$rundbquery = cmdLineSwitch("d",$options);
$runquery = cmdLineSwitch("q",$options);
$initDb = cmdLineSwitch("i",$options);

$anonymousDb = cmdLineSwitch("a",$options);
if ($anonymousDb===TRUE) $dsm=7;

$filter="";
if(array_key_exists("ignore",$options)) $filter = $options["ignore"];

if(array_key_exists("l",$options)!=TRUE and ($runquery===FALSE or $rundbquery===TRUE))
{
	echo "LogCenter path must be specified" . PHP_EOL;
	die();
}
$logcenter_path = $options["l"];

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
		// sudo php7x ./fritzbox-syslog-daemon.php -d -l=/var/services/homes/admin/logs/
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
?>