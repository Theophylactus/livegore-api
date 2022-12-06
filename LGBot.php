<?php

fclose(STDIN);
fclose(STDOUT);
fclose(STDERR);
$STDIN = fopen('/dev/null', 'wb');
$STDOUT = fopen('/var/www/store/adm/lgbot-log.txt', 'wb');
$STDERR = fopen('/var/www/store/adm/lgbot-log.txt', 'wb');

echo "Loading sitemap... ";
$xml = new SimpleXMLElement(str_replace(' xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"', '', file_get_contents('http://livegore.com/sitemap.xml')));
$urls = $xml->xpath('//loc');
echo "Done\n";

# In this website, many POST requests will require us to include a code found in the HTML of the referer page
function getPostCodeFromDocument(string $html) : ?string {
	$loginCodeBegin = strpos($html, '<input type="hidden" name="code" value="');
	if($loginCodeBegin === false) return null;
	return substr($html, $loginCodeBegin + strlen('<input type="hidden" name="code" value="'), strpos($html, '"', $loginCodeBegin + strlen('<input type="hidden" name="code" value="')) - $loginCodeBegin - strlen('<input type="hidden" name="code" value="'));
}

echo "Logging in...\n";

$ch = curl_init('https://www.livegore.com/login');
curl_setopt($ch, CURLOPT_COOKIEJAR, '/var/www/cookies.txt'); 
curl_setopt($ch, CURLOPT_COOKIEFILE, '/var/www/cookies.txt');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate'); 
curl_setopt($ch, CURLOPT_AUTOREFERER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

if(!@filesize('./cookies.txt')) {
	$
	$username = ping;
	$password = pong;

	# Get the login code (probably implemented to deter bots like this one)
	$loginCode = getPostCodeFromDocument(curl_exec($ch));

	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "emailhandle=$username&password=$password&dologin=1&code=$loginCode");
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
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

	# Bam! Logged in.
	curl_exec($ch);
} else {
	echo "Cookies from previous session found. Skipping login.\n";
}

for($x = 0; $x < count($urls); ++$x) {
	try {
		$loc = $urls[$x]->__toString();

		if(strpos($loc, '.com/tag/') || strpos($loc, '.com/user/') || strpos($loc, '.com/questions/') || strpos($loc, '.com/categories')) continue;
		
		if(strpos(@file_get_contents('upvoted.txt'), $loc) !== false)
			continue;
		
		echo "Upvoting $loc... ";
		
		# Get the code
		curl_setopt($ch, CURLOPT_URL, $loc);
		curl_setopt($ch, CURLOPT_HTTPGET, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:108.0) Gecko/20100101 Firefox/108.0',
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
			'Accept-Language: en-US,en;q=0.5',
			'Connection: keep-alive',
			'Upgrade-Insecure-Requests: 1',
		]);
		
		$upvoteCode = getPostCodeFromDocument(curl_exec($ch));
		
		# Submit the upvote
		curl_setopt($ch, CURLOPT_URL, "https://www.livegore.com");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:108.0) Gecko/20100101 Firefox/108.0',
			'Accept: text/plain, */*; q=0.01',
			'Accept-Language: en-US,en;q=0.5',
			'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
			'X-Requested-With: XMLHttpRequest',
			'Origin: https://www.livegore.com',
			'Connection: keep-alive',
			"Referer: $loc",
			'Sec-Fetch-Dest: empty',
			'Sec-Fetch-Mode: cors',
			'Sec-Fetch-Site: same-origin',
			'TE: trailers'
		]);

		$uri = parse_url($loc)['path'];
		$postId = str_replace('/', '', dirname($uri));
		curl_setopt($ch, CURLOPT_POSTFIELDS, "postid=$postId&vote=1&code=$upvoteCode&qa=ajax&qa_operation=vote&qa_root=..%2F&qa_request=$uri");
		curl_exec($ch);
		
		echo "done.\n";
		
		echo "Checking vote... ";
		
		# Check the video is actually upvoted
		curl_setopt($ch, CURLOPT_URL, $loc);
		curl_setopt($ch, CURLOPT_HTTPGET, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:108.0) Gecko/20100101 Firefox/108.0',
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
			'Accept-Language: en-US,en;q=0.5',
			'Connection: keep-alive',
			'Upgrade-Insecure-Requests: 1',
		]);
		
		if(strpos(curl_exec($ch), 'rb-voted-up-button') === false) {
			echo "Server rejected upvote. Waiting 1/2 hour.\n";
			--$x;
			sleep(1820);
		} else {
			echo " Successful\n";
			file_put_contents('upvoted.txt', "$loc\n", FILE_APPEND);
		}
	} catch(Exception $e) {
		sleep(30);
	}
}

?>
