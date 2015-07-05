#!/bin/sh
#
#                     Ampoliros Application Server
#
#                       http://www.ampoliros.com
#

MOD_NAME=ampoliros
SDKDIR=../ampsdk
DOCS_DIR=../ampsdk/API/Main

if [ ! -d ${DOCS_DIR} ]
then
	mkdir -p ${DOCS_DIR}
fi

rm -rf ${DOCS_DIR}/*
cat var/lib/*.library var/lib/*.dblayer var/handlers/*.element var/handlers/*.hui var/handlers/*.xmlrpchandler | sed 's/ *{/;#{/g' | tr "#" "\n" >${DOCS_DIR}/${MOD_NAME}.php
headerdoc2html -o ${DOCS_DIR} ${DOCS_DIR}/${MOD_NAME}.php
rm ${DOCS_DIR}/${MOD_NAME}.php
mv ${DOCS_DIR}/${MOD_NAME}/* ${DOCS_DIR}
rm -rf ${DOCS_DIR}/${MOD_NAME}

${SDKDIR}/build.sh
