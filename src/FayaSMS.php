<?php
namespace yeboahnanaosei\FayaSMS;

/**
 * FayaSMS provides an easy way to communicate with the FayaSMS API
 *
 * This wrapper allows you to hit all the endpoints provided by FayaSMS. With this wrapper you can:
 * - Send an SMS
 * - Get an estimate on how much a message will cost
 * - Retrieve all the messages you have sent using your AppSecret and AppKey
 * - Check your remaining balance
 *
 * @package yeboahnanaosei\FayaSMS
 * @author Nana Osei Yeboah <nana@gitplus.app>
 * @license MIT
 * @copyright 2019 Git Plus Limited, Accra - Ghana
 * @version 1.0.0
 */
class FayaSMS
{
    /**
     * The FayaSMS endpoint to send requests to
     *
     * @internal
     * @var string
     */
    protected $url;

    /**
     * An array of params to be sent in the post body of the FayaSMS request
     *
     * @internal
     * @var array
     */
    protected $params = [
        "AppKey"       => null,
        "AppSecret"    => null,
        "From"         => null,
        "To"           => null,
        "Message"      => null,
        "ScheduleDate" => null,
        "ScheduleTime" => null,
        "MessageId"    => null,
        "Recipients"   => null,
        "Name"         => null,
        "Description"  => null
    ];

    /**
     * Holds the cURL handle to be used in making the cURL request
     *
     * @internal
     * @var resource
     */
    protected $curlHandle;

    /**
     * Holds the error message based on the response from FayaSMS after a request has been made
     *
     * @internal
     * @var string
     */
    protected $error;

    /**
     * Respond codes returned by FayaSMS API
     *
     * @internal
     * @var array
     */
    protected $responseCodes = [
        "100" => "Request unsuccessful",
        "101" => "Insufficient balance",
        "102" => "Unregistered Sender ID",
        "404" => "Invalid request"
    ];

    /**
     * All the endpoints of the FayaSMS API
     *
     * @internal
     * @var array
     */
    protected $endPoints = [
        "send"     => "https://devapi.fayasms.com/send",
        "messages" => "https://devapi.fayasms.com/messages",
        "balance"  => "https://devapi.fayasms.com/balance",
        "estimate" => "https://devapi.fayasms.com/estimate",
        "senders"  => "https://devapi.fayasms.com/senders",
        "new_id"   => "https://devapi.fayasms.com/senders/new"
    ];

    /**
     * Holds the raw response from FayaSMS. This response has not been parsed
     *
     * @internal
     * @var string JSON response from FayaSMS
     */
    protected $rawResponse;

    /**
     * Number of characters allowed in message body
     *
     * @internal
     * @var int
     */
    protected $allowedMessageLength = 3200;

    /**
     * Creates a FayaSMS instance for interacting with the FayaSMS API
     *
     * @param string $appKey Your AppKey provided to you by FayaSMS
     * @param string $appSecret Your AppSecret token provided to you by FayaSMS
     * @param string $senderID Your approved sender ID from FayaSMS
     */
    public function __construct(string $appKey, string $appSecret, $senderID = null)
    {
        $this->params['AppKey']    = trim($appKey);
        $this->params['AppSecret'] = trim($appSecret);
        $this->params['From']      = trim($senderID);
    }

    /**
     * Sets the sender address. Sender IDs must be 11 characters or less without spaces.
     *
     * This sender ID should have been requested and approved by FayaSMS.
     * In case it is a telephone number it must comply with the telephone rules.
     * Meaning it must be in international format.
     * For example 23320XXXXXXX is in the international telephone number format
     *
     * @version 1.0.0
     * @param string $senderID An approved sender ID from FayaSMS or a phone number
     * in international format
     * @return void
     */
    public function setSenderID(string $senderID)
    {
        $this->params['From'] = trim($senderID);
    }

    /**
     * Sets the description for a new sender ID. Required when requesting for a new sender ID.
     *
     * @version 1.0.0
     * @param string $description The description for the new sender ID being requested
     * @return void
     */
    private function setSenderIDDescription(string $description)
    {
        $this->params['Description'] = trim($description);
    }

    /**
     * Sets the body or message of the SMS to be sent
     *
     * @api
     * @version 1.0.0
     * @param string $messageBody Body of the text message (with a maximum length of 3,200 characters), UTF-8
     * @throws \Exception If the length of the body exceeds the allowed limit
     * @return self
     */
    public function setMessageBody(string $messageBody): self
    {
        // Get a trimmed copy of the messageBody
        $msg = trim($messageBody);

        // Ensure the length is valid
        $msgLength = strlen($msg);
        if ($msgLength > $this->allowedMessageLength) {
            throw new \Exception(
                "The number of characters in the message body supplied to FayaSMS exceeds the allowed limit.
                Expected {$this->allowedMessageLength} characters but got {$msgLength} characters"
            );
        }

        $this->params['Message'] = $msg;
        return $this;
    }

    /**
     * Sets the recipient of the message. Which can be one recipient or several recipients
     *
     * It must comply with the telephone rules. Meaning it must be international format (eg. 23326XXXXXXX).
     * For bulk message, separate each number with a comma (e.g: 23326XXXXXXX,23324XXXXXXX,23320XXXXXXX...)
     *
     * @api
     * @version 1.0.0
     * @param string $recipient The recipient telephone number.
     * @return self
     */
    public function setRecipient(string $recipient): self
    {
        $this->params['Recipients'] = $this->params['To'] = $recipient;
        return $this;
    }

    /**
     * Set recipients of a message by supplying an array instead of a string. Convenient for bulk messaging
     *
     * This method allows you to sent the recipients of a message by supplying an
     * array instead of a string. This is very convenient for bulk messages. Just
     * pass an array of numbers for all the recipients:
     * eg ['23324XXXXXXX', '23326XXXXXXX', '23320XXXXXXX'].
     * The numbers must comply with telephone rules i.e. each number must be in
     * international format (eg. 23326XXXXXXX)
     *
     * @api
     * @version 1.0.0
     * @param array $recipients A numerically indexed array of phone numbers
     * @return self
     */
    public function setRecipientsByArray(array $recipients): self
    {
        $this->params['To'] = $this->params['Recipients'] = join(',', $recipients);
        return $this;
    }

    /**
     * Schedules the message to be sent at a later date and time
     *
     * @api
     * @param string $date The date on which to send the message. Must be in this format YYYY-MM-DD
     * @param string $time The time the message should be sent. Must be in this format HH:ii:ss
     * @return void
     */
    public function scheduleMessage(string $date, string $time)
    {
        $this->params['ScheduleDate'] = $date;
        $this->params['ScheduleTime'] = $time;
    }

    /**
     * Initializes the cURL session and sets the FayaSMS endpoint to send requests to
     *
     * @internal
     * @version 1.0.0
     * @param string $endpoint The FayaSMS endpoint for which this cURL session is being initialized
     * @throws \InvalidArgumentException If the endpoint supplied is not valid or not recognized
     * @return void
     */
    private function init($endpoint)
    {

        if (!array_key_exists($endpoint, $this->endPoints)) {
            throw new \InvalidArgumentException('Invalid endpoint provided');
        }

        // Initialize a cURL session
        $this->url = $this->endPoints[$endpoint];
        $this->curlHandle = curl_init($this->url);
        curl_setopt($this->curlHandle, CURLOPT_POST, true);
        curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, true);
    }

    /**
     * Executes and the cURL session after it has been initialized and returns the response
     *
     * @internal
     * @version 1.0.0
     * @return string|bool JSON response on success or false on failure. On failure
     * the response is stored in FayaSMS::$error. This error message can be retrived
     * by calling FayaSMS::getError().
     */
    private function exec()
    {
        curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, $this->params);
        $response = curl_exec($this->curlHandle);

        // If the response === false then it means cURL failed
        if (!$response) {
            $this->error = curl_error($this->curlHandle);
            curl_close($this->curlHandle);
            return false;
        }

        // Store the raw or actual response from FayaSMS. Can be used for debugging
        return $this->rawResponse = $response;
    }

    /**
     * Sends SMS to a recipient. Expects senderID, messageBody and recipient to have been set.
     *
     * Before calling this method make sure you've set the required parameters which are:
     * senderID, messageBody and recipient. You can set these parameters by calling the following methods:
     *
     * - FayaSMS::setSenderID($senderID)
     * - FayaSMS::setMessageBody($body),
     * - FayaSMS::setRecipient($recipient)
     *
     * @api
     * @version 1.0.0
     * @throws \Exception   If required parameters for sending an SMS are not supplied
     * @return string|bool  JSON response from FayaSMS on success or false on failure. On failure, you can get
     * the error message by calling FayaSMS::getError()
     */
    public function send()
    {
        $this->init('send');
        $this->checkRequiredFields('send');
        return $this->exec();
    }

    /**
     * Returns your current balance. The number of units you have left on your FayaSMS account
     *
     * @api
     * @version 1.0.0
     * @throws \Exception If you have not set your AppKey and AppSecret
     * @return string|bool $response JSON response from FayaSMS or false on failure. On failure
     * you can get the error message by calling FayaSMS::getError().
     */
    public function getBalance()
    {
        $this->init('balance');
        $this->checkRequiredFields('balance');
        return $this->exec();
    }

    /**
     * Returns all sender IDs associated with your AppKey and AppSecret
     *
     * @api
     * @version 1.0.0
     * @throws \Exception If you have not set your AppKey and AppSecret
     * @return string|bool $response JSON response from FayaSMS or false on failure. On failure
     * you can get the error message by calling FayaSMS::getError().
     */
    public function getSenderIDs()
    {
        $this->init('senders');
        $this->checkRequiredFields('senders');
        return $this->exec();
    }

    /**
     * Gets the estimate for how much a message will cost based on the message
     * length and number of recipients.
     *
     * This method *requires* you to have already set the recipient of the
     * message as well as the message body. Set these parameters by calling:
     * - FayaSMS::setRecipient($recipient)
     * - FayaSMS::setMessageBody($body).
     *
     * @api
     * @version 1.0.0
     * @throws \Exception If the required parameters are not set
     * @return string|bool $response JSON response from FayaSMS on success or false on failure.
     * On failure you can get the error message by calling FayaSMS::getError().
     */
    public function getEstimate()
    {
        $this->init('estimate');
        $this->checkRequiredFields('estimate');
        return $this->exec();
    }

    /**
     * Gets all messages that have been sent using your AppKey and AppSecret
     *
     * @api
     * @version 1.0.0
     * @throws \Exception If your AppKey or AppSecret is not set
     * @return string|bool $reponse JSON data on success or false on failure. On failure
     * you can get the error message by calling FayaSMS::getError().
     */
    public function getMessages()
    {
        $this->init('messages');
        $this->checkRequiredFields('messages');
        return $this->exec();
    }

    /**
     * Retrieves a single message whose ID matches the supplied message ID
     *
     * @param string $messageID The id of the message that is to be retrieved
     * @return string|bool $reponse JSON data on success or false on failure. On failure
     * you can get the error message by calling FayaSMS::getError().
     */
    public function getMessage($messageID)
    {
        $this->params['MessageId'] = $messageID;
        $this->init('messages');
        $this->checkRequiredFields('messages');
        return $this->exec();
    }

    /**
     * Makes a request to FayaSMS for a new sender ID.
     *
     * @version 1.0.0
     * @param string $senderID The new senderID you are requesting for
     * @param string $description A description for the new sender ID
     * @return string|bool $reponse JSON data on success or false on failure.
     * On failure you can get the error message by calling FayaSMS::getError().
     */
    public function requestSenderID(string $senderID, string $description)
    {
        $this->setSenderID($senderID);
        $this->setSenderIDDescription($description);
        $this->init('new_id');
        $this->checkRequiredFields('new_id');
        return $this->exec();
    }

    /**
     * Checks to ensure that all required fields set for the specified enpoint
     *
     * @internal
     * @throws \Exception If a required field is not set
     * @return bool True if all required fields are set
     */
    private function checkRequiredFields(string $endPoint)
    {
        // No matter the endpoint being accessed, some fields are always required
        // this method checks to make sure those fields are set. Throws an exception
        // if any of them is not set.
        $this->checkDefaultFields();

        // Aside the default required fields, each endpoint has its own additional
        // fields that must be set. The following methods checks to make sure that
        // those fields are set before a request is made to the endpoint.
        switch ($endPoint) {
            case 'send':
                $this->checkSendRequiredFields();
                break;
            case 'messages':
                $this->checkMessagesRequiredFields();
                break;
            case 'balance':
                $this->checkBalanceRequiredFields();
                break;
            case 'estimate':
                $this->checkEstimateRequiredFields();
                break;
            case 'senders':
                $this->checkSendersRequiredFields();
                break;
            case 'new_id':
                $this->checkNewSendersDefaultFields();
                break;
            default:
                throw new \InvalidArgumentException('Unknown endpoint supplied');
        }
        return true;
    }

    /**
     * Checks that the default fields required for every request to FayaSMS are set
     *
     * These fields are AppKey and AppSecret
     *
     * @internal
     * @version 1.0.0
     * @throws \Exception If any of the required fields are not set
     * @return void
     */
    private function checkDefaultFields()
    {
        if (empty($this->url)) {
            throw new \Exception("FayaSMS: No endpoint set");
        }

        if (empty($this->params['AppKey'])) {
            throw new \Exception(
                'FayaSMS: No AppKey supplied. FayaSMS expects a valid AppKey. Supply your AppKey as the first argument to the constructor'
            );
        }

        if (empty($this->params['AppSecret'])) {
            throw new \Exception(
                'FayaSMS: No AppSecret supplied. FayaSMS expects a valid AppSecret token. Supply your AppSecret as the second argument to the constructor'
            );
        }
    }

    /**
     * Checks to ensure that the required fields for sending a message are set
     *
     * @internal
     * @version 1.0.0
     * @throws \Exception If any of the required fields are not set
     * @return void
     */
    private function checkSendRequiredFields()
    {
        if (empty($this->params['From'])) {
            throw new \Exception(
                'FayaSMS: No sender ID supplied. FayaSMS expects a valid sender ID. Supply your senderID by calling FayaSMS::setSenderID($senderID) or by supplying it as the third argument to the constructor'
            );
        }

        if (empty($this->params['Message'])) {
            throw new \Exception(
                'FayaSMS: No SMS body supplied. FayaSMS expects a message body to be supplied. Supply the body of the message by calling FayaSMS::setMessageBody($body)'
            );
        }

        if (empty($this->params['To']) || empty($this->params['Recipients'])) {
            throw new \Exception(
                'FayaSMS: No recipient supplied. FayaSMS expects a recipient to be supplied. Supply the recipient of the message by calling FayaSMS::setRecipient($recipient)'
            );
        }
    }

    /**
     * Checks to ensure that the required fields for the messages endpoint are set
     *
     * @version 1.0.0
     * @throws \Exception If any of the required fields are not set
     * @internal
     * @return void
     */
    private function checkMessagesRequiredFields()
    { }

    /**
     * Checks to ensure that the required fields for the balance endpoint are set
     *
     * @internal
     * @throws \Exception If any of the required fields are not set
     * @return void
     */
    private function checkBalanceRequiredFields()
    { }

    /**
     * Checks that all required fields for the estimate endpoint are set
     *
     * @version 1.0.0
     * @internal
     * @throws \Exception If any of the required fields are not set
     * @return void
     */
    private function checkEstimateRequiredFields()
    {
        if (empty($this->params['Recipients'])) {
            throw new \Exception(
                'FayaSMS: To estimate the cost of a message FayaSMS requires the recipient to be set. Set recipient by calling FayaSMS::setRecipient($recipient)'
            );
        }

        if (empty($this->params['Message'])) {
            throw new \Exception(
                'FayaSMS: To estimate the cost of a message FayaSMS requires the message body to be set. Set message body by calling FayaSMS::setMessageBody($body)'
            );
        }
    }

    /**
     *
     * @internal
     */
    private function checkSendersRequiredFields()
    { }

    /**
     * Checks that all required fields for requesting a new sender ID have been set
     *
     * @internal
     * @throws \Exception If any of the required fields are not set
     * @return void
     */
    private function checkNewSendersDefaultFields()
    {
        if (empty($this->params['From'])) {
            throw new \Exception(
                'FayaSMS: To request a new sender ID, FayaSMS requires the new sender ID to be supplied. Supply the sender ID by calling FayaSMS::setSenderID($senderID)'
            );
        }

        if (empty($this->params['Description'])) {
            throw new \Exception(
                'FayaSMS: To request a new sender ID, FayaSMS requires a sender ID description to be supplied. Supply the sender ID description by calling FayaSMS::setSenderIDDescription($desc)'
            );
        }
    }

    /**
     * Parses the response from FayaSMS and reports it
     *
     * @internal
     * @todo Not completely thought out. In future this can be used to parse and show
     * the exact response from FayaSMS instead of the raw JSON output.
     * @return bool True if the response from FayaSMS indicates success or false on failure
     */
    private function parseResponse($response)
    {
        $response = json_decode($response);

        // Return true if response is ok
        if ($response->status == '200') {
            return true;
        }

        // If the response status is not 200, get the code and report the appropriate error message
        switch ($response->status) {
            case '100':
                $this->error = "FayaSMS responded with: {$this->responseCodes['100']} - $response->result";
                break;
            default:
                $this->error = "FayaSMS responded with: {$this->responseCodes[$response->status]}";
        }

        return false;
    }

    /**
     * Returns the error message from FayaSMS if a failure occures
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Returns the raw response from FayaSMS. This can be used for debugging purposes
     *
     * @internal
     * @return string $rawResponse JSON response from FayaSMS
     */
    private function getRawResponse()
    {
        return $this->rawResponse;
    }
}
