#####################################
This custom scripting is configured and requires the following:
-Nagios Core v 4.2.4
-Linux environment with PHP (with IMAP, MSSQL, MYSQL)
-Lansweeper network accessible database

This was designed to automate Nagios configurations within our environment.
The bash scripts were set up as cron jobs to run automatically.  They would accomplish the following:

update_servers.sh
------------------
    This script would run a PHP script, to poll the Lansweeper database and Active Directory.  It would regenerate the applicable
    Nagios configuration files, based on the information in Lansweeper and AD.  It would automatically add new servers, remove old ones,
    add the right service monitors, contacts, and host groups.  It would also regenerate the contact file, based on AD group membership and
    the listed contacts for each server in the Lansweeper database.

update_printers.sh
-------------------
    This script would look for a properly formated CSV file, and would regenerate all the printer definitions.  It would poll each printer
    to determine the model, and which consumables were able to be monitored.  It would rebuild the definition files, then restart the Nagios service.

schedule_downtime.sh
-------------------
    This script would run daily, and check the current date.  If it was a scheduled patch day, it would automatically put the appropriate servers
    into downtime mode for the correct window.  It also scheduled specific services and servers that had other routine outages that did not warrant
    Nagios alerts.

check_email.sh
-------------------
    This script would run every few minutes, to check a dedicated email account.  Each Nagios alert would generate a unique code, and would save that code
    into the Lansweeper database, associated with the correct host.  The notification email included a mail-to link, so users could acknowledge an outage simply
    by responding to the email.  This script would look for the responses, parse them, and handle the acknowledgement.

    