<?
class FritzLogEvent implements SysLogEvent
{
	private $_id;
	private $_category;
	private $_message;
	private $_ts;
	private $_dt;
	private $_parent;
	
	public function __construct(FritzLog $parent, DateTime $ts, string $category, int $id, string $message)
	{
		$this->_ts = $ts->getTimeStamp();
              if (is_numeric($category))
		{
			$this->_category = (int)$category;
		}
		else
		{
			switch($category)
			{
				case 'sys': $this->_category = 1; break;
    				case 'net': $this->_category = 2; break;
    				case 'fon': $this->_category = 3; break;
    				case 'wlan': $this->_category = 4; break;
    				case 'usb': $this->_category = 5; break;
   			 	
				default:
       			$this->_category = 0;
       		 	break;
			}
		}
		$this->_id = $id;
		$this->_message = $message;
		$this->_parent = $parent;
		$this->_dt = new DateTime($ts->format('c'),new DateTimeZone($parent->tz()));
	}
	public function tslog() : string
	{
		$this->_dt->setTimeStamp($this->_ts + $this->_parent->offset());
		return $this->_dt->format('c');
	}
	public function ts() : int
	{
		return $this->_ts + $this->_parent->offset();
	}	
	public function id() { return $this->_id; }
	public function message() { return $this->_message; }
	
	public function category() : string
	{
		switch ($this->_category) {
		case 1:
		    return 'System';
		case 2:
		    return 'Internet_Connection';
		case 3:
		    return 'Telephony';
		case 4:
		    return 'Wireless';
		case 5:
		    return 'USB_Devices';
		default:
		    return 'Undefined';
		}		
	}

}
?>