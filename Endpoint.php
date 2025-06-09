<?php

/**
 * Core Framework - UsersEndpoint
 *
 * @license    MIT (https://mit-license.org/)
 * @author     Louis Ouellet <louis@laswitchtech.com>
 */

// Import additionnal class into the global namespace
use \LaswitchTech\Core\Objects;
use \LaswitchTech\Core\Abstracts\Endpoint;

class UsersEndpoint extends Endpoint {

    /**
     * Constructor
     */
    public function __construct()
    {

        // Call Parent Constructor
        parent::__construct();

        // Retrieve the namespace
        $namespace = $this->Request->getNamespace();

        // Set Global access
        $this->Public = false;

        // Set Level
        switch($namespace){
            case "/users/index":
            case "/users/fetch":
                $this->Level = 1;
                break;
            case "/users/update":
                $this->Level = 3;
                break;
            case "/users/create":
                $this->Level = 2;
                break;
        }
    }

    /**
     * Fetch all users
     */
    public function indexAction(): array
    {
        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => $this->Model->Users->list()];

        // Return the message
        return $message;
    }

    /**
     * Fetch a User's Information
     */
    public function fetchAction(): array
    {
        // Import Global Variables
        global $CONFIG;

        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => [
            "record" => $this->Model->Users->get(intval($this->Request->getParams('GET', 'id')))
        ]];

        // Return the message
        return $message;
    }

    /**
     * Update a User
     */
    public function updateAction(): array
    {
        // Import Global Variables
        global $CSRF;

        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Check the request method
        if($this->Request->getMethod() == "POST"){
            $message["data"]["CSRF"] = [
                "token" => $CSRF->token(),
                "key" => $CSRF->key()
            ];
        }

        // Retrieve the user id
        $id = intval($this->Request->getParams('REQUEST','id'));

        // Retrieve the user
        $user = $this->Model->Users->get($id, false);

        // Check if the user exists
        if(empty($user)){
            $message = ["status" => 404, "message" => "Not Found", "data" => "Could not find the requested user."];
        }

        // Check if the user is accessible
        if($message['status'] == 200){

            // Check the request method
            if($this->Request->getMethod() == "POST"){

                // Retrieve the parameters
                $parameters = $this->Request->getParams('REQUEST');

                // Initialize the Events
                $message['data']['events'] = [];

                // Update the user
                foreach($parameters as $key => $value){
                    if(isset($user[$key])){
                        switch($key){
                            case 'users':
                                if(is_array($value)){
                                    $user[$key] = [];
                                    foreach($value as $objId){
                                        $user[$key][] = intval($objId);
                                    }
                                    $user[$key] = array_unique($user[$key]);
                                } else {
                                    $user[$key] = $value;
                                }
                                break;
                            case 'isDefault':
                                $user[$key] = intval(filter_var($value, FILTER_VALIDATE_BOOLEAN));
                                break;
                            default:
                                $user[$key] = $value;
                                break;
                        }
                    }
                }

                // Update the user
                $affectedRows = $this->Model->Users->update($id, $user);

                // Retrieve the final user
                $message['data']['record'] = $this->Model->Users->get($id);
            } else {
                $message = ["status" => 405, "message" => "Method Not Allowed", "data" => "The method is not allowed for the requested URL."];
            }
        }

        return $message;
    }

    /**
     * Create a User
     */
    public function createAction(): array
    {
        // Import Global Variables
        global $CSRF, $SMTP, $CONFIG;

        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Check the request method
        if($this->Request->getMethod() == "POST"){
            $message["data"]["CSRF"] = [
                "token" => $CSRF->token(),
                "key" => $CSRF->key()
            ];
        }



        // Check if the user is accessible
        if($message['status'] == 200){

            // Check the request method
            if($this->Request->getMethod() == "POST"){

                // Retrieve the parameters
                $parameters = $this->Request->getParams('REQUEST');

                // Initialize the Events
                $message['data']['events'] = [];

                // Add required fields
                $parameters['owner'] = $this->Auth->user()->username;
                $parameters['organization'] = $this->Auth->user()->organization()->id;
                $parameters['password'] = $this->Helper->Users->generate(12);
                $parameters['isVerified'] = 1;

                // Required Fields
                $required = ["name", "email", "owner", "organization", "password", "isVerified"];

                // Check if all required fields are set
                if(count(array_intersect_key(array_flip($required), $parameters)) == count($required)){

                    // Connect to the smtp server
                    $SMTP->connect();

                    // Check if the smtp server is connected
                    if($SMTP->isConnected()){

                        // Authenticate to the SMTP Server
                        $SMTP->authenticate();

                        // Check if the SMTP Server is authenticated
                        if($SMTP->isAuthenticated()){

                            // Create the User
                            if($userId = $this->Model->Users->create($parameters)){

                                // Retrieve the Organization's vCard
                                $vCard = $this->Model->Vcards->get($this->Auth->user()->organization()->vcard['id']);

                                // Write the email
                                $body = '';
                                $body .= '<p>Welcome to '.$vCard['name'].'!</p>';
                                $body .= '<p>Your account has been created and is ready to use.</p>';
                                $body .= '<p>Here is your account password:</p>';
                                $body .= '<pre style="background-color: #F5F5F5; font-weight: 700; font-size: 28px; text-align: center; letter-spacing: 16px; margin: 20px 20px; padding: 20px 0; font-family: Courier, monospace">'.$parameters['password'].'</pre>';
                                $body .= '<p>Please follow the link below to access %BRAND%.</p>';
                                $body .= '<p style="text-align:center;margin-top: 40px;margin-bottom:40px;">';
                                $body .= '<a href="'.$this->Request->getHostAddress().'" target="_blank" style="margin-left: 6px; margin-right: 6px; text-decoration:none; background-color: #528fb3;color: #fff;font-size: 24px;padding: 20px 40px;text-align: center;margin: 20px 20px;border-radius: 8px;">%BRAND%</a>';
                                $body .= '</p>';
                                $body .= '<p>Thank you for choosing '.$vCard['name'].'!</p>';

                                // Create a new message
                                $eml = $SMTP->message()
                                    ->to($parameters['email'])
                                    ->from($vCard['email'] ?? $this->Config->get('smtp','username'))
                                    ->subject('Welcome to '.$vCard['name'])
                                    ->body($body)
                                    ->var('logo', 'data:'.mime_content_type($this->Config->root() . '/dist/img/logo.png').';base64,' . base64_encode(file_get_contents($this->Config->root() . '/dist/img/logo.png')))
                                    ->var('brand', $CONFIG->get('application','name'))
                                    ->var('greetings', "Sincerely,<br>".$vCard['name']."'s Team");

                                // Send the message
                                $eml->send();

                                // Check if the message was sent
                                if($eml->status()){

                                    // Save the message
                                    $eml->save();

                                    // Retrieve the final user
                                    $message['data']['record'] = $this->Model->Users->get($userId);
                                } else {
                                    $message = ["status" => 500, "message" => "Internal Server Error", "data" => "An error occurred while sending the email."];
                                }
                            } else {
                                $message = ["status" => 500, "message" => "Internal Server Error", "data" => "An error occurred while creating the user."];
                            }
                        } else {
                            $message = ["status" => 500, "message" => "Internal Server Error", "data" => "An error occurred while authenticating to the SMTP Server."];
                        }
                    } else {
                        $message = ["status" => 500, "message" => "Internal Server Error", "data" => "An error occurred while connecting to the SMTP Server."];
                    }
                } else {
                    $message = ["status" => 400, "message" => "Bad Request", "data" => "Some required fields are missing."];
                }
            } else {
                $message = ["status" => 405, "message" => "Method Not Allowed", "data" => "The method is not allowed for the requested URL."];
            }
        }

        return $message;
    }
}
