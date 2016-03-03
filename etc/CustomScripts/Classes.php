<?php
/**
 * Created by PhpStorm.
 * User: bpeters
 * Date: 2/10/2016
 * Time: 4:13 PM
 */



######################################################################################
# Class
######################################################################################

class Config {

    #############################################################
    # Nagios Installation Settings
    #############################################################

    # Where is nagios installed?
    public static $NagiosPath = '/usr/local/nagios/';

    # If the service fails to restart, who should be notified?
    public static $RestartWarnlist = 'bpeters@emich.edu';

    #############################################################
    # Active Directory / LDAP Connection Credentials / Settings
    #############################################################

    # This is the AD / LDAP where all the computers reside
    public static $ldap_host = 'ad.emich.edu';
    public static $ldap_port = '389';
    public static $ldap_basedn = 'CN=users,DC=ad,DC=emich,DC=edu';
    public static $ldap_user = 'ext_windows_nagios';
    public static $ldap_pass =  '#hot713outside';

    ##############################################################
    # Lansweeper Credentials
    ##############################################################

    # This is the connection ifo into Lansweeper, or whatever other SQL database you use to store server inventory information
    public static $LansweeperHost = 'lansweeper';
    public static $LansweeperUser = 'lansweeperuser';
    public static $LansweeperPassword = '#hot713outside';

    ##############################################################
    # Monitor Database Credentials
    ##############################################################

    # This is the database that contains the custom Nagios monitors, if used.
    public static $inventory_username = 'nagios';
    public static $inventory_password = '#hot713outside';
    public static $inventory_dsn = 'mysql:dbname=inventory;host=itservices.emich.edu';

    ##############################################################
    # Contact List Generation Settings
    ##############################################################

    # What is your email domain?
    public static $EmailDomain = 'emich.edu';

    # Set an array of users in all the groups, so we can use it later to build individual contacts.  Add the people here who MUST show up, at a minimum.  All other users
    # will be added by polling the various LDAP / AD groups.
    public static $userarray = array('bpeters@emich.edu' => 'bpeters',
        'pdaughert2@emich.edu' => 'pdaughert2',
        'malghait@emich.edu' => 'malghait');

    # This array will contain all the students we find.  Users in this array will NOT get email notifications.  If you wish to add any users to see the printers,
    # but do not want them to get e-mail, go ahead and add them here.  Use the same format as the userarray above.
    public static $studentarray = array();

    # Define the group for lab attendants.  These people will only see printers, and will only have read access.
    public static $LabUserGroup = 'doit_lab_attendants';

    # Define the group names we'll be using to create config files.  Key should be the name used in inventory system, value should be the matching AD/LDAP group name.
    public static $Groups = array('Team - SIT' => 'doit-sit-team',
        'Team - DBA' => 'doit-dba-team',
        'Team - PSS' => 'doit-pss-team',
        'Lab Attendants' => 'doit_lab_attendants',
        'Team - HelpDesk' => 'doit_helpdesk_ft',
        'Team - VMWare' => 'doit-vmware-team',
        'Team - Security' => 'ib_security_team');


    # Lab users get read-only access and do not get emails.  If you wish any of them to get access, add them here.
    public static $UsersToOverrideLabRestrictions = array('bpeters', 'akirkland1');

    ##############################################################
    # Permissions Settings
    ##############################################################

    # Users in this string will have read-only access to any of the CGI tools within nagios.
    # All IT Lab and Help Desk students / staff are added by default later, but you may add others here if you wish.
    public static $restrictedUsers = '';

    # Define the AD / LDAP group that has the admin users; all these people will get full rights to the CGIs
    public static $AdminGroup = 'CN=doit_app_nagios_admin,CN=users,DC=ad,DC=emich,DC=edu';


    ##############################################################
    # Server / Service List Generation Settings
    ##############################################################

    # These servers will be completely ignored, and will never be included in monitoring.  This is useful if there's a system with someone else's nagios or something,
    # that we don't want conflicting with ours I guess.
    public static $ServersToIgnore = array('INTLTESTDB', 'INTLDB');

    # Comma separated list of any contact group(s) that should be added to ALL servers
    public static $ContactGroupForAllServers = 'WindowsTeam';

    ##############################################################
    # Printer Definition Generation Settings
    ##############################################################

    # Where should the script look for your input CSV file?
    public static $PrinterCSV = '/home/akirkland1/printers.csv';

    # Who should get emailed when a new printer config file is created?
    public static $NotifyNewPrinters = 'akirkland1@emich.edu, bpeters@emich.edu';
}


class LDAP {

    protected $AD;

    function __construct() {
        $this->AD = @ldap_connect(Config::$ldap_host, Config::$ldap_port) or die( "LDAP Service is not available at this time");
        ldap_set_option($this->AD, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->AD, LDAP_OPT_REFERRALS, 0);
        $ldapbind = @ldap_bind($this->AD, Config::$ldap_user . "@" . Config::$ldap_host, Config::$ldap_pass);
        if(!$ldapbind){ die("Bind failed"); }
    }

    # This function should hand back an array containing all the users inside the provided group.
    function getGroupusers($group) {
        $filter = "(&(objectClass=user)(memberOf=$group))";
        $justthese = array("samaccountname");
        $results = ldap_search($this->AD, Config::$ldap_basedn, $filter, $justthese);
        ldap_sort($this->AD, $results, 'samaccountname');
        $users = ldap_get_entries($this->AD, $results);
        return $users;
    }

    # This function should hand back an array containing all the GROUPS inside the provided group.  This is important for nested AD groups.
    function getGroupMemberGroups($group) {
        $filter = "(&(objectClass=group)(memberOf=$group))";
        $justthese = array("samaccountname");
        $results = ldap_search($this->AD, Config::$ldap_basedn, $filter, $justthese);
        ldap_sort($this->AD, $results, 'samaccountname');
        $groups = ldap_get_entries($this->AD, $results);
        return $groups;
    }


}

class LansweeperDB
{
    protected $db;

    function __construct()
    {
        $this->db = mssql_connect(Config::$LansweeperHost, Config::$LansweeperUser, Config::$LansweeperPassword);
        mssql_select_db('lansweeperdb', $this->db);
    }

    # This function should hand back all the servers we want to monitor in Nagios.  We can hand back as much data as we want, but the essentials are:
    # Name, Make (to know if it's a VM or not), Contacts, What custom services to monitor, what the downtime window is, and the server's IP address.
    function getServersWithNagios() {
        $sql = "Select Top 1000000 tblAssets.AssetID,
                  tblAssets.AssetName,
                  tblAssets.Description,
                  tsysOS.Image As icon,
                  tblAssetCustom.Manufacturer As [Make],
                  tblAssetCustom.Custom1 As [Primary OS Contact],
                  tblAssetCustom.Custom2 As [Secondary OS Contact],
                  tblAssetCustom.Custom3 As [Primary App Contact],
                  tblAssetCustom.Custom4 As [Secondary App Contact],
                  tblAssetCustom.Custom19 AS [NagiosServices],
                  tblAssetCustom.Custom6 As [Window],
                  tblAssets.IPAddress
                From tblAssets
                  Inner Join tblAssetCustom On tblAssets.AssetID = tblAssetCustom.AssetID
                  Inner Join tsysOS On tblAssets.OScode = tsysOS.OScode
                  Inner Join tblComputersystem On tblAssets.AssetID = tblComputersystem.AssetID
                Where tblAssets.AssetID In (Select tblSoftware.AssetID
                  From tblSoftware Inner Join tblSoftwareUni On tblSoftwareUni.SoftID =
            tblSoftware.softID
                  Where dbo.tblsoftwareuni.softwareName Like '%NSClient%') And
                  tsysOS.OSname Like '%Win 2%' And tblAssetCustom.State = 1 Order By tblAssets.AssetName";
        $result = array();
        $query=mssql_query($sql);
        if (mssql_num_rows($query)) {
            while ($row = mssql_fetch_assoc($query)) {
                $result[] = $row;
            }
        }
        return $result;
    }

    # This function polls Lansweeper, and finds any servers that are domain controllers.  Returns an indexed array.
    # This is used because we have some services we want to automatically monitor on all domain controllers.
    function getDomainControllers() {
        $sql = "Select Top 1000000 tblAssets.AssetID,
                  tblAssets.AssetName,
                  tblAssets.Domain,
                  tsysOS.OSname,
                  tblAssets.Description,
                  tblComputersystem.Lastchanged,
                  tsysOS.Image As icon
                From tblComputersystem
                  Inner Join tblAssets On tblComputersystem.AssetID = tblAssets.AssetID
                  Inner Join tblAssetCustom On tblAssets.AssetID = tblAssetCustom.AssetID
                  Inner Join tsysOS On tblAssets.OScode = tsysOS.OScode
                Where tblComputersystem.Domainrole = 4 Or tblComputersystem.Domainrole = 5
                Order By tblAssets.AssetName";
        $result = array();
        $query=mssql_query($sql);
        if (mssql_num_rows($query)) {
            while ($row = mssql_fetch_assoc($query)) {
                $result[] = $row;
            }
        }
        $list = array();
        foreach ($result as $item) {
            array_push($list, $item['AssetName']);
        }
        return $list;
    }

    # This function hands back all servers running MDT toolkit.  This let's us specifiy services to monitor on all imaging servers.
    function getImagingServers() {
        $sql = "Select Top 1000000 tblAssets.AssetID,
                  tblAssets.AssetName
                From tblAssets
                  Inner Join tblAssetCustom On tblAssets.AssetID = tblAssetCustom.AssetID
                  Inner Join tsysOS On tblAssets.OScode = tsysOS.OScode
                  Inner Join tblComputersystem On tblAssets.AssetID = tblComputersystem.AssetID
                Where tblAssets.AssetID In (Select tblSoftware.AssetID
                  From tblSoftware Inner Join tblSoftwareUni On tblSoftwareUni.SoftID =
                      tblSoftware.softID
                  Where dbo.tblsoftwareuni.softwareName Like '%Deployment Toolkit%') And
                  tsysOS.OSname Like '%Win 2%' And tblAssetCustom.State = 1 Order By tblAssets.AssetName";
        $result = array();
        $query=mssql_query($sql);
        if (mssql_num_rows($query)) {
            while ($row = mssql_fetch_assoc($query)) {
                $result[] = $row;
            }
        }
        $list = array();
        foreach ($result as $item) {
            array_push($list, $item['AssetName']);
        }
        return $list;
    }

    # This function should hand back all servers running SQL server.
    function getMSSQLServers() {
        $sql = "Select Top 1000000 tblAssets.AssetID,
                  tblAssets.AssetName
                From tblAssets
                  Inner Join tblAssetCustom On tblAssets.AssetID = tblAssetCustom.AssetID
                  Inner Join tsysOS On tblAssets.OScode = tsysOS.OScode
                  Inner Join tblComputersystem On tblAssets.AssetID = tblComputersystem.AssetID
                Where tblAssets.AssetID In (Select tblSoftware.AssetID
                  From tblSoftware Inner Join tblSoftwareUni On tblSoftwareUni.SoftID =
                      tblSoftware.softID
                  Where dbo.tblsoftwareuni.softwareName Like '%Microsoft SQL Server%') And
                  tsysOS.OSname Like '%Win 2%' And tblAssetCustom.State = 1 Order By tblAssets.AssetName";
        $result = array();
        $query=mssql_query($sql);
        if (mssql_num_rows($query)) {
            while ($row = mssql_fetch_assoc($query)) {
                $result[] = $row;
            }
        }
        $list = array();
        foreach ($result as $item) {
            array_push($list, $item['AssetName']);
        }
        return $list;
    }

}

class MonitorDB
{

    protected $db;

    function __construct()
    {
        try {
            $this->db = new PDO(Config::$inventory_dsn, Config::$inventory_username, Config::$inventory_password);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
        }
    }

    # Get all the custom monitors, and all the details
    function BuildMonitorsForNagios()
    {
        $ServiceList = array();

        $sql = 'SELECT id, Code, Description from services';
        $sth = $this->db->prepare($sql);
        $sth->execute(array());
        $list = $sth->fetchAll();

        foreach ($list as $row) {

            # Insert this into our return
            $ServiceList[$row['Code']] = array();

            # Get all the details for this service
            $sql = 'SELECT * from service_details WHERE Code = ?';
            $sth = $this->db->prepare($sql);
            $sth->execute(array($row['Code']));
            $details = $sth->fetchAll();

            foreach ($details as $item) {
                $ServiceList[$row['Code']][$item['name']] = $item['entry'];
            }

        }

        return $ServiceList;
    }

    # This function should return text about how to fix a problem, based on the service description of the service that threw the error.
    function GetResolutions($serviceDescription) {

        # Look up this service, based on the description
        $sql = "SELECT CODE from service_details WHERE name = 'service_description' AND entry = ?";
        $sth = $this->db->prepare($sql);
        $sth->execute(array($serviceDescription));
        $code = $sth->fetchAll();

        # Get the resolution for this, if it exists
        $sql = "SELECT Resolution from services WHERE Code = ?";
        $sth = $this->db->prepare($sql);
        $sth->execute(array($code[0]['CODE']));
        $resolution = $sth->fetchAll();

        return $resolution[0]['Resolution'];

    }
}