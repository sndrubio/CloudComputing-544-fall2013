<!DOCTYPE html>
<?php
#retrieve these values that were set in process.php to make our code more flexible
$queueURL = $_SESSION['queueurl'];
$domain = $_SESSION['domain'];

// Include the SDK using the Composer autoloader
require 'vendor/autoload.php';

use Aws\SimpleDb\SimpleDbClient;
use Aws\S3\S3Client;
use Aws\Sqs\SqsClient;
use Aws\Common\Aws;
use Aws\SimpleDb\Exception\InvalidQueryExpressionException;

//aws factory
$aws = Aws::factory('/var/www/vendor/aws/aws-sdk-php/src/Aws/Common/Resources/custom-config.php');

// Instantiate the S3 client with your AWS credentials and desired AWS region
$client = $aws->get('S3');

$sdbclient = $aws->get('SimpleDb');

$sqsclient = $aws->get('Sqs');

$mbody="";


#####################################################
# SQS Read the queue for some information -- we will consume the queue later
#####################################################
$result = $sqsclient->receiveMessage(array(
    // QueueUrl is required
    'QueueUrl' => $queueURL,
    'MaxNumberOfMessages' => 1, 
));
######################################3
# Probably need some logic in here to handle delays)
######################################
foreach ($result->getPath('Messages/*/Body') as $messageBody) {
    // Do something with the message
    echo "SQS: " . $messageBody . "\n";
    $mbody=$messageBody;
}

##############################################
# Select from SimpleDB element where id = the id in the Queue
##############################################
#$exp = "select * from itm544jrh where id = '$mbody'";
$exp = "select * from '$domain' where id = '$mbody'";
echo "\n".$exp."\n";

try {
$iterator = $sdbclient->getIterator('Select', array(
    'SelectExpression' => $exp,
));
} catch(InvalidQueryExpression $i) {
 echo 'Invalid query: '. $i->getMessage() . "\n";
}
####################################################################
# Declare some variables as place holders for the select object
####################################################################
$email = '';
$rawurl = '';
$finishedurl = '';
$bucket = '';
$id = '';
$phone = '';
$filename = '';
$localfilename = ""; // this is a local variabel used to store the content of the s3 object
###################################################################
# Now we are going to loop through the response object to get the 
# values of the returned object
##################################################################
foreach ($iterator as $item) {
    echo "Item: " . $item['Name'] . "\n";
 #var_export($item['Attributes']);
     foreach ($item['Attributes'] as $attribute) {
        #if ($attribute['Name'] == 'email') {
	#  echo "Email Value: " . $attribute['Value'] . "\n";
    switch ($attribute['Name']) {
    case "id": 
        echo "id Value is: ". $attribute['Value']."\n";
        $id = $attribute['Value'];
        break;
    case "email":
        echo "Email Value is: ". $attribute['Value']."\n";
        $email = $attribute['Value']; 
        break;
    case "bucket":
        echo "Bucket Value is: ". $attribute['Value']."\n";
        $bucket = $attribute['Value'];
        break;
		 case "rawurl":
        echo "RawURL Value is: ". $attribute['Value']."\n";
        $rawurl = $attribute['Value'];
        break;
   case "finishedurl":
        echo "Finished URL Value is: ". $attribute['Value']."\n";
        $finishedurl = $attribute['Value'];
        break;
   case "filename":
        echo "Filename Value is: ". $attribute['Value']."\n";
        $filename = $attribute['Value'];
        break;
   case "phone":
        echo "Phone Value is: ". $attribute['Value']."\n";
        $phone = $attribute['Value'];
        break;
   default: 
        echo "Unable to figure out - " . $attribute['Name'] ." = " . $attribute['Value'];

} // end of switch 
 #     } // end of if
  } // end of inner for loop 
}//end of outer for loop

###########################################################################
#  Now that you have the URI returned in the S3 object you can use wget -
# http://en.wikipedia.org/wiki/Wget to pull down the image from the S3 url
# then we add the stamp on the picture save the image out and then reupload
# it to S3 and then update the item in SimpleDb  S3 has a prefix URL which can
# be hard coded https://s3.amazonaws.com
############################################################################
$s3urlprefix = 'https://s3.amazonaws.com/';
$localfilename = "/tmp/" . $filename;
$result = $client->getObject(array(
    'Bucket' => $bucket,
    'Key'    => $filename,
    'SaveAs' => $localfilename,
));
############################################################################
#  Now that we have called the s3 object and downloaded (getObject) the file
# to our local system - lets pass the file to our watermark library 
# http://en.wikipedia.org/wiki/Watermark -- using a function  
###########################################################################
addStamp($localfilename);

#########################################################################
# PHP function for adding a "stamp" or watermark through the php gd library
#########################################################################
function addStamp($image)
{
// Load the stamp and the photo to apply the watermark to
// http://php.net/manual/en/function.imagecreatefromgif.php
$stamp = imagecreatefromgif('./happy_trans.gif');
$im = imagecreatefromjpeg($image);

// Set the margins for the stamp and get the height/width of the stamp image
$marge_right = 10;
$marge_bottom = 10;
$sx = imagesx($stamp);
$sy = imagesy($stamp);

// Copy the stamp image onto our photo using the margin offsets and the photo 
// width to calculate positioning of the stamp. 
imagecopy($im, $stamp, imagesx($im) - $sx - $marge_right, imagesy($im) - $sy - $marge_bottom, 0, 0, imagesx($stamp), imagesy($stamp));

// Output and free memory
header('Content-type: image/png');
imagepng($im);
imagedestroy($im);

} // end of function

?>
<html>
<head><title>Resize PHP</title></head>
<body>
<img src="/tmp/<? echo $filename ?>" />
</body>
</html>
