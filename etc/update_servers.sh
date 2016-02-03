#!/usr/bin/env bash

# Run the group updates
php /usr/local/nagios/etc/group_contact_generation.php

# Note when this was last run
touch /usr/local/nagios/etc/Servers_last_imported.txt

# Take ownership of the nagios files
chown -R nagios:nagios /usr/local/nagios/share
chown -R nagios:nagios /usr/local/nagios/etc
chown -R nagios:nagios /usr/local/nagios/libexec
chown -R nagios:nagios /usr/local/nagios/include

RESTART="No"

# Now, check to see if any of the config files are actually different.
if cmp -s /usr/local/nagios/etc/objects/servers_from_lansweeper_new.cfg /usr/local/nagios/etc/objects/servers_from_lansweeper.cfg ; then
    rm /usr/local/nagios/etc/objects/servers_from_lansweeper_new.cfg
else
    rm /usr/local/nagios/etc/objects/servers_from_lansweeper.cfg
    mv /usr/local/nagios/etc/objects/servers_from_lansweeper_new.cfg /usr/local/nagios/etc/objects/servers_from_lansweeper.cfg
    RESTART="Yes"
    echo "Server definitions are different."
fi

if cmp -s /usr/local/nagios/etc/objects/contacts_from_ad_new.cfg /usr/local/nagios/etc/objects/contacts_from_ad.cfg ; then
    rm /usr/local/nagios/etc/objects/contacts_from_ad_new.cfg
else

    rm /usr/local/nagios/etc/objects/contacts_from_ad.cfg
    mv /usr/local/nagios/etc/objects/contacts_from_ad_new.cfg /usr/local/nagios/etc/objects/contacts_from_ad.cfg
    RESTART="Yes"
    echo "Contact definitions are different."
fi

if cmp -s /usr/local/nagios/etc/cgi_new.cfg /usr/local/nagios/etc/cgi.cfg ; then
    rm /usr/local/nagios/etc/cgi_new.cfg
else
    rm /usr/local/nagios/etc/cgi.cfg
    mv /usr/local/nagios/etc/cgi_new.cfg /usr/local/nagios/etc/cgi.cfg
    RESTART="Yes"
    echo "CGI definitions are different."
fi

if [ $RESTART == "Yes" ] ; then
  # Restart nagios service to load the new configs
  service nagios restart
fi
