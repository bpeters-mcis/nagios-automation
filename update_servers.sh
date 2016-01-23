#!/usr/bin/env bash

# Run the group updates
php /usr/local/nagios/etc/group_contact_generation.php

# Restart nagios service to load the new configs
service nagios restart
