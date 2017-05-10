#!/usr/bin/env bash

###################################################################
# Downtime Settings
###################################################################

# Downtime Date Arrays
TESTDATES=(2017-02-24 2017-03-31 2017-05-12 2017-06-02 2017-07-14 2017-07-28 2017-09-15 2017-10-06 2017-11-10 2017-11-20)
PRODDATES=(2017-03-03 2017-04-07 2017-05-19 2017-06-09 2017-07-21 2017-08-04 2017-09-22 2017-10-13 2017-11-17 2017-12-01)

# Get the day of the week, and the full date
day=$(date +%a)
DATE=`date +%Y-%m-%d`
datetime=`date +%s`

# Schedule downtime for the people.campus replication - we know that happens every night, don't sweat it
starttime=`date +%s -d`
endtime=`date +%s -d "+4 hours"`
echo "[$datetime] SCHEDULE_SVC_DOWNTIME;idm2.emich.edu;People.campus Replication;$starttime;$endtime;1;0;7200;********;People.campus Downtime" > /usr/local/nagios/var/rw/nagios.cmd

# Schedule downtime for the imaging server, that reboots every single night
starttime=`date +%s -d "+21 hours"`
endtime=`date +%s -d "+23 hours"`
echo "[$datetime] SCHEDULE_HOST_DOWNTIME;IMAGES;$starttime;$endtime;1;0;7200;********;Nightly Reboot" > /usr/local/nagios/var/rw/nagios.cmd

# If it's Wednesday, mark reboot servers for downtime
if [ $day == 'Wed' ]; then

    # Set a downtime start to be ~10pm tonight, end at midnight
    starttime=`date +%s -d "+22 hours"`
    endtime=`date +%s -d "+24 hours"`
    # Send the downtime request
    echo "[$datetime] SCHEDULE_HOSTGROUP_HOST_DOWNTIME;Auto-Patch-And-Reboot;$starttime;$endtime;1;0;7200;********;Auto Reboot Downtime" > /usr/local/nagios/var/rw/nagios.cmd

fi

# If today is a test downtime, mark the test downtime boxes for downtime
if [[ " ${TESTDATES[*]} " == *" $DATE "* ]]; then

    # Set a downtime start to be ~12pm today, end at 5pm
    starttime=`date +%s -d "+675 minutes"`
    endtime=`date +%s -d "+975 minutes"`
    # Send the downtime request
    echo "[$datetime] SCHEDULE_HOSTGROUP_HOST_DOWNTIME;Downtime-Test;$starttime;$endtime;1;0;7200;********;Test Downtime" > /usr/local/nagios/var/rw/nagios.cmd
    echo "[$datetime] SCHEDULE_HOSTGROUP_HOST_DOWNTIME;Downtime-Linux-Test;$starttime;$endtime;1;0;7200;********;Test Downtime" > /usr/local/nagios/var/rw/nagios.cmd

fi

# If today is a prod downtime, mark the prod downtime boxes for downtime
if [[ " ${PRODATES[*]} " == *" $DATE "* ]]; then

    # Set a downtime start to be ~5:45pm today, end at 11pm
    starttime=`date +%s -d "+1035 minutes"`
    endtime=`date +%s -d "+1350 minutes"`
    # Send the downtime request
    echo "[$datetime] SCHEDULE_HOSTGROUP_HOST_DOWNTIME;Downtime-Prod;$starttime;$endtime;1;0;7200;********;Production Downtime" > /usr/local/nagios/var/rw/nagios.cmd
    echo "[$datetime] SCHEDULE_HOSTGROUP_HOST_DOWNTIME;Downtime-Linux-Prod;$starttime;$endtime;1;0;7200;********;Production Downtime" > /usr/local/nagios/var/rw/nagios.cmd

fi