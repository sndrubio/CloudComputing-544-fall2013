<!DOCTYPE html>
<html>
<head>
<title>Cleanup Image</title>
</head>
<?php
 session_start();
 error_reporting(E_ERROR | E_PARSE);

 
$queueURL = $_SESSION['queueurl'];
$rcptHandle = $_SESSION['rcpthandle'];
$domain = $_SESSION['domain'];
$finishedurl = $_SESSION['finishedurl'];
$topicArn = $_SESSION['topicArn'];
$bucket = $_SESSION['bucket'];
$aux = $_SESSION['file'];
$url = $_SESSION['url'];
$itemName = $_SESSION['itemname'];
 
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

###################################################################
# Generate S3 link valid for 10 minutes
##################################################################
$expires = '+10 minutes';

$expireUrl = $client->getObjectUrl($bucket, $aux, $expires);

###################################################################
# Short link
##################################################################

//Server Key
$apiKey = 'AIzaSyAUeyeKWJZACEJWoFsFVaAgX1O6zQZPayw';
//Long to Short URL
$longUrl = $expireUrl;
$postData = array('longUrl' => $longUrl, 'key' => $apiKey);
$info = httpsPost($postData);
	if($info != null)
	{
		$shortUrl = $info->id;
		//echo "Short URL is : ".$info->id."\n";
	}
 
//Short URL Information
$shortUrl = $info->id;

 
function httpsPost($postData)
{
$curlObj = curl_init();
 
$jsonData = json_encode($postData);
 
curl_setopt($curlObj, CURLOPT_URL, 'https://www.googleapis.com/urlshortener/v1/url');
curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($curlObj, CURLOPT_HEADER, 0);
curl_setopt($curlObj, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
curl_setopt($curlObj, CURLOPT_POST, 1);
curl_setopt($curlObj, CURLOPT_POSTFIELDS, $jsonData);
 
$response = curl_exec($curlObj);
 
//change the response json string to object
$json = json_decode($response);
curl_close($curlObj);
 
return $json;
}


###################################################################
# Send the SMS message of the finished S3 URL
##################################################################

$result = $snsclient->publish(array(
    'TopicArn' => $topicArn,
    //'TargetArn' => $topicArn,
    // Message is required
    'Message' => 'Your image has been uploaded and edited',
    'Subject' => 'Your image has been uploaded and edited  '.$shortUrl,
    'MessageStructure' => 'sms',
));


###################################################################
# Consume the Queue to make sure the job is done
##################################################################

$result = $sqsclient->deleteMessage(array(
   // QueueUrl is required
    'QueueUrl' => $queueURL,
    // ReceiptHandle is required
    'ReceiptHandle' => $rcptHandle,
));

 ?>
 
 
 <body>


<!--Display before and after images -->

<h2>Before</h2>
<img src="<?php echo $url ?>">

<h2>After</h2>
<img src="<?php echo $shortUrl ?>">

 
</body>
</html>
