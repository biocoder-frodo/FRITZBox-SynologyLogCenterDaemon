<?
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
?>