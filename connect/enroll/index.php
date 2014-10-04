<?php 

require '../../cfpropertylist/CFPropertyList.php';

$data = file_get_contents('php://input');

$data = strstr($data, '<?xml');
if($data === false)
	exit;
$data = strstr($data, '</plist>', true);
if($data === false)
	return false;
$data .= '</plist>';

$p = new CFPropertyList();
$p->parse($data);
$info = $p->toArray();

file_put_contents('../data.txt', 'time: '.time()."\n".'user: '.$_GET['user']."\n".'udid: '.$info['UDID']);

//echo 'Hello';

header('Location: http://www.0022.nl/connect/finish?user='.$_GET['user'].'&udid='.$info['UDID']);
//echo '<b>UDID: </b>'.htmlentities($_GET['udid']);
//header('Content-Type: application/x-apple-aspen-config');
//echo file_get_contents('webclip.mobileconfig');
//echo file_get_contents('../connect.mobileconfig');
exit;
?>