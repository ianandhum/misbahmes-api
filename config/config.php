<?php
//DataBase Connections
require_once("constantConfig.php");
class DB
{

    private $host = DB_HOST_NAME;
    private $db_name = DB_NAME;
    private $username = USER_DB_USER_NAME;
    private $password = USER_DB_USER_PASSWORD;
    private $lastError=null;
    public $dbConn;

    public function __construct($db) {
    	$this->db_name=$db;
    }
    public function dbConnection()
	{

	    $this->dbConn = null;
        try
		{
            $this->dbConn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password,array(PDO::MYSQL_ATTR_FOUND_ROWS => true));
			$this->dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
		catch(PDOException $exception)
		{
            $e=new ErrorMsg("ECONN");
            $this->lastError=$e->toString($exception->getMessage());
            return false;
      }

      return $this->dbConn;


    }
    //to be used only at critical points of data fetch
    public function getData($tab,$col,$id,$value,$lt="20") {
    		$query="SELECT $col FROM $tab WHERE $id = :value  LIMIT  $lt";
    		$stmt=$this->dbConn->prepare($query);
    		$stmt->binparam(":value",$value);
			$stmt->execute();
			if($stmt) {
				$userRow=$stmt->fetchAll(PDO::FETCH_BOTH);
				return $userRow;
			}
			return null;
	}
	//To be used only on critical points of data update
	 public function setData($tab,$key,$val,$id,$param) {
    		$query="UPDATE $tab SET $key = :value WHERE $id = :param";
    		$stmt->bindparam(":value",$val);
    		$stmt->bindparam(":param",$param);
    		$stmt=$this->dbConn->prepare($query);
			$res=$stmt->execute();
			if($res) {
				return true;

			}
			return false;
	}

};
//Error generation class
class ErrorMsg{
	private $error;
	private  $errorMsg=array(
						"EAUTH"=>"Incorrect Password",
						"EEMAIL"=>"Could not find your account",
						"EACCESS"=>"Access denied",
						"EPASS"=>"Incorrect password",
						"EUID"=>"Incorrect user name",
						);
	public function __construct($e) {
		if(array_key_exists($e, $this->errorMsg)) {
			$this->error=$this->errorMsg[$e];
		}
		else{
			$this->error=$e;
		}
	}
	public function throwError($e) {
		if(array_key_exists($e, $this->errorMsg)) {
			$this->error=$this->errorMsg[$e];
		}
		else{
			$this->error=$e;
		}
	}
	public function toString($adder=""){
		return "".$this->error." $adder";
	}
};

?>
