<?php

/**
 * Core Framework - UsersModel
 *
 * @license    MIT (https://mit-license.org/)
 * @author     Louis Ouellet <louis@laswitchtech.com>
 */

// Import additionnal class into the global namespace
use \LaswitchTech\Core\Abstracts\Model;

class UsersModel extends Model {

    /**
     * Retrieve the list of Users
     *
     * @return array
     */
    public function list(): array
    {
        // Retrieve the Users
        $Query = $this->Database->query()
            ->table('users')
            ->select('*')
            ->join('owner', 'users', 'username')
            ->join('vcard', 'vcards', 'id')
            ->where('id', 9999, '<>');

        // Fetch the Users
        $users = $Query->fetch();

        // Return the Users
        return $users;
    }

    /**
     * Retrieve Users's Details
     *
     * @param int $id
     * @param bool $all
     * @return array
     */
    public function get(int $id, bool $all = true): array
    {
        // Retrieve the User
        $Query = $this->Database->query()
            ->table('users')
            ->select('*')
            ->join('owner', 'users', 'username')
            ->join('organization', 'organizations', 'id')
            ->where('id', $id)
            ->where('id', 9999, '<>')
            ->limit(1);

        // Fetch the Users
        $users = $Query->fetch();

        // Loop through the Users
        foreach($users as $key => $user){

            // Decode JSON Fields
            $user['users'] = json_decode($user['users'] ?? '[]', true);

            // Check if all the details should be retrieved
            if($all){

                // Retrieve the vCard
                $Query = $this->Database->query()
                    ->table('vcards')
                    ->select('*')
                    ->join('state', 'states', 'code')
                    ->join('country', 'countries', 'code')
                    ->join('avatar', 'files', 'id')
                    ->where('id', $user['vcard'])
                    ->limit(1);
                $user['vcard'] = $Query->result()[0] ?? $user['vcard'];

                // Retrieve the vCard
                $Query = $this->Database->query()
                    ->table('vcards')
                    ->select('*')
                    ->join('avatar', 'files', 'id')
                    ->where('id', $user['organization']['vcard'])
                    ->limit(1);
                $user['organization']['vcard'] = $Query->result()[0] ?? $user['organization']['vcard'];

                // Retrieve the Events
                $Query = $this->Database->query()
                    ->table('events')
                    ->select('*')
                    ->filter()
                    ->where('owner', $user['username'])
                    ->filter('OR')
                    ->where('message', '%'.$user['username'].'%', 'LIKE')
                    ->filter('OR')
                    ->where('targetTable', 'users')
                    ->where('targetId', $user['id'])
                    ->index('id');
                $user['events'] = $Query->result();

                // Retrieve the Files
                $Query = $this->Database->query()
                    ->table('files')
                    ->select('*')
                    ->join('owner', 'users', 'username')
                    ->filter()
                    ->where('targetTable', 'users')
                    ->where('targetId', $user['id'])
                    ->index('id');
                $user['files'] = $Query->result();

                // Retrieve the Notes
                $Query = $this->Database->query()
                    ->table('notes')
                    ->select('*')
                    ->join('owner', 'users', 'username')
                    ->filter()
                    ->where('targetTable', 'users')
                    ->where('targetId', $user['id'])
                    ->index('id');
                foreach($Query->result() as $note){
                    $note['sharedWith'] = json_decode($note['sharedWith'] ?? '[]', true);
                    $user['notes'][$note['id']] = $note;
                }

                // Retrieve the Contacts
                $Query = $this->Database->query()
                    ->table('contacts')
                    ->select('*')
                    ->join('owner', 'users', 'username')
                    ->join('vcard', 'vcards', 'id')
                    ->filter()
                    ->where('targetTable', 'users')
                    ->where('targetId', $user['id'])
                    ->index('id');
                $user['contacts'] = $Query->result();
            }

            // Save the User
            return $user;
        }

        // Return the User
        return [];
    }

    /**
     * Update a user
     *
     * @param int $id
     * @param array $data
     * @return int
     */
    public function update(int $id, array $data): int
    {
        // Create the Query
        $Query = $this->Database->query()
            ->table('users')
            ->update($data)
            ->where('id', $id);

        // Execute the Query
        return $Query->execute();
    }

    /**
     * Create a new user and return the id
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        // Import Global Variables
        global $UUID;

        // Sanitize the Data
        foreach($data as $key => $value){
            if(empty($value)){
                unset($data[$key]);
            }
        }

        // Initialize the Affected Rows
        $affected = 0;

        // Create the Backend
        $backend = [];
        $schema = $this->Database->schema()->define('backends')->describe();
        foreach($schema as $column => $definition){
            $value = $this->sanitize($definition['Field'], $data);
            if($value){
                $backend[$definition['Field']] = $value;
            }
        }
        $Query = $this->Database->query()->table('backends')->insert($backend);
        $affected += $Query->execute();
        $backendId = $Query->lastId();
        $data['backend'] = $backendId;

        // Create the vCard
        $vcard = [];
        $schema = $this->Database->schema()->define('vcards')->describe();
        foreach($schema as $column => $definition){
            $value = $this->sanitize($definition['Field'], $data);
            if($value){
                $vcard[$definition['Field']] = $value;
            }
        }
        $Query = $this->Database->query()->table('vcards')->insert($vcard);
        $affected += $Query->execute();
        $vcardId = $Query->lastId();
        $data['vcard'] = $vcardId;

        // Create the Token
        $token = ["hash" => $this->sanitize("hash", $data)];
        $schema = $this->Database->schema()->define('tokens')->describe();
        foreach($schema as $column => $definition){
            $value = $this->sanitize($definition['Field'], $data);
            if($value){
                $token[$definition['Field']] = $value;
            }
        }
        $Query = $this->Database->query()->table('tokens')->insert($token);
        $affected += $Query->execute();
        $tokenId = $Query->lastId();
        $data['token'] = $tokenId;

        // Create the User
        $user = [];
        $schema = $this->Database->schema()->define('users')->describe();
        foreach($schema as $column => $definition){
            $value = $this->sanitize($definition['Field'], $data);
            if(!is_null($value) && !empty($value)){
                $user[$definition['Field']] = $value;
            }
        }
        $Query = $this->Database->query()->table('users')->insert($user);
        $affected += $Query->execute();
        $userId = $Query->lastId();
        $token['user'] = $userId;

        // Update the token
        $Query = $this->Database->query()->table('tokens')->update($token)->where('id', $tokenId);
        $affected += $Query->execute();

        // Update the organization to add the user
        $Query = $this->Database->query()->table('organizations')->select('*')->where('id', $data['organization'] ?? null)->limit(1);
        $organization = $Query->fetch()[0] ?? [];
        if(!empty($organization)){
            $users = json_decode($organization['users'] ?? '[]', true);
            $users[] = $userId;
            $Query = $this->Database->query()->table('organizations')->update(['users' => json_encode($users, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)])->where('id', $data['organization']);
            $affected += $Query->execute();
        }

        return $userId;
    }

    /**
     * Sanitize the Data
     *
     * @param string $column
     * @param array $data
     * @return mixed
     */
    private function sanitize(string $column, array $data): mixed
    {
        // Import Global Variables
        global $UUID;

        // Initialize the value
        $value = null;

        // Sanitize the Data
        switch($column){
            case 'category':
                if(isset($data[$column])){
                    $value = $data[$column];
                } else {
                    $value = "User";
                }
                break;
            case 'type':
                if(isset($data[$column])){
                    $value = $data[$column];
                } else {
                    $value = "local";
                }
                break;
            case 'hash':
                if(isset($data[$column])){
                    $value = $data[$column];
                } else {
                    $value = password_hash($UUID->toString($data['username'] ?? ($data['email'] ?? '')), PASSWORD_DEFAULT);
                }
                break;
            case 'email':
            case 'username':
                if(isset($data['email']) || isset($data['email'])){
                    $value = $data['username'] ?? ($data['email'] ?? '');
                }
                break;
            case 'password':
                if(isset($data[$column])){
                    $value = password_hash($data[$column], PASSWORD_DEFAULT);
                }
                break;
            default:
                if(isset($data[$column])){
                    $value = $data[$column];
                }
                break;
        }

        return $value ?? null;
    }
}
