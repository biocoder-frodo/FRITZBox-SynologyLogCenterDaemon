<?
class FritzLuaSession
{
	private $sessionid;
	private $host;
	private $user;
	private $password;
	private $protocol;
	private $loginURI;
	private $ch;
	private $debug;
	
	public function __construct(string $host,string $password, string $user = "",string $protocol = 'https', bool $with_debug_output = false)
	{			
		$this->sid = '0000000000000000';
		$this->host = $host;
		$this->user = $user;
		$this->password = $password;
		$this->protocol = $protocol;
		$this->loginURI = $this->protocol . '://' . $this->host . '/login_sid.lua';
		$this->debug = $with_debug_output;
		try // for testing on Windows - idc this fails
		{
			$this->ch = curl_init();
		}
		//php 7
		catch (\Throwable $ex)
		{
			echo 'CURL not present'.PHP_EOL;
		}
	}
	function __destruct()
	{
		$this->logout();
		print_r(" closing CURL session ");
		curl_close ($this->ch);
		print_r(" complete" . PHP_EOL);
	}
	public function login() : bool
	{	
		global $fgcOption;
		
		if($this->debug) print_r('Login URI ' . $this->loginURI . PHP_EOL);
		
		// Get Challenge-String
		$tmp = simplexml_load_string(file_get_contents($this->loginURI, false, stream_context_create($fgcOption)));
		if ($tmp->BlockTime > 0)
		{
			print_r('Asked to wait for ' . $tmp->BlockTime . ' seconds'. PHP_EOL);
			sleep(intval($tmp->BlockTime)+1);
			$tmp = simplexml_load_string(file_get_contents($this->loginURI, false, stream_context_create($fgcOption)));
		}
		if ($this->debug)
		{
			print_r('Got challenge: ' . $tmp->asXML() . PHP_EOL);
		}
		$challenge = $tmp->Challenge;
		
		// Get SID
		$challenge_str = $challenge . '-' . $this->password;
		$md_str = md5(iconv("UTF-8", "UTF-16LE",  $challenge_str));
		$response = $challenge . '-' . $md_str;
		$tmp = simplexml_load_string(file_get_contents($this->loginURI . '?user=' . $this->user . '&response=' . $response, false, stream_context_create($fgcOption)));
		if ($this->debug)
		{print_r(' Got session ID: ' . $tmp->asXML() . PHP_EOL);
		}
		$this->sid = (string)$tmp->SID;
		$result = $this->sid ==='0000000000000000';
		if ($result===TRUE) echo 'Please come back in '. $tmp->BlockTime . ' seconds..' . PHP_EOL;
		return !$result;
	}
	
	public function logout()
	{
		global $fgcOption;
		
		if ($this->sid!=='0000000000000000')
		{
			echo 'Destroying session '. $this->sid ;
			// Logout
			$tmp = simplexml_load_string(file_get_contents($this->loginURI.'?logout=1&sid=' . $this->sid, false, stream_context_create($fgcOption)));
			if ($this->debug)
			{
				print_r(' Logout: ' . $tmp->asXML() . PHP_EOL);
			}
			$this->sid = (string)$tmp->SID;
		}
		return $sid ==='0000000000000000' ? TRUE: FALSE;
	}

	public function postDataRequest(array $postvars,bool $parseJson = true) 
	{
		global $CAfile;

		curl_setopt($this->ch, CURLOPT_URL, $this->protocol . '://' . $this->host . '/data.lua');
		curl_setopt($this->ch, CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_CAINFO, $CAfile);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($postvars));
		
		// Receive server response ...
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		$first=0;
		$response = curl_exec($this->ch);
		if ($parseJson===true)
		{
			$json = json_decode($response);
			if ($this->sid===$json->sid)
			{
				return $json;
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			return $response;
		}
	}
}
function cmdLineSwitch($opt, $options)
{
	$result=FALSE;
	if(array_key_exists($opt,$options))
	{
		$result = TRUE;
	}
	return $result;

}
function parseCommandLine(string $extraShort, $extraLong, string $user="stats")
{
	global $fritz_host;
	global $fritz_user;
	global $transport;
	global $pwdfile;
	global $fritz_pwd;
	
	$fritz_host = 'fritz.box';
	$fritz_user = $user;
	
	$shortopts  = "";
	$shortopts .= "p::";  // Optional value
	$shortopts .= "f::"; // Optional value
	$shortopts .= "u::"; // Optional value
	$shortopts .= "k::"; // Optional value
	$shortopts .= "t::"; // Optional value
	$shortopts .= $extraShort;

	$options = getopt($shortopts, $extraLong);
	if ($options===FALSE)
	{
		echo "getopt failed" . PHP_EOL;
		die();
	}

	if(array_key_exists("k",$options))
	{
		$CAfile = $options["k"];
	}
	else
	{
		$CAfile = __DIR__ .'/boxcert.cer';
	}
	if(array_key_exists("t",$options))
	{
		$transport = $options["t"];
	}
	else
	{
		$transport = 'https';
	}
	if(array_key_exists("p",$options))
	{
		$pwdfile = $options["p"];
	}
	else
	{
		$pwdfile = __DIR__ .'/'.$fritz_user . '.pwdfile';
	}
	$fritz_pwd = file_get_contents($pwdfile);
	if($fritz_pwd===FALSE)
	{
		echo "Password file not found" . PHP_EOL;
		die();	
	}
	
	if(array_key_exists("f",$options)) $fritz_host = $options["f"];
	if(array_key_exists("u",$options)) $fritz_user = $options["u"];
	
	
	$fgcOption = array(
	    "ssl" => array(
	        "cafile" => $CAfile,
	        "verify_peer"=> true,
	        "verify_peer_name"=> true,
	    ),
	);

	return $options;
}
?>