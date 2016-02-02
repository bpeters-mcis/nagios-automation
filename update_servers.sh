#!/usr/bin/env bash

# Run the group updates
php /usr/local/nagios/etc/group_contact_generation.php

# Note when this was last run
touch /usr/local/nagios/etc/Servers_last_imported.txt

# Now, check to see if any of the config files are actually different.
if [ diff objects/servers_from_lansweeper_new.cfg objects/servers_from_lansweeper.cfg >/dev/null ] || [ diff objects/contacts_from_ad_new.cfg objects/contacts_from_ad.cfg >/dev/null ] || [ diff cgi_new.cfg cgi.cfg >/dev/null ] ; then

  # Replace the config files with the new ones, since they are different
  mv objects/servers_from_lansweeper_new.cfg objects/servers_from_lansweeper.cfg
  mv objects/contacts_from_ad_new.cfg objects/contacts_from_ad.cfg
  mv cgi_new.cfg cgi.cfg

  # Restart nagios service to load the new configs
  service nagios restart
fi
