<?php
/*
    htop-start.php 

    Copyright (c) 2018 - 2020 Andreas Schmidhuber
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
require_once("/usr/local/www/ext/onebuttoninstaller/extension-lib.inc");
$rootfolder = dirname(__FILE__);

$return_val = 0;

$pkgName = "htop";
$pkgFileNameNeeded = $pkgName;
$manifest = ext_load_package($pkgName, $pkgFileNameNeeded, $rootfolder);
$return_val += mwexec("mkdir -p /usr/local/share/pixmaps", true);
$return_val += mwexec("ln -sf '{$rootfolder}/bin/{$pkgName}/usr/local/bin/htop' /usr/local/bin", true);
$return_val += mwexec("ln -sf '{$rootfolder}/bin/{$pkgName}/usr/local/share/pixmaps/htop.png' /usr/local/share/pixmaps", true);

if ($return_val == 0) mwexec("logger {$pkgName}-extension: started successfully");
else mwexec("logger {$pkgName}-extension: error(s) during startup, failed with return value = {$return_val}");
echo "RETVAL = {$return_val}\n";
?>
