OneButtonInstaller
------------------

NAS4Free extension to display and install all known available Extensions/Add-Ons directly inside the NAS4Free WebGUI 
just with the press of one button without the need to use the CLI.

The extension
- allows the installation of all known Extensions/Add-Ons inside the NAS4Free WebGUI with a common interface
- allows a One Button Installation, just by selecting one or more entries and pressing 'Install'
- shows all known available/installed Extensions/Add-Ons on one page with a short description and links to the appropriate forum threads
- pre-checks and displays known unsupported platforms/architectures per extension
- features manual/automatic update of the Extensions list to get new extensions to install
- is based on the current installation procedures of the currently known extension/add-ons
- works on all plattforms
- does not need jail or pkg_add.
- add pages to NAS4Free WebGUI
- features easy installation, configuration and extension update & removal management

INSTALLATION
------------
1. Prior to the installation perform a backup of the NAS4Free configuration via SYSTEM | BACKUP/RESTORE | Download configuration.
2. Open the NAS4Free WebGUI menu entry ADVANCED | COMMAND, copy the following line, paste it to the command field and push "Execute", this will copy the installer to your system:
<pre>
fetch https://raw.github.com/crestAT/nas4free-onebuttoninstaller/master/OBI.php && mkdir -p ext/OBI; echo \'<a href="OBI.php">OneButtonInstaller</a>\' > ext/OBI/menu.inc
</pre>
3. Refresh the NAS4Free WebGUI and open the menu entry EXTENSIONS | OneButtonInstaller, choose a directory to install the extension to and hit 'Save' to finish the installation or hit 'Cancel' to abort and remove the installer from the system.
4. After successful completion you can access the extension from the WebGUI menu entry EXTENSIONS | OneButtonInstaller.

DISCLAIMER
----------
This Extensions is provided AS-IS, I'm NOT responsible for any data loss or damage caused by the use of it, use it solely at your own risk.

