<?php

namespace Microsite;

include 'microsite.phar';
include 'postmark.php';

$app = new App();

Config::load(__DIR__ . '/config.php');

$app->template_dirs = [
	__DIR__ . '/views',
];

$app->route('all', '/', function() { echo 'Greetings from IF.'; });

$app->route('invite_get', '/invite', function(Response $response) {
	return $response->render('invite.php');
})->get();

$app->share('postmark', function(){
	return new \PostMark(Config::get('postmark_key'), Config::get('from_address'));
});

$app->route('invite_post', '/invite', function(Response $response, App $app) {
	$email = $_POST['email'];

	$response['shipname'] = 'Nostromo';
	$plain = $response->render('plain_invite.php');
	$html = $response->render('html_invite.php');

	$result = $app->postmark()->to($email)
		->subject('Congratulations, Captain!')
		->plain_message($plain)
		->html_message($html)
		->send();
	
	echo '<p>The invitation was sent ' . ($result ? 'successfully' : 'unsuccessfully') . '.</p>';

})->post();

$app->route('inbound_email', '/inbound/mail', function(App $app) {
	$data = json_decode(file_get_contents('php://input'));
	$output = print_r($data, 1) . "\r\n----------\r\n";
	file_put_contents(__DIR__ . '/inbound.log', $output, FILE_APPEND);

	$from = $data->From;
	
	$result = $app->postmark()->to($from)
		->subject('Sample Mailback')
		->plain_message('The response system is not yet complete, but notice that your message was received.')
		->send();

	echo 'inbound - ok';
});

$app->run();


?>
