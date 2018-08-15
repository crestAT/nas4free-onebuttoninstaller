#!/bin/sh
# filename:     ncdu.sh
# author:       Graham Inggs
# date:         2018-05-24 ; Initial release for NAS4Free 11.1.0.4
# purpose:      Install NCurses Disk Usage (ncdu) on NAS4Free (embedded version).
# Note:         Check the end of the page.
#
#----------------------- Set variables ------------------------------------------------------------------
DIR=`dirname $0`;
PLATFORM=`uname -m`
RELEASE=`uname -r | cut -d- -f1`
REL_MAJOR=`echo $RELEASE | cut -d. -f1`
REL_MINOR=`echo $RELEASE | cut -d. -f2`
#URL="http://distcache.freebsd.org/FreeBSD:${REL_MAJOR}:${PLATFORM}/release_${REL_MINOR}/All"
URL="http://distcache.freebsd.org/FreeBSD:${REL_MAJOR}:${PLATFORM}/release_1/All"
NCDUFILE="ncdu-1.12.txz"
#----------------------- Set Errors ---------------------------------------------------------------------
_msg() { case $@ in
  0) echo "The script will exit now."; exit 0 ;;
  1) echo "No route to server, or file do not exist on server"; _msg 0 ;;
  2) echo "Can't find ${FILE} on ${DIR}"; _msg 0 ;;
  3) echo "NCurses Disk Usage installed and ready! (ONLY USE DURING A SSH SESSION)"; exit 0 ;;
  4) echo "Always run this script using the full path: /mnt/.../directory/ncdu.sh"; _msg 0 ;;
esac ; exit 0; }
#----------------------- Check for full path ------------------------------------------------------------
if [ ! `echo $0 |cut -c1-5` = "/mnt/" ]; then _msg 4; fi
cd $DIR;
#----------------------- Download and decompress ncdu files if needed -----------------------------------
FILE=${NCDUFILE}
if [ ! -d ${DIR}/usr/local/bin ]; then
  if [ ! -e ${DIR}/${FILE} ]; then fetch ${URL}/${FILE} || _msg 1; fi
  if [ -f ${DIR}/${FILE} ]; then tar xzf ${DIR}/${FILE} || _msg 2;
    rm ${DIR}/+*; rm -R ${DIR}/usr/local/man; rm -R ${DIR}/usr/local/share; fi
  if [ ! -d ${DIR}/usr/local/bin ] ; then _msg 4; fi
fi
#----------------------- Create symlinks ----------------------------------------------------------------
for i in `ls $DIR/usr/local/bin/`
  do if [ ! -e /usr/local/bin/${i} ]; then ln -s ${DIR}/usr/local/bin/$i /usr/local/bin; fi; done
_msg 3 ; exit 0;
#----------------------- End of Script ------------------------------------------------------------------
# 1. Keep this script in its own directory.
# 2. chmod the script u+x,
# 3. Always run this script using the full path: /mnt/.../directory/ncdu.sh
# 4. You can add this script to WebGUI: Advanced: Command Scripts as a PostInit command (see 3).
# 5. To run Ncurses Disk Usage from shell type 'ncdu'.
