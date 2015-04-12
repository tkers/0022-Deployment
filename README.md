# 0022-Deployment
OTA distribution for iOS apps

PHP application to easily deploy iOS applications to testers (or enterprise users). It scans the applications folder for `.ipa` files and displays them to the user.

Although this might sound like a simple file browser, the application also generates the manifest file that is needed to install apps over the air (OTA), which enables users to directly install the app from an iPhone or iPad.

This platform also checks if the iOS app is compatible with the user's current device, and whether or not the provisioning profile included in the app has expired. Multiple versions of the same app are grouped together with the option to include release notes for each version.


**Warning:** This is a faily old project, and Apple *might* have changed the way of properly doing OTA distribution for iOS apps. 
