<?
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
?>