<?php
/* 
    onebuttoninstaller-config.php

    Copyright (c) 2015 - 2016 Andreas Schmidhuber
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
require("auth.inc");
require("guiconfig.inc");

// Dummy standard message gettext calls for xgettext retrieval!!!
$dummy = gettext("The changes have been applied successfully.");
$dummy = gettext("The configuration has been changed.<br />You must apply the changes in order for them to take effect.");
$dummy = gettext("The following input errors were detected");

$pgtitle = array(gettext("Extensions"), gettext("OneButtonInstaller")." ".$config['onebuttoninstaller']['version'], gettext("Configuration"));

if (!isset($config['onebuttoninstaller']) || !is_array($config['onebuttoninstaller'])) $config['onebuttoninstaller'] = array();

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
                $input_errors[] = sprintf(gettext("OneButtonInstaller needs at least read & execute permissions at the mount point for directory %s! Set the Read and Execute bits for Others (Access Restrictions | Mode) for the mount point %s (in <a href='disks_mount.php'>Disks | Mount Point | Management</a> or <a href='disks_zfs_dataset.php'>Disks | ZFS | Datasets</a>) and hit Save in order to take them effect."), $path, "/{$path_check[1]}/{$path_check[2]}");
            }
        }
    }
}

function cronjob_process_updatenotification($mode, $data) {
	global $config;
	$retval = 0;
	switch ($mode) {
		case UPDATENOTIFY_MODE_NEW:
		case UPDATENOTIFY_MODE_MODIFIED:
			break;
		case UPDATENOTIFY_MODE_DIRTY:
			if (is_array($config['cron']['job'])) {
				$index = array_search_ex($data, $config['cron']['job'], "uuid");
				if (false !== $index) {
					unset($config['cron']['job'][$index]);
					write_config();
				}
			}
			break;
	}
	return $retval;
}

if (isset($_POST['save']) && $_POST['save']) {
    unset($input_errors);
	if (empty($input_errors)) {
        $config['onebuttoninstaller']['enable'] = isset($_POST['enable']) ? true : false;
        $config['onebuttoninstaller']['storage_path'] = !empty($_POST['storage_path']) ? $_POST['storage_path'] : $g['media_path'];
        $config['onebuttoninstaller']['storage_path'] = rtrim($config['onebuttoninstaller']['storage_path'],'/');         // ensure to have NO trailing slash
        if (!is_dir($config['onebuttoninstaller']['storage_path'])) mkdir($config['onebuttoninstaller']['storage_path'], 0775, true);
        change_perms($_POST['storage_path']);

        if (isset($_POST['enable_schedule']) && ($_POST['startup'] == $_POST['closedown'])) { $input_errors[] = gettext("Startup and closedown hour must be different!"); }
        else {
            if (isset($_POST['enable_schedule'])) {
                $config['onebuttoninstaller']['enable_schedule'] = isset($_POST['enable_schedule']) ? true : false;
                $config['onebuttoninstaller']['schedule_startup'] = $_POST['startup'];
                $config['onebuttoninstaller']['schedule_closedown'] = $_POST['closedown'];
    
                $cronjob = array();
                $a_cronjob = &$config['cron']['job'];
                $uuid = isset($config['onebuttoninstaller']['schedule_uuid_startup']) ? $config['onebuttoninstaller']['schedule_uuid_startup'] : false;
                if (isset($uuid) && (FALSE !== ($cnid = array_search_ex($uuid, $a_cronjob, "uuid")))) {
                	$cronjob['enable'] = true;
                	$cronjob['uuid'] = $a_cronjob[$cnid]['uuid'];
                	$cronjob['desc'] = "OneButtonInstaller startup (@ {$config['onebuttoninstaller']['schedule_startup']}:00)";
                	$cronjob['minute'] = $a_cronjob[$cnid]['minute'];
                	$cronjob['hour'] = $config['onebuttoninstaller']['schedule_startup'];
                	$cronjob['day'] = $a_cronjob[$cnid]['day'];
                	$cronjob['month'] = $a_cronjob[$cnid]['month'];
                	$cronjob['weekday'] = $a_cronjob[$cnid]['weekday'];
                	$cronjob['all_mins'] = $a_cronjob[$cnid]['all_mins'];
                	$cronjob['all_hours'] = $a_cronjob[$cnid]['all_hours'];
                	$cronjob['all_days'] = $a_cronjob[$cnid]['all_days'];
                	$cronjob['all_months'] = $a_cronjob[$cnid]['all_months'];
                	$cronjob['all_weekdays'] = $a_cronjob[$cnid]['all_weekdays'];
                	$cronjob['who'] = 'root';
                	$cronjob['command'] = "{$config['onebuttoninstaller']['rootfolder']}onebuttoninstaller_start.php && logger onebuttoninstaller: scheduled startup";
                } else {
                	$cronjob['enable'] = true;
                	$cronjob['uuid'] = uuid();
                	$cronjob['desc'] = "OneButtonInstaller startup (@ {$config['onebuttoninstaller']['schedule_startup']}:00)";
                	$cronjob['minute'] = 0;
                	$cronjob['hour'] = $config['onebuttoninstaller']['schedule_startup'];
                	$cronjob['day'] = true;
                	$cronjob['month'] = true;
                	$cronjob['weekday'] = true;
                	$cronjob['all_mins'] = 0;
                	$cronjob['all_hours'] = 0;
                	$cronjob['all_days'] = 1;
                	$cronjob['all_months'] = 1;
                	$cronjob['all_weekdays'] = 1;
                	$cronjob['who'] = 'root';
                	$cronjob['command'] = "{$config['onebuttoninstaller']['rootfolder']}onebuttoninstaller_start.php && logger onebuttoninstaller: scheduled startup";
                    $config['onebuttoninstaller']['schedule_uuid_startup'] = $cronjob['uuid'];
                }
                if (isset($uuid) && (FALSE !== $cnid)) {
                		$a_cronjob[$cnid] = $cronjob;
                		$mode = UPDATENOTIFY_MODE_MODIFIED;
                	} else {
                		$a_cronjob[] = $cronjob;
                		$mode = UPDATENOTIFY_MODE_NEW;
                	}
                updatenotify_set("cronjob", $mode, $cronjob['uuid']);
//                write_config();
    
                unset ($cronjob);
                $cronjob = array();
                $a_cronjob = &$config['cron']['job'];
                $uuid = isset($config['onebuttoninstaller']['schedule_uuid_closedown']) ? $config['onebuttoninstaller']['schedule_uuid_closedown'] : false;
                if (isset($uuid) && (FALSE !== ($cnid = array_search_ex($uuid, $a_cronjob, "uuid")))) {
                	$cronjob['enable'] = true;
                	$cronjob['uuid'] = $a_cronjob[$cnid]['uuid'];
                	$cronjob['desc'] = "OneButtonInstaller closedown (@ {$config['onebuttoninstaller']['schedule_closedown']}:00)";
                	$cronjob['minute'] = $a_cronjob[$cnid]['minute'];
                	$cronjob['hour'] = $config['onebuttoninstaller']['schedule_closedown'];
                	$cronjob['day'] = $a_cronjob[$cnid]['day'];
                	$cronjob['month'] = $a_cronjob[$cnid]['month'];
                	$cronjob['weekday'] = $a_cronjob[$cnid]['weekday'];
                	$cronjob['all_mins'] = $a_cronjob[$cnid]['all_mins'];
                	$cronjob['all_hours'] = $a_cronjob[$cnid]['all_hours'];
                	$cronjob['all_days'] = $a_cronjob[$cnid]['all_days'];
                	$cronjob['all_months'] = $a_cronjob[$cnid]['all_months'];
                	$cronjob['all_weekdays'] = $a_cronjob[$cnid]['all_weekdays'];
                	$cronjob['who'] = 'root';
                	$cronjob['command'] = "{$config['onebuttoninstaller']['rootfolder']}onebuttoninstaller_stop.php && logger onebuttoninstaller: scheduled closedown";
                } else {
                	$cronjob['enable'] = true;
                	$cronjob['uuid'] = uuid();
                	$cronjob['desc'] = "OneButtonInstaller closedown (@ {$config['onebuttoninstaller']['schedule_closedown']}:00)";
                	$cronjob['minute'] = 0;
                	$cronjob['hour'] = $config['onebuttoninstaller']['schedule_closedown'];
                	$cronjob['day'] = true;
                	$cronjob['month'] = true;
                	$cronjob['weekday'] = true;
                	$cronjob['all_mins'] = 0;
                	$cronjob['all_hours'] = 0;
                	$cronjob['all_days'] = 1;
                	$cronjob['all_months'] = 1;
                	$cronjob['all_weekdays'] = 1;
                	$cronjob['who'] = 'root';
                	$cronjob['command'] = "{$config['onebuttoninstaller']['rootfolder']}onebuttoninstaller_stop.php && logger onebuttoninstaller: scheduled closedown";
                    $config['onebuttoninstaller']['schedule_uuid_closedown'] = $cronjob['uuid'];
                }
                if (isset($uuid) && (FALSE !== $cnid)) {
                		$a_cronjob[$cnid] = $cronjob;
                		$mode = UPDATENOTIFY_MODE_MODIFIED;
                	} else {
                		$a_cronjob[] = $cronjob;
                		$mode = UPDATENOTIFY_MODE_NEW;
                	}
                updatenotify_set("cronjob", $mode, $cronjob['uuid']);
//                write_config();
            }   // end of enable_schedule
            else {
                $config['onebuttoninstaller']['enable_schedule'] = isset($_POST['enable_schedule']) ? true : false;
            	updatenotify_set("cronjob", UPDATENOTIFY_MODE_DIRTY, $config['onebuttoninstaller']['schedule_uuid_startup']);
            	if (is_array($config['cron']['job'])) {
            				$index = array_search_ex($data, $config['cron']['job'], "uuid");
            				if (false !== $index) {
            					unset($config['cron']['job'][$index]);
            				}
            			}
//            	write_config();
            	updatenotify_set("cronjob", UPDATENOTIFY_MODE_DIRTY, $config['onebuttoninstaller']['schedule_uuid_closedown']);
            	if (is_array($config['cron']['job'])) {
            				$index = array_search_ex($data, $config['cron']['job'], "uuid");
            				if (false !== $index) {
            					unset($config['cron']['job'][$index]);
            				}
            			}
//            	write_config();
            }   // end of disable_schedule -> remove cronjobs
    		$retval = 0;
    		if (!file_exists($d_sysrebootreqd_path)) {
    			$retval |= updatenotify_process("cronjob", "cronjob_process_updatenotification");
    			config_lock();
    			$retval |= rc_update_service("cron");
    			config_unlock();
    		}
//    		$savemsg .= get_std_save_message($retval).'<br />';
    		if ($retval == 0) {
    			updatenotify_delete("cronjob");
    		}
        }   // end of schedule change

        $savemsg .= get_std_save_message(write_config());
    }   // end of empty input_errors
}

$pconfig['enable'] = isset($config['onebuttoninstaller']['enable']) ? true : false;
$pconfig['storage_path'] = !empty($config['onebuttoninstaller']['storage_path']) ? $config['onebuttoninstaller']['storage_path'] : $g['media_path'];

include("fbegin.inc");?>  
<script type="text/javascript">
<!--
function enable_change(enable_change) {
    var endis = !(document.iform.enable.checked || enable_change);
	document.iform.storage_path.disabled = endis;
	document.iform.storage_pathbrowsebtn.disabled = endis;
}
//-->
</script>
<form action="onebuttoninstaller-config.php" method="post" name="iform" id="iform">
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr><td class="tabnavtbl">
		<ul id="tabnav">
        <?php if (isset($config['onebuttoninstaller']['enable'])) { ?>
    			<li class="tabinact"><a href="onebuttoninstaller.php"><span><?=gettext("Install");?></span></a></li>
    			<li class="tabact"><a href="onebuttoninstaller-config.php"><span><?=gettext("Configuration");?></span></a></li>
    			<li class="tabinact"><a href="onebuttoninstaller-update_extension.php"><span><?=gettext("Maintenance");?></span></a></li>
        <?php } else { ?>
    			<li class="tabact"><a href="onebuttoninstaller-config.php"><span><?=gettext("Configuration");?></span></a></li>
    			<li class="tabinact"><a href="onebuttoninstaller-update_extension.php"><span><?=gettext("Maintenance");?></span></a></li>
        <?php } ?>
		</ul>
	</td></tr>
    <tr><td class="tabcont">
        <?php if (!empty($input_errors)) print_input_errors($input_errors);?>
        <?php if (!empty($savemsg)) print_info_box($savemsg);?>
        <table width="100%" border="0" cellpadding="6" cellspacing="0">
            <?php html_titleline_checkbox("enable", gettext("OneButtonInstaller"), $pconfig['enable'], gettext("Enable"), "enable_change(false)");?>
            <?php html_text("installation_directory", gettext("Installation directory"), sprintf(gettext("The extension is installed in %s."), $config['onebuttoninstaller']['rootfolder']));?>
			<?php html_filechooser("storage_path", gettext("Common directory"), $pconfig['storage_path'], gettext("Common root directory for all extensions (a persistant place where all extensions are/should be - a directory below <b>/mnt/</b>)."), $pconfig['storage_path'], true, 60);?>
        </table>
        <div id="submit">
			<input id="save" name="save" type="submit" class="formbtn" value="<?=gettext("Save & Restart");?>"/>
        </div>
	</td></tr>
	</table>
	<?php include("formend.inc");?>
</form>
<script type="text/javascript">
<!--
enable_change(false);
//-->
</script>
<?php include("fend.inc");?>
