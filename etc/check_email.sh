#!/usr/bin/env bash

# Run the email check
/usr/local/bin/php /usr/local/nagios/etc/CustomScripts/server_and_contact_generation.php
touch /usr/local/nagios/etc/email_ran.txt