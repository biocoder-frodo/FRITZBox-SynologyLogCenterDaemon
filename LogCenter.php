<?
function getLogCenterTail(string $path, string $host, int $limit=2000)
{
	global $dsm;
	
	$last_ts = array();
	$tail = array();
	$prev_ts=0;
	$valsw=0;
	
	$dbh = getLogCenterDb($path, $host);
	$query =  "select * FROM logs".((6==$dsm)?(""):(" where host='".$host."'"))." order by utcsec desc, id desc limit " . $limit;
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
		
		$query =  "select * FROM logs where utcsec>=" .$prev_ts .((6==$dsm)?(""):(" and host='".$host."'"))." order by utcsec desc, id desc limit " . $limit;
		//echo($query . PHP_EOL);
		$rows = $dbh->query($query);
		$rows = $rows->fetchall();		
		if (count($rows)>0)
		{
			foreach ($rows as $row)
			{
				array_push($tail, new FritzLogCenterEvent((int)$row['id'], (int)$row['utcsec'], (string)$row['prog'], (string)$row['msg']));
			}
		}
	}
	$last_ts=null;
	$dbh = null;
	return $tail;
}
function getLogCenterTailFromTime(string $path, string $host, int $utcsec)
{
	global $dsm;
	
	$tail = array();
	
	$dbh = getLogCenterDb($path, $host);
	$query =  "select * FROM logs".((6==$dsm)?(""):(" where host='".$host."'"))." order by utcsec desc, id desc limit 1";
	//echo($query . PHP_EOL);
	
	$rows = $dbh->query($query);
	if($rows===false)
	{
		echo 'LogCenter table empty.' . PHP_EOL;
	}
	else
	{
		$query =  "select * FROM logs where utcsec >= ".$utcsec."".((6==$dsm)?(""):(" and host='".$host."'"))." order by utcsec desc, id desc";
		//echo($query . PHP_EOL);
		$rows = $dbh->query($query);
		$rows = $rows->fetchall();		
		if (count($rows)>0)
		{
			foreach ($rows as $row)
			{
				array_push($tail, new FritzLogCenterEvent((int)$row['id'], (int)$row['utcsec'], (string)$row['prog'], (string)$row['msg']));
			}
		}
	}

	$dbh = null;
	return $tail;
}

function getLogCenterRepeatEvent(string $path, string $host, string $message, int $ts)
{
	global $dsm;
	
	$result=array();
	
	$dbh = getLogCenterDb($path, $host);
		
	$query =  "select * FROM logs where (utcsec=" .$ts. " or utcsec=" .($ts-1). ") and msg='".$message."'".((6==$dsm)?(""):(" and host='".$host."'"))." order by utcsec desc, id desc";
	
	$rows = $dbh->query($query);
	$rows = $rows->fetchall();		
	if (count($rows)>0)
	{
		foreach ($rows as $row)
		{
			array_push($result, new FritzLogCenterEvent((int)$row['id'], (int)$row['utcsec'], (string)$row['prog'], (string)$row['msg']));
		}
	}
	
	$last_ts=null;
	$dbh = null;
	return $result;	
}
function removeLogCenterRepeatEvent(string $path, string $host, int $id, bool $verbose)
{
	$dbh = getLogCenterDb($path, $host);
	$count = $dbh->exec("delete from logs where id=".$id);
	if ($verbose===TRUE)echo $count . ' row(s) removed.' . PHP_EOL;

}

function getLogCenterTimeStamps(string $path, string $host, int $limit=10)
{	
	global $dsm;

	$last_ts = array();
	$prev_ts=0;
	$valsw=0;
	
	$dbh = getLogCenterDb($path, $host);
	$query =  "select * FROM logs".((6==$dsm)?(""):(" where host='".$host."'"))." order by utcsec desc, id desc limit 399";
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
	global $dsm;

	$options = [
			    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			    PDO::ATTR_EMULATE_PREPARES   => false,
	];

	$dir = (6 == $dsm) ? 'sqlite:'.$path.'/'.$host.'/SYNOSYSLOGDB_'.$host.'.DB' : 'sqlite:'.$path.'/SYNOSYSLOGDB__ARCH.DB';
	//echo $dir . PHP_EOL;
	try
	{
		$pdo  = new PDO($dir);
		return $pdo;
		
	} catch (\PDOException $e)
	{
	     throw new \PDOException($e->getMessage(), (int)$e->getCode());
	}
}
function removeDbInitMarker(string $path, string $host)
{	
	$dbh = getLogCenterDb($path, $host);
	$count = $dbh->exec("delete from logs where prog='SysLogDaemon'");
	$count = $dbh->exec("vacuum");
	echo $count . ' row(s) removed.' . PHP_EOL;
	
}
function getLogCenterCount(string $path, string $host, int $limit=400)
{
	global $dsm;

	$dbh = getLogCenterDb($path, $host);
	$query =  "select count(*) as rows from (select * from logs".((6==$dsm)?(""):(" where host='".$host."'"))." limit " . $limit.")";
	//echo($query . PHP_EOL);
	
	$result = $dbh->query($query);
	if($result===false)
	{
		echo 'LogCenter table does not exist.' . PHP_EOL;
		return 0;
	}
	else
	{
		$result = $result->fetchall();
		return intval($result[0]["rows"]);
	}
}
?>