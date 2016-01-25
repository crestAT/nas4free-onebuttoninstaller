#!/bin/sh
# filename:     mcommander.sh
# author:       Dan Merschi
# date:         2009-07-28 ; Add multiplatform support
# author:       Graham Inggs <graham@nerve.org.za>
# date:         2012-04-11 ; Updated for NAS4Free 9.0.0.1
# date:         2013-02-09 ; Updated for ftp.freebsd.org restructuring and latest mc-light version
# date:         2013-05-05 ; Switch from mc-light to mc ; drop compat7x ; add libslang
# date:         2013-08-10 ; Update mc package name to mc-4.8.8.tbz
# date:         2013-08-23 ; Fetch files from packages-9.2-release ; add libssh2
# date:         2014-12-18 ; Update for v9.3; Clean up
# date:         2015-02-21 ; Update mc package to mc-4.8.11.tbz
# purpose:      Install Midnight Commander on NAS4Free (embedded version).
# Note:         Check the end of the page.
#
#----------------------- Set variables ------------------------------------------------------------------
DIR=`dirname $0`;
URL="ftp://ftp-archive.freebsd.org/pub/FreeBSD-Archive/ports/amd64/packages-9.2-release/Latest"
MCLIGHTFILE="mc.tbz"
LIBSLANGFILE="libslang2.tbz"
LIBSSH2FILE="libssh2.tbz"
#----------------------- Set Errors ---------------------------------------------------------------------
_msg() { case $@ in
  0) echo "The script will exit now."; exit 0 ;;
  1) echo "Error! Can not download ${FILE}"; _msg 0 ;;
  2) echo "Can't find ${FILE} on ${DIR}"; _msg 0 ;;
  3) echo "Midnight Commander installed and ready!"; exit 0 ;;
  4) echo "Always run this script using the full path: /path/to/$(basename $0)"; _msg 0 ;;
esac }
#----------------------- Check for full path ------------------------------------------------------------
[ `echo $0 | cut -c1` = "/" ] || _msg 4
cd ${DIR}
#----------------------- Download and decompress mc files if don't exist --------------------------------
FILE=${MCLIGHTFILE}
if [ ! -d ${DIR}/bin ]; then
  if [ ! -e ${DIR}/${FILE} ]; then fetch ${URL}/${FILE} || _msg 1; fi
  if [ -r ${DIR}/${FILE} ]; then tar -xf ${DIR}/${FILE} || _msg 2; rm ${DIR}/+*; rm -R ${DIR}/man; fi
  [ -d ${DIR}/bin ] || _msg 4
fi
#----------------------- Download and decompress libslang files if don't exist --------------------------
FILE=${LIBSLANGFILE}
if [ ! -d ${DIR}/lib ]; then
  if [ ! -e ${DIR}/${FILE} ]; then fetch ${URL}/${FILE} || _msg 1; fi
  if [ -r ${DIR}/${FILE} ]; then tar -xf ${DIR}/${FILE} || _msg 2; rm ${DIR}/+*;
    rm -R ${DIR}/libdata; rm -R ${DIR}/man; rm -R ${DIR}/include; rm ${DIR}/lib/*.a;
    rm ${DIR}/bin/slsh; rm ${DIR}/etc/slsh.rc; fi
  [ -d ${DIR}/lib ] || _msg 4
fi
#----------------------- Download and decompress libssh2 files if don't exist ---------------------------
FILE=${LIBSSH2FILE}
if [ ! -f ${DIR}/lib/libssh2.so ]; then
  if [ ! -e ${DIR}/${FILE} ]; then fetch ${URL}/${FILE} || _msg 1; fi
  if [ -f ${DIR}/${FILE} ]; then tar xzf ${DIR}/${FILE} || _msg 2}; rm ${DIR}/+*;
    rm -R ${DIR}/libdata; rm -R ${DIR}/man; rm -R ${DIR}/include; rm ${DIR}/lib/*.a; rm ${DIR}/lib/*.la; fi
  if [ ! -d ${DIR}/lib ]; then _msg 4; fi
fi
#----------------------- Create symlinks ----------------------------------------------------------------
[ -e /usr/local/share/mc ] || ln -s ${DIR}/share/mc /usr/local/share
[ -e /usr/local/libexec/mc ] || ln -s ${DIR}/libexec/mc /usr/local/libexec
[ -e /usr/local/etc/mc ] || ln -s ${DIR}/etc/mc /usr/local/etc
for i in `ls ${DIR}/bin/`; do
   [ -e /usr/local/bin/${i} ] || ln -s ${DIR}/bin/${i} /usr/local/bin
done
for i in `ls ${DIR}/share/locale`; do
  if [ ! -e /usr/local/share/locale/${i} ]; then
    ln -s ${DIR}/share/locale/${i} /usr/local/share/locale
  else
    [ -e /usr/local/share/locale/${i}/LC_MESSAGES/mc.mo ] || \
    ln -s ${DIR}/share/locale/${i}/LC_MESSAGES/mc.mo /usr/local/share/locale/${i}/LC_MESSAGES
  fi
done
for i in `ls ${DIR}/lib`; do
   [ -e /usr/local/lib/${i} ] || ln -s ${DIR}/lib/${i} /usr/local/lib
done

# Symlinks for v10.1.0.2
cd /usr/local/lib
[ -e libssl.so.6 ] || ln -s /usr/lib/libssl.so.7 libssl.so.6
[ -e libcrypto.so.6 ] || ln -s /lib/libcrypto.so.7 libcrypto.so.6
[ -e libpcre.so.3 ] || ln -s /usr/local/lib/libpcre.so.1 libpcre.so.3
[ -e libintl.so.9 ] || ln -s /usr/local/lib/libintl.so.8 libintl.so.9
[ -e libiconv.so.3 ] || ln -s /usr/local/lib/libiconv.so.2 libiconv.so.3
cd -

# Aliases
mkdir -p /etc/profile.d
ln -s ${DIR}/libexec/mc/mc.sh /etc/profile.d
ln -s ${DIR}/libexec/mc/mc.csh /etc/profile.d

cat <<'EOFF' >> /etc/profile

# Append any additional sh scripts found in /etc/profile.d
for profile_script in /etc/profile.d/*.sh; do
  [ -x ${profile_script} ] && . ${profile_script}
done
unset profile_script
EOFF

cat <<'EOFF' >> /etc/csh.login

# Append any additional csh scripts found in /etc/profile.d
[ -d /etc/profile.d ]
if ($status == 0) then
  set nonomatch
  foreach file ( /etc/profile.d/*.csh )
    [ -x $file ]
    if ($status == 0) then
      source $file
    endif
  end
  unset file nonomatch
endif
EOFF

# Done
_msg 3

#----------------------- End of Script ------------------------------------------------------------------
# 1. Keep this script in his own directory.
# 2. chmod the script u+x,
# 3. Always run this script using the full path: /mnt/share/directory/mcommander
# 4. You can add this script to WebGUI: Advanced: Commands as Post command (see 3).
# 5. To run Midnight Commander from shell type 'mc'.
