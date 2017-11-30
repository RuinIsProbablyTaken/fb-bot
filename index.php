<?php

//print_r(json_decode(file_get_contents('php://input')));
const VERIFY_TOKEN = "dik"; //but it can be anything, this should be application generated and generated only once
const ACCESS_TOKEN = "EAAYaPZB8xkmEBAIYTZBZCsAwaMWUrMc6xIj2Hp68r9VLNn1uTbwbeZBZCO4Oh3KEq7ps1UZChXSjxGUbXmr9njdyjUXxoTxWnXjm4SMb3e5VjQOSW8VQPIcfBjZCClaIwMEcQgr0FhYym2SHC4JZCqcZCm2LcrRFOZBBOCeWz1kzZAGa6ZA7qZBueqZBCL";


/**
 * Authenticate with the facebook application.
 *
 * Sends a response with 
 */
function authenticate($request) {
	if($request["hub_verify_token"] === VERIFY_TOKEN) {
		$ch = curl_init('https://graph.facebook.com/v2.6/me/subscribed_apps?access_token='.ACCESS_TOKEN);
		$options = [
			CURLOPT_POST => true
		];
		curl_setopt_array($ch, $options);
		$result = curl_exec($ch);
		curl_close($ch);

		print_r(json_decode($result));

		return $request["hub_challenge"];
	}
	return "Token doesn't match";
}

/**
 * Initializes greeting message, persistent menu and so forth
 */
function initialize() {

}

/**
 * Constructs a reply message and sends it.
 *
 * Will have a type attached to it for more control over the type of messages sent(text, url, cta, etc.)
 */
function reply($messaging) {
	$matches = [
		"hi" => [
			"attachment" => [
				"type" => "template",
				"payload" => [
					"template_type" => "button",
					"text" => "Hello. Here's some stuff",
					"buttons" => [
						[
							"type" => "web_url",
							"url" => "www.jesus.com",
							"title" => "the dankest website"
						],
						[
							"type" => "postback",
							"title" => "Tell me things",
							"payload" => "Tell me things"
						]
					]
				]
			]
		],
		"tell me things" => ["text" => "Woah, hold up, I didn't think I'd get this far ok"],
		"default" => ["text" => "Welp, idk what to tell ya fam. Try saying 'Hi' to start a conversation with me, I ain't that bright"]
	];

	foreach($matches as $match => $text) {
		if($match === $messaging["message"]) {
			return ["recipient" => ["id" => $messaging["sender"]], "message" => $text];
		}
	}
	
	return ["recipient" => ["id" => $messaging["sender"]], "message" => $matches["default"]];
}

/**
 * Extracts the message received.
 */
function getMessage($request, $postback = false) {
	$message = $request->entry[0]->messaging[0];
	$sender = $message->sender->id;
	if($postback) {
		$text = $message->postback->payload;
	}
	else {
		$text = $message->message->text;
	}

	return ["sender" => $sender, "message" => strtolower($text)];
}

/**
 * Sends a message.
 */
function sendMessage($response) {
	if(!$response) {
		echo "Bad response";
		return false;
	}
	$ch = curl_init('https://graph.facebook.com/v2.6/me/messages?access_token='.ACCESS_TOKEN);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	$result = curl_exec($ch);
	curl_close($ch);

	return true;
}

/**
 * Handles the request message and sends a response.
 */
function handle($request, $postback = false) {
	$messaging = getMessage($request, $postback);
	$response = reply($messaging);
	sendMessage($response);
}

if(isset($_GET["hub_verify_token"])) {
	echo authenticate($_GET);
	exit;
}

$request = json_decode(file_get_contents('php://input'));

if(empty($request)) {
	die("Bad request");
}

if(isset($request->entry[0]->messaging[0]->message)) {
	handle($request);
}
else if(isset($request->entry[0]->messaging[0]->postback)) {
	handle($request, true);
}
else {
	print_r($request);
}