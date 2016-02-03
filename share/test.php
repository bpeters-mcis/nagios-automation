<?php
// Allow specifying main window URL for permalinks, etc.

# Added to give lab attendants only a grid view
class AD {

    protected $ad;
	
	public static $ADDomain = "ad.emich.edu";
    public static $ADUsername = 'ext_windows_nagios';
    public static $ADPassword = '#hot713outside';
    public static $ADHost = 'ad.emich.edu';
    public static $ADPort = '389';
    public static $ADBaseDN = 'dc=ad,dc=emich,dc=edu';

    function __construct() {
        $this->ad = @ldap_connect(AD::$ADDomain, AD::$ADPort) or die ( "Ad unavailable");
        ldap_set_option($this->ad, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->ad, LDAP_OPT_REFERRALS, 0);

        $adbind = @ldap_bind($this->ad, AD::$ADUsername . '@' . AD::$ADDomain, AD::$ADPassword);
        if(!$adbind){ die("Bind failed"); }
    }
	
    function getGroupusers($group) {
        $filter = "(&(objectClass=user)(memberOf=$group))";
        $justthese = array("samaccountname");
        $results = ldap_search($this->ad, AD::$ADBaseDN, $filter, $justthese);
        $users = ldap_get_entries($this->ad, $results);
        return $users;
    }
	
	function __destruct() {
        ldap_close($this->ad);
    }
}

# Check to see if this user is in the lab attendant group
    $AD = new AD();
	$users =$AD->getGroupusers('CN=doit_lab_attendants,CN=users,DC=ad,DC=emich,DC=edu');
	$restrictedUsers = array();
	
	$i = 0;
    while ($i < $users['count']) {
        array_push($restrictedUsers, $users[$i]['samaccountname'][0]);
        $i++;
    }
	
	# Check if user is in help desk student group
	$users2 =$AD->getGroupusers('CN=doit_fs_hdstudents,CN=users,DC=ad,DC=emich,DC=edu');
	
	$i = 0;
    while ($i < $users2['count']) {
        array_push($restrictedUsers, $users2[$i]['samaccountname'][0]);
        $i++;
    }

print_r($restrictedUsers);
?>

