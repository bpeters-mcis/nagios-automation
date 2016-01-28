#!/usr/bin/env bash

# Get the day of the week
day=$(date +%a)

# If it's Wednesday, mark reboot servers for downtime
if
day=`($date +%A)`
echo $day

#datetime=`date +%s`
#starttime=`date +%s -d "+ $20 minutes"`
#endtime=`date +%s -d "+ $25 minutes"`
#/usr/bin/printf "[$datetime] SCHEDULE_HOSTGROUP_HOST_DOWNTIME;Auto-Patch-And-Reboot;$starttime;$endtime;1;0;7200;bpeters-AD;Test Downtime From Remote\n" $now > /usr/local/nagios/var/rw/nagios.cmd
#/usr/bin/printf "[$datetime] ADD_HOST_COMMENT;LANSWEEPER;1;bpeters;This is a test comment\n" $now > /usr/local/nagios/var/rw/nagios.cmd
#echo "Downtimed the lansweeper host groups for 5 minutes"

