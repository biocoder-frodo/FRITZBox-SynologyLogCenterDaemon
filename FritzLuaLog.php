<?
include("FritzLuaSession.php");
include("FritzLog.php");

class FritzLuaLog extends FritzLuaSession
{
	
	public function parselogs(object $json, string $ignore) : FritzLog
	{
		$result = new FritzLog(date_default_timezone_get());
		
		if($this->debug)print_r('JSON result =' . var_dump($json));
		foreach($json->data->log as $row)
		{
			$fieldDate     = ($logVersion==0) ? ($row[0]) : ($row->date);
			$fieldTime     = ($logVersion==0) ? ($row[1]) : ($row->time);
			$fieldMessage  = ($logVersion==0) ? ($row[2]) : ($row->msg);
			$fieldId       = ($logVersion==0) ? ($row[3]) : ($row->id);
			$fieldCategory = ($logVersion==0) ? ($row[4]) : ($row->group);

			$datetime= str_replace('.','-',substr($fieldDate,0,6).'20'.substr($fieldDate,-2)) . ' ' . $fieldTime . '.000'; // this will be bad in 2099, idc tbh
			$dt = new DateTime($datetime, new DateTimeZone(date_default_timezone_get()));			
			if (strlen($ignore)>0)
			{  
				if(str_starts_with($fieldMessage, $ignore) || str_starts_with($datetime, '2070-'))
				{
					;
				}
				else
				{
					$result->add($dt, $fieldCategory, (int)$fieldId, $fieldMessage);
				}
			}
			else
			{
				$result->add($dt, $fieldCategory, (int)$fieldId, $fieldMessage); 
			}
		
		}		
		return $result;
	}
	
	public function getlogs(string $ignore="") 
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
			return $this->parselogs($json, $ignore);
		}
		else
		{
			return $json;
		}
	}	
}
?>