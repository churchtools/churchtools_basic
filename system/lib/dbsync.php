<?php


//TODO: not sure where to place this. There are statements at end of file!

/**
 * Database Synchronizer class.
 * Used for upgrading the database schema.
 */
class DBSync {
    private $dom = null;
    private $db = null;
    private $tablePrefix = "";
    
    public $MARIADB = 0;
    public $POSTGRESQL = 1;

    /**
     * Constructor.
     */
    public function __construct() {
    }

    /**
     * Destructor.
     */
    public function __destruct() {
        $this->dom = null;
    }
    
    /**
     * Set table prefix. Default is none.
     *
     * @param type $prefix
     */
    public function setTablePrefix($prefix) {
        $this->tablePrefix = $prefix;
    }
    
    /**
     * Connect to the database.
     * Note: Currently only MariaDB supported.
     *
     * @global mysqli
     * @param type $vendor
     * @param type $host
     * @param type $user
     * @param type $password
     * @param type $dbname
     * @return boolean
     */
    public function connect($vendor, $host, $user, $password, $dbname) {
        if ($vendor == $this->MARIADB) {
            $this->db = new mysqli($host, $user, $password, $dbname);
            if ($this->db->connect_errno) {
                return $this->db->connect_errno;
            }
        }
        
        if (!$this->db-set_charset("utf8")) {
            return $this->db->error;
        }

        return null;
    }

    /**
     * Query the database.
     *
     * @param type $template
     * @param type $params
     */
    public function query($template, $params=null) {
        // Prefix around?
        if ($this->tablePrefix) {
            $template = str_replace("}", "", str_replace("{", $this->tablePrefix, $template));
        }

        // Set params into the SQL template
        if ($params != null) {
            foreach ($params as $ref => $value) {
                $value = escape_string($value);
                if (is_string($value)) {
                    $value = "'" . $value . "'";
                }

                $template = str_replace($ref, $value, $template);
            }
        }
        
        // Perform
        $result = $this->db-query($template);
        if (!$result) {
            throw new SQLException("SQL: " . $template . "\nError: " . $this->db->error);
        }
    }


    /**
     * Load one document and parse queries.
     */
    private function loadDocument($path) {
        $this->dom = null;
        if (file_exists($path)) {
            $this->dom = new DOMDocument();
            $this->dom->load($path);
            echo "Document at $path has been loaded\n";
            foreach ($this->getQueries() as $query) {
                // Execute query here
            }
        }
    }

    /**
     * Run sequence on updating schema.
     */
    public function updateSchema($path) {
        if (file_exists($path)) {
            foreach(scandir($path) as $fname) {
                if (!strncmp($fname, ".", 1)) {
                    continue;
                }
                $this->loadDocument(sprintf("%s/%s", $path, $fname));
            }
        }
    }

    /**
     * Load queries from one document.
     */
    private function getQueries() {
        $queries = array();
        foreach($this->dom->getElementsByTagName("query") as $queryNode) {
            foreach($queryNode->childNodes as $cdataNode) {
                if ($cdataNode->nodeType == 4) {
                    $query = trim($queryNode->nodeValue);
                    if ($query) {
                        array_push($queries, $query);
                    }
                }
            }
        }

        return $queries;
    }
    
    /**
     * Check if schema upgrade is required.
     * Returns True or False appropriately.
     */
    public function needsSchemaUpgrade() {
        return false;
    }
}

$dbsync = new DBSync();
$dbsync->updateSchema(SYSTEM."/DSL/update/mariadb/");
