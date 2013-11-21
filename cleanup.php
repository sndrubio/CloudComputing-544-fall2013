<!DOCTYPE html>
<html>
<head>
<title>Cleanup Image</title>
</head>
<?php
// add code to consume the Queue to make sure the job is done
// add code to send the SMS message of the finished S3 URL
// Set object expire to remove the image in one day
// set ACL to public
 session_start();
ini_set('display_errors',1); 
 error_reporting(E_ALL);
 
$queueURL = $_SESSION['queueurl'];
$domain = $_SESSION['domain'];
$finishedurl = $_SESSION['finishedurl'] ;
 
 // Include the SDK using the Composer autoloader
require 'vendor/autoload.php';

use Aws\SimpleDb\SimpleDbClient;
use Aws\S3\S3Client;
use Aws\Sqs\SqsClient;
use Aws\Sns\SnsClient;
use Aws\Sns\Exception\InvalidParameterException;
use Aws\Common\Aws;
use Aws\SimpleDb\Exception\InvalidQueryExpressionException;
 
 //aws factory
$aws = Aws::factory('/var/www/vendor/aws/aws-sdk-php/src/Aws/Common/Resources/custom-config.php');
 
 $client = $aws->get('S3'); 

$sdbclient = $aws->get('SimpleDb'); 

$snsclient = $aws->get('Sns'); 

$sqsclient = $aws->get('Sqs');





//code to send the SMS message of the finished S3 URL

$result = $snsclient->publish(array(
    'TopicArn' => $topicArn,
    //'TargetArn' => $topicArn,
    // Message is required
    'Message' => 'Your image has been uploaded',
    'Subject' => $finishedurl,
    'MessageStructure' => 'sms',
));

 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 ?>
