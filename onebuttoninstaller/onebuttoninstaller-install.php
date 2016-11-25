<?php
/* 
    onebuttoninstaller-install.php
    
    Copyright (c) 2015 - 2017 Andreas Schmidhuber
    All rights reserved.
    
    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:
    
    1. Redistributions of source code must retain the above copyright notice, this
       list of conditions and the following disclaimer.
    2. Redistributions in binary form must reproduce the above copyright notice,
       this list of conditions and the following disclaimer in the documentation
       and/or other materials provided with the distribution.
    
    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
    ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
    WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
    DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
    ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
    (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
    LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
    ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
    (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
    SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
    
    The views and conclusions contained in the software and documentation are those
    of the authors and should not be interpreted as representing official policies,
    either expressed or implied, of the FreeBSD Project.
*/
$v = "v0.3.4";      // extension version
$appname = "OneButtonInstaller";

require_once("config.inc");

$arch = $g['arch'];
$platform = $g['platform'];
// no check necessary since the extension is for all archictectures/platforms/releases
//if (($arch != "i386" && $arch != "amd64") && ($arch != "x86" && $arch != "x64" && $arch != "rpi" && $arch != "rpi2")) { echo "\f{$arch} is an unsupported architecture!\n"; exit(1);  }
//if ($platform != "embedded" && $platform != "full" && $platform != "livecd" && $platform != "liveusb") { echo "\funsupported platform!\n";  exit(1); }

// install extension
global $input_errors;
global $savemsg;

$install_dir = dirname(__FILE__)."/";                           // get directory where the installer script resides
if (!is_dir("{$install_dir}log")) { mkdir("{$install_dir}log", 0775, true); }

// check FreeBSD release for fetch options >= 9.3
$release = explode("-", exec("uname -r"));
if ($release[0] >= 9.3) $verify_hostname = "--no-verify-hostname";
else $verify_hostname = "";
// create stripped version name
$vs = str_replace(".", "", $v);
// fetch release archive
$return_val = mwexec("fetch {$verify_hostname} -vo {$install_dir}master.zip 'https://github.com/crestAT/nas4free-onebuttoninstaller/releases/download/{$v}/onebuttoninstaller-{$vs}.zip'", true);
if ($return_val == 0) {
    $return_val = mwexec("tar -xf {$install_dir}master.zip -C {$install_dir} --exclude='.git*' --strip-components 2", true);
    if ($return_val == 0) {
        exec("rm {$install_dir}master.zip");
        exec("chmod -R 775 {$install_dir}");
        if (is_file("{$install_dir}version.txt")) { $file_version = exec("cat {$install_dir}version.txt"); }
        else { $file_version = "n/a"; }
        $savemsg = sprintf(gettext("Update to version %s completed!"), $file_version);
    }
    else { 
        $input_errors[] = sprintf(gettext("Archive file %s not found, installation aborted!"), "master.zip corrupt /"); 
        return;
    }
}
else { 
    $input_errors[] = sprintf(gettext("Archive file %s not found, installation aborted!"), "master.zip"); 
    return;
}

// install / update application on NAS4Free
if (!isset($config['onebuttoninstaller']) || !is_array($config['onebuttoninstaller'])) $config['onebuttoninstaller'] = array(); 
$config['onebuttoninstaller']['appname'] = $appname;
$config['onebuttoninstaller']['version'] = exec("cat {$install_dir}version.txt");
$config['onebuttoninstaller']['rootfolder'] = $install_dir;

// remove start/stop commands
// remove existing old rc format entries
if (is_array($config['rc']) && is_array($config['rc']['postinit']) && is_array( $config['rc']['postinit']['cmd'])) {
    $rc_param_count = count($config['rc']['postinit']['cmd']);
    for ($i = 0; $i < $rc_param_count; ++$i) {
        if (preg_match('/onebuttoninstaller/', $config['rc']['postinit']['cmd'][$i])) unset($config['rc']['postinit']['cmd'][$i]);
    }
}
if (is_array($config['rc']) && is_array($config['rc']['shutdown']) && is_array( $config['rc']['shutdown']['cmd'])) {
    $rc_param_count = count($config['rc']['shutdown']['cmd']);
    for ($i = 0; $i < $rc_param_count; ++$i) {
        if (preg_match('/onebuttoninstaller/', $config['rc']['shutdown']['cmd'][$i])) unset($config['rc']['shutdown']['cmd'][$i]);
    }
}
// remove existing entries for new rc format
if (is_array($config['rc']) && is_array($config['rc']['param'])) {
	$rc_param_count = count($config['rc']['param']);
    for ($i = 0; $i < $rc_param_count; $i++) {
        if (preg_match('/onebuttoninstaller/', $config['rc']['param'][$i]['value'])) unset($config['rc']['param'][$i]);
	}
}

if ($release[0] >= 11.0) {	// new rc format
	// postinit command
	$rc_param = [];
	$rc_param['uuid'] = uuid();
	$rc_param['name'] = "{$appname} Extension";
	$rc_param['value'] = "/usr/local/bin/php-cgi -f {$config['onebuttoninstaller']['rootfolder']}onebuttoninstaller-start.php";
	$rc_param['comment'] = "Start {$appname} Extension";
	$rc_param['typeid'] = '2';
	$rc_param['enable'] = true;
	$config['rc']['param'][] = $rc_param;
	$config['onebuttoninstaller']['rc_uuid_start'] = $rc_param['uuid'];

	unset($rc_param);
	/* shutdown command */
	$rc_param = [];
	$rc_param['uuid'] = uuid();
	$rc_param['name'] = "{$appname} Extension";
	$rc_param['value'] = "/usr/local/bin/php-cgi -f {$config['onebuttoninstaller']['rootfolder']}onebuttoninstaller-stop.php";
	$rc_param['comment'] = "Stop {$appname} Extension";
	$rc_param['typeid'] = '3';
	$rc_param['enable'] = true;
	$config['rc']['param'][] = $rc_param;
	$config['onebuttoninstaller']['rc_uuid_stop'] = $rc_param['uuid'];
}
else {
	$config['rc']['postinit']['cmd'][] = "/usr/local/bin/php-cgi -f {$config['onebuttoninstaller']['rootfolder']}onebuttoninstaller-start.php";
	$config['rc']['shutdown']['cmd'][] = "/usr/local/bin/php-cgi -f {$config['onebuttoninstaller']['rootfolder']}onebuttoninstaller-stop.php";
}

write_config();
require_once("{$config['onebuttoninstaller']['rootfolder']}onebuttoninstaller-start.php");
// finally fetch the most recent extensions list to get the latest changes if not already in the master release
$return_val = mwexec("fetch -o {$config['onebuttoninstaller']['rootfolder']}extensions.txt https://raw.github.com/crestAT/nas4free-onebuttoninstaller/master/onebuttoninstaller/extensions.txt", true);
?>
