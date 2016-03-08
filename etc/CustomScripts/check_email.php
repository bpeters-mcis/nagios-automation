<?php
/**
 * Created by PhpStorm.
 * User: bpeters
 * Date: 3/8/2016
 * Time: 2:40 PM
 */

# Include Files
include('/usr/local/nagios/etc/CustomScripts/Config.php');
include('/usr/local/nagios/etc/CustomScripts/Classes.php');

# Connect To Inbox
$inbox = imap_open(Config::$MailHost, Config::$MailUser, Config::$MailPassword) or die('Cannot connect to Gmail: ' . imap_last_error());

# Get the emails
$emails = imap_search($inbox,'ALL');

# IF there are any, go through them
if($emails) {

    # Open up a lansweeper connection, as we'll need it later
    $Lansweeper = new LansweeperDB();

    # Begin Output
    $output = '';

    # Newest on top
    rsort($emails);

    # Go through each message
    foreach($emails as $email_number) {

        # Get the email data
        $overview = imap_headerinfo($inbox,$email_number);

        # Get who sent it
        $Sender = $overview->fromaddress;

        # Get the subject line
        $Subject = $overview->subject;

        # Check to see if this subject is properly formatted... as close to SQL injection protection as we can get
        if (substr($Subject, 0, 6) == "Code: " && ctype_alnum(substr($Subject, 6, 30)) && strlen($Subject) == 36) {


            echo "Would process this message! " . $Subject . PHP_EOL;
            # See if this code matches anything in the database
            $matches = $Lansweeper->getCommentsByCode($Subject);
            if (count($matches) > 0) {

                # Now we know it's a valid code.  Get the info about this server
                $ServerDetails = $Lansweeper->getServersDetailsByID($matches[0]['AssetID']);
                print_r($ServerDetails);

                # Email

            }



        } else {

            # Subject not valid. Process accordingly
            $senderDomain = explode('@', $Sender);

            # If this is sent from an EMU user, explain why it failed.  If not, just delete it.
            if (substr($senderDomain[1], 0, 9) == 'emich.edu') {
                echo "Input not valid.  Would delete. Length: " . strlen($Subject) . " - start: " . substr($Subject, 0, 6) . " - " . $Subject . PHP_EOL;

                $headers = "From: Windows Nagios";
                $subject = "Nagios Acknowledgement Failed";
                $to = $Sender;
                $body =  "Your recent attempt at acknowledging a Nagios outage has failed.\r\n";
                $body .= "This is most likely caused by having the wrong subject line in your email.  The subject should be: 'Code: (code from your outage)'\r\n";
                $body .= "As an example, it could be: 'Code: 00f6cb4b4efd69cc80cf3b7cd07ba2'  Please try again.";
                #mail($to, $subject, $body, $headers);
            }
            print_r($senderDomain);

            #imap_delete($inbox, $email_number);
        }

    }
}

# Close
imap_close($inbox);
?>