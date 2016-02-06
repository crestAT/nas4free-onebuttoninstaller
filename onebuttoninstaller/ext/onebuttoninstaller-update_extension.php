<?php
/*
    onebuttoninstaller-update_extension.php
    
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

bindtextdomain("nas4free", "/usr/local/share/locale-obi");
$pgtitle = array(gettext("Extensions"), gettext("OneButtonInstaller")." ".$config['onebuttoninstaller']['version'], gettext("Maintenance"));

if (is_file("{$config['onebuttoninstaller']['rootfolder']}log/oneload")) { require_once("{$config['onebuttoninstaller']['rootfolder']}log/oneload"); }

$return_val = mwexec("fetch -o {$config['onebuttoninstaller']['rootfolder']}log/version.txt https://raw.github.com/crestAT/nas4free-onebuttoninstaller/master/onebuttoninstaller/version.txt", true);
if ($return_val == 0) { 
    $server_version = exec("cat {$config['onebuttoninstaller']['rootfolder']}log/version.txt"); 
    if ($server_version != $config['onebuttoninstaller']['version']) { $savemsg = sprintf(gettext("New extension version %s available, push '%s' button to install the new version!"), $server_version, gettext("Update Extension")); }
    mwexec("fetch -o {$config['onebuttoninstaller']['rootfolder']}release_notes.txt https://raw.github.com/crestAT/nas4free-onebuttoninstaller/master/onebuttoninstaller/release_notes.txt", false);
}
else { $server_version = gettext("Unable to retrieve version from server!"); }

if (isset($_POST['ext_remove']) && $_POST['ext_remove']) {
// remove start/stop commands
    if ( is_array($config['rc']['postinit'] ) && is_array( $config['rc']['postinit']['cmd'] ) ) {
		for ($i = 0; $i < count($config['rc']['postinit']['cmd']);) {
    		if (preg_match('/onebuttoninstaller/', $config['rc']['postinit']['cmd'][$i])) { unset($config['rc']['postinit']['cmd'][$i]);} else{}
		++$i;
		}
	}
	if ( is_array($config['rc']['shutdown'] ) && is_array( $config['rc']['shutdown']['cmd'] ) ) {
		for ($i = 0; $i < count($config['rc']['shutdown']['cmd']); ) {
            if (preg_match('/onebuttoninstaller/', $config['rc']['shutdown']['cmd'][$i])) { unset($config['rc']['shutdown']['cmd'][$i]); } else {}
		++$i;
		}
	}
// remove extension pages
	mwexec ("rm -rf /usr/local/www/ext/onebuttoninstaller");
	mwexec ("rm -rf /usr/local/www/onebuttoninstaller*");
// unlink created links
    if (is_link("/usr/local/share/locale-obi")) unlink("/usr/local/share/locale-obi");
// remove application section from config.xml
	if ( is_array($config['onebuttoninstaller'] ) ) { unset( $config['onebuttoninstaller'] ); write_config();}
	header("Location:index.php");
}

if (isset($_POST['ext_update']) && $_POST['ext_update']) {
// download installer & install
    $return_val = mwexec("fetch -vo {$config['onebuttoninstaller']['rootfolder']}onebuttoninstaller-install.php 'https://raw.github.com/crestAT/nas4free-onebuttoninstaller/master/onebuttoninstaller/onebuttoninstaller-install.php'", true);
    if ($return_val == 0) {
        require_once("{$config['onebuttoninstaller']['rootfolder']}onebuttoninstaller-install.php"); 
        header("Refresh:8");;
    }
    else { $input_errors[] = sprintf(gettext("Download of installation file %s failed, installation aborted!"), "onebuttoninstaller-install.php"); }
}

bindtextdomain("nas4free", "/usr/local/share/locale");                  // to get the right main menu language
include("fbegin.inc");
bindtextdomain("nas4free", "/usr/local/share/locale-obi"); ?>
<!-- The Spinner Elements -->
<?php include("ext/onebuttoninstaller/spinner.inc");?>
<script src="ext/onebuttoninstaller/spin.min.js"></script>
<!-- use: onsubmit="spinner()" within the form tag -->

<form action="onebuttoninstaller-update_extension.php" method="post" name="iform" id="iform" onsubmit="spinner()">
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr><td class="tabnavtbl">
		<ul id="tabnav">
        <?php if (isset($config['onebuttoninstaller']['enable'])) { ?>
    			<li class="tabinact"><a href="onebuttoninstaller.php"><span><?=gettext("Install");?></span></a></li>
    			<li class="tabinact"><a href="onebuttoninstaller-config.php"><span><?=gettext("Configuration");?></span></a></li>
    			<li class="tabact"><a href="onebuttoninstaller-update_extension.php"><span><?=gettext("Maintenance");?></span></a></li>
        <?php } else { ?>
    			<li class="tabinact"><a href="onebuttoninstaller-config.php"><span><?=gettext("Configuration");?></span></a></li>
    			<li class="tabact"><a href="onebuttoninstaller-update_extension.php"><span><?=gettext("Maintenance");?></span></a></li>
        <?php } ?>
		</ul>
	</td></tr>
	<tr><td class="tabcont">
        <?php if (!empty($input_errors)) print_input_errors($input_errors);?>
        <?php if (!empty($savemsg)) print_info_box($savemsg);?>
        <table width="100%" border="0" cellpadding="6" cellspacing="0">
            <?php html_titleline(gettext("Extension Update"));?>
			<?php html_text("ext_version_current", gettext("Installed version"), $config['onebuttoninstaller']['version']);?>
			<?php html_text("ext_version_server", gettext("Latest version"), $server_version);?>
			<?php html_separator();?>
        </table>
        <div id="update_remarks">
            <?php html_remark("note_remove", gettext("Note"), gettext("Removing OneButtonInstaller integration from NAS4Free will leave the installation folder untouched - remove the files using Windows Explorer, FTP or some other tool of your choice. <br /><b>Please note: this page will no longer be available.</b> You'll have to re-run OneButtonInstaller extension installation to get it back on your NAS4Free."));?>
            <br />
            <input id="ext_update" name="ext_update" type="submit" class="formbtn" value="<?=gettext("Update Extension");?>" onclick="return confirm('<?=gettext("The selected operation will be completed. Please do not click any other buttons!");?>')" />
            <input id="ext_remove" name="ext_remove" type="submit" class="formbtn" value="<?=gettext("Remove Extension");?>" onclick="return confirm('<?=gettext("Do you really want to remove the extension from the system?");?>')" />
        </div>
        <table width="100%" border="0" cellpadding="6" cellspacing="0">
			<?php html_separator();?>
			<?php html_separator();?>
			<?php html_titleline(gettext("Extension")." ".gettext("Release Notes"));?>
			<tr>
                <td class="listt">
                    <div>
                        <textarea style="width: 98%;" id="content" name="content" class="listcontent" cols="1" rows="25" readonly="readonly"><?php unset($lines); exec("/bin/cat {$config['onebuttoninstaller']['rootfolder']}release_notes.txt", $lines); foreach ($lines as $line) { echo $line."\n"; }?></textarea>
                    </div>
                </td>
			</tr>
        </table>
        <?php include("formend.inc");?>
    </td></tr>
</table>
</form>
<?php include("fend.inc");?>
