# FayaSMS is a library that provides a simple interface to the [FayaSMS API]("https://www.fayasms.com/")


This library allows you to interact with the FayaSMS API seamlessly in your PHP application. The API from [FayaSMS]("https://www.fayasms.com/") exposes endpoints that allow you to do several things like:
- Sending SMS (single or bulk)
- Checking your balance
- Get an estimate on how much an sms will cost
- Request a sender ID
- Get a list of all your sender IDs
- Retrieve messages (a single message or all your sent messages)

**This library allows you to perform all the above mentioned actions**
. Lets take them one by one
> NB: Please note that you need to have a registered app with [FayaSMS]("https://www.fayasms.com/") in order to use their API. Head over to [their website]("https://www.fayasms.com/") to create your account and register an app.

<br>

- #### Contents
  - [Installation](#Installation)
  - [Usage:](#Usage)
    - [Sending single message](#Sending-single-message)
    - [Sending bulk messages](#Sending-bulk-messages)
    - [Getting an estimate](#Getting-an-estimate)
    - [Retrieving all messages](#Retrieving-messages)
    - [Retrieving single message](#Retrieving-single-message)
    - [Checking your balance](#Checking-your-balance)
    - [Retrieving your sender IDs](#Retrieving-your-sender-IDs)
    - [Requesting a new sender ID](#Requesting-a-new-sender-ID)

<br>
<br>

## Installation:

Easily installable via composer. Just run this command in your terminal at the root of your project. Visit [Composer]("https://getcomposer.org/") to see how to use and install composer on your machine.
```bash
composer require yeboahnanaosei/fayasms
```
<br>
<br>

###### [Back to top](#Contents)
## Usage:
### Sending single message:
(To send a single message all you need is your appKey, appSecret and senderID. Of course you need a recipient and a message)
```php
<?php
use yeboahnanaosei/FayaSMS/FayaSMS;

require "vendor/autoload.php";

$appKey    = "app_key";     // Obtained from FayaSMS
$appSecret = "app_secret";  // Obtained from FayaSMS
$senderID  = "sender_id";   // Obtained from FayaSMS

// Create an instance of FayaSMS and supply your appKey, appSecret and senderID.
$fayasms = new FayaSMS($appKey, $appSecret, $senderID);

// Set the recipient phone number. The recipient number must comply with FayaSMS
// telephone rules. Meaning the number must be in international format.
// Read more from FayaSMS.com
$fayasms->setRecipient("23326XXXXXXX");

// Set the body of the message
$fayasms->setMessageBody("Message to be sent to the recipient");

// Then you just send. This will return a JSON payload from FayaSMS indicating
// whether your message was sent successfully or otherwise
$response = $fayasms->send();
```

<br>
<br>

###### [Back to top](#Contents)
### Sending bulk messages:
(Sending bulk message is no different from sending a single message, you just need to provide multiple phone numbers)
```php
<?php
use yeboahnanaosei/FayaSMS/FayaSMS;

require "vendor/autoload.php";

$appKey    = "app_key";     // Obtained from FayaSMS
$appSecret = "app_secret";  // Obtained from FayaSMS
$senderID  = "sender_id";   // Obtained from FayaSMS

// Create an instance of FayaSMS and supply your appKey, appSecret and senderID
$fayasms = new FayaSMS($appKey, $appSecret, $senderID);

// For bulk messages you have two methods you can use to set the recipients. Choose
// whichever one you prefer

// The first one is this:
// Just provide a string of phone numbers separated by comma.
// The numbers must comply with FayaSMS telephone rules. Meaning each number must
// be in international format. Read more from FayaSMS.com
$fayasms->setRecipient("23326XXXXXXX, 23324XXXXXXX, 23327XXXXXXX");

// The other option is this:
// Instead of a string, you just supply an array of phone numbers by calling the
// setRecipientsByArray() method. The numbers must be in international format
$recipients = ["23326XXXXXXX", "23324XXXXXXX", "23327XXXXXXX"];
$fayasms->setRecipientsByArray($recipients);

// Set the body of the message
$fayasms->setMessageBody("Message to be sent to recipients");

// Then you just send. This will return a JSON payload from FayaSMS indicating
// whether your message was sent successfully or otherwise
$response = $fayasms->send();
```

<br>
<br>

###### [Back to top](#Contents)
### Getting an estimate:
(You can get an estimate on how many units it will cost you to send a message)
```php
<?php
use yeboahnanaosei/FayaSMS/FayaSMS;

require "vendor/autoload.php";

$appKey    = "app_key";     // Obtained from FayaSMS
$appSecret = "app_secret";  // Obtained from FayaSMS

// Create an instance of FayaSMS and supply your appKey, appSecret
// Your senderID is not required
$fayasms = new FayaSMS($appKey, $appSecret);

// You need to provide the message you want to send and the recipient or
// recipients of the messaage
$message = "Some message to be sent";
$recipients = "23326XXXXXXX, 23355XXXXXXX";

$fayasms->setRecipient($recipients);
$fayasms->setMessageBody($message);

// Request for the estimate.
// Returns a JSON payload indicating how many units such a message will cost
$fayasms->getEstimate();
```


<br>
<br>

###### [Back to top](#Contents)
### Retrieving messages:
(FayaSMS allows you to retrieve all the messages you've sent. All you need is your appKey and appSecret. No sender id required)
```php
<?php
use yeboahnanaosei/FayaSMS/FayaSMS;

require "vendor/autoload.php";

$appKey    = "app_key";     // Obtained from FayaSMS
$appSecret = "app_secret";  // Obtained from FayaSMS

// Create an instance of FayaSMS and supply your appKey, appSecret
$fayasms = new FayaSMS($appKey, $appSecret);

// The getMessages() method returns JSON data holding all messages sent using
// your appKey and appSecret
$messages = $fayasms->getMessages();
```

<br>
<br>

###### [Back to top](#Contents)
### Retrieving single message:
(You are able to retrieve a single message. All you need is your appKey, appSecret and a message id.
To get the id of the message you want, you will have to sift through all your sent messages for the id)
```php
<?php
use yeboahnanaosei/FayaSMS/FayaSMS;

require "vendor/autoload.php";

$appKey    = "app_key";     // Obtained from FayaSMS
$appSecret = "app_secret";  // Obtained from FayaSMS

// Create an instance of FayaSMS and supply your appKey, appSecret
$fayasms = new FayaSMS($appKey, $appSecret);

// The getMessage($messageID) method expects a message id and returns JSON data
// holding all messages sent using your appKey and appSecret
$message = $fayasms->getMessage($messageID);
```

<br>
<br>

###### [Back to top](#Contents)
### Checking your balance:
(You can check your unit balance from FayaSMS to determine the number of messages you can send)
```php
<?php
use yeboahnanaosei/FayaSMS/FayaSMS;

require "vendor/autoload.php";

$appKey    = "app_key";     // Obtained from FayaSMS
$appSecret = "app_secret";  // Obtained from FayaSMS

// Create an instance of FayaSMS and supply your appKey, appSecret
$fayasms = new FayaSMS($appKey, $appSecret);

// Call the getBalance() method to find out your remaining balance. Returns a JSON payload
$balance = $fayasms->getBalance();

```

<br>
<br>

###### [Back to top](#Contents)
### Retrieving your sender IDs:
(You can retrieve all the sender IDs you have registered with your appKey and appSecret)
```php
<?php
use yeboahnanaosei/FayaSMS/FayaSMS;

require "vendor/autoload.php";

$appKey    = "app_key";     // Obtained from FayaSMS
$appSecret = "app_secret";  // Obtained from FayaSMS

// Create an instance of FayaSMS and supply your appKey, appSecret
$fayasms = new FayaSMS($appKey, $appSecret);

// Call the getSenderIDs() method to get all your sender IDs.
//Returns a JSON payload
$senderIDs = $fayasms->getSenderIDs();
```

<br>
<br>

###### [Back to top](#Contents)
### Requesting a new sender ID:
(You can request a new sender ID using your appKey and appSecret)
> Please note that the sender ID you request is subject to approval
```php
<?php
use yeboahnanaosei/FayaSMS/FayaSMS;

require "vendor/autoload.php";

$appKey    = "app_key";     // Obtained from FayaSMS
$appSecret = "app_secret";  // Obtained from FayaSMS

// Create an instance of FayaSMS and supply your appKey, appSecret
$fayasms = new FayaSMS($appKey, $appSecret);

// To request a new sender ID of course you need the new sender ID you want and
// a description for the new sender id
$senderID = "new_sender_id";
$description = "Description for my new sender id";

// Returns a JSON payload indicating that your request has been submitted for
// review
$newSenderID = $fayasms->requestSenderID($senderID, $description);
```
