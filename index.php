<?php
include("vendor/autoload.php");

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

session_start();
set_time_limit(0);
ob_implicit_flush(true);

define('CLIENT_ID', getenv('GOOGLE_CLIENT_ID'));
define('CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET'));
define('REDIRECT_URL', getenv('GOOGLE_REDIRECT_URI'));

$client = new Google_Client();
$client->setClientId(CLIENT_ID);
$client->setClientSecret(CLIENT_SECRET);
$client->addScope(Google_Service_Gmail::GMAIL_READONLY);
$client->setRedirectUri(REDIRECT_URL);

function getMessageFrom($headers) {

	$from = null;

	foreach($headers as $header) {
		if($header->getName() === "X-Original-From") return $header->getValue();
		if($header->getName() === "From") $from = $header->getValue();
	}

	return $from;

}

if(isset($_GET['reauth'])) {
	$_SESSION['access_token'] = null;
	unset($_SESSION['access_token']);
	header("Location: /");
	exit();
}

if(isset($_SESSION['access_token'])) {

	if(!isset($_POST['query'])) {
		echo "<form method='POST' action='/'>
		<input type='text' name='query' placeholder='Mail query...' /> 
		<input type='number' name='max' value='1000' placeholder='Num records...' /> 
		<button type='submit'>Extract</button>
		</form>";
		exit();
	}

	$query = $_POST['query'];
	$max = intval($_POST['max']);

	$index = 0;
	$group = 0;
	$remaining = 500;
	$nextPageToken = null;

	echo "<h3>Query: '{$query}', max={$max}</h3>";
	echo "<a href='/'>New query</a> | <a href='/?reauth=1'>Re-authenticate</a> ";
	echo "<hr />";

	$client->setAccessToken($_SESSION['access_token']);

	$gmail = new Google_Service_Gmail($client);

	echo "<table cellpadding='1' cellspacing='0' border='1'>";
	echo "<thead><tr><th>Index</th><th>Group</th><th>Batch</th><th>Name</th><th>E-mail</th></tr></thead>";
	echo "<tbody>";

	while($remaining > 0) {

		$client->setUseBatch(false);

		$params = ['q' => $query, 'maxResults' => 500];

		if($nextPageToken) $params['pageToken'] = $nextPageToken;

		$messages = $gmail->users_messages->listUsersMessages('me', $params);
		$remaining = $messages->getResultSizeEstimate() - ($group * 500);
		$nextPageToken = $messages->getNextPageToken();
		$group++;

		$client->setUseBatch(true);

		foreach(collect($messages)->chunk(100) as $chunk => $chunkList) {

			$batch = new Google_Http_Batch($client);

			foreach($chunkList as $message) {
				$batch->add($gmail->users_messages->get('me', $message->getId(), ['format' => 'metadata', 'metadataHeaders' => ['From', 'X-Original-From']]), $message->getId());
			}

			$batchMessages = $batch->execute();

			foreach($batchMessages as $message) { /* @var $message Google_Service_Gmail_Message */
				$headers = $message->getPayload()->getHeaders();

				$from = getMessageFrom($headers);
				$fromName = substr($from, 0, strpos($from, '<'));
				$fromEmail = str_replace(['<', '>'], '', substr($from, strpos($from, '<') + 1));

				echo "<tr><td>{$index}</td><td>{$group}</td><td>{$chunk}</td><td>{$fromName}</td><td>{$fromEmail}</td></tr>";

				$index++;

				flush();
				ob_flush();
			}

		}
	}


	echo "</tbody>";
	echo "</table>";
	//dd($batchMessages);


} else if(isset($_GET['code'])) {

	$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
	$_SESSION['access_token'] = $token;

	header("Location: /");

} else {
	$loginURL = $client->createAuthUrl(Google_Service_Gmail::GMAIL_READONLY);

	echo "<a href='{$loginURL}'>Login here</a>";
}

