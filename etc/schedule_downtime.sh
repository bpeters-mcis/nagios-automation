#!/usr/bin/env bash

###################################################################
# Downtime Settings
###################################################################

# Downtime Date Arrays
TESTDATES=(2016-10-14 2016-11-04 2016-11-18)
PRODDATES=(2016-09-23 2016-10-21 2016-11-11 2016-12-02)

# Get the day of the week, and the full date
day=$(date +%a)
DATE=`date +%Y-%m-%d`
datetime=`date +%s`

# Schedule downtime for the people.campus replication - we know that happens every night, don't sweat it
starttime=`date +%s -d "+1 hours"`
endtime=`date +%s -d "+6 hours"`
echo "[$datetime] SCHEDULE_SVC_DOWNTIME;idm2.emich.edu;People.campus Replication;$starttime;$endtime;1;0;7200;bpeters-AD;People.campus Downtime" > /usr/local/nagios/var/rw/nagios.cmd


# If it's Wednesday, mark reboot servers for downtime
if [ $day == 'Wed' ]; then

    # Set a downtime start to be ~10pm tonight, end at midnight
    starttime=`date +%s -d "+22 hours"`
    endtime=`date +%s -d "+24 hours"`
    # Send the downtime request
    echo "[$datetime] SCHEDULE_HOSTGROUP_HOST_DOWNTIME;Auto-Patch-And-Reboot;$starttime;$endtime;1;0;7200;bpeters-AD;Auto Reboot Downtime" > /usr/local/nagios/var/rw/nagios.cmd

fi

# If today is a test downtime, mark the test downtime boxes for downtime
if [[ " ${TESTDATES[*]} " == *" $DATE "* ]]; then

    # Set a downtime start to be ~12pm today, end at 5pm
    starttime=`date +%s -d "+675 minutes"`
    endtime=`date +%s -d "+975 minutes"`
    # Send the downtime request
    echo "[$datetime] SCHEDULE_HOSTGROUP_HOST_DOWNTIME;Downtime-Test;$starttime;$endtime;1;0;7200;bpeters-AD;Test Downtime" > /usr/local/nagios/var/rw/nagios.cmd
    echo "[$datetime] SCHEDULE_HOSTGROUP_HOST_DOWNTIME;Downtime-Linux-Test;$starttime;$endtime;1;0;7200;bpeters-AD;Test Downtime" > /usr/local/nagios/var/rw/nagios.cmd

fi

# If today is a prod downtime, mark the prod downtime boxes for downtime
if [[ " ${PRODATES[*]} " == *" $DATE "* ]]; then

    # Set a downtime start to be ~5:45pm today, end at 11pm
    starttime=`date +%s -d "+1035 minutes"`
    endtime=`date +%s -d "+1350 minutes"`
    # Send the downtime request
    echo "[$datetime] SCHEDULE_HOSTGROUP_HOST_DOWNTIME;Downtime-Prod;$starttime;$endtime;1;0;7200;bpeters-AD;Production Downtime" > /usr/local/nagios/var/rw/nagios.cmd
    echo "[$datetime] SCHEDULE_HOSTGROUP_HOST_DOWNTIME;Downtime-Linux-Prod;$starttime;$endtime;1;0;7200;bpeters-AD;Production Downtime" > /usr/local/nagios/var/rw/nagios.cmd

fi