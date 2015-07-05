#
#                     Ampoliros Application Server
#
#                       http://www.ampoliros.com
#

include config.h

SRCDIR = .

.PHONY: backup license

acceptlicense:	inst cron configuration goon

all:	license inst cron configuration goon

license:
	@more LICENSE

message:
	@echo "                     Ampoliros Application Server"
	@echo ""
	@echo "                       http://www.ampoliros.com"
	@echo ""

inst:
	@echo "----- Installing Ampoliros -----"

	@echo "===> Making private directory $(PRIVATEDIR)..."
	@mkdir -p $(PRIVATEDIR)

	@echo "===> Making public directory $(PUBLICDIR)..."
	@mkdir -p $(PUBLICDIR)

	@echo "===> Making sites directory $(SITESDIR)..."
	@mkdir -p $(SITESDIR)

	@echo "===> Installing public tree..."
	@mkdir $(PUBLICDIR)/cgi
	@mkdir $(PUBLICDIR)/cgi/icons
	@mkdir $(PUBLICDIR)/cgi/styles
	@mkdir $(PUBLICDIR)/cgi/icons/crystal
	@mkdir $(PUBLICDIR)/cgi/styles/amp4000
	@mkdir $(PUBLICDIR)/root
	@mkdir $(PUBLICDIR)/admin
	@cp -a $(SRCDIR)/www/* $(PUBLICDIR)/
	@cp -ra $(SRCDIR)/www/cgi/* $(PUBLICDIR)/cgi
	@cp -ra $(SRCDIR)/www/root/* $(PUBLICDIR)/root
	@cp -ra $(SRCDIR)/www/admin/* $(PUBLICDIR)/admin
	@cp -ra $(SRCDIR)/www/themes/icons/crystal/* $(PUBLICDIR)/cgi/icons/crystal/
	@cp -ra $(SRCDIR)/www/themes/styles/amp4000/* $(PUBLICDIR)/cgi/styles/amp4000/
	@echo >$(PUBLICDIR)/root/cfgpath.php  "<?php require( \"$(PRIVATEDIR)/etc/ampoliros.php\" ); ?>"
	@echo >$(PUBLICDIR)/admin/cfgpath.php "<?php require( \"$(PRIVATEDIR)/etc/ampoliros.php\" ); ?>"
	@echo >$(PUBLICDIR)/cgi/cfgpath.php "<?php require( \"$(PRIVATEDIR)/etc/ampoliros.php\" ); ?>"
	@echo >$(PUBLICDIR)/cfgpath.php "<?php require( \"$(PRIVATEDIR)/etc/ampoliros.php\" ); ?>"
	@chown $(HTTPDUSER):$(HTTPDGROUP) $(PUBLICDIR) -R

	@echo "===> Installing private tree..."
	@mkdir -p $(PRIVATEDIR)/etc
	@echo >$(PRIVATEDIR)/etc/ampconfigpath.php "<?php define( \"AMP_CONFIG\", \"$(PRIVATEDIR)/etc/ampconfig.cfg\" );?>"
	@cp -ra $(SRCDIR)/etc/* $(PRIVATEDIR)/etc
	@mkdir -p $(PRIVATEDIR)/var
	@mkdir -p $(PRIVATEDIR)/var/bin
	@cp -ra $(SRCDIR)/var/bin/* $(PRIVATEDIR)/var/bin
	@mkdir -p $(PRIVATEDIR)/var/db
	@cp -ra $(SRCDIR)/var/db/*.*sql $(PRIVATEDIR)/var/db
	@mkdir -p $(PRIVATEDIR)/var/help
	@mkdir -p $(PRIVATEDIR)/var/locale
	@cp -ra $(SRCDIR)/var/locale/*.catalog $(PRIVATEDIR)/var/locale
	@cp -ra $(SRCDIR)/var/locale/*.country $(PRIVATEDIR)/var/locale
	@mkdir -p $(PRIVATEDIR)/var/lib
	@cp -ra $(SRCDIR)/var/lib/*.library $(PRIVATEDIR)/var/lib
	@cp -ra $(SRCDIR)/var/lib/*.dblayer $(PRIVATEDIR)/var/lib
	@mkdir -p $(PRIVATEDIR)/var/log
	@mkdir -p $(PRIVATEDIR)/var/modules
	@mkdir -p $(PRIVATEDIR)/var/sites
	@mkdir -p $(PRIVATEDIR)/var/classes
	@cp -ra $(SRCDIR)/var/classes/* $(PRIVATEDIR)/var/classes
	@mkdir -p $(PRIVATEDIR)/var/handlers
	@cp -ra $(SRCDIR)/var/handlers/*.element $(PRIVATEDIR)/var/handlers
	@cp -ra $(SRCDIR)/var/handlers/*.xmlrpchandler $(PRIVATEDIR)/var/handlers
	@cp -ra $(SRCDIR)/var/handlers/*.hui $(PRIVATEDIR)/var/handlers
	@cp -ra $(SRCDIR)/var/handlers/*.huivalidator $(PRIVATEDIR)/var/handlers
	@cp -ra $(SRCDIR)/var/handlers/*.maintenance $(PRIVATEDIR)/var/handlers
	@mkdir -p $(PRIVATEDIR)/tmp
	@chmod 755 $(PRIVATEDIR)/tmp
	@mkdir -p $(PRIVATEDIR)/tmp/ampoliros
	@cp -ra $(SRCDIR)/* $(PRIVATEDIR)/tmp/ampoliros
	@cp -a $(SRCDIR)/LICENSE $(PRIVATEDIR)
	@cp -a $(SRCDIR)/README $(PRIVATEDIR)
	@cp -a $(SRCDIR)/VERSION $(PRIVATEDIR)
	@cp -a $(SRCDIR)/AUTHORS $(PRIVATEDIR)
	@cp -a $(SRCDIR)/CHANGES $(PRIVATEDIR)
	@cp -a $(SRCDIR)/TROUBLESHOOTING $(PRIVATEDIR)
	@touch $(PRIVATEDIR)/tmp/.setup
	@chown $(HTTPDUSER):$(HTTPDGROUP) $(PRIVATEDIR) -R

	@echo "===> Installing sites tree..."
	@chown $(HTTPDUSER):$(HTTPDGROUP) $(SITESDIR) -R

configuration:
	@echo "===> Building Ampoliros configuration..."
	@echo >$(PRIVATEDIR)/etc/ampconfig.cfg HTTPD_GROUP = $(HTTPDGROUP)
	@echo >>$(PRIVATEDIR)/etc/ampconfig.cfg HTTPD_USER = $(HTTPDUSER)
	@echo >>$(PRIVATEDIR)/etc/ampconfig.cfg ROOTCRONTAB = $(ROOTCRONTAB)
	@echo >>$(PRIVATEDIR)/etc/ampconfig.cfg AMP_HOST = $(AMP_HOST)
	@echo >>$(PRIVATEDIR)/etc/ampconfig.cfg AMP_URL = $(AMP_URL)
	@echo >>$(PRIVATEDIR)/etc/ampconfig.cfg AMP_ROOTURL = $(AMP_ROOTURL)
	@echo >>$(PRIVATEDIR)/etc/ampconfig.cfg ADMIN_URL = $(AMP_ADMINURL)
	@echo >>$(PRIVATEDIR)/etc/ampconfig.cfg CGI_URL = $(AMP_CGIURL)
	@echo >>$(PRIVATEDIR)/etc/ampconfig.cfg PUBLIC_TREE = $(PUBLICDIR)/
	@echo >>$(PRIVATEDIR)/etc/ampconfig.cfg PRIVATE_TREE = $(PRIVATEDIR)/
	@echo >>$(PRIVATEDIR)/etc/ampconfig.cfg SITES_TREE = $(SITESDIR)/
	@echo >>$(PRIVATEDIR)/etc/ampconfig.cfg AMP_LANG = en
	@echo >>$(PRIVATEDIR)/etc/ampconfig.cfg PHP_EXECUTION_TIME_LIMIT = 0
	@echo >>$(PRIVATEDIR)/etc/ampconfig.cfg PHP_MEMORY_LIMIT = 64M
	@echo >>$(PRIVATEDIR)/etc/ampconfig.cfg SESSION_LIFETIME = 525600
	@chown $(HTTPDUSER):$(HTTPDGROUP) $(PRIVATEDIR)/etc/ampconfig.cfg
	@echo "===> Done.";

cron:
	@echo "===> Setting up root cron tab..."
	@echo >>$(ROOTCRONTAB) "* * * * * $(PRIVATEDIR)/var/bin/updater \"sh $(PRIVATEDIR)/etc/simplecron_temporary\"  1>/dev/null 2>/dev/null"
	@echo >>$(ROOTCRONTAB) "* * * * * $(PRIVATEDIR)/var/bin/updater \"sh $(PRIVATEDIR)/etc/simplecron_regular\" 1>/dev/null 2>/dev/null"

goon:
	@echo "Ampoliros first stage installation has been completed."
	@echo "Now you can point your browser to Ampoliros url ($(AMP_URL))."

remove: message
	@echo "----- Removing Ampoliros -----"
	@echo "===> Removing $(PRIVATEDIR)..."
	@rm -rf $(PRIVATEDIR)
	@echo "===> Removing $(PUBLICDIR)..."
	@rm -rf $(PUBLICDIR)
	@echo "===> Done. All databases and sites directory were not removed."
	@echo "You should remove the following lines from $(ROOTCRONTAB):"
	@echo "* * * * * $(PRIVATEDIR)/var/bin/updater \"sh $(PRIVATEDIR)/etc/simplecron_temporary\"  1>/dev/null 2>/dev/null"
	@echo "* * * * * $(PRIVATEDIR)/var/bin/updater \"sh $(PRIVATEDIR)/etc/simplecron_regular\" 1>/dev/null 2>/dev/null"

rpm: message
	cd ..;tar cfz /usr/src/RPM/SOURCES/ampoliros.tgz ampoliros
	rpm -ba ampoliros.spec
