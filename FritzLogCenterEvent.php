<?
class FritzLogCenterEvent implements SysLogEvent
{
	private $_category;
	private $_message;
	private $_ts;
	private $_id;
	private $_ts_recorded;

	public function __construct(int $id, int $ts, string $category, string $message, int $ts_recorded)
	{
		$this->_id = $id;
		$this->_ts = $ts;
		$this->_ts_recorded = $ts_recorded;
		$this->_category = $category;
		$this->_message = $message;
	}
	
	public function id() : int	{ return $this->_id; }
	
	public function ts() : int	{ return $this->_ts; }	
	public function ts_recorded() : int	{ return $this->_ts_recorded; }	
	public function message() : string  { return $this->_message; }
	
	public function category() : string { return $this->_category; }
	public function key() : string { return '_'.$this->_ts.'_'.$this->_message.'_'.$this->_category;}
	
}
?>