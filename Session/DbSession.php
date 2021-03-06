<?php

/**
 * DbSession will store and retrieve session data from a database table.
 * The data is base64 encoded in case we store binary data inside the session.
 * It uses the same column names as Laravel 4 database session.
 */
class DbSession
{

    /**
     * Holds the PDO database object
     */
    private $db;


    /*
     * Create a new database session and do the session_start() .
     * 
     * 
     * @param PdoDatabaseWrapper $database
     * @return void
     */
    public function __construct($database)
    {
        // Instantiate new Database object
        $this->db = $database;

        // Set handler to overide SESSION
        session_set_save_handler(
                array($this, "open"), 
                array($this, "close"), 
                array($this, "read"), 
                array($this, "write"), 
                array($this, "destroy"), 
                array($this, "gc")
        );
                
        // Start the session
        session_start();
    }

    /**
     * Open session storage.
     * 
     * @return boolean
     */
    public function open()
    {
        // If successful
        if ($this->db) {
            // Return True
            return true;
        }
        // Return False
        return false;
    }

    /**
     * Close session storage.
     * 
     * @return boolean
     */
    public function close()
    {
        return $this->db->close();
    }

    /**
     * Read payload from session.
     * 
     * @param int $id
     * @return mixed
     */
    public function read($id)
    {
        // Set query
        $this->db->query('SELECT payload FROM sessions WHERE id = :id');

        // Bind the Id
        $this->db->bind(':id', $id);

        // Attempt execution
        // If successful
        if ($this->db->execute()) {
            // Save returned row
            $row = $this->db->single();
            // Return the data
            return base64_decode($row['payload']);
        } else {
            // Return an empty string
            return '';
        }
    }

    /**
     * Write to session.
     * 
     * @param int $id
     * @param mixed $payload
     * @return boolean
     */
    public function write($id, $payload)
    {
        // Create time stamp
        $access = time();

        // Set query 
        $this->db->query('REPLACE INTO sessions VALUES (:id, :payload, :access)');

        // Bind data
        $this->db->bind(':id', $id);
        $this->db->bind(':payload', base64_encode($payload));
        $this->db->bind(':access', $access);

        // Attempt Execution
        // If successful
        return $this->db->execute();
    }

    /**
     * Destroy session.
     * 
     * @param int $id
     * @return boolean
     */
    public function destroy($id)
    {
        // Set query
        $this->db->query('DELETE FROM sessions WHERE id = :id');

        // Bind data
        $this->db->bind(':id', $id);

        // Attempt execution
        return $this->db->execute();    
    }

    /**
     * Garbage Collection
     * 
     * @param int $max
     * @return boolean
     */
    public function gc($max)
    {
        // Calculate what is to be deemed old
        $old = time() - $max;

        // Set query
        $this->db->query('DELETE * FROM sessions WHERE last_activity < :old');

        // Bind data
        $this->db->bind(':old', $old);

        // Attempt execution
        return $this->db->execute();
    }
}