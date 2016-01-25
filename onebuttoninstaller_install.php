#!/usr/local/bin/php-cgi -f
<?php
require_once("config.inc");

// check FreeBSD release for fetch options >= 9.3
$release = explode("-", exec("uname -r"));
if ($release[0] >= 9.3) $verify_hostname = "--no-verify-hostname";
else $verify_hostname = "";

$return_val = 0;//mwexec("fetch {$verify_hostname} -vo onebuttoninstaller/onebuttoninstaller-install.php 'https://raw.github.com/crestAT/nas4free-onebuttoninstaller/master/onebuttoninstaller/onebuttoninstaller-install.php'", true);
if ($return_val == 0) { 
    chmod("onebuttoninstaller/onebuttoninstaller-install.php", 0775);
    require_once("onebuttoninstaller/onebuttoninstaller-install.php"); 
}
else { echo "\nInstallation file 'onebuttoninstaller-install.php' not found, installation aborted!\n"; }
?>
