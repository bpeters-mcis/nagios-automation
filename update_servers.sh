#!/usr/bin/env bash

# Run the group updates
php /usr/local/nagios/etc/group_contact_generation.php

# Note when this was last run
touch /usr/local/nagios/etc/Servers_last_imported.txt

RESTART="No"

# Now, check to see if any of the config files are actually different.
if cmp -s objects/servers_from_lansweeper_new.cfg objects/servers_from_lansweeper.cfg ; then
    rm objects/servers_from_lansweeper.cfg
    mv objects/servers_from_lansweeper_new.cfg objects/servers_from_lansweeper.cfg
    RESTART="Yes"
    echo "Server definitions are different."
else
    rm objects/servers_from_lansweeper_new.cfg
fi

if cmp -s objects/contacts_from_ad_new.cfg objects/contacts_from_ad.cfg ; then
    rm objects/contacts_from_ad.cfg
    mv objects/contacts_from_ad_new.cfg objects/contacts_from_ad.cfg
    RESTART="Yes"
    echo "Contact definitions are different."
else
    rm objects/contacts_from_ad_new.cfg
fi

if cmp -s cgi_new.cfg cgi.cfg ; then
    rm cgi.cfg
    mv cgi_new.cfg cgi.cfg
    RESTART="Yes"
    echo "CGI definitions are different."
else
    rm cgi_new.cfg
fi

if [ $RESTART == "Yes" ] ; then
  # Restart nagios service to load the new configs
  service nagios restart
fi
