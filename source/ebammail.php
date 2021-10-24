#!/usr/bin/env php
<?php
//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
//use ebamclass;
require 'inc/PHPMailer.php';
require 'inc/SMTP.php';
require 'inc/Exception.php';
require 'inc/ebamclass.php';

$config = include('mailconfig.php');

$ebam = new Ebam();

        $ebam->table=$config['dbtable'];
        $ebam->dbhost=$config['dbhost'];
        $ebam->dbname=$config['database'];
        $ebam->dbport=$config['dbport'];
        $ebam->dbuser=$config['dbuser'];
        $ebam->dbpass=$config['dbpass'];
        $ebam->mask=$config['filemask'];
        $ebam->workdir=$config['workdir'];

        $conn=$ebam->connectdb();
	$arr12=$ebam->selectmail($conn);

//Create an instance; passing `true` enables exceptions
$mail = new PHPMailer(true);

try {
    //Server settings
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
    $mail->isSMTP();                                            //Send using SMTP
//    $mail->Host       = 'smtp.yandex.ru';                     //Set the SMTP server to send through
    $mail->Host       = $config['smtpserver'];                     //Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
    $mail->Username   = $config['smtpuser'];                     //SMTP username
    $mail->Password   = $config['smtppass'];                               //SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            //Enable implicit TLS encryption
    $mail->Port       = $config['smtpport'];                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

    //Recipients
    $mail->setFrom($config['smtpuser'], $config['organization']);
    $mail->addAddress($config['emailto'], 'Joe User');     //Add a recipient
    //Content
    $mail->isHTML(false);                                  //Set email format to HTML
    $mail->Subject = $config['organisation'];
        foreach($arr12 as $arr)
        {
	    $mail->Body    .= $config['station'].";".$arr['date'].";".$arr['time'].";".$arr['concrt'].";/;"
			.$arr['ws'].";".$arr['wsm'].";".$arr['ws'].";".$arr['wd'].";".$arr['at'].";/;/ \n";

//            echo "#13". $arr['datetime1']." ".$arr['date']." ".$arr['time']." ".$arr['concrt']." ".$arr['at']." ".$arr['ws']." ".$arr['wd']."\n";
        }

    $mail->send();
        foreach($arr12 as $arr)
        {
	    $ebam->is_sent($conn,$arr['datetime1']);
        }

    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    exit();
}
        $ebam->closedb($conn);
unset($ebam);
