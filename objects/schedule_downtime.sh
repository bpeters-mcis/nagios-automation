#!/usr/bin/env bash

###################################################################
# Downtime Settings
###################################################################

# Downtime Date Arrays
TESTDATES=(2016-02-05 2016-02-26 2016-04-01 2016-05-13 2016-06-03)
PRODDATES=(2016-02-12 2016-03-04 2016-04-08 2016-05-20 2016-06-10)

# Get the day of the week, and the full date
day=$(date +%a)
DATE=`date +%Y-%m-%d`


# If it's Wednesday, mark reboot servers for downtime
if [ $day == 'Wed' ]; then

    # Set a downtime start to be ~10pm tonight, end at midnight
    datetime=`date +%s`
    starttime=`date +%s -d "+22 hours"`
    endtime=`date +%s -d "+24 hours"`
    # Send the downtime request
    echo "[$datetime] SCHEDULE_HOSTGROUP_HOST_DOWNTIME;Auto-Patch-And-Reboot;$starttime;$endtime;1;0;7200;bpeters-AD;Auto Reboot Downtime" > /usr/local/nagios/var/rw/nagios.cmd

fi

# If today is a test downtime, mark the test downtime boxes for downtime
if [[ " ${TESTDATES[*]} " == *" $DATE "* ]]; then

    # Set a downtime start to be ~12pm today, end at 5pm
    datetime=`date +%s`
    starttime=`date +%s -d "+675 minutes"`
    endtime=`date +%s -d "+975 minutes"`
    # Send the downtime request
    echo "[$datetime] SCHEDULE_HOSTGROUP_HOST_DOWNTIME;Downtime-Test;$starttime;$endtime;1;0;7200;bpeters-AD;Test Downtime" > /usr/local/nagios/var/rw/nagios.cmd

fi

# If today is a prod downtime, mark the prod downtime boxes for downtime
if [[ " ${PRODATES[*]} " == *" $DATE "* ]]; then

    # Set a downtime start to be ~5:45pm today, end at 11pm
    datetime=`date +%s`
    starttime=`date +%s -d "+1035 minutes"`
    endtime=`date +%s -d "+1350 minutes"`
    # Send the downtime request
    echo "[$datetime] SCHEDULE_HOSTGROUP_HOST_DOWNTIME;Downtime-Prod;$starttime;$endtime;1;0;7200;bpeters-AD;Production Downtime" > /usr/local/nagios/var/rw/nagios.cmd

fi