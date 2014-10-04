<?php 
header('Content-Type: application/x-apple-aspen-config');
//echo file_get_contents('connect.mobileconfig');
//exit;

$user = $_GET['user'];

echo'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
        <dict>
    	    <key>PayloadDisplayName</key>
    	    <string>0022 Enrollment</string>
    	    <key>PayloadOrganization</key>
    	    <string>0022.nl</string>
    	    <key>PayloadDescription</key>
    	    <string>This profile extracts the UDID of your device and signs in at 0022.</string>
    	    <key>PayloadVersion</key>
    	    <integer>1</integer>
    	    <key>PayloadUUID</key>
    	    <string>A0C8E357-34C2-86C4-AD0C-C9896F3A738F</string>
    	    <key>PayloadIdentifier</key>
    	    <string>nl.divbyzero.connect-profile</string>
    	    <key>PayloadType</key>
    	    <string>Profile Service</string>
    	    <key>PayloadContent</key>
    	    <dict>
                <key>URL</key>
                <string>http://www.0022.nl/connect/enroll/?user='.$user.'</string>
                <key>DeviceAttributes</key>
                <array>
                    <string>UDID</string>
                </array>
             </dict>
        </dict>
</plist>
';
?>
