<?
include("FritzLuaSession.php");
include("FritzLog.php");

class FritzLuaLog extends FritzLuaSession
{
	
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
		

		$json = $this->postDataRequest($postvars);
		
		if ($json!==FALSE)
		{
			return $this->parselogs($json);
		}
		else
		{
			return $json;
		}
	}	
}
?>