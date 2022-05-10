<?php
/**
 * 
 * Author:    Ravi Patel
 * Created:   06-05-2022
 * Version:   1.0
 **/

require __DIR__ . '/vendor/autoload.php';

/*
 * To generate token.json file, run this file in your Commad Line. i.e. php index.php
 * It will generate one link which need to be run in browser.
 * After authenticating to your Google App, it will redirect to specified redirect URL.
 * Copy "code" parameter value from url and paste it to commad line. i.e. Enter Verification Code: XXXX
 * Now check token.json in you directory. 
 * Comment below IF block once successfully token generated.
 */
if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Gmail API PHP Quickstart');
    $client->setScopes(["https://mail.google.com/","https://www.googleapis.com/auth/gmail.send"]);
    $client->setAuthConfig('credentials.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Load previously authorized token from a file, if it exists.
    // The file token.json stores the user's access and refresh tokens, and is
    // created automatically when the authorization flow completes for the first
    // time.
    $tokenPath = 'token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
        }
        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }

        $token_arr = $client->getAccessToken();

        // Save the refresh token to a file.
        if (!file_exists("refresh_token.json")) {
            $refresh_token = $token_arr['refresh_token'];
            file_put_contents("refresh_token.json", json_encode(array("refresh_token" => $refresh_token)));
        } else {
            $refresh_token = json_decode(file_get_contents("refresh_token.json"), true);
            if (!in_array("refresh_token", $token_arr)) {
                $token_arr["refresh_token"] = $refresh_token["refresh_token"];
            }
        }
        file_put_contents($tokenPath, json_encode($token_arr));
    }
    return $client;
}

/**
 * Get the API client and construct the service object.
 */ 
$client     = getClient();
$service    = new Google_Service_Gmail($client);

/**
 * @param $sender      array  sender name and email address
 * @param $recipient   array  recipient name and email address
 * @param $subject     string email subject
 * @param $messageText string email text
 * @return Google_Service_Gmail_Message
 */
function createMessage($sender, $recipient, $subject, $messageText) {
    $boundary = uniqid(rand(), true);

    $rawMessageString = "From: ".$sender["name"]." <".$sender["email"].">\r\n";
    $rawMessageString .= "To: ".$recipient["name"]." <".$recipient["email"].">\r\n";
    $rawMessageString .= 'Subject: =?utf-8?B?' . base64_encode($subject) . "?=\r\n";
    $rawMessageString .= "MIME-Version: 1.0\r\n";
    $rawMessageString .= 'Content-type: Multipart/Mixed; boundary="' . $boundary . '"' . "\r\n";
    $rawMessageString .= 'Content-Transfer-Encoding: quoted-printable' . "\r\n\r\n";
    $rawMessageString .= $messageText;
    

    $files = array("image.jpg","document.pdf");
    if(count($files)){

        $rawMessageString .= "--{$boundary}\r\n";

        foreach ($files as $filePath) {
            if($filePath!="" && file_exists($filePath)){
                $array = explode('/', $filePath);
                $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
                $mimeType = finfo_file($finfo, $filePath);
                $fileName = $array[sizeof($array)-1];
                $fileData = base64_encode(file_get_contents($filePath));

                $rawMessageString .= "\r\n--{$boundary}\r\n";
                $rawMessageString .= 'Content-Type: '. $mimeType .'; name="'. $fileName .'";' . "\r\n";            
                $rawMessageString .= 'Content-ID: <' . $sender["email"] . '>' . "\r\n";            
                $rawMessageString .= 'Content-Description: ' . $fileName . ';' . "\r\n";
                $rawMessageString .= 'Content-Disposition: attachment; filename="' . $fileName . '"; size=' . filesize($filePath). ';' . "\r\n";
                $rawMessageString .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n";
                $rawMessageString .= chunk_split(base64_encode(file_get_contents($filePath)), 76, "\n") . "\r\n";
                $rawMessageString .= "--{$boundary}\r\n";
            }
        }
    }

    $mime = rtrim(strtr(base64_encode($rawMessageString), '+/', '-_'), '=');
    
    $msg = new Google_Service_Gmail_Message();
    $msg->setRaw($mime);
    return $msg;
}

/**
 * @param $service     object  service object
 * @return Google_Users_Gmail_Lebels
 */
function getUserAccountLabel($service) {
    $user = 'me';
    $results = $service->users_labels->listUsersLabels($user);

    if (count($results->getLabels()) == 0) {
      print "No labels found.\n";
    } else {
      print "Labels:\n";
      foreach ($results->getLabels() as $label) {
        printf("- %s\n", $label->getName());
      }
    }
}

/**
 * @param $service   object  service object
 * @param $msg       string  raw message text
 * @return Google_Users_Gmail_Lebels
 */
function sendEmail($service,$msg) {
    try {
        $response = $service->users_messages->send("me", $msg);
        return $response;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

$subject            = "Your Subject";
$messageText        = "Your message body";
$sender             = array("name" => "Sender Name", "email" => "sender_email@gmail.com");
$recipient          = array("name" => "Recipient Name", "email" => "recipient_email@gmail.com");

/**
 * First create raw message including "sender" and "recipient" info
 */
$msg = createMessage($sender, $recipient, $subject, $messageText);

/**
 * To send email, pass "service object" and "raw message" parameter to sendEmail function
 */
$response = sendEmail($service,$msg);

print_r($response);
?>