<?php
class DbConnect {
    private $conn;
    private $dbh;
    function connect() {
        include_once dirname(__FILE__) . '/Config.php';
        $this->conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        mysqli_query($this->conn,"set character_set_results='utf8'");
        return $this->conn;
    }
	
	function pdoconnect() {
		include_once dirname(__FILE__) . '/Config.php';		
		$this->dbh = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USERNAME, DB_PASSWORD);
        $this->dbh->exec("set names utf8");
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL-PDO: " . mysqli_connect_error();
        }
        return  $this->dbh;	
	}
}
?>
