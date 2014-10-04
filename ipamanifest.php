<?php
/*
 * ipamanifest.php, generate wireless app distribution mainifest
 * directly from IPA file.
 *
 * More information can be found on Apples developer site:
 * http://developer.apple.com/library/ios/#featuredarticles/FA_Wireless_Enterprise_App_Distribution/Introduction/Introduction.html
 *
 * Copyright (c) 2010 <mattias.wadman@gmail.com>
 *
 * MIT License:
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 */

require_once("cfpropertylist/CFPropertyList.php");

class IOSVersion {
  public $models;
  public $major;
  public $minor;

  function __construct($models, $major, $minor) {
    $this->models = $models;
    $this->major = $major;
    $this->minor = $minor;  
  }

  static public function from_agent($agent) {
  
  /*
  ^(?:(?:(?:Mozilla/\d\.\d\s*\()+|Mobile\s*Safari\s*\d+\.\d+(\.\d+)?\s*)(?:iPhone(?:\s+Simulator)?|iPad|iPod);\s*(?:U;\s*)?(?:[a-z]+(?:-[a-z]+)?;\s*)?CPU\s*(?:iPhone\s*)?(?:OS\s*\d+_\d+(?:_\d+)?\s*)?(?:like|comme)\s*Mac\s*O?S?\s*X(?:;\s*[a-z]+(?:-[a-z]+)?)?\)\s*)?(?:AppleWebKit/\d+(?:\.\d+(?:\.\d+)?|\s*\+)?\s*)?(?:\(KHTML,\s*(?:like|comme)\s*Gecko\s*\)\s*)?(?:Version/\d+\.\d+(?:\.\d+)?\s*)?(?:Mobile/\w+\s*)?(?:Safari/\d+\.\d+(\.\d+)?.*)?$
  */
  
  /*preg_match('^(?:(?:(?:Mozilla/\d\.\d\s*\()+|Mobile\s*Safari\s*\d+\.\d+(\.\d+)?\s*)(?:iPhone(?:\s+Simulator)?|iPad|iPod);\s*(?:U;\s*)?(?:[a-z]+(?:-[a-z]+)?;\s*)?CPU\s*(?:iPhone\s*)?(?:OS\s*\d+_\d+(?:_\d+)?\s*)?(?:like|comme)\s*Mac\s*O?S?\s*X(?:;\s*[a-z]+(?:-[a-z]+)?)?\)\s*)?(?:AppleWebKit/\d+(?:\.\d+(?:\.\d+)?|\s*\+)?\s*)?(?:\(KHTML,\s*(?:like|comme)\s*Gecko\s*\)\s*)?(?:Version/\d+\.\d+(?:\.\d+)?\s*)?(?:Mobile/\w+\s*)?(?:Safari/\d+\.\d+(\.\d+)?.*)?$', $agent, $m);
  print_r($m);*/
  
  /*
  Mozilla/5.0 (iPod touch; CPU iPhone OS 7_0_3 like Mac OS X) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11B511 Safari/9537.53
  */
  
    if(!preg_match('/\((?P<model>iPhone|iPad|iPod|iPod touch)(?: Simulator)?;.*OS (?P<major>\d+)_(?P<minor>\d+)(?:_\d+)?/', $agent, $m))
      return FALSE;
      
    if($m["model"] == 'iPod' || $m["model"] == 'iPod touch')
    	$m["model"] = 'iPod Touch';

    return new IOSVersion(array($m["model"]), (int)$m["major"], (int)$m["minor"]);
  }

  static public function from_family_version($family, $version) {
    if(!preg_match('/^(?P<major>\d+)\.(?P<minor>\d+).*$/', $version, $m))
      return FALSE;

    if(!is_array($family))
      $family = array($family);
    $models = array();
    foreach($family as $f) {
      if($f == 1) {
	$models[] = "iPhone";
	$models[] = "iPod Touch";
	$models[] = "iPad";
      } else if($f == 2) {
	$models[] = "iPad";
      }
    }

    return new IOSVersion($models, (int)$m["major"], (int)$m["minor"]);
  }

  public function less_than($other) {
    return
      $this->major < $other->major ||
      ($this->major == $other->major && $this->minor < $other->minor);
  }

  public function has_model($other) {
    foreach($other->models as $m)
      if(in_array($m, $this->models))
		return TRUE;

    return FALSE;
  }

  public function version_string() {
    return $this->major . "." . $this->minor;
  }
}

class MobileProvisionFile {
	function __construct($ipa){
		$this->exists = false;
		if(!$ipa->exists("embedded.mobileprovision"))
			return;
		$this->exists = true;
		$data = strstr(strstr($ipa->read("embedded.mobileprovision"), '<?xml'), '</plist>', true).'</plist>';
		$p = new CFPropertyList();
		$p->parse($data);
		$this->info = $p->toArray();
		$this->name= $this->info["Name"];
		$this->team_name= $this->info["TeamName"];
		$this->created = $this->info["CreationDate"];
		$this->expires = $this->info["ExpirationDate"];
		$this->devices = $this->info["ProvisionedDevices"];
		$this->devices_count = count($this->devices);
	}
	
	public function is_entitled($udid){
		if(!$this->exists || !$this->devices)
			return false;
		return in_array($udid, $this->devices);
	}
	
	public function is_expired(){
		return ($this->expires <= time());
	}
	
	public function expires_in(){
		$dt = $this->expires - time();
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
	
}

class IPAFile {
  private $zip;
  private $base_path;

  function __construct($ipafile) {
    $this->zip = new ZipArchive();
    
    if($this->zip->open($ipafile) !== true){
   		$this->is_damaged = true;
   		return;
   	}
   	$this->is_damaged = false;

    // find Payload/Name.app prefix inside IPA
    for($i = 0; $i < $this->zip->numFiles; $i++) {
      $dirs = explode("/", $this->zip->getNameIndex($i));
      if(count($dirs) > 2) {
	$this->base_path = "{$dirs[0]}/{$dirs[1]}";
	break;
      }
    }

    $p = new CFPropertyList();
    $p->parse($this->read("Info.plist"));
    $this->info = $p->toArray();
    $s = $this->zip->statName($this->ipa_name($this->info["CFBundleExecutable"]));
    $this->created = $s["mtime"];
    $this->filesize = filesize($ipafile);
    $this->display_size = $this->convert_size($this->filesize);
    $this->id = $this->info["CFBundleIdentifier"];
    $this->version = $this->info["CFBundleShortVersionString"];
    $this->build = $this->info["CFBundleVersion"];
    $this->ios_version = IOSVersion::from_family_version($this->info["UIDeviceFamily"], $this->info["MinimumOSVersion"]);
    $this->name = $this->info["CFBundleName"];
    $this->display_name = $this->info["CFBundleDisplayName"];
    $this->icon_name = $this->find_icon_name();
    $this->has_prerendered_icon = (isset($this->info["UIPrerenderedIcon"]) && $this->info["UIPrerenderedIcon"]);
    $this->mobileprovision = new MobileProvisionFile($this);
  }

  private function convert_size($size){
  	$sizes=array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'); 
  	for($i=0; $size>1000 && $i<count($sizes)-1; $i++){
      	$size/=1024; 
  	}
  	if($i<2){
  		$size=round($size,0);
  	}
  	else if($i == 2){
  		$size=round($size,1);
  	}
  	else{
  		$size=round($size,2);
  	}
  	$size.=" ".$sizes[$i];
  	return $size;
  }
  

  public function ipa_name($name) {
    return "{$this->base_path}/{$name}";
  }

  public function read($name) {
    return $this->zip->getFromName($this->ipa_name($name));
  }
  
  public function stream($name) {
    return $this->zip->getStream($this->ipa_name($name));
  }
  
  public function stats($name) {
    return $this->zip->statName($this->ipa_name($name));
  }

  public function exists($name) {
    return $this->zip->statName($this->ipa_name($name)) != FALSE;
  }			 

  public function strip_image_name($name) {
    $pi = pathinfo($name);

    if(empty($pi["extension"]))
      $ext = "png";
    else
      $ext = $pi["extension"];

    if($pi["dirname"] == ".")
      $prefix = "";
    else
      $prefix = "{$pi["dirname"]}/";

    if($pi["extension"] == "")
      $name = $pi["basename"];
    else
      $name = substr($pi["basename"], 0, -(strlen($ext) + 1));

    if(substr($name, -3, 3) == "@2x")
      $name = substr($name, 0, -3);

    return "$prefix$name";
  }
  
  public function find_icon_name() {
    $icons = array();
    if(isset($this->info["CFBundleIconFiles"]))
      $icons = $this->info["CFBundleIconFiles"];
    elseif(isset($this->info["CFBundleIcons"]["CFBundlePrimaryIcon"]["CFBundleIconFiles"]))
      $icons = $this->info["CFBundleIcons"]["CFBundlePrimaryIcon"]["CFBundleIconFiles"];
    $icons[] = $this->info["CFBundleIconFile"];
    $icons[] = "Icon";
    $icons[] = "icon";
    foreach($icons as $icon) {
      $stripped = $this->strip_image_name($icon);
      foreach(array("@2x.png", ".png") as $ext) {
	if($this->exists("$stripped$ext"))
	  return $stripped.$ext;
      }
    }

    return FALSE;
  }
}

class AbstractIPAManifest {
  private $ipafile;

  function __construct($file_id, $ipafile) {
    $this->ipafile = $ipafile;
    
    $this->ipa = new IPAFile($ipafile);
    $this->ipa_is_damaged = $this->ipa->is_damaged;
    if($this->ipa_is_damaged)
    	return;
    	
    $this->link_ipa = $this->link($file_id, "ipa");
    if(isset($this->ipa->icon_name))
      $this->link_icon = $this->link($file_id, "icon");
    $this->link_manifest = $this->link($file_id, "manifest");
    $this->link_display_image = $this->link($file_id, "display-image");
    if($this->ipa->exists("iTunesArtwork"))
      $this->link_full_size_image = $this->link($file_id, "full-size-image");
    if($this->ipa->exists("embedded.mobileprovision"))
      $this->link_mobileprovision = $this->link($file_id, "mobileprovision");
    $this->link_itms =
      "itms-services://" .
      "?action=download-manifest" .
      "&url=" . urlencode($this->link_manifest);
  }
 
  // override this method and return URLs that will end up in the dispatch method
  public function link($file_id, $action) {
  	//if($action == "ipa")
  	//	return 'http://0022.nl/apps/'.urlencode($file_id).'.ipa';	
  	return 'http://0022.nl/'.urlencode($file_id).'/'.urlencode($action);
  }

  public function dispatch($action) {
    if($action == "ipa")
      return $this->ipa_data();
    else if($action == "icon")
      return $this->icon_data();
    else if($action == "manifest")
      return $this->manifest_data();
    else if($action == "display-image")
      return $this->display_image_data();
    else if($action == "full-size-image")
      return $this->full_size_image_data();
    else if($action == "mobileprovision")
      return $this->mobileprovision_data();
    else
      return false;
  }
  
  private function attachment_filename($s) {
    $len = strlen($s);
    for($i = 0; $i < $len; $i++)
      if(strstr(" .,:+-abcdefghijklmnopqrstuvwxyzABCEDFGHIJKLMNOPQRSTUVWXYZ0123456789", $s[$i]) == FALSE)
        $s[$i] = "_";

    return $s;
  }
  
  public function ipa_data() {
    $filename = $this->attachment_filename(basename($this->ipafile));
    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Content-Length: " . filesize($this->ipafile));
    readfile($this->ipafile);
    return true;
  }

  public function icon_data() {
    header("Content-Type: image/png");    
    echo $this->ipa->read($this->ipa->icon_name);
    return true;
  }

  public function display_image_data() {
    return $this->icon_data();
  }

  public function full_size_image_data() {
    header("Content-Type: image/png");
    echo $this->ipa->read("iTunesArtwork");
    return true;
  }

  public function mobileprovision_data() {
    $filename = $this->attachment_filename($this->ipa->name) . ".mobileprovision";
    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    echo $this->ipa->read("embedded.mobileprovision");
    return true;
  }

  public function manifest_data() {
    $plist = new CFPropertyList();
    $dict = new CFDictionary();
    $plist->add($dict);
    $items = new CFArray();
    $dict->add("items", $items);
    $download = new CFDictionary();
    $items->add($download);
    $assets = new CFArray();
    $metadata = new CFDictionary();
    $download->add("assets", $assets);
    $download->add("metadata", $metadata);
    
    $software_package = new CFDictionary();
    $assets->add($software_package);
    $software_package->add("kind", new CFString("software-package"));
    $software_package->add("url", new CFString($this->link_ipa));

    $display_image = new CFDictionary();
    $assets->add($display_image);
    $display_image->add("kind", new CFString("display-image"));
    $display_image->add("url", new CFString($this->link_display_image));
    if($this->has_prerendered_icon)
      $display_image->add("needs-shine", False);

    if($this->ipa->exists("iTunesArtwork")) {
      $full_size_image = new CFDictionary();
      $assets->add($full_size_image);
      $full_size_image->add("kind", new CFString("full-size-image"));
      $full_size_image->add("url", new CFString($this->link_full_size_image));
      if($this->has_prerendered_icon)
	$full_size_image->add("needs-shine", False);
    }

    $metadata->add("bundle-identifier", new CFString($this->ipa->id));
    $metadata->add("bundle-version", new CFString($this->ipa->version));
    $metadata->add("kind", new CFString("software"));
    // iTunes?
    //$metadata->add("subtitle", new CFString("subtitle"));
    $metadata->add("title", new CFString($this->ipa->display_name));

    header("Content-Type: text/xml");
    echo $plist->toXML(); 
    return true;
  }
}

?>
