<?php
// Allow specifying main window URL for permalinks, etc.

# Added to give lab attendants only a grid view
class AD {

    protected $ad;
	
	public static $ADDomain = "ad.emich.edu";
    public static $ADUsername = 'proxy_adregistrations';
    public static $ADPassword = 'q5Q7zy9UswGmWEbzoyzp';
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
	
	# Now, see if this is a restricted user or not
	if (in_array($_SERVER['REMOTE_USER'], $restrictedUsers)) {
		header( 'Location: https://winmon.emich.edu/nagios/cgi-bin/status.cgi?hostgroup=all&style=grid' ) ;
	}
	
$url = 'main.php';
if (isset($_GET['corewindow'])) {

	// The default window url may have been overridden with a permalink...
	// Parse the URL and remove permalink option from base.
	$a = parse_url($_GET['corewindow']);

	// Build the base url.
	$url = htmlentities($a['path']).'?';
	$url = (isset($a['host'])) ? $a['scheme'].'://'.$a['host'].$url : '/'.$url;

	$query = isset($a['query']) ? $a['query'] : '';
	$pairs = explode('&', $query);
	foreach ($pairs as $pair) {
		$v = explode('=', $pair);
		if (is_array($v)) {
			$key = urlencode($v[0]);
			$val = urlencode(isset($v[1]) ? $v[1] : '');
			$url .= "&$key=$val";
		}
	}
}

$this_year = '2015';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">

<html>
<head>
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
	<title>Nagios Core</title>
	<link rel="shortcut icon" href="images/favicon.ico" type="image/ico">
</head>

<frameset cols="180,*" style="border: 0px; framespacing: 0px">
	<frame src="side.php" name="side" frameborder="0" style="">
	<frame src="<?php echo $url; ?>" name="main" frameborder="0" style="">

	<noframes>
		<!-- This page requires a web browser which supports frames. -->
		<h2>Nagios Core</h2>
		<p align="center">
			<a href="https://www.nagios.org/">www.nagios.org</a><br>
			Copyright &copy; 2010-<?php echo $this_year; ?> Nagios Core Development Team and Community Contributors.
			Copyright &copy; 1999-2010 Ethan Galstad<br>
		</p>
		<p>
			<i>Note: These pages require a browser which supports frames</i>
		</p>
	</noframes>
</frameset>

</html>



