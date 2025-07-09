<?php

// Import additionnal class into the global namespace
use \LaswitchTech\Core\Base\BaseModel;

class UsersModel extends BaseModel {

    /**
     * Constructor
     */
    public function __construct()
    {
        // Call the parent constructor
        parent::__construct();

        // Initialize the Model
        $this->init('users');
    }

    /**
     * Retrieve multiple records
     *
     * @param array $conditions
     * @return array
     */
    public function fetchAll(array $conditions = [], string $conjunction = 'AND'): array
    {
        // Create the Query
        $Query = $this->Database->query()
            ->table($this->table)
            ->select('*')
            ->join('owner', 'users', 'username')
            ->join('vcard', 'vcards', 'id')
            ->join('organization', 'organizations', 'id')
            ->index($this->primary)
            ->filter()
            ->where('id', 9999, '<>')
            ->where('organization', $this->Auth->user()->organization()->id);

        // Check if the conditions are empty
        if(!empty($conditions)){

            // Add a Filter
            $Query->filter();

            // Add the Conditions
            foreach($conditions as $key => $condition){

                // Check if the key exists in the definition
                if(!array_key_exists($condition['key'], $this->definition)){

                    // Remove the key from the data
                    unset($conditions[$key]);
                    continue;
                }

                // Add the condition to the Query
                $Query->where($condition["key"], $condition["value"], $condition["operator"], $conjunction);
            }
        }

        // Retrieve the Results
        $records = $Query->fetch();

        // Loop through the records to process them
        foreach($records as $key => $record){

            // Overwrite the record with the processed one
            $records[$key] = $this->process($record);
        }

        // Return the Results
        return $records;
    }

    /**
     * Retrieve a single record
     *
     * @param int $id
     * @return array
     */
    public function fetch(int $id): array
    {
        // Create the Query
        $Query = $this->Database->query()
            ->table($this->table)
            ->select('*')
            ->join('owner', 'users', 'username')
            ->join('backend', 'backends', 'id')
            ->join('session', 'sessions', 'id')
            ->join('vcard', 'vcards', 'id')
            ->join('organization', 'organizations', 'id')
            ->join('organization.vcard', 'vcards', 'id')
            ->join('pin', 'pins', 'id')
            ->join('token', 'tokens', 'id')
            ->filter()
            ->where('id', 9999, '<>')
            ->filter()
            ->where($this->primary, $id)
            ->limit(1);

        // Retrieve the record
        $records = $Query->fetch();

        // Loop through the records to process them
        foreach($records as $key => $record){

            // Overwrite the record with the processed one
            $records[$key] = $this->process($record);
        }

        // Return the record or an empty array if not found
        return $records[array_key_first($records)] ?? [];
    }

    /**
     * Create a record
     *
     * @param int $id
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        // Check if the username already exists
        if(array_key_exists('username', $data) && !empty($data['username'])){

            // Check if the username already exists in the database
            $existingUser = $this->Database->query()
                ->table($this->table)
                ->select('id')
                ->where('username', $data['username'])
                ->limit(1)
                ->fetch();

            // If a user with the same username exists, throw an exception
            if(!empty($existingUser)){
                return 0;
            }
        }

        // Call the parent create method
        return parent::create($data);
    }
}
