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
     * Apply Joins to the Query
     *
     * @param Query $Query
     * @return Query
     */
    protected function joins(object $Query): object
    {
        // Apply Joins
        $Query->join('vcard', 'vcards', 'id');

        return $Query;
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
            ->join('owner.vcard', 'vcards', 'id')
            ->join('backend', 'backends', 'id')
            ->join('session', 'sessions', 'id')
            ->join('pin', 'pins', 'id')
            ->join('token', 'tokens', 'id')
            ->filter()
            ->where('id', 9999, '<>')
            ->filter()
            ->where($this->primary, $id)
            ->limit(1);

        // Set the Owner
        if(array_key_exists('organization',$this->definition)){
            $Query->join('organization', 'organizations', 'id')
                ->join('organization.vcard', 'vcards', 'id')
                ->where('organization', $this->Auth->user()->organization()->id);
        }

        // Apply Joins
        $Query = $this->joins($Query);

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
