<?php


require_once 'LGBot.php';

$bot = new LGBot('your-username');

$videos = LGBot::fetchAllVideos();

$count = 0;

foreach($videos as &$video) {
  /*
	++$count;
	
	if($count < 6100) {
		continue;
	}*/
	
	try {
		$bot->setTargetVideo($video);
		
		$couldVote = $bot->vote(1);
		$couldComment = $bot->comment('Funny video as always');
		
		if($couldVote)
			$bot->log("Voted on $video");
		else
			$bot->log("Failed to vote on $video");
			
			
		if($couldComment)
			$bot->log("Commented on $video");
		else
			$bot->log("Failed to comment on $video");
		
				
		if(!$couldVote && !$couldComment) {
			$bot->log("Failed to vote AND comment on $video. Waiting 1 hour");
			sleep(3610);
		}
		
	} catch(Exception $e) {
		echo "Caught exception: ". $e->getMessage() . "\n";
	}
}

?>
