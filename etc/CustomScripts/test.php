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

$matches = $Lansweeper->getCommentsByCode('00f6cb4b4efd69cc80cf3b7cd07ba2');

print_r($matches);

if (count($matches) > 0) {

    # Now we know it's a valid code.  Get the info about this server
    $ServerDetails = $Lansweeper->getServersDetailsByID($matches[0]['AssetID']);

    # Process the Acknlowedgement
    $DescStart = strpos($matches[0]['Comment'], '(');
    $DescStop = strpos($matches[0]['Comment'], ')');
    $DescLength = $DescStop - $DescStart;
    $ServiceDescription = substr($matches[0]['Comment'], $DescStart, $DescLength);
    $output = 'ACKNOWLEDGE_SVC_PROBLEM;' . $ServerDetails[0]['AssetName'] . ';' . $ServiceDescription;

    echo $output;

}

?>