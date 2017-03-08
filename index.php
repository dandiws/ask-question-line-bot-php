<?php

require __DIR__ . '/vendor/autoload.php';

use \LINE\LINEBot\SignatureValidator as SignatureValidator;

// load config
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

// initiate app
$configs =  [
	'settings' => ['displayErrorDetails' => true],
];
$app = new Slim\App($configs);

/* ROUTES */
$app->get('/', function ($request, $response) {
	return "Lanjutkan!";
});

$app->post('/', function ($request, $response)
{
	// get request body and line signature header
	$body 	   = file_get_contents('php://input');
	$signature = $_SERVER['HTTP_X_LINE_SIGNATURE'];

	// log body and signature
	file_put_contents('php://stderr', 'Body: '.$body);

	// is LINE_SIGNATURE exists in request header?
	if (empty($signature)){
		return $response->withStatus(400, 'Signature not set');
	}

	// is this request comes from LINE?
	if($_ENV['PASS_SIGNATURE'] == false && ! SignatureValidator::validateSignature($body, $_ENV['CHANNEL_SECRET'], $signature)){
		return $response->withStatus(400, 'Invalid signature');
	}

	// init bot
	$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
	$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);

	$data = json_decode($body, true);
	foreach ($data['events'] as $event)
	{
		if ($event['type'] == 'message')
		{
			if($event['message']['type'] == 'text')
			{
				// send same message as reply to user
				//$result = $bot->replyText($event['replyToken'], $event['message']['text']);

				require_once('src/searchAnswer.php');
				$question=$event['message']['text'];
				$search =new searchAnswer($question);
				$search->getGoogleResult();
				$message=$search->getAnswer();
				$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message);
				$result = $bot->pushMessage($event['source']['userId'], $textMessageBuilder);
				return $result->getHTTPStatus() . ' ' . $result->getRawBody();
			}
			elseif ($event['message']['type'] == 'sticker') {
				$messages=array("Nice sticker!", "I hope i have one", "That's a cute sticker");
				$message=$messages[mt_rand(0,sizeof($messages)-1)];
				$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message);
				$result = $bot->replyText($event['replyToken'], $textMessageBuilder);
				//$response = $bot->getProfile($event['source']['userId']);
				//if ($response->isSucceeded()) {
				    //$profile = $response->getJSONDecodedBody();
				    //echo $profile['displayName'];
				    //echo $profile['pictureUrl'];
				    //echo $profile['statusMessage'];
				//}
				return $result->getHTTPStatus() . ' ' . $result->getRawBody();
			}
		}
		elseif ($event['type'] == 'follow') {
			$response = $bot->getProfile($event['source']['userId']);
			if ($response->isSucceeded()) {
					$profile = $response->getJSONDecodedBody();
			}
			$name=$profile['displayName'];
			$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder("Hello $name, thanks for adding me as a friend.\r\nYou can tell me your problem or question and I'll answer if I can.");
			$sticker= new \LINE\LINEBot\MessageBuilder\StickerMessageBuilder('2','157');
			$user=$event['source']['userId'];
			$result = $bot->pushMessage($user,$textMessageBuilder);
			$result = $bot->pushMessage($user,$sticker);

			return $result->getHTTPStatus() . ' ' . $result->getRawBody();
		}
	}

});

// $app->get('/push/{to}/{message}', function ($request, $response, $args)
// {
// 	$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
// 	$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);

// 	$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($args['message']);
// 	$result = $bot->pushMessage($args['to'], $textMessageBuilder);

// 	return $result->getHTTPStatus() . ' ' . $result->getRawBody();
// });

/* JUST RUN IT */
$app->run();
