<?php 
// Load libs
require 'ipamanifest.php';

// Function definitions
function showPage($title = '0022 Deployment', $subtitle = 'An error occurred', $body = '', $status = 200){
	if($status == 404)
		header("HTTP/1.0 404 Not Found");
	include 'header.php';
	echo '<div class="outer">';
	echo '<img src="/icon.png">';
	echo '<div class="inner">';
	echo '<b class="title">'.$title.'</b><br>';
	echo $subtitle;
	echo '</div>';
	echo '</div>';
	echo '<div class="pagebody">';
	echo $body;
	echo '</div>';
	include 'footer.php';
	exit;
}

// Search for IPAs
function scanIPAs($filterkey, $filtervalue){
	$files = array();
	$ids = array();
	$names = array();
	$versions = array();
	$builds = array();
	$dates = array();
	if($handle = opendir('apps')){
		while(false !== ($file = readdir($handle))){
			if($file != '.' && $file != '..' && substr($file, -4) == '.ipa'){// && substr($file, 0, strlen($manifest->ipa->name)) == $manifest->ipa->name){
				$temp_ipa = new IPAFile('apps/'.$file);
				if($temp_ipa->is_damaged)
					continue;
				if(($filterkey == 1 && $temp_ipa->id == $filtervalue) || ($filterkey == 2 && $temp_ipa->mobileprovision->is_entitled($filtervalue))){
					$files[] = substr($file, 0, -4);
					$ids[] = $temp_ipa->id;
					$names[] = $temp_ipa->display_name;
					$versions[] = $temp_ipa->version;
					$builds[] = $temp_ipa->build;
					$dates[] = $temp_ipa->created; //same version/build? latest on top
				}
			}
		}
		closedir($handle);
	}
	
	return array($files, $ids, $names, $versions, $builds, $dates);
}

function relativeDate($date){
	$dt = abs(time() - $date);
	if($dt <= 0){
		return false;
	}
	elseif($dt < 60){
		if($dt == 1){
			return '1 second';
		}
		else{
			return $dt.' seconds';
		}
	}
	elseif($dt < 60*60){
		if(round($dt/60) == 1){
			return '1 minute';
		}
		else{
			return round($dt/60).' minutes';
		}
	}
	elseif($dt < 60*60*24){
		if(round($dt/60/60) == 1){
			return '1 hour';
		}
		else{
			return round($dt/60/60).' hours';
		}
	}
	elseif($dt < 60*60*24*7){
		if(round($dt/60/60/24) == 1){
			return '1 day';
		}
		else{
			return round($dt/60/60/24).' days';
		}
	}
	elseif($dt < 60*60*24*30){
		if(round($dt/60/60/24/7) == 1){
			return '1 week';
		}
		else{
			return round($dt/60/60/24/7).' weeks';
		}
	}
	elseif($dt < 60*60*24*365){
		if(round($dt/60/60/24/30) == 1){
			return '1 month';
		}
		else{
			return round($dt/60/60/24/30).' months';
		}
	}
	else{
		if(round($dt/60/60/24/365) == 1){
			return '1 year';
		}
		else{
			return round($dt/60/60/24/365).' years';
		}
	}
}

// Analyse user agent
$userAgent = $_SERVER['HTTP_USER_AGENT'];
$userOS = IOSVersion::from_agent($userAgent);

// Sign in with UDID
if(isset($_GET['udid'])){
	setcookie('0022UDID', $_GET['udid'], time()+(100*24*60*60), '/');
	$_COOKIE['0022UDID'] = $_GET['udid'];
}

// Home page
if(!isset($_GET['req'])){
	//$_COOKIE['0022UDID'] = '9b1935894777887fe0da636a0eb97811902e2768'; //iPad
	//$_COOKIE['0022UDID'] = '4fd42e4d653f6aa266b632c1ae9935bd2a30da8e'; //iPhone
	if(isset($_COOKIE['0022UDID']) && $_COOKIE['0022UDID'] != ''){
		// Scan IPAs, filter on UDID
		list($files, $ids, $names, $versions, $builds, $dates) = scanIPAs(2, $_COOKIE['0022UDID']);
		array_multisort($dates, SORT_DESC, $builds, SORT_DESC, $names, SORT_DESC, $files, SORT_ASC, $ids);
		
		$applist = '';
		$groups = array();
		
		for($i = 0; $i < count($files); $i++){
			if(in_array($ids[$i], $groups))
				continue;
			$groups[] = $ids[$i];
			//$applist .= $names[$i].' - '.$files[$i].' - v'.$versions[$i].'  b'.$builds[$i].'<br>';
			
			$applist .= '<div class="springboard" onclick="window.location=\'/'.$files[$i].'\'; return false;">'; //class="springboard" 
			$applist .= '<img src="/'.$files[$i].'/icon">';
			$applist .= '<div class="inner">';
			$applist .= '<b class="title">'.$names[$i].'</b><br>';
			$applist .= relativeDate($dates[$i]).' ago';
			$applist .= '</div>';
			$applist .= '</div>';
			
		}
		
		showPage('0022 Deployment', 'Logged in <a href="#" onclick="if(!window.confirm(\'Are you sure you want to log out?\')){return false;} document.cookie = \'0022UDID=;path=/\'; window.location=\'/\'; return false;">~'.substr($_COOKIE['0022UDID'], -6).'</a>', $applist);
	}
	else{
		//showPage('0022 Deployment', '<a href="#" onclick="var ttl = new Date(); ttl.setTime(ttl.getTime()+(100*24*60*60*1000)); var udid = window.prompt(\'Enter your UDID:\', \'\'); if(!udid){ return false; } document.cookie = \'0022UDID=\' + udid + \';expires=\' + ttl.toGMTString() + \';path=/\'; window.location=\'/\'; return false;">Sign in with your UDID</a>');
		if($userOS === false)
			showPage('0022 Deployment', 'No application selected');
		else
			showPage('0022 Deployment', '<a href="#" onclick="window.location=\'/connect\'; return false;">Sign in with your UDID</a>');
	}
}

// Parse request URL - split to APP ID and ACTION
$req = explode('/', urldecode($_GET['req']), 2);
$app = $req[0];
if(count($req) == 2){
	$cmd = $req[1];
}

// Catch DETAILS action
if($cmd == 'details'){
	$details = true;
	$cmd = '';
}
	
// App does not exist
if(!file_exists('apps/'.$app.'.ipa')){
	showPage('Error', 'App not found', 'The application you were looking for does not exist.', 404);
}

// Load the app
$manifest = new AbstractIPAManifest($app, 'apps/'.$app.'.ipa');

// File can not be read
if($manifest->ipa_is_damaged){
	showPage('Error', 'Application is damaged', 'The application you requested seems to be damaged. The upload could still be in progress, in which case you can <a href="'.$_SERVER['REQUEST_URI'].'">try again</a> in a few minutes. Otherwise, contact the developer about this problem.');
}
	
// Execute requested action
if(isset($cmd) && $cmd != ''){
	if($manifest->dispatch($cmd))
		exit;
	else
		showPage('Error', 'Resource not found', 'The resource you were looking for does not exist.', 404);
}

//*
//$userOS = false; 									// Desktop 
//$userOS = new IOSVersion(array('iPhone'), 3, 0);	// No OTA support (iOS <4)
//$userOS = new IOSVersion(array('iPhoner'), 2, 0);	// Unsupported device
//$userOS = new IOSVersion(array('iPhone'), 4, 0);	// Unsupported iOS version
//$userOS = new IOSVersion(array('iPhone'), 6, 0);	// OK :)
//*/

$download = false;
$install = false;
$message = '';

// Error: Incompatible device
if($userOS === false || !$manifest->ipa->ios_version->has_model($userOS)){
	$message .= '<fieldset>';
	$message .= '<legend><b>Incompatible device</b></legend>';
	$message .= $manifest->ipa->name.' only runs on <b>';
	$array = $manifest->ipa->ios_version->models;
	$message .= join('</b> and <b>', array_filter(array_merge(array(join('</b>, <b>', array_slice($array, 0, -1))), array_slice($array, -1)))).'</b>. Open this page on a compatible device to install '.$manifest->ipa->name.' directly.';
	
	// Non-iOS device -- Allow download
	if($userOS === false){
		$message .= '<br><br>You can also choose to install manually: Download the application, import it to your iTunes library and sync your device.';
		$download = true; 
	}
	
	$message .= '</fieldset>';
}
// Error: OTA not supported by iOS < 4.0
elseif($userOS->major < 4){
	$message .= '<fieldset>';
	$message .= '<legend><b>Incompatible device</b></legend>';
	$message .= 'Over The Air app distribution requires <b>iOS '.$minOS->version_string().'</b> or higher. Upgrade your '.$userOS->models[0].' or visit this page on a computer to perform a manual install.';
	$message .= '</fieldset>';
}
// Error: App requires higher iOS version
elseif($userOS->less_than($manifest->ipa->ios_version)){
	 $message .= '<fieldset>';
	 $message .= '<legend><b>Incompatible iOS version</b></legend>';
	 $message .= $manifest->ipa->name.' requires <b>iOS '.$manifest->ipa->ios_version->version_string().'</b> or higher.';
	 $message .= '</fieldset>';
}
// Success: Able to install OTA!
else{
	$message .= '<fieldset>';
	$message .= '<legend><b>Over The Air installation</b></legend>';
	$message .= 'You can download and install '.$manifest->ipa->name.' directly to your device by clicking on the button below.';
	$message .= '</fieldset>';	
	$install = true;
}

// Show application logo, name and version number
include 'header.php';

echo '<div class="outer">';

if(isset($_COOKIE['0022UDID']) && $_COOKIE['0022UDID'] != '')
	echo '<a href="#" onclick="window.location=\'/\'; return false;">';
echo '<img src="'.$manifest->link_icon.'">';
if(isset($_COOKIE['0022UDID']) && $_COOKIE['0022UDID'] != '')
	echo '</a>';

echo '<div class="inner">';
echo '<b class="title" id="title">'.$manifest->ipa->display_name.'</b><br>';
echo '<span id="subtitle">';
echo 'version <b>'.$manifest->ipa->version.'</b> &bull; build <b>'.$manifest->ipa->build.'</b>';
echo '</span>';
echo '</div>';

echo '</div>';

// Show the information messages
echo '<div class="inner" id="info">';
echo $message;

if($install || $download){

	// Show information about the embedded provisioning profile
	if($manifest->ipa->mobileprovision->exists){
		echo '<fieldset>';
		echo '<legend><b>Mobile provisioning</b></legend>';
		if($manifest->ipa->mobileprovision->is_expired())
			echo 'The embedded provisioning profile has <b>expired!</b> You need to manually install a valid profile before you are able to install this app.';
		else
			echo 'The embedded provisioning profile expires in <b>'.relativeDate($manifest->ipa->mobileprovision->expires).'</b>. Your '.($userOS === false ? 'device' : $userOS->models[0]).' has to be registered for use with '.$manifest->ipa->name.' by the developer. If they sent you here, it probably already is.';
		echo '</fieldset>';
	}

	// Show release notes when available
	$notes = json_decode(file_get_contents('apps/releasenotes.json'), true);
	if(array_key_exists($app, $notes)){
		echo '<fieldset>';
		echo '<legend><b>Release notes</b></legend>';
		echo $notes[$app];
		echo '</fieldset>';
	}
	
}

// Show extra details
if($details){	
	echo '<fieldset>';
	echo '<legend><b>Bundle details</b></legend>';
	echo '<table>';
	echo '<tr><td><b>Identifier:</b></td><td>'.$manifest->ipa->id.'</td></tr>';
	echo '<tr><td><b>Name:</b></td><td>'.$manifest->ipa->name.'</td></tr>';
	echo '<tr><td><b>Display name:</b></td><td>'.$manifest->ipa->display_name.'</td></tr>';
	echo '<tr><td><b>Version:</b></td><td>'.$manifest->ipa->build.'</td></tr>';
	echo '<tr><td><b>Short version:</b></td><td>'.$manifest->ipa->version.'</td></tr>';
	echo '<tr><td><b>Created:</b></td><td>'.date('j-m-Y', $manifest->ipa->created).' at '.date('H:i:s', $manifest->ipa->created).'</td></tr>';
	echo '</table>';
	echo '</fieldset>';
	
	//*
	if($manifest->ipa->mobileprovision->exists){
		echo '<fieldset>';
		echo '<legend><b>Provision profile details</b></legend>';
		echo '<table>';
		echo '<tr><td><b>Name:</b></td><td>'.$manifest->ipa->mobileprovision->name.'</td></tr>';
		echo '<tr><td><b>Team name:</b></td><td>'.$manifest->ipa->mobileprovision->team_name.'</td></tr>';
		echo '<tr><td><b>Created:</b></td><td>'.date('j-m-Y', $manifest->ipa->mobileprovision->created).' at '.date('H:i:s', $manifest->ipa->mobileprovision->created).'</td></tr>';
		echo '<tr><td><b>Expires:</b></td><td>'.date('j-m-Y', $manifest->ipa->mobileprovision->expires).' at '.date('H:i:s', $manifest->ipa->mobileprovision->expires).'</td></tr>';
		echo '<tr><td><b>Devices count:</b></td><td>'.$manifest->ipa->mobileprovision->devices_count.'</td></tr>';
		echo '<tr><td><b>Devices:</b></td><td>'.implode($manifest->ipa->mobileprovision->devices, '<br>').'</td></tr>';
		echo '</table>';
		echo '</fieldset>';
	}
}

// End of information blocks
echo '</div>';

// Show download or install button
$warn_profile = ($manifest->ipa->mobileprovision->is_expired()) ? 'onclick="return window.confirm(\'The provisioning profile embedded in this app has expired. Have you manually installed a valid profile?\')"' : '';

if($install){
	echo '<a href="'.$manifest->link_itms.'" class="fat"'.$warn_profile.'>';
	echo 'INSTALL ('.$manifest->ipa->display_size.')';
	echo '</a>';
}
elseif($download){
	echo '<a href="'.$manifest->link_ipa.'" class="fat"'.$warn_profile.'>';
	echo 'DOWNLOAD ('.$manifest->ipa->display_size.')';
	echo '</a>';
}

// Show version navigator
if($download || $install){

	// Scan IPAs, filter on bundleID
	list($files, , , $versions, $builds, $dates) = scanIPAs(1, $manifest->ipa->id);	
	array_multisort($versions, SORT_DESC, $builds, SORT_DESC, $dates, SORT_DESC, $files);
	
	// Other versions found
	if(count($files) > 1){
	
		echo '<div class="versionnav">';
		echo 'OTHER VERSIONS';
		echo '<select onchange="document.getElementById(\'title\').innerHTML = \'Loading...\'; document.getElementById(\'subtitle\').innerHTML = \''.$manifest->ipa->name.'\'; document.getElementById(\'info\').innerHTML = \'\'; window.scrollTo(0, 0); window.location=\'/\'+this.value">';
		
		$current_version = "";
		for($i = 0; $i < count($files); $i++){
			if($versions[$i] != $current_version){
				if($i > 0)
					echo '</optgroup>';
				$current_version = $versions[$i];
				echo '<optgroup label="Version '.$current_version.'">';
			}
			echo '<option value="'.$files[$i].'"';
			if($app == $files[$i])
				echo ' SELECTED';
			echo '>Build '.$builds[$i].'</option>';
		}
		echo '</optgroup>';
		echo '</select>';
		echo '</div>';
	
	}
}

include 'footer.php';
 ?>