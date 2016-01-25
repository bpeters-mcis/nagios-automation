<?php
/**
 * Created by PhpStorm.
 * User: bpeters
 * Date: 1/21/2016
 * Time: 3:30 PM
 */

# Define the group names we'll be using to create config files
$Groups = array('doit-sit-team', 'doit-dba-team', 'doit-pss-team', 'doit_lab_attendants', 'doit_helpdesk_ft');

$ServersToIgnore = array('ADAUTHDC2', 'INTLTESTDB', 'INTLDB');

# Here is our list of custom services to monitor.
$ServicesToMonitor = array('D' => array('use' => 'generic-service',
                                        'service_description' => 'D:\ Drive Space',
                                        'check_command' => 'check_nt!USEDDISKSPACE!-l d -w 80 -c 90',
                                        'host_name' => ''),
                            'E' => array('use' => 'generic-service',
                                        'service_description' => 'E:\ Drive Space',
                                        'check_command' => 'check_nt!USEDDISKSPACE!-l e -w 80 -c 90',
                                        'host_name' => ''),
                            'F' => array('use' => 'generic-service',
                                        'service_description' => 'F:\ Drive Space',
                                        'check_command' => 'check_nt!USEDDISKSPACE!-l F -w 80 -c 90',
                                        'host_name' => ''),
                            'RLoad' => array('use' => 'generic-service',
                                        'service_description' => 'Remote Loader for IDM',
                                        'check_command' => 'check_nt!PROCSTATE!-d SHOWALL -l dirxml_remote.exe',
                                        'host_name' => ''),
                            'PrintSpool' => array('use' => 'generic-service',
                                        'service_description' => 'Print Spooler',
                                        'check_command' => 'check_nt!SERVICESTATE!-d SHOWALL -l Spooler',
                                        'host_name' => ''),
                            'MDT' => array('use' => 'generic-service',
                                        'service_description' => 'MDT Monitor',
                                        'check_command' => 'check_nt!SERVICESTATE!-d SHOWALL -l MDT_Monitor',
                                        'host_name' => ''),
                            'PXE' => array('use' => 'generic-service',
                                        'service_description' => 'PXE Boot Service',
                                        'check_command' => 'check_nt!SERVICESTATE!-d SHOWALL -l WDSServer',
                                        'host_name' => ''),
                            'ADRepl' => array('use' => 'generic-service',
                                        'service_description' => 'AD Replication',
                                        'check_command' => 'ADReplication_Check',
                                        'host_name' => '')
                            );

$restrictedUsers = '';
$UsersToOverrideLabRestrictions = array('bpeters', 'akirkland1');

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

    function getGroupusers($group) {
        $filter = "(&(objectClass=user)(memberOf=$group))";
        $justthese = array("samaccountname");
        $results = ldap_search($this->AD, LDAP::$ldap_basedn, $filter, $justthese);
        $users = ldap_get_entries($this->AD, $results);
        return $users;
    }

    function getGroupMemberGroups($group) {
        $filter = "(&(objectClass=group)(memberOf=$group))";
        $justthese = array("samaccountname");
        $results = ldap_search($this->AD, LDAP::$ldap_basedn, $filter, $justthese);
        $groups = ldap_get_entries($this->AD, $results);
        return $groups;
    }


}

class LansweeperDB
{
    protected $db;

    public static $LansweeperHost = 'lansweeper';
    public static $LansweeperUser = 'AD\bpeters';
    public static $LansweeperPassword = 'unit7oscodaisfun';

    function __construct()
    {
        $this->db = mssql_connect(LansweeperDB::$LansweeperHost, LansweeperDB::$LansweeperUser, LansweeperDB::$LansweeperPassword);
        mssql_select_db('lansweeperdb', $this->db);
    }

    function getServersWithNagios() {
        $sql = "Select Top 1000000 tblAssets.AssetID,
                  tblAssets.AssetName,
                  tblAssets.Description,
                  tsysOS.Image As icon,
                  tblAssetCustom.Custom1 As [Primary OS Contact],
                  tblAssetCustom.Custom2 As [Secondary OS Contact],
                  tblAssetCustom.Custom3 As [Primary App Contact],
                  tblAssetCustom.Custom4 As [Secondary App Contact],
                  tblAssetCustom.Custom19 AS [NagiosServices],
                  tblAssets.IPAddress
                From tblAssets
                  Inner Join tblAssetCustom On tblAssets.AssetID = tblAssetCustom.AssetID
                  Inner Join tsysOS On tblAssets.OScode = tsysOS.OScode
                  Inner Join tblComputersystem On tblAssets.AssetID = tblComputersystem.AssetID
                Where tblAssets.AssetID In (Select tblSoftware.AssetID
                  From tblSoftware Inner Join tblSoftwareUni On tblSoftwareUni.SoftID =
            tblSoftware.softID
                  Where dbo.tblsoftwareuni.softwareName Like '%NSClient%') And
                  tsysOS.OSname Like '%Win 2%' And tblAssetCustom.State = 1";
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
                  tsysOS.OSname Like '%Win 2%' And tblAssetCustom.State = 1";
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
                  tsysOS.OSname Like '%Win 2%' And tblAssetCustom.State = 1";
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


################################################################
# Build the contact list
################################################################

# Set an array of users in all the groups, so we can use it later to build individual contacts
$userarray = array();

# Start building the team definition output
$output =  '###########################################' . PHP_EOL;
$output .=  '# !!!! WARNING !!!!' . PHP_EOL;
$output .=  '###########################################' . PHP_EOL;
$output .= PHP_EOL;
$output .= '# This file was automatically generated by group_contact_generation.php.  Do not edit it manually!!!' . PHP_EOL;
$output .= '# If you need to change this, please edit that PHP file instead.' . PHP_EOL;
$output .= PHP_EOL;
$output .= PHP_EOL;
$output .= PHP_EOL;
$output .= PHP_EOL;
$output .= PHP_EOL;
$output .= '###########################################' . PHP_EOL;
$output .= '# Team Contact Definitions' . PHP_EOL;
$output .= '###########################################' . PHP_EOL;

foreach ($Groups as $group) {

    # Get members of the group
    $LDAP = new LDAP();
    $userlist = '';

    # Get any sub groups within this main group
    $subgroups = $LDAP->getGroupMemberGroups('CN=' . $group . ',CN=users,DC=ad,DC=emich,DC=edu');

    # Go through each sub group and pull out the members
    $groupnum = 0;
    while ($groupnum < $subgroups['count']) {

        # Run through all the group members, and build a comma separated list, and add the user to an array for use later
        $users = $LDAP->getGroupUsers($subgroups[$groupnum]['dn']);

        $usernum = 0;
        while ($usernum < $users['count']) {
            $userlist .= $users[$usernum]['samaccountname'][0] . ",";
            if ($group == 'doit_lab_attendants') {
                $email = $users[$usernum]['samaccountname'][0] . '@winmon.emich.edu';
                if (!in_array($users[$usernum]['samaccountname'][0], $UsersToOverrideLabRestrictions)) {
                    $restrictedUsers .= $users[$usernum]['samaccountname'][0] . ',';
                }
            } else {
                $email = $users[$usernum]['samaccountname'][0] . "@emich.edu";
            }
            $userarray[$email] = $users[$usernum]['samaccountname'][0];
            $usernum++;
        }

        $groupnum++;
    }


    # Run through all the group members, and build a comma separated list, and add the user to an array for use later
    $users = $LDAP->getGroupUsers('CN=' . $group . ',CN=users,DC=ad,DC=emich,DC=edu');

    $i = 0;
    while ($i < $users['count']) {
        $userlist .= $users[$i]['samaccountname'][0] . ",";
        if ($group == 'doit_lab_attendants') {
            $email = $users[$i]['samaccountname'][0] . '@winmon.emich.edu';
            if (!in_array($users[$i]['samaccountname'][0], $UsersToOverrideLabRestrictions)) {
                $restrictedUsers .= $users[$i]['samaccountname'][0] . ',';
            }
        } else {
            $email = $users[$i]['samaccountname'][0] . "@emich.edu";
        }
        $userarray[$email] = $users[$i]['samaccountname'][0];
        $i++;
    }

    # If this is the lab group, we add Aric so he is included, and make sure his email is properly populated so he actually gets emails
    if ($group == 'doit_lab_attendants') {
        $userarray['akirkland1@emich.edu'] = 'akirkland1';
        $userlist .= 'akirkland1,';
    }

    # Add Ben for testing
    if ($group == 'doit_lab_attendants') {
        $userlist .= 'bpeters,';
    }

    # Trim trailing comma from user list
    $userlist = rtrim($userlist, ",");

    # Generate the contact group definition
    $output .= PHP_EOL;
    $output .= 'define contactgroup{' . PHP_EOL;
    $output .= '        contactgroup_name       ' . $group . PHP_EOL;
    $output .= '        alias                   ' . $group . PHP_EOL;
    $output .= '        members                 ' . $userlist . PHP_EOL;
    $output .= '        }' . PHP_EOL;
    $output .= PHP_EOL;

}

# Trim the trailing comma off restricted user list
$restrictedUsers = rtrim($restrictedUsers, ",");
$cgiCFGOutput = PHP_EOL;
$cgiCFGOutput .= '###########################################' . PHP_EOL;
$cgiCFGOutput .= '# Set Restricted CGI Access for these users' . PHP_EOL;
$cgiCFGOutput .= '###########################################' . PHP_EOL;
$cgiCFGOutput .= PHP_EOL;
$cgiCFGOutput .= 'authorized_for_read_only=' . $restrictedUsers . PHP_EOL;
$cgiCFGOutput .= PHP_EOL;

$lines = file('/usr/local/nagios/etc/cgi.cfg');
$last = sizeof($lines) - 7 ;
unset($lines[$last]);
$lines = $lines . $cgiCFGOutput;

// write the new data to the file
$file = fopen('/usr/local/nagios/etc/cgi.cfg', 'w');
fwrite($file, implode('', $lines));
fclose($file);


# Start building the individual contacts output
$output .= PHP_EOL;
$output .= '###########################################' . PHP_EOL;
$output .= '# User Contact Definitions' . PHP_EOL;
$output .= '###########################################' . PHP_EOL;
$output .= PHP_EOL;

# Now go through the list of all the individual users, and remove any duplicates
$userarray = array_unique($userarray);

# Build a contact file for each user in the list - but omit e-mail addresses from lab students
foreach ($userarray as $key => $value) {
    $output .= 'define contact{' . PHP_EOL;
    $output .= '        contact_name            ' . $value . PHP_EOL;
    $output .= '        use                     generic-contact' . PHP_EOL;
    $output .= '        alias                   ' . $value . '-AD' . PHP_EOL;
    $output .= '        email                   ' . $key . PHP_EOL;
    $output .= '}' . PHP_EOL;
    $output .= PHP_EOL;
}

file_put_contents('/usr/local/nagios/etc/objects/contacts_from_ad.cfg', $output);

################################################################
# Build the server list
################################################################

# Run a SQL search against lansweeper to find all active windows servers with Nagios installed
$Servers = new LansweeperDB();
$list = $Servers->getServersWithNagios();

# Get a list of all domain controllers
$DCs = $Servers->getDomainControllers();

# Get a list of all imaging servers
$Imaging = $Servers->getImagingServers();

# Start building the server output
$output =  '###########################################' . PHP_EOL;
$output .= '# Windows Server Definitions' . PHP_EOL;
$output .= '###########################################' . PHP_EOL;
$output .= PHP_EOL;

foreach ($list as $server) {

    # Make sure we aren't supposed to ignore this server for some reason
    if (!in_array($server['AssetName'], $ServersToIgnore)) {

        # Check who the contact people are, and build our host group list accordingly. Add it to basic windows servers by default.
        $contactgroups = 'WindowsTeam';
        if ($server['Primary OS Contact'] == 'Team - SIT' || $server['Secondary OS Contact'] == 'Team - SIT' || $server['Primary App Contact'] == 'Team - SIT' || $server['Secondary App Contact'] == 'Team - SIT') {
            $contactgroups .= ',doit-sit-team';
        }
        if ($server['Primary OS Contact'] == 'Team - DBA' || $server['Secondary OS Contact'] == 'Team - DBA' || $server['Primary App Contact'] == 'Team - DBA' || $server['Secondary App Contact'] == 'Team - DBA') {
            $contactgroups .= ',doit-dba-team';
        }
        if ($server['Primary OS Contact'] == 'Team - PSS' || $server['Secondary OS Contact'] == 'Team - PSS' || $server['Primary App Contact'] == 'Team - PSS' || $server['Secondary App Contact'] == 'Team - PSS') {
            $contactgroups .= ',doit-pss-team';
        }
        if ($server['Primary OS Contact'] == 'Team - HelpDesk' || $server['Secondary OS Contact'] == 'Team - HelpDesk' || $server['Primary App Contact'] == 'Team - HelpDesk' || $server['Secondary App Contact'] == 'Team - HelpDesk') {
            $contactgroups .= ',doit_helpdesk_ft';
        }

        # See what special services should be monitored on this server
        $services = $server['NagiosServices'];
        $services = explode(',', $services);

        # See which customs services this server should monitor
        foreach ($services as $service) {

            # Make sure this is a known service defined above.  Only add it to the list only if it's a legitimate service name
            if (isset($ServicesToMonitor[$service])) {
                $ServicesToMonitor[$service]['host_name'] .= $server['AssetName'] . ',';
            }

        }

        # Build the host groups for this server, and append extra host groups based on lansweeper query results
        $HostGroups = "windows-servers";

        if (in_array($server['AssetName'], $DCs)) {
            $HostGroups .= ",windows-servers-dcs";
        }

        if (in_array($server['AssetName'], $Imaging)) {
            $HostGroups .= ",windows-servers-imaging";
        }

        # Build the individual host output
        $output .= 'define host{' . PHP_EOL;
        $output .= '    use             windows-server' . PHP_EOL;
        $output .= '    host_name       ' . $server['AssetName'] . PHP_EOL;
        $output .= '    alias           ' . $server['AssetName'] . PHP_EOL;
        $output .= '    address         ' . $server['IPAddress'] . PHP_EOL;
        $output .= '    hostgroups      ' . $HostGroups . PHP_EOL;
        $output .= '    contact_groups  ' . $contactgroups . PHP_EOL;
        $output .= '}' . PHP_EOL;
        $output .= PHP_EOL;
    }


}

################################################################
# Build the service list
################################################################

$output .= '###########################################' . PHP_EOL;
$output .= '# Windows Service Definitions' . PHP_EOL;
$output .= '###########################################' . PHP_EOL;
$output .= PHP_EOL;

# Go through each custom service defined at the top
foreach ($ServicesToMonitor as $row) {

    # Make sure there is at least one host that uses this.  If none, don't bother including it.
    if ($row['host_name'] != '') {

        # Strip trailing comma from the host list
        $row['host_name'] = rtrim($row['host_name'], ',');

        # Build output config for this service
        $output .= 'define service{' . PHP_EOL;
        foreach ($row as $key => $value) {
            $output .= '    ' . $key . '        ' . $value . PHP_EOL;
        }
        $output .= '}' . PHP_EOL;
        $output .= PHP_EOL;
    }
}

# Send the list of servers to the nagios config file
file_put_contents('/usr/local/nagios/etc/objects/servers_from_lansweeper.cfg', $output);

?>