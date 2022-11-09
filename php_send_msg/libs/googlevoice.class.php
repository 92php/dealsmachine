<?PHP
/*
Google Voice Api
License	:���ִ�������http://www.lostleon.com���������˴�����ʾ,������Ϣ,Ⱥ��,����תƴ��  ����PHP��չ��curl
Author:threesky@gmail.com, http://www.heqee.com/
*/
class GoogleVoice
{
    public $username;
    public $password;
    public $status;
    private $lastURL;
    private $login_auth;
	private $inboxURL = 'https://www.google.com/voice/m/';
	private $loginURL = 'https://www.google.com/accounts/ClientLogin';
	private $smsURL = 'https://www.google.com/voice/m/sendsms';

    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
		if(!function_exists('curl_init')){
		$this->error("Call to undefined function curl_init()");
		}
    }

	public function getLoginAuth()
	{
		$login_param = "accountType=GOOGLE&Email={$this->username}&Passwd={$this->password}&service=grandcentral&source=lostleonATgmailDOTcom-GoogleVoiceTool";
	    $login_output = $this->curl($this->loginURL, $this->lastURL, $login_param);
	    $this->login_auth = $this->match('/Auth=([A-z0-9_-]+)/', $login_output, 1);
		if($this->login_auth==''){
			$this->error("��¼ʧ��,����ԭ��:GoogleVoice�ʺŻ��������;����������");
		}
	    return $this->login_auth;
	}

	public function get_rnr_se()
	{
		$auth_param = "?auth=".$this->getLoginAuth();
	    $inbox_output = $this->curl($this->inboxURL.$auth_param, $this->lastURL);
	    $_rnr_se = $this->match('!<input.*?name="_rnr_se".*?value="(.*?)"!ms', $inbox_output, 1);
	    return $_rnr_se;
	}

	private function sms($to_phonenumber, $smstxt)
	{
	    $_rnr_se = $this->get_rnr_se();
	    $sms_param = "id=&c=&number=".urlencode($to_phonenumber)."&smstext=".urlencode($smstxt)."&_rnr_se=".urlencode($_rnr_se);
		$posturl = $this->smsURL."?auth=".$this->login_auth;
		
	    $sms_output = $this->curl($posturl, $this->lastURL, $sms_param);

		$preg='/<span class=\"\">(.*)<\/span>/i';

		preg_match_all($preg,$sms_output,$title);

		$this->status =  $title[0][0]."<br>"; 

		if($this->status==''){
			$this->error("��Ϣ����ʧ��");
		}
	    return $this->status;
	}
	public function send($to,$smstxt){
		$smstxt = $this->make_semiangle($smstxt);
		$to = $this->make_semiangle($to);
		if(strstr($to,','))
		{
			$to=explode(',',$to);
		}else{
			$to=array($to);
		}
		foreach ($to as $v)
		{
			$log.= $this ->sms($v,$smstxt);
		}
		return $log;
	
	}

	private function curl($url, $referer = null, $post_param = null)
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (iPhone; U; CPU iPhone OS 2_2_1 like Mac OS X; en-us) AppleWebKit/525.18.1 (KHTML, like Gecko) Version/3.1.1 Mobile/5H11 Safari/525.20");

		if($referer)
			curl_setopt($ch, CURLOPT_REFERER, $referer);

	    if(!is_null($post_param))
	    {
	        curl_setopt($ch, CURLOPT_POST, true);
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_param);
	    }

		$html = curl_exec($ch);
        $this->lastURL = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		curl_close($ch);
		return $html;
	}
	
	private function match($regex, $str, $out_ary = 0)
	{
	    return preg_match($regex, $str, $match) == 1 ? $match[$out_ary] : false;
	}
	private function make_semiangle($str)
	{
		$arr = array('��' => '(', '��' => ')','��' => '%', '��' => '+', '��' => '-', '��' => '-', '�C' => '-',
					 '��' => ':', '��' => '.', '��' => ',', '��' => ',', '--' => '-','��' => "'", '��' => "'","�@" => "'");
		return strtr($str, $arr);
	}
	/**
     * error handle
     *
     * @param string $emsg
     */
    private function error($emsg)
    {
    	$this->error = $emsg;
    	echo "<b>Error</b>: ".$emsg;
		exit;
    }

}

?>
