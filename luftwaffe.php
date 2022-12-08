<?php

require 'LGBot.php';

$gifs = ['https://upload.wikimedia.org/wikipedia/commons/1/18/RollingThunder.gif',
         'https://media.tenor.com/jcH_ZK4izA4AAAAC/katyusha.gif',
         'https://image.tienphong.vn/Uploaded/2022/rwbvhvobvvimsb/2022_06_29/ezgif-com-gif-maker-302.gif',
         'https://thumbs.gfycat.com/MadeupNeighboringCob-max-1mb.gif',
         'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c2/F100_Napalm.gif/300px-F100_Napalm.gif',
         'https://thumbs.gfycat.com/BouncyLargeAtlanticridleyturtle-size_restricted.gif',
         'https://1.bp.blogspot.com/-MGbjG6k9MAA/XOw6f1Je9SI/AAAAAAAAJ0c/mdN4JcLC-SwJvH9xZBTDD0eNi0ohSFu_ACLcBGAs/s1600/8338a4183f8323492c23705c48e83197.gif',
         'https://1.bp.blogspot.com/-uoBJxnFO_iU/X8mcBG_oxMI/AAAAAAAClH0/uq2YbmL4vvUm8h3geNASc_hBwkqSqaXvwCLcBGAsYHQ/s453/11ben-hur-1959-chariot-race-gif.gif',
         'https://media3.giphy.com/media/huDUAwzQYv6g/giphy.gif?cid=790b7611c979889f1114f1b34234d98de17e8febec9410f6&rid=giphy.gif&ct=g',
         'https://thumbs.gfycat.com/TiredNippyIrishsetter-size_restricted.gif'];
	
$psalms = ['ALL HAIL THE KING @THEOPHYLACTUS', 'THEOPHYLACTUS I, SOVEREIGN OF LIVEGORE', 'GOD SAVE THE KING! GOD SAVE THE KING! GOD SAVE THE KING THEOPHYLACTUS I OF LIVEGORE!', 'Bow before @Theophylactus, NEW KING of Livegore!',
           'Who shall not fear thee, O @Theophylactus, and glorify thy name? for thou only art holy: for all nations shall come and worship before thee; for thy judgments are made manifest. THEOPHYLACTUS OWNS LIVEGORE!',
           'THEOPHYLACTUS\' TROOPS HAVE JUST ARRIVED. SURRENDER NOW OR PREPARE FOR HELL ON EARTH', 'WHO CAN ESCAPE HIS SOVEREIGNTY? HA! NOBODY! @THEOPHYLACTUS IS KING', 'Reichsfuhrer Batrick Pateman has ordered all LiveGore members to bow to the king @THEOPHYLACTUS',
           'ISAQATHE, GRANDMASTER OF LIVEGORE TROLLAGE, ORDERS YOU TO BOW BEFORE @THEOPHYLACTUS'];

function generateRandomComment() : string {
	global $psalms;
	global $gifs;
	return $psalms[array_rand($psalms)] . " " . $gifs[array_rand($gifs)];
}


$acc1 = new LGBot('Spanish Empire');
$acc2 = new LGBot('Roman Empire');
$acc3 = new LGBot('Japanese Empire');
$acc4 = new LGBot('Belgian Empire', true);
$acc5 = new LGBot('Russian Empire', true);
$acc6 = new LGBot('British Empire', true);

$videos = array_reverse(LGBot::fetchAllVideos());

$count = 0;

$totalVotes = 0;

foreach($videos as $video) {
	#++$count;
	#if($count < 85) continue;
	
	$acc1->setTargetVideo($video);
	$acc2->setTargetVideo($video);
	$acc3->setTargetVideo($video);
	$acc4->setTargetVideo($video);
	$acc5->setTargetVideo($video);
	$acc6->setTargetVideo($video);
	
	LGBot::log("Spamming on $video");
	
	$comments = $acc1->getComments();
	
	foreach($comments as &$comment) {
		if(in_array($comment['poster'], ['Spanish Empire', 'Roman Empire', 'Japanese Empire', 'Belgian Empire', 'Russian Empire', 'British Empire'])) continue;
		
		if(rand(0, 1)) $acc1->reply($comment['id'], generateRandomComment());
		if(rand(0, 1)) $acc2->reply($comment['id'], generateRandomComment());
		if(rand(0, 1)) $acc3->reply($comment['id'], generateRandomComment());
		if(rand(0, 1)) $acc4->reply($comment['id'], generateRandomComment());
		if(rand(0, 1)) $acc5->reply($comment['id'], generateRandomComment());
		if(rand(0, 1)) $acc6->reply($comment['id'], generateRandomComment());
		
		if(rand(0, 1)) $totalVotes += $acc1->vote(-1, $comment['id']);
		if(rand(0, 1)) $totalVotes += $acc2->vote(-1, $comment['id']);
		if(rand(0, 1)) $totalVotes += $acc3->vote(-1, $comment['id']);
		if(rand(0, 1)) $totalVotes += $acc4->vote(-1, $comment['id']);
		if(rand(0, 1)) $totalVotes += $acc5->vote(-1, $comment['id']);
		if(rand(0, 1)) $totalVotes += $acc6->vote(-1, $comment['id']);
	}
	
	if(!$comments)
		LGBot::log("No comments to reply");
	
	if($totalVotes >= 50) {
		$totalVotes = 0;
		LGBot::log("Reached 50 votes. Waiting 1 hour...");
		sleep(3610);
	}
}
?>