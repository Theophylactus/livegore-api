<?php

class LGBot {
	public static function log($text) : void {
		echo '[' . date('d/m/y H:i:s') . "] $text\n";
	}
	
	public $response;
	public $currentDom;
	public $xpath;
	
	private $ch;
	
	public function curlopt($opt, $val) : bool {
		return curl_setopt($this->ch, $opt, $val);
	}
	private function request() {
		$this->response = curl_exec($this->ch);
		
		if($this->response == '') return;
		
		try {
			@$this->currentDom->loadHTML($this->response);
			$this->xpath = new DOMXpath($this->currentDom);
		} catch(Exception $e) { }
	}
	
	private $cookiesPath;
	
	public function __construct(string $username, bool $useTor = false) {
		# Initializes the DomDocument object that will hold the response of the server
		$this->currentDom = new DomDocument();
		$this->xpath = new DOMXpath($this->currentDom);
		
		# Initializes curl
		$this->ch = curl_init();
		$this->curlopt(CURLOPT_RETURNTRANSFER, true);
		#$this->curlopt(CURLOPT_VERBOSE, true);
		$this->curlopt(CURLOPT_ENCODING, 'gzip,deflate'); 
		$this->curlopt(CURLOPT_AUTOREFERER, true);
		$this->curlopt(CURLOPT_FOLLOWLOCATION, true);
		
		if($useTor)
			$this->setTor();
		
		$this->cookiesPath = dirname(__FILE__)."/cookies-$username.txt"; # IT WILL ONLY WORK USING ABSOLUTE PATHS
		self::log("Storing cookies in ".$this->cookiesPath);
		
		if(!@filesize($this->cookiesPath)) {
			if(!$this->login($username))
				throw new Exception('(LGBot::__construct) Failed to log in');
		} else {
			self::log("Cookies from previous session found. Skipping login.");
			$this->curlopt(CURLOPT_COOKIEJAR, $this->cookiesPath);
			$this->curlopt(CURLOPT_COOKIEFILE, $this->cookiesPath);
		}
	}
	
	# Logs the bot in using the newline-separated credentials in $loginCredsFilePath
	public function login(string $username) : bool {
		self::log("Logging '$username' in...");
		
		$loginCredsFile = fopen("login-$username.txt", 'r');
		
		if(!$loginCredsFile) throw new Exception("Could not open login credentials file login-$username.txt");
		
		$password = urlencode(fgets($loginCredsFile));
		fclose($loginCredsFile);
		
		$this->curlopt(CURLOPT_URL, 'https://www.livegore.com/login');
		$this->curlopt(CURLOPT_COOKIEJAR, $this->cookiesPath);
		$this->curlopt(CURLOPT_COOKIEFILE, $this->cookiesPath);
		
		$this->request();
		
		$loginCode = $this->xpath->query("//input[@type='hidden' and @name='code']/@value")[0]->nodeValue;

		$this->curlopt(CURLOPT_POST, true);
		$this->curlopt(CURLOPT_POSTFIELDS, "emailhandle=$username&password=$password&dologin=1&code=$loginCode");
		$this->curlopt(CURLOPT_HTTPHEADER, [
			'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:108.0) Gecko/20100101 Firefox/108.0',
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
			'Accept-Language: en-US,en;q=0.5',
			'Content-Type: application/x-www-form-urlencoded',
			'Origin: https://www.livegore.com',
			'Connection: keep-alive',
			'Referer: https://www.livegore.com/login',
			'Upgrade-Insecure-Requests: 1',
			'Sec-Fetch-Dest: document',
			'Sec-Fetch-Mode: navigate',
			'Sec-Fetch-Site: same-origin',
			'Sec-Fetch-User: ?1',
			'TE: trailers'
		]);

		$this->request();
		
		$this->curlopt(CURLOPT_URL, 'http://www.livegore.com/');
		$this->curlopt(CURLOPT_HTTPGET, true);
		$this->curlopt(CURLOPT_HTTPHEADER, [
			'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:108.0) Gecko/20100101 Firefox/108.0',
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
			'Accept-Language: en-US,en;q=0.5',
			'Connection: keep-alive',
			'Upgrade-Insecure-Requests: 1'
		]);
		$this->request();
		
		$userLink = $this->xpath->query("//div[@class='rb-userp']/div[@class='rb-havatar']/a[@class='rb-avatar-link' and @href='./user/".urlencode($username)."']");
		
		return count($userLink) != 0; # if userLink is empty, the login failed
	}
	
	public function setTor() : void {
		$this->curlopt(CURLOPT_PROXY, "127.0.0.1:9050");
		$this->curlopt(CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
	}
	public function unsetTor() : void {
		$this->curlopt(CURLOPT_PROXY, '');
	}
	
	private $currentVideo;
	
	public function setTargetVideo(string $url) : void {
		$this->currentVideo = $url;
	}
	
	# Loads the DOM of $this->currentVideo
	private function loadVideo() : void {
		$this->curlopt(CURLOPT_URL, $this->currentVideo);
		$this->curlopt(CURLOPT_HTTPGET, true);
		$this->curlopt(CURLOPT_HTTPHEADER, [
			'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:108.0) Gecko/20100101 Firefox/108.0',
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
			'Accept-Language: en-US,en;q=0.5',
			'Connection: keep-alive',
			'Upgrade-Insecure-Requests: 1'
		]);
		$this->request();
	}
	
	public static function fetchAllVideos() : array {
		$xml = new SimpleXMLElement(str_replace(' xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"', '', file_get_contents('http://livegore.com/sitemap.xml')));
		$urls = $xml->xpath('//loc');
		$result = [];
		
		foreach($urls as $url) {
			array_push($result, $url->__toString());
		}
		
		return $result;
	}
	
	# Returns -1, 0 or 1
	public function getVote(int $id = null) : int {
		$this->loadVideo();
		
		if($id == null) {
			$uri = parse_url($this->currentVideo)['path'];
			$id = str_replace('/', '', dirname($uri));
		}
		
		# Gets the raw HTML of the voting panel
		$votingPanel = $this->currentDom->getElementById("voting_$id");
		$xml = $votingPanel->ownerDocument->saveXML($votingPanel);
		
		if(strpos($xml, 'rb-voted-up-button'))
			return 1;
		else if(strpos($xml, 'rb-voted-down-button'))
			return -1;
		else
			return 0;
	}
	
	# Takes -1, 0 or 1
	public function vote(int $vote, int $id = null) : bool {
		if($vote != -1 && $vote != 0 && $vote != 1)
			throw new Exception("Error (LGBot::vote): attempted to vote with a value different from -1, 0 and 1. Given value: $vote");
			
		$uri = parse_url($this->currentVideo)['path'];
		
		$this->loadVideo();
		
		if($id == null) {
			$uri = parse_url($this->currentVideo)['path'];
			$id = str_replace('/', '', dirname($uri));
			
			#echo "\n\ntarget='".$this->currentVideo."'\nuri=$uri\nid=$id\n//form[div[@id='voting_$id']]/input[@type='hidden' and @name='code']/@value\n\n";
			
			$votingCode = $this->xpath->query("//form[div[@id='voting_$id']]/input[@type='hidden' and @name='code']/@value")[0]->nodeValue;
		} else {
			$votingCode = $this->xpath->query("//input[@type='hidden' and @name='code' and ../div/@id='voting_$id']/@value")[0]->nodeValue;
		}
		
		#echo "Voting code: $votingCode\n";
		
		$this->curlopt(CURLOPT_URL, "https://www.livegore.com");
		$this->curlopt(CURLOPT_POST, true);
		$this->curlopt(CURLOPT_HTTPHEADER, [
			'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:108.0) Gecko/20100101 Firefox/108.0',
			'Accept: text/plain, */*; q=0.01',
			'Accept-Language: en-US,en;q=0.5',
			'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
			'X-Requested-With: XMLHttpRequest',
			'Origin: https://www.livegore.com',
			'Connection: keep-alive',
			'Referer: '.$this->currentVideo,
			'Sec-Fetch-Dest: empty',
			'Sec-Fetch-Mode: cors',
			'Sec-Fetch-Site: same-origin',
			'TE: trailers'
		]);
		
		$this->curlopt(CURLOPT_POSTFIELDS, "postid=$id&vote=$vote&code=$votingCode&qa=ajax&qa_operation=vote&qa_root=..%2F&qa_request=$uri");
		$this->request();
		
		return $this->getVote($id) === $vote;
	}
	
	# Returns all comments as an array of associative arrays containing the following fields: text, poster, date, id
	public function getComments() : array {
		# Load the video DOM
		$this->curlopt(CURLOPT_URL, $this->currentVideo);
		$this->curlopt(CURLOPT_HTTPGET, true);
		$this->curlopt(CURLOPT_HTTPHEADER, [
			'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:108.0) Gecko/20100101 Firefox/108.0',
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
			'Accept-Language: en-US,en;q=0.5',
			'Connection: keep-alive',
			'Upgrade-Insecure-Requests: 1',
		]);
		$this->request();
		
		# Get all comment ids to vote on them
		$commentIds = $this->xpath->query("//div[@class='rb-a-item-content']/a[1]/@name");
		
		# Prepare cURL to send the voting requests
		$this->curlopt(CURLOPT_URL, "https://www.livegore.com");
		$this->curlopt(CURLOPT_POST, true);
		$this->curlopt(CURLOPT_HTTPHEADER, [
			'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:108.0) Gecko/20100101 Firefox/108.0',
			'Accept: text/plain, */*; q=0.01',
			'Accept-Language: en-US,en;q=0.5',
			'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
			'X-Requested-With: XMLHttpRequest',
			'Origin: https://www.livegore.com',
			'Connection: keep-alive',
			'Referer: '.$this->currentVideo,
			'Sec-Fetch-Dest: empty',
			'Sec-Fetch-Mode: cors',
			'Sec-Fetch-Site: same-origin',
			'TE: trailers'
		]);
		
		# Get the voting id of the first video (it's the same for all videos)
		#$votingCode = $this->xpath->query("//input[@type='hidden' and @name='code' and contains(../div/@id, 'voting')]/@value")[0]->nodeValue;
		$commentNodes = $this->xpath->query("//div[@class='commentmain']");
		
		$comments = [];
		
		for($x = 0; $x < $commentNodes->length; ++$x) {
			$commentNode = $commentNodes->item($x);
			
			# Selects the wanted parameters of each comment node. Note that for this to work, the initial dot (.) is required
			$id = $this->xpath->query(".//div[@class='rb-a-item-content']/a[1]/@name", $commentNode)[0]->nodeValue;
			$text = $this->xpath->query(".//div[@class='entry-content']/text()", $commentNode)[0]->nodeValue;
			$poster = $this->xpath->query(".//a[contains(@class, 'nickname')]/text()", $commentNode)[0]->nodeValue;
			$date = $this->xpath->query(".//span[contains(@class, 'published')]/span/@title", $commentNode)[0]->nodeValue;
			
			array_push($comments, ['id' => $id, 'text' => html_entity_decode($text), 'poster' => html_entity_decode($poster), 'date' => $date]);
		}
		
		return $comments;
	}
	
	# Post a comment to the current video
	public function comment(string $text) : bool {
		# Get the code
		$this->loadVideo();
		
		$commentingCodeNode = $this->xpath->query("//form[@name='a_form']/input[@type='hidden' and @name='code']/@value");
		
		if(!count($commentingCodeNode)) {
			self::log("(comment) Commenting code not found");
			return false;
		}
		
		$commentingCode = $commentingCodeNode[0]->nodeValue;
		
		$this->curlopt(CURLOPT_URL, "https://www.livegore.com");
		$this->curlopt(CURLOPT_POST, true);
		$this->curlopt(CURLOPT_HTTPHEADER, [
			'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:108.0) Gecko/20100101 Firefox/108.0',
			'Accept: text/plain, */*; q=0.01',
			'Accept-Language: en-US,en;q=0.5',
			'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
			'X-Requested-With: XMLHttpRequest',
			'Origin: https://www.livegore.com',
			'Connection: keep-alive',
			'Referer: '.$this->currentVideo,
			'Sec-Fetch-Dest: empty',
			'Sec-Fetch-Mode: cors',
			'Sec-Fetch-Site: same-origin',
			'TE: trailers'
		]);
		
		$this->curlopt(CURLOPT_POSTFIELDS, "a_content=".urlencode($text)."&=Add+Comment&a_editor=&a_doadd=1&code=$commentingCode&a_questionid=2&qa=ajax&qa_operation=answer&qa_root=.%2F&qa_request=2");
		$this->request();
		
		$resultComments = $this->getComments();
		
		foreach($resultComments as &$resultComment)
			if($resultComment['text'] == $text)
				return true;
		
		return false;		
	}
	
	# Comment on comment 27033
	public function reply(int $id, string $text) : void {
		# Get the code
		$this->loadVideo();
		
		$code = $this->xpath->query("//input[@type='hidden' and @name='c${id}_code']/@value")[0]->nodeValue;
		
		$this->curlopt(CURLOPT_URL, "https://www.livegore.com");
		$this->curlopt(CURLOPT_POST, true);
		$this->curlopt(CURLOPT_HTTPHEADER, [
			'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:108.0) Gecko/20100101 Firefox/108.0',
			'Accept: text/plain, */*; q=0.01',
			'Accept-Language: en-US,en;q=0.5',
			'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
			'X-Requested-With: XMLHttpRequest',
			'Origin: https://www.livegore.com',
			'Connection: keep-alive',
			'Referer: '.$this->currentVideo,
			'Sec-Fetch-Dest: empty',
			'Sec-Fetch-Mode: cors',
			'Sec-Fetch-Site: same-origin',
			'TE: trailers'
		]);
		
		$this->curlopt(CURLOPT_POSTFIELDS, "c${id}_content=".urlencode($text)."&=Add+comment&docancel=Cancel&c${id}_editor=&c${id}_doadd=1&c${id}_code=$code&c_questionid=2&c_parentid=27033&qa=ajax&qa_operation=comment&qa_root=.%2F&qa_request=2");
		$this->request();
	}
}

?>
