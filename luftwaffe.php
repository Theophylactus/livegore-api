<?php
	require_once 'LGBot.php';
	
	$gifs = ['https://upload.wikimedia.org/wikipedia/commons/1/18/RollingThunder.gif',
	         'https://media.tenor.com/jcH_ZK4izA4AAAAC/katyusha.gif',
	         'https://image.tienphong.vn/Uploaded/2022/rwbvhvobvvimsb/2022_06_29/ezgif-com-gif-maker-302.gif',
	         'https://thumbs.gfycat.com/MadeupNeighboringCob-max-1mb.gif',
	         'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c2/F100_Napalm.gif/300px-F100_Napalm.gif',
	         'https://thumbs.gfycat.com/BouncyLargeAtlanticridleyturtle-size_restricted.gif'];
	
	$psalms = ['ALL HAIL THE KING THEOPHYLACTUS', 'THEOPHYLACTUS I, SOVEREIGN OF LIVEGORE', 'GOD SAVE THE KING! GOD SAVE THE KING! GOD SAVE THE KING THEOPHYLACTUS I OF LIVEGORE!', 'Bow before Theophylactus, NEW KING of Livegore!',
	           'Who shall not fear thee, O Theophylactus, and glorify thy name? for thou only art holy: for all nations shall come and worship before thee; for thy judgments are made manifest. THEOPHYLACTUS OWNS LIVEGORE!',
	           'THEOPHYLACTUS\' TROOPS HAVE JUST ARRIVED. SURRENDER NOW OR PREPARE FOR HELL ON EARTH', 'WHO CAN ESCAPE HIS SOVEREIGNTY? HA! NOBODY! THEOPHYLACTUS IS KING'];
	
	//$spain = new LGBot('Spanish Empire');
	//$britain = new LGBot('British Empire');
	$japan = new LGBot('Japanese Empire');
	$rome = new LGBot('Roman Empire', true);
	
	$videos = LGBot::fetchAllVideos();
	
	$count = 0;
	
	foreach($videos as &$video) {
		++$count;
		
		if($count < 300) {
			echo "Skipping $video\n";
			continue;
		}
		
		$japan->setTargetVideo($video);
		if($japan->vote(1))
			LGBot::log("(japan) Voted on $video");
		else {
			LGBot::log("(japan) Failed to vote $video");
			sleep(1805);
		}
		
		$rome->setTargetVideo($video);
		if($rome->vote(1))
			LGBot::log("(rome) Voted on $video");
		else {
			LGBot::log("(rome) Failed to vote $video");
			sleep(1805);
		}
	}
?>
