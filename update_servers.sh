#!/usr/bin/env bash

# Run the group updates
php /usr/local/nagios/etc/group_contact_generation.php

# Note when this was last run
touch /usr/local/nagios/etc/Servers_last_imported.txt

# Restart nagios service to load the new configs
service nagios restart

# Get the day of the week
day=$(date +%a)

# If it's Wednesday, mark reboot servers for downtime
if [ $day == 'Wed' ]; then

    # Set a downtime start to be ~10pm tonight
    datetime=`date +%s`
    starttime=`date +%s -d "+22 hours"`
    endtime=`date +%s -d "+24 hours"`
    echo "yay"
    # Send the downtime request
    echo "[$datetime] SCHEDULE_HOSTGROUP_HOST_DOWNTIME;Auto-Patch-And-Reboot;$starttime;$endtime;1;0;7200;bpeters-AD;Test Downtime" > /usr/local/nagios/var/rw/nagios.cmd

fi


