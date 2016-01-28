#!/usr/bin/env bash
datetime=`date +%s`
CMD='SCHEDULE_HOSTGROUP_HOST_DOWNTIME;Auto-Patch-And-Reboot;2016-01-28 14:28:00;2016-01-28 14:30:00;1;0;bpeters;Test Downtime Function'
CommandFile="/usr/local/nagios/var/rw/nagios.cmd"
`$echocmd $cmdline >> $CommandFile`