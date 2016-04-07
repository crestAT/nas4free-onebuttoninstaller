#!/usr/local/bin/php-cgi -f
<?php
require_once("config.inc");

// check FreeBSD release for fetch options >= 9.3
$release = explode("-", exec("uname -r"));
if ($release[0] >= 9.3) $verify_hostname = "--no-verify-hostname";
else $verify_hostname = "";

$install_dir = dirname(__FILE__)."/";                           // get directory where the installer script resides
if (!is_dir("{$install_dir}onebuttoninstaller/log")) { mkdir("{$install_dir}onebuttoninstaller/log", 0775, true); }
$return_val = mwexec("fetch {$verify_hostname} -vo onebuttoninstaller/onebuttoninstaller-install.php 'https://raw.github.com/crestAT/nas4free-onebuttoninstaller/master/onebuttoninstaller/onebuttoninstaller-install.php'", true);
if ($return_val == 0) { 
    chmod("onebuttoninstaller/onebuttoninstaller-install.php", 0775);
    require_once("onebuttoninstaller/onebuttoninstaller-install.php"); 
}
else { echo "\nInstallation file 'onebuttoninstaller-install.php' not found, installation aborted!\n"; }
?>
