<?php

// Import additionnal class into the global namespace
use \LaswitchTech\Core\Base\BaseEndpoint;

class UsersEndpoint extends BaseEndpoint {

    /**
     * Constructor
     */
    public function __construct()
    {
        // Call the parent constructor
        parent::__construct();

        // Initialize the Endpoint
        $this->init('users');

        // Set Properties
        $this->required = ['username'];
        $this->optional = ['name', 'locale','email','phone','tollfree','mobile','fax','tags','dba','industries','businessNumber','taxExtension','importerExtension','website','address','city','country','state','zipcode'];
    }

    /**
     * Retrieve a record
     */
    public function fetchAction(): array
    {
        // Call the parent constructor
        $message = parent::fetchAction();

        // Check if the records is accessible
        if($message['status'] == 200){

            // Check if the vCards Plugin is accessible
            if($this->Helper->Core->isInstalled('vcards')){
                $message['data']['record']['vcard'] = $this->Model->Vcards->fetch(intval($message['data']['record']['vcard']['id']));
            }

            // Check if the Relationship Plugin is accessible
            if($this->Helper->Core->isInstalled('relationship')){
                $message['data']['dependencies']['relationship'] = $this->Model->Relationship->get($this->basename, $message['data']['record']['id']);
                if($this->Helper->Core->isInstalled('vcards') && array_key_exists('vcard', $message['data']['record'])){
                    $message['data']['dependencies']['relationship'] = array_merge(
                        $message['data']['dependencies']['relationship'],
                        $this->Model->Relationship->get('vcards', $message['data']['record']['vcard']['id'])
                    );
                }
            }

            // Check if the Contacts is accessible
            if($this->Helper->Core->isInstalled('contacts')){
                $message['data']['dependencies']['contacts'] = $this->Model->Contacts->fetchAll([
                    ["key" => "targetTable", "operator" => "=", "value" => $this->basename],
                    ["key" => "targetId", "operator" => "=", "value" => $message['data']['record']['id']],
                    ["key" => "isArchived", "operator" => "<>", "value" => 1],
                ]);
            }

            // Check if the Events is accessible
            if($this->Helper->Core->isInstalled('event')){
                $message['data']['dependencies']['event'] = $this->Model->Event->fetchAll([
                    ["key" => "targetTable", "operator" => "=", "value" => $this->basename],
                    ["key" => "targetId", "operator" => "=", "value" => $message['data']['record']['id']],
                    ["key" => "isArchived", "operator" => "<>", "value" => 1],
                ]);
                // $message['data']['dependencies']['event'] = array_merge(
                //     $message['data']['dependencies']['event'],
                //     $this->Model->Event->fetchAll([
                //         ["key" => "owner", "operator" => "=", "value" => $message['data']['record']['username']],
                //     ])
                // );
                // usort($message['data']['dependencies']['event'], function($a, $b) {
                //     return strtotime($b['created']) - strtotime($a['created']);
                // });
            }

            // Check if the Files is accessible
            if($this->Helper->Core->isInstalled('files')){
                $message['data']['dependencies']['files'] = $this->Model->Files->fetchAll([
                    ["key" => "targetTable", "operator" => "=", "value" => $this->basename],
                    ["key" => "targetId", "operator" => "=", "value" => $message['data']['record']['id']],
                    ["key" => "isArchived", "operator" => "<>", "value" => 1],
                ]);
            }

            // Check if the Documents is accessible
            if($this->Helper->Core->isInstalled('documents')){
                $message['data']['dependencies']['documents'] = $this->Model->Documents->fetchAll([
                    ["key" => "targetTable", "operator" => "=", "value" => $this->basename],
                    ["key" => "targetId", "operator" => "=", "value" => $message['data']['record']['id']],
                    ["key" => "isArchived", "operator" => "<>", "value" => 1],
                ]);
            }

            // Check if the Notes is accessible
            if($this->Helper->Core->isInstalled('notes')){
                $message['data']['dependencies']['notes'] = $this->Model->Notes->fetchAll([
                    ["key" => "targetTable", "operator" => "=", "value" => $this->basename],
                    ["key" => "targetId", "operator" => "=", "value" => $message['data']['record']['id']],
                    ["key" => "isArchived", "operator" => "<>", "value" => 1],
                ]);
            }

            // Check if the Inventory is accessible
            if($this->Helper->Core->isInstalled('inventory')){
                $message['data']['dependencies']['inventory'] = $this->Model->Inventory->fetchAll([
                    ["key" => "targetTable", "operator" => "=", "value" => $this->basename],
                    ["key" => "targetId", "operator" => "=", "value" => $message['data']['record']['id']],
                    ["key" => "isArchived", "operator" => "<>", "value" => 1],
                ]);
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Create a record
     */
    public function createAction(): array
    {
        // Import Global Variables
        global $UUID, $SMTP, $BUILDER;

        // Retrieve the username
        $username = $this->Request->getParams('REQUEST','username');

        // Check for a duplicate username
        $users = $this->Model->Users->fetchAll([["key" => "username","operator" => "=","value" => $username]]);

        // Check the count of users
        if(count($users) > 0){

            // Return an error message
            return ['status' => 400,'message' => 'Bad Request','data' => 'The username is already in use.'];
        }

        // Call the parent constructor
        $message = parent::createAction();

        // Check if the record is accessible
        if($message['status'] == 200){

            // Retrieve the parameters
            $parameters = $message['data']['parameters'];

            // Initialize the fields array
            $fields = [];

            // Check if the Event Plugin is accessible
            if($this->Helper->Core->isInstalled('event')){

                // Initialize the Events
                $message['data']['event'] = [];

                // Setup a new event
                $event = [
                    'category' => 'User',
                    'message' => 'New User Created by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                    'icon' => 'circle',
                    'color' => 'secondary',
                    'link' => '/plugin/users/details?id='.$message['data']['record']['id'],
                    'targetTable' => 'users',
                    'targetId' => $message['data']['record']['id'],
                ];

                // Create the event
                $message['data']['event'][] = $this->Model->Event->create($event);
            }

            // Check if the vCards Plugin is accessible
            if($this->Helper->Core->isInstalled('vcards')){

                // Initialize the record
                $record = $parameters;

                // Set the vCard category
                $record['category'] = 'User';
                $record['locale'] = isset($record['locale']) ? $record['locale'] : $this->Locale->current();

                // Create the vCard
                $fields['vcard'] = $this->Model->Vcards->create($record);

                // Check if the Event Plugin is accessible
                if($this->Helper->Core->isInstalled('event')){

                    // Setup a new event
                    $event = [
                        'category' => 'vCard',
                        'message' => 'New vCard Created by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                        'icon' => 'circle',
                        'color' => 'secondary',
                        'link' => '/plugin/users/details?id='.$message['data']['record']['id'],
                        'targetTable' => 'users',
                        'targetId' => $message['data']['record']['id'],
                    ];

                    // Create the event
                    $message['data']['event'][] = $this->Model->Event->create($event);
                }
            }

            // Check if the Auth Plugin is accessible
            if($this->Helper->Core->isInstalled('auth')){

                // Initialize the record
                $record = $parameters;

                // Generate a random password
                $password = $this->Helper->Auth->generate();

                // Set the Backend
                $record['type'] = 'local';
                $record['password'] = password_hash($password, PASSWORD_DEFAULT);

                // Create the Backend
                $fields['backend'] = $this->Model->Backends->create($record);

                // Check if the Event Plugin is accessible
                if($this->Helper->Core->isInstalled('event')){

                    // Setup a new event
                    $event = [
                        'category' => 'Backend',
                        'message' => 'New Backend Created by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                        'icon' => 'circle',
                        'color' => 'secondary',
                        'link' => '/plugin/users/details?id='.$message['data']['record']['id'],
                        'targetTable' => 'users',
                        'targetId' => $message['data']['record']['id'],
                    ];

                    // Create the event
                    $message['data']['event'][] = $this->Model->Event->create($event);
                }
            }

            // Check if the Tokens Plugin is accessible
            if($this->Helper->Core->isInstalled('tokens')){

                // Initialize the record
                $record = $parameters;

                // Generate an UUID
                $token = $UUID->toString($message['data']['record']['username']);

                // Set the Token
                $record['hash'] = password_hash($token, PASSWORD_DEFAULT);
                $record['user'] = $message['data']['record']['id'];

                // Create the Token
                $fields['token'] = $this->Model->Tokens->create($record);

                // Check if the Event Plugin is accessible
                if($this->Helper->Core->isInstalled('event')){

                    // Setup a new event
                    $event = [
                        'category' => 'Token',
                        'message' => 'New Token Created by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                        'icon' => 'circle',
                        'color' => 'secondary',
                        'link' => '/plugin/users/details?id='.$message['data']['record']['id'],
                        'targetTable' => 'users',
                        'targetId' => $message['data']['record']['id'],
                    ];

                    // Create the event
                    $message['data']['event'][] = $this->Model->Event->create($event);
                }
            }

            // Check if the Pins Plugin is accessible
            if($this->Helper->Core->isInstalled('pins')){

                // Initialize the record
                $record = $parameters;

                // Generate a Pin
                $pin = $this->Helper->Auth->generate(6, true);

                // Set the Pin
                $record['hash'] = password_hash($pin, PASSWORD_DEFAULT);
                $record['user'] = $message['data']['record']['id'];

                // Create the Pin
                $fields['pin'] = $this->Model->Pins->create($record);

                // Check if the Event Plugin is accessible
                if($this->Helper->Core->isInstalled('event')){

                    // Setup a new event
                    $event = [
                        'category' => 'Pin',
                        'message' => 'New Pin Created by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                        'icon' => 'circle',
                        'color' => 'secondary',
                        'link' => '/plugin/users/details?id='.$message['data']['record']['id'],
                        'targetTable' => 'users',
                        'targetId' => $message['data']['record']['id'],
                    ];

                    // Create the event
                    $message['data']['event'][] = $this->Model->Event->create($event);
                }
            }

            // Check if the Organizations Plugin is accessible
            if($this->Helper->Core->isInstalled('organizations')){

                // Retrieve the organization
                $organization = $this->Model->Organizations->fetch($this->Auth->user()->organization()->id);

                // Add the user to the organization
                $organization['users'][] = $message['data']['record']['id'];

                // Filter the users to remove duplicates
                $organization['users'] = array_unique($organization['users']);

                // Update the organization
                $affectedRows = $this->Model->Organizations->update($organization['id'], ['users' => $organization['users']]);

                // Check if the Organization was updated
                if($affectedRows && $this->Helper->Core->isInstalled('event')){

                    // Setup a new event
                    $event = [
                        'category' => 'User',
                        'message' => 'User <vcard>'.$message['data']['record']['vcard']['id'].':'.$message['data']['record']['vcard']['name'].'</vcard> added to Organization <vcard>'.$organization['id'].':'.$organization['vcard']['name'].'</vcard> by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                        'icon' => 'circle',
                        'color' => 'secondary',
                        'link' => '/plugin/organizations/details?id='.$organization['id'].'&name='.urlencode($organization['vcard']['name']),
                        'targetTable' => 'organizations',
                        'targetId' => $organization['id'],
                    ];

                    // Create the event
                    $message['data']['event'][] = $this->Model->Event->create($event);
                }
            }

            // Check if $fields is empty
            if(!empty($fields)){
                $affectedRows = $this->Model->Users->update($message['data']['record']['id'], $fields);

                // Check if we send out the notification
                if($affectedRows){

                    // Retrieve the updated record
                    $message['data']['record'] = $this->Model->Users->fetch($message['data']['record']['id']);

                    // Connect to the smtp server
                    $SMTP->connect();

                    // Check if the smtp server is connected
                    if($SMTP->isConnected()){

                        // Authenticate to the SMTP Server
                        $SMTP->authenticate();

                        // Check if the SMTP Server is authenticated
                        if($SMTP->isAuthenticated()){

                            // Write the email
                            $body = '';
                            $body .= '<p>Welcome to '.$message['data']['record']['organization']['vcard']['name'].'!</p>';
                            $body .= '<p>Your account has been created and is ready to use.</p>';
                            $body .= '<p>Here is your account password:</p>';
                            $body .= '<pre style="background-color: #F5F5F5; font-weight: 700; font-size: 28px; text-align: center; letter-spacing: 16px; margin: 20px 20px; padding: 20px 0; font-family: Courier, monospace">'.($password ?? 'ERROR!').'</pre>';
                            $body .= '<p>Please follow the link below to access %BRAND%.</p>';
                            $body .= '<p style="text-align:center;margin-top: 40px;margin-bottom:40px;">';
                            $body .= '<a href="'.$this->Request->getHostAddress().'" target="_blank" style="margin-left: 6px; margin-right: 6px; text-decoration:none; background-color: #528fb3;color: #fff;font-size: 24px;padding: 20px 40px;text-align: center;margin: 20px 20px;border-radius: 8px;">%BRAND%</a>';
                            $body .= '</p>';
                            $body .= '<p>Thank you for choosing '.$message['data']['record']['organization']['vcard']['name'].'!</p>';

                            // Create a new message
                            $eml = $SMTP->message()
                                ->to($message['data']['record']['vcard']['email'])
                                ->from($message['data']['record']['organization']['vcard']['email'] ?? $this->Config->get('smtp','username'))
                                ->subject('Welcome to '.$message['data']['record']['organization']['vcard']['name'])
                                ->body($body)
                                ->var('logo', 'data:'.mime_content_type($this->Config->root() . '/webroot' . $BUILDER->logo()).';base64,' . base64_encode(file_get_contents($this->Config->root() . '/webroot' . $BUILDER->logo())))
                                ->var('brand', $this->Config->get('application','name'))
                                ->var('greetings', "Sincerely,<br>".$message['data']['record']['organization']['vcard']['name']."'s Team");

                            // Send the message
                            $eml->send();

                            // Check if the message was sent
                            if($eml->status()){

                                // Save the message
                                $eml->save();
                            }
                        }
                    }
                }
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Update a record
     */
    public function updateAction(): array
    {
        // Call the parent constructor
        $message = parent::updateAction();

        // Check if the record is accessible
        if($message['status'] == 200){

            // Check if the Event Plugin is accessible
            if($this->Helper->Core->isInstalled('event')){

                // Initialize the Events
                $message['data']['event'] = [];

                // Setup a new event
                $event = [
                    'category' => 'User',
                    'message' => 'User Updated for <vcard>'.$message['data']['record']['vcard']['id'].':'.$message['data']['record']['vcard']['name'].'</vcard> by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                    'icon' => 'circle',
                    'color' => 'secondary',
                    'link' => '/plugin/users/details?id='.$message['data']['record']['id'].'&name='.urlencode($message['data']['record']['vcard']['name']),
                    'targetTable' => 'users',
                    'targetId' => $message['data']['record']['id'],
                ];

                // Create the event
                $message['data']['event'][] = $this->Model->Event->create($event);
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Delete a record
     */
    public function deleteAction(): array
    {
        // Call the parent constructor
        $message = parent::deleteAction();

        // Check if the record is accessible
        if($message['status'] == 200){

            // Check if the Event Plugin is accessible
            if($this->Helper->Core->isInstalled('event')){

                // Initialize the Events
                $message['data']['event'] = [];

                // Setup a new event
                $event = [
                    'category' => 'User',
                    'message' => 'User Deleted for <vcard>'.$message['data']['record']['vcard']['id'].':'.$message['data']['record']['vcard']['name'].'</vcard> by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                    'icon' => 'circle',
                    'color' => 'secondary',
                    'link' => '/plugin/users/details?id='.$message['data']['record']['id'].'&name='.urlencode($message['data']['record']['vcard']['name']),
                    'targetTable' => 'users',
                    'targetId' => $message['data']['record']['id'],
                ];

                // Create the event
                $message['data']['event'][] = $this->Model->Event->create($event);
            }

            // Check if the vCards Plugin is accessible
            if($this->Helper->Core->isInstalled('vcards')){

                // Delete the vCard
                $affectedRows = $this->Model->Vcards->delete($message['data']['record']['vcard']['id']);

                // Check if the Event Plugin is accessible
                if($affectedRows && $this->Helper->Core->isInstalled('event')){

                    // Setup a new event
                    $event = [
                        'category' => 'vCard',
                        'message' => 'vCard Deleted by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                        'icon' => 'circle',
                        'color' => 'secondary',
                        'link' => '/plugin/users/details?id='.$message['data']['record']['id'],
                        'targetTable' => 'users',
                        'targetId' => $message['data']['record']['id'],
                    ];

                    // Create the event
                    $message['data']['event'][] = $this->Model->Event->create($event);
                }
            }

            // Check if the Backends Plugin is accessible
            if($this->Helper->Core->isInstalled('backends')){

                // Delete the Backend
                $affectedRows = $this->Model->Backends->delete($message['data']['record']['backend']['id']);

                // Check if the Event Plugin is accessible
                if($affectedRows && $this->Helper->Core->isInstalled('event')){

                    // Setup a new event
                    $event = [
                        'category' => 'Backend',
                        'message' => 'Backend Deleted by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                        'icon' => 'circle',
                        'color' => 'secondary',
                        'link' => '/plugin/users/details?id='.$message['data']['record']['id'],
                        'targetTable' => 'users',
                        'targetId' => $message['data']['record']['id'],
                    ];

                    // Create the event
                    $message['data']['event'][] = $this->Model->Event->create($event);
                }
            }

            // Check if the Tokens Plugin is accessible
            if($this->Helper->Core->isInstalled('tokens')){

                // Delete the Token
                $affectedRows = $this->Model->Tokens->delete($message['data']['record']['token']['id']);

                // Check if the Event Plugin is accessible
                if($affectedRows && $this->Helper->Core->isInstalled('event')){

                    // Setup a new event
                    $event = [
                        'category' => 'Token',
                        'message' => 'Token Deleted by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                        'icon' => 'circle',
                        'color' => 'secondary',
                        'link' => '/plugin/users/details?id='.$message['data']['record']['id'],
                        'targetTable' => 'users',
                        'targetId' => $message['data']['record']['id'],
                    ];

                    // Create the event
                    $message['data']['event'][] = $this->Model->Event->create($event);
                }
            }

            // Check if the Pins Plugin is accessible
            if($this->Helper->Core->isInstalled('pins')){

                // Delete the Pin
                $affectedRows = $this->Model->Pins->delete($message['data']['record']['pin']['id']);

                // Check if the Event Plugin is accessible
                if($affectedRows && $this->Helper->Core->isInstalled('event')){

                    // Setup a new event
                    $event = [
                        'category' => 'Pin',
                        'message' => 'Pin Deleted by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                        'icon' => 'circle',
                        'color' => 'secondary',
                        'link' => '/plugin/users/details?id='.$message['data']['record']['id'],
                        'targetTable' => 'users',
                        'targetId' => $message['data']['record']['id'],
                    ];

                    // Create the event
                    $message['data']['event'][] = $this->Model->Event->create($event);
                }
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Archive a record
     */
    public function archiveAction(): array
    {
        // Call the parent constructor
        $message = parent::archiveAction();

        // Check if the record is accessible
        if($message['status'] == 200){

            // Check if the Event Plugin is accessible
            if($this->Helper->Core->isInstalled('event')){

                // Initialize the Events
                $message['data']['event'] = [];

                // Setup a new event
                $event = [
                    'category' => 'User',
                    'message' => 'User Archived by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                    'icon' => 'circle',
                    'color' => 'secondary',
                    'link' => '/plugin/users/details?id='.$message['data']['record']['id'],
                    'targetTable' => 'users',
                    'targetId' => $message['data']['record']['id'],
                ];

                // Create the event
                $message['data']['event'][] = $this->Model->Event->create($event);
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Recover a record
     */
    public function recoverAction(): array
    {
        // Call the parent constructor
        $message = parent::recoverAction();

        // Check if the record is accessible
        if($message['status'] == 200){

            // Check if the Event Plugin is accessible
            if($this->Helper->Core->isInstalled('event')){

                // Initialize the Events
                $message['data']['event'] = [];

                // Setup a new event
                $event = [
                    'category' => 'User',
                    'message' => 'User Recovered by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                    'icon' => 'circle',
                    'color' => 'secondary',
                    'link' => '/plugin/users/details?id='.$message['data']['record']['id'],
                    'targetTable' => 'users',
                    'targetId' => $message['data']['record']['id'],
                ];

                // Create the event
                $message['data']['event'][] = $this->Model->Event->create($event);
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Reset a record password
     */
    public function resetAction(): array
    {
        // Import Global Variables
        global $SMTP, $BUILDER;

        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Retrieve the username
        $username = $this->Request->getParams('REQUEST','username');

        // Check if the username is provided
        if(empty($username) || is_null($username)){
            $message = ['status' => 400,'message' => 'Bad Request','data' => 'The username is required.'];
        }

        // Check if the record is accessible
        if($message['status'] == 200){

            // Check for a duplicate username
            $users = $this->Model->Users->fetchAll([["key" => "username","operator" => "=","value" => $username]]);

            // Check the count of users
            if(count($users) > 0){

                // Select the user
                $user = $users[array_key_first($users)];

                // Generate a random password
                $password = $this->Helper->Auth->generate();

                // Update the Backend password
                if($this->Helper->Core->isInstalled('auth')){

                    // Check if the user has a backend
                    if(is_null($user['backend'])){

                        // Initialize the backend
                        $backend = [];

                        // Set the Backend
                        $backend['type'] = 'local';
                        $backend['password'] = password_hash($password, PASSWORD_DEFAULT);

                        // Create the Backend
                        $user['backend'] = $this->Model->Backends->create($backend);

                        // Check if the backend was created
                        if($user['backend']){

                            // Update the user with the new backend
                            $this->Model->Users->update($user['id'], ['backend' => $user['backend']]);

                            // Check if the Event Plugin is accessible
                            if($this->Helper->Core->isInstalled('event')){

                                // Setup a new event
                                $event = [
                                    'category' => 'Backend',
                                    'message' => 'New Backend Created by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                                    'icon' => 'circle',
                                    'color' => 'secondary',
                                    'link' => '/plugin/users/details?id='.$user['id'],
                                    'targetTable' => 'users',
                                    'targetId' => $user['id'],
                                ];

                                // Create the event
                                $message['data']['event'][] = $this->Model->Event->create($event);
                            }
                        } else {
                            return ['status' => 500,'message' => 'Internal Server Error','data' => 'The user backend could not be created.'];
                        }
                    }

                    // Retrieve the backend
                    $backend = $this->Model->Backends->fetch($user['backend']);

                    // Update the password
                    if($this->Model->Backends->update($backend['id'], ['password' => password_hash($password, PASSWORD_DEFAULT)])){

                        // Connect to the smtp server
                        $SMTP->connect();

                        // Check if the smtp server is connected
                        if($SMTP->isConnected()){

                            // Authenticate to the SMTP Server
                            $SMTP->authenticate();

                            // Check if the SMTP Server is authenticated
                            if($SMTP->isAuthenticated()){

                                // Write the email
                                $body = '';
                                $body .= '<p>Your account password has been reset.</p>';
                                $body .= '<p>Here is your new account password:</p>';
                                $body .= '<pre style="background-color: #F5F5F5; font-weight: 700; font-size: 28px; text-align: center; letter-spacing: 16px; margin: 20px 20px; padding: 20px 0; font-family: Courier, monospace">'.($password ?? 'ERROR!').'</pre>';
                                $body .= '<p>Please follow the link below to access %BRAND%.</p>';
                                $body .= '<p style="text-align:center;margin-top: 40px;margin-bottom:40px;">';
                                $body .= '<a href="'.$this->Request->getHostAddress().'" target="_blank" style="margin-left: 6px; margin-right: 6px; text-decoration:none; background-color: #528fb3;color: #fff;font-size: 24px;padding: 20px 40px;text-align: center;margin: 20px 20px;border-radius: 8px;">%BRAND%</a>';
                                $body .= '</p>';
                                $body .= '<p>If you did not request this change, please contact your system administrator immediately.</p>';

                                // Create a new message
                                $eml = $SMTP->message()
                                    ->to($user['username'])
                                    ->from($user['organization']['vcard']['email'] ?? $this->Config->get('smtp','username'))
                                    ->subject('Your account password has been reset')
                                    ->body($body)
                                    ->var('logo', 'data:'.mime_content_type($this->Config->root() . '/webroot' . $BUILDER->logo()).';base64,' . base64_encode(file_get_contents($this->Config->root() . '/webroot' . $BUILDER->logo())))
                                    ->var('brand', $this->Config->get('application','name'))
                                    ->var('greetings', "Sincerely,<br>".$user['organization']['vcard']['name']."'s Team");

                                // Send the message
                                $eml->send();

                                // Check if the message was sent
                                if($eml->status()){

                                    // Save the message
                                    $eml->save();

                                    // Set the success message
                                    $message['data']['message'] = 'The password reset email has been sent.';
                                } else {
                                    $message = ['status' => 500,'message' => 'Internal Server Error','data' => 'The password reset email could not be sent.'];
                                }
                            } else {
                                $message = ['status' => 500,'message' => 'Internal Server Error','data' => 'Could not authenticate to the SMTP server.'];
                            }
                        } else {
                            $message = ['status' => 500,'message' => 'Internal Server Error','data' => 'Could not connect to the SMTP server.'];
                        }
                    } else {
                        $message = ['status' => 500,'message' => 'Internal Server Error','data' => 'The user password could not be updated.'];
                    }
                } else {
                    $message = ['status' => 500,'message' => 'Internal Server Error','data' => 'The Backends Plugin is not installed.'];
                }
            } else {
                $message = ['status' => 404,'message' => 'Not Found','data' => 'The username does not exist.'];
            }
        }

        // Return the message
        return $message;
    }
}
