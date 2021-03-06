#!/usr/bin/env bash

# Check to see if there is a new printer upload
if [ -f /********/printers.csv ]; then

    # Copy the old printer config file, just in case this generation breaks it
    cp /usr/local/nagios/etc/objects/lab_printers.cfg /********/lab_printers_$(date +%F).cfg

    # Do disk clean up, remove files and directories older than 30 days
    find /home/akirkland1 -type f -mtime +30 -exec rm {} \;

    # Run the PHP script to generate a new config file for lab printers
    /usr/local/bin/php /usr/local/nagios/etc/CustomScripts/lab_printer_generation.php

    # Delete the uploaded CSV
    mv /********/printers.csv /********/printers_$(date +%F).csv

    # Note when this was last run
    touch /usr/local/nagios/etc/Printers_last_imported.txt
    touch /********/Printers_last_imported.txt

    # Replace the config file
    mv /********/lab_printers.cfg /usr/local/nagios/etc/objects/lab_printers.cfg
    chown nagios:nagios /usr/local/nagios/etc/objects/lab_printers.cfg

    # Restart nagios service to load the new configs
    service nagios restart

fi