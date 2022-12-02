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
		return curl_setop($this->ch, $opt, $val);
	}
	private function request() {
		$this->response = curl_exec($this->ch);
		
		@$this->currentDom->loadHTML($this->response);
		$this->xpath = new DOMXpath($this->currentDom);
	}
	
	private function getCodeFromDocument() : string {
		$entries = $this->xpath->query('//input[@type="hidden" and @name="code"]/@value');
		
		return $entries[0]->nodeValue;
	}
	
	
	public function __construct(string $loginCredsFilePath = null) {
		# Initializes the DomDocument object that will hold the response of the server
		$this->currentDom = new DomDocument();
		$this->xpath = new DOMXpath($this->currentDom);
		
		# Initializes curl
		$this->ch = curl_init('https://www.livegore.com/login');
		$this->curlopt(CURLOPT_COOKIEJAR, 'cookies.txt'); 
		$this->curlopt(CURLOPT_COOKIEFILE, 'cookies.txt');
		$this->curlopt(CURLOPT_RETURNTRANSFER, true);
		$this->curlopt(CURLOPT_ENCODING, 'gzip,deflate'); 
		$this->curlopt(CURLOPT_AUTOREFERER, true);
		$this->curlopt(CURLOPT_FOLLOWLOCATION, true);
		
		if(!@filesize('./cookies.txt')) {
			if($loginCredsFilePath == null) {
				throw new Exception("Error: no login credentials file was specified and no session cookies were found either");
			}
			
			$this->login($loginCredsFilePath);
		} else {
			self::log("Cookies from previous session found. Skipping login.");
		}
	}
	
	# Logs the bot in using the newline-separated credentials in $loginCredsFilePath
	public function login(string $loginCredsFilePath) {
		self::log('Logging in...');
		
		$loginCredsFile = fopen($loginCredsFilePath, 'r');
		
		if(!$loginCredsFile) throw new Exception("Could not open login credentials file $loginCredsFilePath");
		
		$username = urlencode(fgets($loginCredsFile));
		$password = urlencode(fgets($loginCredsFile));
	
		$this->request();
		$loginCode = $this->getCodeFromDocument();

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
	}
	
	private $currentVideo;
	
	public function loadVideo(string $url) : bool {
		$this->currentVideo = $url
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
	}
	
	# Returns -1, 0 or 1
	public function getVote() : int {
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
		
		$uri = parse_url($this->currentVideo)['path'];
		$videoId = str_replace('/', '', dirname($uri));
		
		# Gets the raw HTML of the voting panel
		$votingPanel = $this->currentDom->getElementById("voting_$videoId");
		$xml = $votingPanel->ownerDocument->saveXML($votingPanel);
		
		if(strpos($xml, 'rb-voted-up-button'))
			return 1;
		else if(strpos($xml, 'rb-voted-down-button'))
			return -1;
		else
			return 0;
	}
	
	# Takes -1, 0 or 1
	public function vote(int $vote, string $votingCode, int $id = null) : bool {
		if($vote != -1 && $vote != 0 && $vote != 1)
			throw new Exception("Error (LGBot::vote): attempted to vote with a value different from -1, 0 and 1. Given value: $vote");
			
		$uri = parse_url($this->currentVideo)['path'];
		
		if($votingCode == null) {
			# Gets the voting code
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
			
			$votingCode = $this->xpath->query("//form[div[@id='voting_$videoId']]/input[@type='hidden' and @name='code']/@value")[0]->nodeValue;
		}
		
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
		
		return getVote() === $vote;
	}
	
	# Returns all comments as an array of associative arrays containing the following fields: text, poster, date, id, voting code
	public function getComments() : array {
		if($vote != -1 && $vote != 0 && $vote != 1)
			throw new Exception("Error (LGBot::voteAllComments): attempted to vote with a value different from -1, 0 and 1. Given value: $vote");
			
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
			$votingCode = $this->xpath->query(".//input[@type='hidden' and @name='code' and contains(../div/@id, 'voting')]/@value", $commentNode)[0]->nodeValue;
			$poster = $this->xpath->query(".//a[contains(@class, 'nickname')]/text()", $commentNode)[0]->nodeValue;
			$date = $this->xpath->query(".//span[contains(@class, 'published')]/span/@title", $commentNode)[0]->nodeValue;
			
			array_push($comments, ['id' => $id, 'text' => $text, 'votingCode' => $votingCode, 'poster' => $poster, 'date' => $date]);
		}
		
		return $comments;
	}
	
	/*
		
	# Votes. Either a video or a comment.
	public function vote(int $id, int $vote) : int {
		if($vote != -1 && $vote != 0 && $vote != 1)
			throw new Exception("Error (LGBot::voteAllComments): attempted to vote with a value different from -1, 0 and 1. Given value: $vote");
			
		# Load the video DOM
		$this->curlopt(CURLOPT_URL, $this->currentVideo);
		$this->curlopt(CURLOPT_HTTPGET, true);
		$this->curlopt(CURLOPT_HTTPHEADER, [
			'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:108.0) Gecko/20100101 Firefox/108.0',
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*//*;q=0.8',
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
			'Accept: text/plain, *//*; q=0.01',
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
		$votingCode = $this->xpath->query("//input[@type='hidden' and @name='code' and contains(../div/@id, 'voting')]/@value")[0]->nodeValue;
		
		
		foreach($commentIds as $commentId) {
			$id = $commentId->nodeValue;
			$this->curlopt(CURLOPT_POSTFIELDS, "postid=$id&vote=$vote&code=$votingCode&qa=ajax&qa_operation=vote&qa_root=..%2F&qa_request=$uri");
			$this->request();
		}
	}
	*/
}

?>
