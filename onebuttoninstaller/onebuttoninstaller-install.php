<?php
/* 
    onebuttoninstaller-install.php
    
    Copyright (c) 2015 - 2020 Andreas Schmidhuber
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
*/
$v = "v0.4.1";															// extension version
$appName = "OneButtonInstaller";
$configName = "onebuttoninstaller";

require_once("config.inc");

$arch = $g['arch'];
$platform = $g['platform'];
// no check necessary since the extension is for all archictectures/platforms/releases => N: warnings for livecd && liveusb within the pages 
//if (($arch != "i386" && $arch != "amd64") && ($arch != "x86" && $arch != "x64" && $arch != "rpi" && $arch != "rpi2")) { echo "\f{$arch} is an unsupported architecture!\n"; exit(1);  }
//if ($platform != "embedded" && $platform != "full" && $platform != "livecd" && $platform != "liveusb") { echo "\funsupported platform!\n";  exit(1); }

// install extension
global $input_errors;
global $savemsg;

$install_dir = dirname(__FILE__);                           			// get directory where the installer script resides

// check FreeBSD release for fetch options >= 9.3
$release = explode("-", exec("uname -r"));
if ($release[0] >= 9.3) $verify_hostname = "--no-verify-hostname";
else $verify_hostname = "";
// create stripped version name
$vs = str_replace(".", "", $v);
// fetch release archive
$return_val = mwexec("fetch {$verify_hostname} -vo {$install_dir}/master.zip 'https://github.com/crestAT/nas4free-{$configName}/releases/download/{$v}/{$configName}-{$vs}.zip'", false);
if ($return_val == 0) {
    $return_val = mwexec("tar -xf {$install_dir}/master.zip -C {$install_dir} --exclude='.git*' --strip-components 2", true);
    if ($return_val == 0) {
        exec("rm {$install_dir}/master.zip");
        exec("chmod -R 775 {$install_dir}");
        require_once("{$install_dir}/ext/extension-lib.inc");
        $configFile = "{$install_dir}/ext/{$configName}.conf";
        if (is_file("{$install_dir}/version.txt")) $file_version = exec("cat {$install_dir}/version.txt");
        else $file_version = "n/a";
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

// uninstall old OBI files < v0.4 and remove the application section from config.xml
if (is_array($config['onebuttoninstaller'])) {
	mwexec("rm -Rf /usr/local/www/onebuttoninstaller*", false);
	mwexec("rm -Rf /usr/local/www/ext/onebuttoninstaller", false);
	mwexec("rm -Rf {$install_dir}/locale-obi", false);
	mwexec("rm -Rf {$install_dir}/log", false);
	if (is_link("/usr/local/share/locale-obi")) unlink("/usr/local/share/locale-obi");
	$configuration = $config['onebuttoninstaller'];
	unset($config['onebuttoninstaller']);
	write_config();
} 

// install / update application on NAS4Free
if (!is_array($configuration)) {                                    	// from an old OBI < 0.4 installation
	if (($configuration = ext_load_config($configFile)) === false) {    // from an OBI >= 0.4 installation
	    $configuration = array();             							// new installation
	    $new_installation = true;
    }
}
$configuration['appname'] = $appName;
$configuration['version'] = exec("cat {$install_dir}/version.txt");
$configuration['rootfolder'] = $install_dir;
$configuration['postinit'] = "/usr/local/bin/php-cgi -f {$install_dir}/{$configName}-start.php";
$configuration['shutdown'] = "/usr/local/bin/php-cgi -f {$install_dir}/{$configName}-stop.php";

// remove start/stop commands and existing old rc format entries
ext_remove_rc_commands($configName);
$configuration['rc_uuid_start'] = $configuration['postinit'];
$configuration['rc_uuid_stop'] = $configuration['shutdown'];
ext_create_rc_commands($appName, $configuration['rc_uuid_start'], $configuration['rc_uuid_stop']);
ext_save_config($configFile, $configuration);

require_once("{$install_dir}/{$configName}-start.php");
if ($new_installation) echo "\nInstallation completed, use WebGUI | Extensions | {$appName} to configure the application!\n";

// finally fetch the most recent extensions list to get the latest changes if not already in the master release
$return_val = mwexec("fetch -o {$install_dir}/extensions.txt https://raw.github.com/crestAT/nas4free-{$configName}/master/{$configName}/extensions.txt", false);
?>
