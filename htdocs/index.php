<?php

require __DIR__ . '/../lib/vendor/autoload.php';

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
				$stackresult=json_decode($search->getStackAnswer());
				if ($stackresult) {
					$stackQuestion=$stackresult->question;
					$stackAnswer=$stackresult->answer;
					$stackAnswer=strip_tags($stackAnswer);
					$more="More answer here : ".$search->getQuestionURL();
					$message="$stackQuestion\r\n\r\n$stackAnswer\r\n$more";
				}
				else {
					$message="I'm sorry. I don't think theres is no answer for that question :(";
				}
				$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message);
				$result = $bot->pushMessage($event['source']['userId'], $textMessageBuilder);
				// or we can use pushMessage() instead to send reply message
				// $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($event['message']['text']);
				// $result = $bot->pushMessage($event['source']['userId'], $textMessageBuilder);
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
			$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder('Terimakasih sudah menambahkan sebagai teman, semoga betah :v');
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
