<?php

class DBSync {
    private $dom = null;

    /*
     * Constructor.
     */
    public function __construct() {
    }

    /*
     * Destructor.
     */
    public function __destruct() {
        $this->dom = null;
    }

    /*
     * Load one document and parse queries.
     */
    private function loadDocument($path) {
        $this->dom = null;
        if (file_exists($path)) {
            $this->dom = new DOMDocument();
            $this->dom->load($path);
            echo "Document at $path has been loaded\n";
            foreach ($this->getQueries() as $q) {
                // Execute query here
            }
        }
    }

    /*
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

    /*
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
}

$dbsync = new DBSync();
$dbsync->updateSchema("system/DSL/update/");
?>
