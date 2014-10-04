<?php 

require '../ipamanifest.php';

$icon = '/icon.png';
$title = '0022 Deployment';
$subtitle = ' ';
$body = ' ';
$code = '';

function showPage($redeem = false){
	global $icon, $title, $subtitle, $body, $code, $itunescode;
	
	include '../header.php';
	echo '<div class="outer">';
	echo '<img src="'.$icon.'">';
	echo '<div class="inner">';
	echo '<b class="title">'.$title.'</b><br>';
	if($redeem)
		echo 'Redeeming code...';
	else
		echo $subtitle;
	echo '</div>';
	echo '</div>';
	echo '<div class="pagebody">';
	echo $body;
	echo '</div>';
	
	if($redeem){
		echo '<iframe src="';
		echo 'https://phobos.apple.com/WebObjects/MZFinance.woa/wa/freeProductCodeWizard?code=';
		echo $itunescode;
		echo '" class="itunesframe" onload="window.location = \'';
		echo '/code/'.$code.'/done';
		echo '\'"/>';
	}
	
	include '../footer.php';
	exit;
}

// Parse request URL - split to CODE and DONE
$req = explode('/', urldecode($_GET['req']), 2);
$code = $req[0];
if(count($req) == 2){
	$done = $req[1];
}
else{
	$done = '';
}

// Get corresponding app and promocode
$codes = json_decode(file_get_contents('promocodes.json'), true);
if(!array_key_exists($code, $codes) || ($done != 'done' && $done != '')){
	$subtitle = 'Unusable code';
	$body = 'The code you are trying to redeem does not exist or has expired.';
	showPage();
}

$itunescode = $codes[$code]['code'];
$app = $codes[$code]['app'];
$app_file = '../apps/'.$app.'.ipa';

// App exists on server
if(file_exists($app_file)){
	
	// Load the app
	$manifest = new AbstractIPAManifest($app, $app_file);
	// File is not damaged
	if(!$manifest->ipa_is_damaged){
	
		// Load name and icon
		$icon = $manifest->link_icon;
		$title = $manifest->ipa->display_name;
	}
}

if($done == 'done'){
	$subtitle = 'Waiting for iTunes';
	showPage();
}
else{
	showPage(true);
}

?>