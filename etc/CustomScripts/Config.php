<?php
/**
 * Created by PhpStorm.
 * User: bpeters
 * Date: 2/19/2016
 * Time: 1:26 PM
 */

class Config {

    #############################################################
    # Nagios Installation Settings
    #############################################################

    # Where is nagios installed?
    public static $NagiosPath = '/usr/local/nagios/';

    # If the service fails to restart, who should be notified?
    public static $RestartWarnlist = '*****@*****';

    #############################################################
    # Email Service Account Settings
    #############################################################
    public static $MailUser = '*****@******';
    public static $MailPassword = '*******';
    public static $MailHost = '{imap.gmail.com:993/imap/ssl}INBOX';

    #############################################################
    # Active Directory / LDAP Connection Credentials / Settings
    #############################################################

    # This is the AD / LDAP where all the computers reside
    public static $ldap_host = '********';
    public static $ldap_port = '389';
    public static $ldap_basedn = '********';
    public static $ldap_user = '********';
    public static $ldap_pass =  '********';

    ##############################################################
    # Lansweeper Credentials
    ##############################################################

    # This is the connection ifo into Lansweeper, or whatever other SQL database you use to store server inventory information
    public static $LansweeperHost = 'dblib:host=********:1433;dbname=********';
    public static $LansweeperUser = '********';
    public static $LansweeperPassword = '********';

    ##############################################################
    # Monitor Database Credentials
    ##############################################################

    # This is the database that contains the custom Nagios monitors, if used.
    public static $inventory_username = '********';
    public static $inventory_password = '********';
    public static $inventory_dsn = 'mysql:dbname=********;host=********';

    ##############################################################
    # Contact List Generation Settings
    ##############################################################

    # What is your email domain?
    public static $EmailDomain = '********';

    # Set an array of users in all the groups, so we can use it later to build individual contacts.  Add the people here who MUST show up, at a minimum.  All other users
    # will be added by polling the various LDAP / AD groups.
    public static $userarray = array('********' => 'user1',
                                    '********' => 'user2',
                                    '********' => 'user3');

    # This array will contain all the students we find.  Users in this array will NOT get email notifications.  If you wish to add any users to see the printers,
    # but do not want them to get e-mail, go ahead and add them here.  Use the same format as the userarray above.
    public static $studentarray = array();

    # Define the group for lab attendants.  These people will only see printers, and will only have read access.
    public static $LabUserGroup = '********';

    # Define the group names we'll be using to create config files.  Key should be the name used in inventory system, value should be the matching AD/LDAP group name.
    public static $Groups = array('Team - SIT' => 'doit-sit-team',
                                    'Team - DBA' => 'doit-dba-team',
                                    'Team - PSS' => 'doit-pss-team',
                                    'Lab Attendants' => 'doit_lab_attendants',
                                    'Team - HelpDesk' => 'doit_helpdesk_ft',
                                    'Team - VMWare' => 'doit-vmware-team',
                                    'Team - Security' => 'ib_security_team');


    # Lab users get read-only access and do not get emails.  If you wish any of them to get access, add them here.
    public static $UsersToOverrideLabRestrictions = array('********', '********');

    ##############################################################
    # Permissions Settings
    ##############################################################

    # Users in this string will have read-only access to any of the CGI tools within nagios.
    # All IT Lab and Help Desk students / staff are added by default later, but you may add others here if you wish.
    public static $restrictedUsers = '';

    # Define the AD / LDAP group that has the admin users; all these people will get full rights to the CGIs
    public static $AdminGroup = 'CN=********,CN=users,DC=********,DC=********,DC=********';


    ##############################################################
    # Server / Service List Generation Settings
    ##############################################################

    # These servers will be completely ignored, and will never be included in monitoring.  This is useful if there's a system with someone else's nagios or something,
    # that we don't want conflicting with ours I guess.
    public static $ServersToIgnore = array('********', '********', '********');

    # Comma separated list of any contact group(s) that should be added to ALL servers
    public static $ContactGroupForAllServers = 'WindowsTeam';

    # Comma separated list of any users/contacts that run a server, that would cause us to ignore that server completely.
    # If a server has one of these users listed as the primary OS contact, it will not show up in Nagios.
    public static $ContactsToTriggerServerIgnore = array('********');

    ##############################################################
    # Printer Definition Generation Settings
    ##############################################################

    # Where should the script look for your input CSV file?
    public static $PrinterCSV = '/********/printers.csv';

    # Who should get emailed when a new printer config file is created?
    public static $NotifyNewPrinters = '********@********, ********@********';
}