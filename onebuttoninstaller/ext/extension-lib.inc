<?php
/*
	extension-lib.inc

    Copyright (c) 2016 - 2018 Andreas Schmidhuber
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

$extension_lib_version = "1.2";
/* Version history:
 * 1.2	2018.08.13	function ext_check_version: append line break to the output message 
 * 1.1	2017.02.28	save config in ext_remove_rc_commands and ext_create_rc_commands
 * 1.0	2017.02.27	includes changes for PHP 7.1
*/

/*	function ext_check_release
 *	checks current release against a minimal neccessary release
 *	params:
 *		$min_release:	a float string to compare with the current used release, e.g.: 9.3, 10.3.0.3.2853 => 10.3032853, 11.0
 *	return:
 * 		true	=> current release >= minimal release
 * 		false	=> current release < minimal release 
*/
if (!function_exists('ext_check_release'))   {
    function ext_check_release($min_release) {
		global $config;
		$product_version = explode(".", get_product_version());				// p.version = 10.3.0.3, p.revision = 2853
		$current_release = $product_version[0].".".$product_version[1].$product_version[2].$product_version[3].get_product_revision();
		if ($current_release < floatval($min_release)) return false;		// release not supported
		else return true;													// release supported
	}
}

/*	function ext_create_rc_commands
 *	params:
 *		$appname:	extension name
 * 		$postinit:	postinit command
 * 		$shutdown:	shutdown command
 * 		$start_action:	comment e.g. Start, Run, Initialize
 * 		$stop_action:	comment e.g. Stop, Closedown
 * 	return:
 * 		$postinit:	uuid or false
 * 		$shutdown:	uuid or false
*/
if (!function_exists('ext_create_rc_commands'))   {
    function ext_create_rc_commands($appname, &$postinit = false, &$shutdown = false, $start_action = "Start", $stop_action = "Stop") {
		global $config;
		if (ext_check_release(11.0)) {										// new rc format
			unset($rc_param);
			// postinit command
			if (($postinit !== false) && !empty($postinit)) {
				$rc_param = [];
				$rc_param['uuid'] = uuid();
				$rc_param['name'] = "{$appname} Extension";
				$rc_param['value'] = $postinit;
				$rc_param['comment'] = "{$start_action} {$appname}";
				$rc_param['typeid'] = '2';
				$rc_param['enable'] = true;
				if (!is_array($config['rc'])) $config['rc'] = [];
				if (!is_array($config['rc']['param'])) $config['rc']['param'] = [];
				$config['rc']['param'][] = $rc_param;
				$postinit = $rc_param['uuid'];
				unset($rc_param);
			}
			// shutdown command
			if (($shutdown !== false) && !empty($shutdown)) {
				$rc_param = [];
				$rc_param['uuid'] = uuid();
				$rc_param['name'] = "{$appname} Extension";
				$rc_param['value'] = $shutdown;
				$rc_param['comment'] = "{$stop_action} {$appname}";
				$rc_param['typeid'] = '3';
				$rc_param['enable'] = true;
				if (!is_array($config['rc'])) $config['rc'] = [];
				if (!is_array($config['rc']['param'])) $config['rc']['param'] = [];
				$config['rc']['param'][] = $rc_param;
				$shutdown = $rc_param['uuid'];				
				unset($rc_param);
			}
		}
		else {
			if (($postinit !== false) && !empty($postinit)) {
			    $config['rc']['postinit']['cmd'][] = $postinit;
				$postinit = false;
			}
			if (($shutdown !== false) && !empty($shutdown)) {
			    $config['rc']['shutdown']['cmd'][] = $shutdown;
				$shutdown = false;
			}
		}
	write_config();
    }
}

/*	function ext_remove_rc_commands
 *	remove postinit / shutdown commands from rc array for both NAS4Free < 11.x and >= 11.x series
 *	params:
 *		$config_name:	string to identify the rc command entry in rc|postinit/shutdown|cmd or rc|param|value 		                                    	
*/
if (!function_exists('ext_remove_rc_commands'))   {
    function ext_remove_rc_commands($config_name) {
		global $config;
		// remove existing old rc format entries
		if (is_array($config['rc']) && is_array($config['rc']['postinit']) && is_array( $config['rc']['postinit']['cmd'])) {
		    $rc_param_count = count($config['rc']['postinit']['cmd']);
		    for ($i = 0; $i < $rc_param_count; $i++) {
		        if (preg_match("/{$config_name}/", $config['rc']['postinit']['cmd'][$i])) unset($config['rc']['postinit']['cmd'][$i]);
		    }
		}
		if (is_array($config['rc']) && is_array($config['rc']['shutdown']) && is_array( $config['rc']['shutdown']['cmd'])) {
		    $rc_param_count = count($config['rc']['shutdown']['cmd']);
		    for ($i = 0; $i < $rc_param_count; $i++) {
		        if (preg_match("/{$config_name}/", $config['rc']['shutdown']['cmd'][$i])) unset($config['rc']['shutdown']['cmd'][$i]);
		    }
		}
		// remove existing entries for new rc format
		if (is_array($config['rc']) && is_array($config['rc']['param']['0'])) {
			$rc_param_count = count($config['rc']['param']);
		    for ($i = 0; $i < $rc_param_count; $i++) {
		        if (preg_match("/{$config_name}/", $config['rc']['param'][$i]['value'])) unset($config['rc']['param'][$i]);
			}
		}
	write_config();
    }
}

/*	function ext_check_version
 *	checks an extension if a new version exists against the currently installed version
 *	params:
 *		$test_filename:		filename including the full path which holds the version number
 * 		$extension_name:	extension name in URL format to retrive from Github
 * 		$current_version:	string of current installed version
 * 		$button_name:		gettext("name") to create a note for user action, e.g. Maintenance, Update
 * 	return:
 * 		empty:	no new version found
 * 		or string:	message for user action on new version found
 * 		or false: 	Github version could not be retrieved 
*/
if (!function_exists('ext_check_version'))   {
    function ext_check_version($test_filename, $extension_name, $current_version, $button_name) {
		global $config;
		if (!is_file($test_filename) || filemtime($test_filename) < time() - 86400) {	// test if file exists or is older than 24 hours
			$return_val = mwexec("fetch -o {$test_filename} https://raw.github.com/crestAT/nas4free-{$extension_name}/master/{$extension_name}/version.txt", false);
			if ($return_val == 0) {
			    $server_version = exec("cat {$test_filename}");
			    if ($server_version != $current_version) return sprintf(gettext("New extension version %s available, push '%s' button to install the new version!"), $server_version, $button_name)."<br />";
			}
			else return false;
		}
    }
}

/*	function ext_load_config
 *	loads a json formatted array from a standalone file 
 *	params:
 *		$config_file:	config file including full path
 * 	return:
 *		data array:	json formatted array
 * 		or false:	config file not found or content not retrievable 		
*/
if (!function_exists('ext_load_config'))   {
    function ext_load_config($config_file, $assoc = true, $depth = 512, $options = 0) {
        if (is_file($config_file)) {
            if (($config_data = file_get_contents($config_file)) === false) return false;
            $config_data = utf8_encode($config_data);
            $config_data = json_decode($config_data, $assoc, $depth, $options);
            return $config_data;
        }
        else return false;
    }
}

/*	function ext_save_config
 *	loads a json formatted array from a standalone file
 *	params:
 *		$config_file:	config file including full path
 * 		$config_data:	php array with configuration data
 * 	return:
 * 		error condition
 */
if (!function_exists('ext_save_config'))   {
    function ext_save_config($config_file, $config_data){
    	header("Content-Type: application/json; UTF-8\n");
        file_put_contents($config_file, json_encode($config_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ));
        return json_last_error();
    }
}
?>
