<?php
/**
 * Created by PhpStorm.
 * User: bpeters
 * Date: 2/9/2016
 * Time: 2:18 PM
 */


$subject = 'Nagios Service Restart Failure';
$headers = "From: DoNotReply@emich.edu\n";
$headers .= "MIME-Version: 1.0\n";
$headers .= "Content-Type: text/html; charset=\"iso-8859-1\"\n";
$to = 'bpeters@emich.edu';
$body = '<br>';

$output = file('/usr/local/nagios/etc/restart.log');

foreach ($output as $line) {
 $body .= $line . PHP_EOL;
}

mail($to, $subject, $body, $headers);

?>