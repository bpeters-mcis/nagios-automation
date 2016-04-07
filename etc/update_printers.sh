#!/usr/bin/env bash

# Check to see if there is a new printer upload
if [ -f /home/akirkland1/printers.csv ]; then

    # Copy the old printer config file, just in case this generation breaks it
    cp /usr/local/nagios/etc/objects/lab_printers.cfg /home/akirkland1/lab_printers_$(date +%F).cfg

    # Do disk clean up, remove files and directories older than 30 days
    find /home/akirkland1 -type f -mtime +30 -exec rm {} \;

    # Run the PHP script to generate a new config file for lab printers
    php /usr/local/nagios/etc/lab_printer_generation.php

    # Delete the uploaded CSV
    mv /home/akirkland1/printers.csv /home/akirkland1/printers._$(date +%F).csv

    # Note when this was last run
    touch /usr/local/nagios/etc/Printers_last_imported.txt
    touch /home/akirkland1/Printers_last_imported.txt

    # Replace the config file
    mv /home/akirkland1/lab_printers.cfg /usr/local/nagios/etc/objects/lab_printers.cfg
    chown nagios:nagios /usr/local/nagios/etc/objects/lab_printers.cfg

    # Restart nagios service to load the new configs
    service nagios restart

fi