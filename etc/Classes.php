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

class LDAP {

    # Enter your LDAP connection details here
    public static $ldap_host = 'ad.emich.edu';
    public static $ldap_port = '389';
    public static $ldap_basedn = 'CN=users,DC=ad,DC=emich,DC=edu';
    public static $ldap_user = 'ext_windows_nagios';
    public static $ldap_pass =  '#hot713outside';

    protected $AD;

    function __construct() {
        $this->AD = @ldap_connect(LDAP::$ldap_host, LDAP::$ldap_port) or die( "LDAP Service is not available at this time");
        ldap_set_option($this->AD, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->AD, LDAP_OPT_REFERRALS, 0);
        $ldapbind = @ldap_bind($this->AD, LDAP::$ldap_user . "@" . LDAP::$ldap_host, LDAP::$ldap_pass);
        if(!$ldapbind){ die("Bind failed"); }
    }

    # This function should hand back an array containing all the users inside the provided group.
    function getGroupusers($group) {
        $filter = "(&(objectClass=user)(memberOf=$group))";
        $justthese = array("samaccountname");
        $results = ldap_search($this->AD, LDAP::$ldap_basedn, $filter, $justthese);
        ldap_sort($this->AD, $results, 'samaccountname');
        $users = ldap_get_entries($this->AD, $results);
        return $users;
    }

    # This function should hand back an array containing all the GROUPS inside the provided group.  This is important for nested AD groups.
    function getGroupMemberGroups($group) {
        $filter = "(&(objectClass=group)(memberOf=$group))";
        $justthese = array("samaccountname");
        $results = ldap_search($this->AD, LDAP::$ldap_basedn, $filter, $justthese);
        ldap_sort($this->AD, $results, 'samaccountname');
        $groups = ldap_get_entries($this->AD, $results);
        return $groups;
    }


}

class LansweeperDB
{
    protected $db;

    public static $LansweeperHost = 'lansweeper';
    public static $LansweeperUser = 'lansweeperuser';
    public static $LansweeperPassword = '#hot713outside';

    function __construct()
    {
        $this->db = mssql_connect(LansweeperDB::$LansweeperHost, LansweeperDB::$LansweeperUser, LansweeperDB::$LansweeperPassword);
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

class InventoryDB
{

    public static $inventory_username = 'nagios';
    public static $inventory_password = '#hot713outside';
    public static $inventory_dsn = 'mysql:dbname=inventory;host=itservices.emich.edu';

    protected $db;

    function __construct()
    {
        try {
            $this->db = new PDO(InventoryDB::$inventory_dsn, InventoryDB::$inventory_username, InventoryDB::$inventory_password);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
        }
    }

    # Get all the monitors, and all the details
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