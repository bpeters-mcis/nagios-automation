<?php
/**
 * Created by PhpStorm.
 * User: bpeters
 * Date: 3/8/2016
 * Time: 3:54 PM
 */

# Include Files
include('/usr/local/nagios/etc/CustomScripts/Config.php');
include('/usr/local/nagios/etc/CustomScripts/Classes.php');

# Open up a lansweeper connection, as we'll need it later
$Lansweeper = new LansweeperDB();

$matches = $Lansweeper->getAllServerComments('1');

print_r($matches);

?>
