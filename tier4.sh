#!/usr/bin/env bash
datetime=`date +%s`
starttime=`date +%s -d "+ $2 minutes"`
endtime=`date +%s -d "+ $5 minutes"`
#echo "['date +%s'] SCHEDULE_HOST_DOWNTIME;LANSWEEPER;$starttime;$endtime;1;0;;bpeters;scheduled downtime\n" > /usr/local/nagios/var/rw/nagios.cmd

echo "['date +%s'] SCHEDULE_HOST_DOWNTIME;LANSWEEPER;$starttime;$endtime;1;0;;bpeters;scheduled downtime" > /usr/local/nagios/var/rw/nagios.cmd
echo "Downtimed the lansweeper host groups for 5 minutes"



1454012244
1454012176