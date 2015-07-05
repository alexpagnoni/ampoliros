#!/bin/bash
#
#                     Ampoliros Application Server
#
#                       http://www.ampoliros.com
#

WHERE=`pwd`

TGZ_NAME="ampoliros_4000-4.1.tgz"
DIR_NAME="ampoliros"
#  ./sdk.sh

rm `find -name "*~"` 2>/dev/null
rm `find|grep "#"` 2>/dev/null
cd ..
tar -cz --exclude=OLD --exclude=work --exclude=*~ --exclude=CVS --exclude=.?* --exclude=np --exclude=.cvsignore -f $TGZ_NAME $DIR_NAME
cd "$WHERE"
