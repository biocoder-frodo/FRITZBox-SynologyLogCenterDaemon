<?
class FritzLogCenterRepeatedEvent implements SysLogEvent
{
	private $_message;
	private $_ts;
	private $_event;
	private $_count;
	private $_first;

	public function __construct(string $path, string $host, &$match, FritzLogCenterEvent &$event)
	{	

		$datetime= $match["d1"].'-'.$match["d2"].'-20'.$match["d3"]. ' ' .$match["ts"]. '.000'; // this will be bad in 2099, idc tbh
		$dt = new DateTime($datetime, new DateTimeZone(date_default_timezone_get()));
		
	//	echo $dt->format("c").' '.$match["message"].PHP_EOL;
		
		$this->_ts = $dt->getTimestamp();
		$this->_message = $match["message"];
		$this->_count = $match["count"];
		$this->_event = $event;
		
		$firstRow = getLogCenterRepeatEvent($path, $host, $this->message(), $this->ts());

		if (count($firstRow)>0){
			$this->_first = $firstRow[0]->id();
		}
	}
	
	public function ts() : int	{ return $this->_ts; }	
	
	public function message() : string  { return $this->_message; }
	
	public function category() : string { return $this->_event->_category; }
	public function id() : string { return $this->_event->id(); }
	
	public function firstId()  { return $this->_first; }
	public function key() : string { return '_'.$this->_ts.'_'.$this->_message;}
	public function count() : int	{ return $this->_count; }	
	
}
?>