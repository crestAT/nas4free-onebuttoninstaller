<?php
/* 
    OBI.php

    Copyright (c) 2015 - 2018 Andreas Schmidhuber
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
require("auth.inc");
require("guiconfig.inc");

$application = "OneButtonInstaller";
$pgtitle = array(gettext("Extensions"), gettext($application), gettext("Configuration"));

if (!isset($config['onebuttoninstaller']) || !is_array($config['onebuttoninstaller'])) $config['onebuttoninstaller'] = array();

$platform = $g['platform'];
if ($platform == "livecd" || $platform == "liveusb") 
	$input_errors[] = sprintf(gettext("Attention: the used XigmaNAS platform '%s' is not recommended for extensions! After a reboot all extensions will no longer be available, use XigmaNAS embedded or full platform instead!"), $platform);

/* Check if the directory exists, the mountpoint has at least o=rx permissions and
 * set the permission to 775 for the last directory in the path
 */
function change_perms($dir) {
    global $input_errors;

    $path = rtrim($dir,'/');                                            // remove trailing slash
    if (strlen($path) > 1) {
        if (!is_dir($path)) {                                           // check if directory exists
            $input_errors[] = sprintf(gettext("Directory %s doesn't exist!"), $path);
        }
        else {
            $path_check = explode("/", $path);                          // split path to get directory names
            $path_elements = count($path_check);                        // get path depth
            $fp = substr(sprintf('%o', fileperms("/$path_check[1]/$path_check[2]")), -1);   // get mountpoint permissions for others
            if ($fp >= 5) {                                             // transmission needs at least read & search permission at the mountpoint
                $directory = "/$path_check[1]/$path_check[2]";          // set to the mountpoint
                for ($i = 3; $i < $path_elements - 1; $i++) {           // traverse the path and set permissions to rx
                    $directory = $directory."/$path_check[$i]";         // add next level
                    exec("chmod o=+r+x \"$directory\"");                // set permissions to o=+r+x
                }
                $path_elements = $path_elements - 1;
                $directory = $directory."/$path_check[$path_elements]"; // add last level
                exec("chmod 775 {$directory}");                         // set permissions to 775
                exec("chown {$_POST['who']} {$directory}*");
            }
            else
            {
                $input_errors[] = sprintf(gettext("%s needs at least read & execute permissions at the mount point for directory %s! Set the Read and Execute bits for Others (Access Restrictions | Mode) for the mount point %s (in <a href='disks_mount.php'>Disks | Mount Point | Management</a> or <a href='disks_zfs_dataset.php'>Disks | ZFS | Datasets</a>) and hit Save in order to take them effect."), $application, $path, "/{$path_check[1]}/{$path_check[2]}");
            }
        }
    }
}

if (isset($_POST['save']) && $_POST['save']) {
    unset($input_errors);
	if (empty($input_errors)) {
        $config['onebuttoninstaller']['storage_path'] = !empty($_POST['storage_path']) ? $_POST['storage_path'] : $g['media_path'];
        $config['onebuttoninstaller']['storage_path'] = rtrim($config['onebuttoninstaller']['storage_path'],'/');         // ensure to have NO trailing slash
        if (!isset($_POST['path_check']) && (strpos($config['onebuttoninstaller']['storage_path'], "/mnt/") === false)) {
            $input_errors[] = gettext("The common directory for all extensions MUST be set to a directory below <b>'/mnt/'</b> to prevent to loose the extensions after a reboot on embedded systems!");
        }
        else {
            if (!is_dir($config['onebuttoninstaller']['storage_path'])) mkdir($config['onebuttoninstaller']['storage_path'], 0775, true);
            change_perms($config['onebuttoninstaller']['storage_path']);
            $config['onebuttoninstaller']['path_check'] = isset($_POST['path_check']) ? true : false;
            $install_dir = $config['onebuttoninstaller']['storage_path']."/";   // get directory where the installer script resides
            if (!is_dir("{$install_dir}onebuttoninstaller/log")) { mkdir("{$install_dir}onebuttoninstaller/log", 0775, true); }
            $return_val = mwexec("fetch {$verify_hostname} -vo {$install_dir}onebuttoninstaller/onebuttoninstaller-install.php 'https://raw.github.com/crestAT/nas4free-onebuttoninstaller/master/onebuttoninstaller/onebuttoninstaller-install.php'", false);
            if ($return_val == 0) {
                chmod("{$install_dir}onebuttoninstaller/onebuttoninstaller-install.php", 0775);
                require_once("{$install_dir}onebuttoninstaller/onebuttoninstaller-install.php");
            }
            else {
                $input_errors[] = sprintf(gettext("Installation file %s not found, installation aborted!"), "{$install_dir}onebuttoninstaller/onebuttoninstaller-install.php");
                return;
            }
            mwexec("rm -Rf ext/OBI; rm -f OBI.php", true);
            header("Location:onebuttoninstaller-config.php");
        }
    }
}

if (isset($_POST['cancel']) && $_POST['cancel']) {
    $return_val = mwexec("rm -Rf ext/OBI; rm -f OBI.php", true);
    if ($return_val == 0) { $savemsg .= $application." ".gettext("not installed"); }
    else { $input_errors[] = $application." removal failed"; }
    header("Location:index.php");
}

$pconfig['storage_path'] = !empty($config['onebuttoninstaller']['storage_path']) ? $config['onebuttoninstaller']['storage_path'] : $g['media_path'];
$pconfig['path_check'] = isset($config['onebuttoninstaller']['path_check']) ? true : false;

include("fbegin.inc"); ?>
<form action="OBI.php" method="post" name="iform" id="iform">
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr><td class="tabcont">
        <?php if (!empty($input_errors)) print_input_errors($input_errors);?>
        <?php if (!empty($savemsg)) print_info_box($savemsg);?>
        <table width="100%" border="0" cellpadding="6" cellspacing="0">
            <?php html_titleline($application);?>
			<?php html_filechooser("storage_path", gettext("Common directory"), $pconfig['storage_path'], gettext("Common directory for all extensions (a persistant place where all extensions are/should be - a directory below <b>/mnt/</b>)."), $pconfig['storage_path'], true, 60);?>
            <?php html_checkbox("path_check", gettext("Path check"), $pconfig['path_check'], gettext("If this option is selected no examination of the common directory path will be carried out (whether it was set to a directory below /mnt/)."), "<b><font color='red'>".gettext("Please use this option only if you know what you are doing!")."</font></b>", false);?>        </table>
        <div id="submit">
			<input id="save" name="save" type="submit" class="formbtn" value="<?=gettext("Save");?>"/>
			<input id="cancel" name="cancel" type="submit" class="formbtn" value="<?=gettext("Cancel");?>"/>
        </div>
	</td></tr>
	</table>
	<?php include("formend.inc");?>
</form>
<?php include("fend.inc");?>
