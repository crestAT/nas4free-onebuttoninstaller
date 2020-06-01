<?php
/*
    onebuttoninstaller-start.php 

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
require_once("config.inc");

$configName = "onebuttoninstaller";
$rootfolder = dirname(__FILE__);

if (is_link("/usr/local/share/locale-{$configName}")) unlink("/usr/local/share/locale-{$configName}");
if (is_link("/usr/local/www/{$configName}.php")) unlink("/usr/local/www/{$configName}.php");
if (is_link("/usr/local/www/{$configName}-config.php")) unlink("/usr/local/www/{$configName}-config.php");
if (is_link("/usr/local/www/{$configName}-update_extension.php")) unlink("/usr/local/www/{$configName}-update_extension.php");
if (is_link("/usr/local/www/ext/{$configName}")) unlink("/usr/local/www/ext/{$configName}");
mwexec("rmdir -p /usr/local/www/ext");
$return_val = 0;
// create links to extension files
$return_val += mwexec("ln -sw {$rootfolder}/locale-{$configName} /usr/local/share/", true);
$return_val += mwexec("ln -sw {$rootfolder}/{$configName}.php /usr/local/www/{$configName}.php", true);
$return_val += mwexec("ln -sw {$rootfolder}/{$configName}-config.php /usr/local/www/{$configName}-config.php", true);
$return_val += mwexec("ln -sw {$rootfolder}/{$configName}-update_extension.php /usr/local/www/{$configName}-update_extension.php", true);
$return_val += mwexec("mkdir -p /usr/local/www/ext", true);
$return_val += mwexec("ln -sw {$rootfolder}/ext /usr/local/www/ext/{$configName}", true);
// check for product name and eventually rename translation files for new product name (XigmaNAS)
$domain = strtolower(get_product_name());
if ($domain <> "nas4free") $return_val += mwexec("find {$rootfolder}/locale-{$configName} -name nas4free.mo -execdir mv nas4free.mo {$domain}.mo \;", true);

if ($return_val == 0) exec("logger {$configName}-extension: started");
else exec("logger {$configName}-extension: error(s) during startup, failed with return value = {$return_val}"); 
?>
