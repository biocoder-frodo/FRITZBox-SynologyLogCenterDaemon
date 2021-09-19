<?
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
?>