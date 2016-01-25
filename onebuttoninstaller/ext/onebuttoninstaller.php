<?php
/*
	onebuttoninstaller.php
	
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
if (!isset($config['onebuttoninstaller']['enable'])) header("Location:onebuttoninstaller-config.php");

$pgtitle = array(gettext("Extensions"), gettext("OneButtonInstaller")." ".$config['onebuttoninstaller']['version']);

$log = 0;
$loginfo = array(
    array(
    	"visible" => TRUE,
    	"desc" => gettext("Extensions"),
    	"logfile" => "{$config['onebuttoninstaller']['rootfolder']}extensions.txt",
    	"filename" => "extensions.txt",
    	"type" => "plain",
		"pattern" => "/^(.*)###(.*)###(.*)###(.*)###(.*)###(.*)$/",
    	"columns" => array(
    		array("title" => gettext("Extensions"), "class" => "listlr", "param" => "align=\"left\"  valign=\"middle\" nowrap", "pmid" => 0),
    		array("title" => gettext("Version"), "class" => "listr", "param" => "align=\"center\" valign=\"middle\"", "pmid" => 1),
    		array("title" => gettext("Description"), "class" => "listr", "param" => "align=\"left\" valign=\"middle\"", "pmid" => 5),
    		array("title" => gettext("Install"), "class" => "listr", "param" => "align=\"center\" valign=\"middle\"", "pmid" => 4)
    	))
);

function log_get_contents($logfile) {
	$content = array();
    if (is_file($logfile)) exec("cat {$logfile}", $extensions);
    else return;
    $content = $extensions;    
	return $content;
}

function log_display($loginfo) {
    global $config;
    global $savemsg;
    
	if (!is_array($loginfo)) return;

	// Create table header
	echo "<tr>";
	foreach ($loginfo['columns'] as $columnk => $columnv) {
		echo "<td {$columnv['param']} class='" . (($columnk == 0) ? "listhdrlr" : "listhdrr") . "'>".htmlspecialchars($columnv['title'])."</td>\n";
	}
	echo "</tr>";

	// Get file content
	$content = log_get_contents($loginfo['logfile']);
	if (empty($content)) return;
    sort($content);

    $j = 0;

/*
 * EXTENSIONS.TXT format description: PARAMETER DELIMITER -> ###
 *                      PMID    COMMENT
 * name:                0       extension name
 * version:             1       extension version (base for config entry - could change for newer versions)
 * xmlstring:           2       config.xml or installation directory
 * command(list)1:      3       SHELL commands
 * command(list)2:      4       PHP commands
 * description:         5       plain text which can include HTML tags
 */

/*  DEBUGGER
print_r($result[3]);
echo('<br />');
print_r($result[4]);
echo('<br />');
 */

    // Create table data
	foreach ($content as $contentv) {                                   // handle each line => one extension
		unset($result);
        $result = explode("###", $contentv);                            // retrieve extension content (pmid based) 
		if ((FALSE === $result) || (0 == $result)) continue;
		echo "<tr valign=\"top\">\n";
		for ($i = 0; $i < count($loginfo['columns']); $i++) {           // handle pmids (columns)
            if ($i == count($loginfo['columns']) - 1) {
                // check if extension is already installed (config.xml entry or for command line tools based on install directory)
                if ((isset($config[$result[2]])) || ((strpos($result[2], "/") == 0) && (is_dir("{$config['onebuttoninstaller']['storage_path']}{$result[2]}")))){ 
                    echo "<td {$loginfo['columns'][$i]['param']} class='{$loginfo['columns'][$i]['class']}'> <img src='status_enabled.png' border='0' alt='' title='".gettext('Enabled')."' /> </td>\n";                    
                }  
                else {                                                  // data for installation
                    echo "<td {$loginfo['columns'][$i]['param']} class='{$loginfo['columns'][$i]['class']}'> 
                        <input type='checkbox' name='name[".$j."][extension]' value='".$result[2]."' />
                        <input type='hidden' name='name[".$j."][command1]' value='".$result[3]."' />
                        <input type='hidden' name='name[".$j."][command2]' value='".$result[4]."' />
                    </td>\n"; 
                }
            }
            else echo "<td {$loginfo['columns'][$i]['param']} class='{$loginfo['columns'][$i]['class']}'>" . $result[$loginfo['columns'][$i]['pmid']] . "</td>\n";
        }
		echo "</tr>\n";
		$j++;
	}
}

//ob_start();
//$ausgabe = ob_get_contents();
//ob_end_clean();
//foreach ($ausgabe as $msg) $savemsg .= $msg."<br />";
if (isset($_POST) && $_POST) {                                          
    foreach($_POST[name] as $line) {
        if (isset($line['extension'])) {
            $savemsg .= "<b>{$line['extension']}</b>"."<br />";
            $savemsg .= print_r($line, true)."<br />";
            unset($result);
            exec("cd {$config["onebuttoninstaller"]["storage_path"]} && {$line['command1']}", $result, $return_val);
            if ($return_val == 0) {
                $savemsg .= "<b>command1 successful</b>"."<br />";
            	foreach ($result as $msg) $savemsg .= $msg."<br />";
                unset($result);
//                exec("cd {$config["onebuttoninstaller"]["storage_path"]} && {$line['command2']}", $result, $return_val);
//                if (file_exists("{$config["onebuttoninstaller"]["storage_path"]}/{$line['command2']}")) {
                if ("{$line['command2']}" != "-") {
ob_start();
                    require_once("{$config['onebuttoninstaller']['storage_path']}/{$line['command2']}");
ob_end_clean();
                    if ($return_val == 0) {
                        $savemsg .= "<br />"."<b>command2 successful</b>"."<br />";
//                    	foreach ($result as $msg) $savemsg .= $msg."<br />";
                    }
                    else {
                    	$errormsg .= gettext("Error")."<br />";
//                    	foreach ($result as $msg) $errormsg .= $msg."<br />";
                        $savemsg .= "<b>command2 NOT successful</b>"."<br />";
                    }
                }
//                else $errormsg .= "Error: file {$config["onebuttoninstaller"]["storage_path"]}/{$line['command2']} not found!"."<br />";
            }
            else {
               	$errormsg .= gettext("Error on command1")."<br />";
            	foreach ($result as $msg) $errormsg .= $msg."<br />";
                $savemsg .= "<b>command1 NOT successful</b>"."<br />";
            }  
        }
    }
}

if (!is_file("{$config['onebuttoninstaller']['rootfolder']}extensions.txt")) $savemsg .= sprintf(gettext("File %s not found!"), "{$config['onebuttoninstaller']['rootfolder']}extensions.txt");

include("fbegin.inc");?>
<script type="text/javascript">
function spinner() {
        var opts = {
            lines: 10, // The number of lines to draw
            length: 7, // The length of each line
            width: 4, // The line thickness
            radius: 10, // The radius of the inner circle
            corners: 1, // Corner roundness (0..1)
            rotate: 0, // The rotation offset
            color: '#000', // #rgb or #rrggbb
            speed: 1, // Rounds per second
            trail: 60, // Afterglow percentage
            shadow: false, // Whether to render a shadow
            hwaccel: false, // Whether to use hardware acceleration
            className: 'spinner', // The CSS class to assign to the spinner
            zIndex: 2e9, // The z-index (defaults to 2000000000)
        };
        var target = document.getElementById('foo');
        var spinner = new Spinner(opts).spin(target);
}
</script>
<div id="foo">
<script src="ext/onebuttoninstaller/spin.min.js"></script>

<form action="onebuttoninstaller.php" method="post" name="iform" id="iform" onsubmit="spinner()">
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
    	<tr><td class="tabnavtbl">
    		<ul id="tabnav">
    			<li class="tabact"><a href="onebuttoninstaller.php"><span><?=gettext("Install");?></span></a></li>
    			<li class="tabinact"><a href="onebuttoninstaller-config.php"><span><?=gettext("Configuration");?></span></a></li>
    			<li class="tabinact"><a href="onebuttoninstaller-update_extension.php"><span><?=gettext("Maintenance");?></span></a></li>
    		</ul>
    	</td></tr>
    	<tr><td class="tabcont">
            <?php if (!empty($errormsg)) print_error_box($errormsg);?>
            <?php if (!empty($savemsg)) print_info_box($savemsg);?>
    		<table width="100%" border="0" cellpadding="0" cellspacing="0">
                <?php 
                    log_display($loginfo[$log]);
                ?>
    		</table>
            <div id="submit">
                <input name="install" type="submit" class="formbtn" title="<?=gettext("Install extensions");?>" value="<?=gettext("Install");?>" onclick="return confirm('<?=gettext("Ready to install the selected extensions?");?>')" />
                <input name="update" type="submit" class="formbtn" title="<?=gettext("Update extensions list");?>" value="<?=gettext("Update");?>" />
            </div>
    		<?php include("formend.inc");?>
         </td></tr>
    </table>
</form>
<?php include("fend.inc");?>
</div>  <!-- foo -->
