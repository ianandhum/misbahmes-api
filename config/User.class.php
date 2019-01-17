<?php
//the global user class for different access levels
//contains
//	::user authentication and access privilege fetch
/////////////////////////////////////////

//config.php
require_once("constantConfig.php");

require_once("config.php");

//class User:a base level class for user login and authentication
//cannot be used for user manipulation

class User{
	//data members

	//user Details
	private  $userId=null;

	private  $userFullName=null;

	private $accessLevel=null;

	private  $userType=null;

	private  $deviceType=null;

	private  $loggedIn=null;

	private $userEmail=null;

	private $loginDevices=array("DEV_WEB_STD","DEV_ANDROID_APP","DEV_DATA_FETCH");

	private $userTable=USER_DB_TABLE;

	public  $lastError;

	public  $dbConn=null;

	private $userTableFields=array("userId","userFullName","userEmail","sessionKey","userPass","accessLevel","userType","loggedIn","deviceType","state","editCode");

	private $sessUID;

	private $sessUName="test_user";

	private  $responseData;

	private $freshLogin=false;
	//ok
	//if arg1 is false then selectUser() should be used to init
	public function __construct($magnetAutoUser=true){
		$this->connectToDB();
		if($magnetAutoUser) {
	 		$this->getClientData();
	 		$this->stdUser();
	 	}
	}
	public function stdUser() {
		$this->selectUser($this->sessUName);
	}
	//ok
	public function selectUser($user=null) {
		if ($user!=null){
			$stmt=$this->dbConn->prepare("SELECT * FROM $this->userTable WHERE userId=:uid LIMIT 1");
			try{
				$stmt->execute(array(":uid" => $user));
			}
			catch(\Exception $e){
				return false;
			}
			if($stmt) {
				$userRow=$stmt->fetch(PDO::FETCH_ASSOC);
				if($userRow['userId']==$user) {
					$this->userFullName=$userRow['userFullName'];
					$this->userId=$userRow['userId'];
					$this->userEmail=$userRow['userEmail'];
					$this->accessLevel=$userRow['accessLevel'];
					$this->userType=$userRow['userType'];
					$this->loggedIn=$userRow['loggedIn'];
					$this->deviceType=$userRow['deviceType'];
				}
			}
			else{
				$e=new ErrorMsg("EUID");
				$this->lastError=$e->toString();

			}
		}
		else{
			$e=new ErrorMsg("EUID");
			$this->lastError=$e->toString();
		}
	}
	public function selectUserByMail($mail) {
		$userRow=$this->getUserFromMail($mail);
		if($userRow) {
			$id=$userRow['userId'];
			$this->selectUser($id);
		}
	}
	//ok
	private function connectToDB() {
		try{
			$db=new DB(DB_NAME);
			$this->dbConn=$db->dbConnection();
		}
		catch(PDOException $e){
			error_log("Connection to database failed");
			exit;
		}
	}
	//tested:FORKS
	public function login($email,$pass,$isAuto=false,$device="DEV_WEB_STD") {
		$userRow=$this->getUserFromMail($email);
		if($userRow["dataRowCount"] === 1){
			$this->deviceType=$userRow['deviceType'];
			if(((($userRow['userPass'] === md5($pass)) && $device==$this->deviceType)|| ($isAuto==true)) && $userRow['state']!="DISABLED") {
							$this->selectUser($userRow['userId']);

							if($this->getUserType()!="admin" && !$isAuto) {
								$error= new ErrorMsg("EPERMISSION");
								$this->lastError=$error->toString();
								return false;
							}
							if ($this->isNaiveUser()) {
								$this->setDataForUser('state','DISABLED');
							}
							$this->setDataForUser("loggedIn","Y");
							$session_key=md5(uniqid(rand()));
							$this->setDataForUser("sessionKey",$session_key);
							$device=$this->deviceType;
							if($device=="DEV_WEB_STD") {
								setcookie(ACTIVE_USER_NAME_IDENTIFIER , $this->userId, time()+60*60*24,"/", $domain = SERVER, $secure = null, $httponly=true);
								setcookie(ACTIVE_USER_TOKEN_IDENTIFIER, $session_key, 0, "/", $domain = SERVER, $secure = null, true);
							}
							else if($device=="DEV_ANDROID_APP") {
								//extension plans to be taken care of
								$this->responseData="{\"".ACTIVE_USER_NAME_IDENTIFIER."\":\"".$this->userId ."\",\"".ACTIVE_USER_TOKEN_IDENTIFIER."\":\"".$session_key ."\"}";
							}
							else if($device=="DEV_DATA_FETCH") {
								setcookie(ACTIVE_USER_NAME_IDENTIFIER , $this->userId, time()+60*60*24,"/", $domain = SERVER, $secure = null, $httponly=true);
								setcookie(ACTIVE_USER_TOKEN_IDENTIFIER, $session_key, time()+60*60*24, "/", $domain = SERVER, $secure = null, $httponly = true);
							}
							else {
								exit;
							}
							$this->freshLogin=true;
							$this->loggedIn="Y";
							$_SESSION[ACTIVE_USER_TOKEN_IDENTIFIER]=$session_key;
							$_SESSION[ACTIVE_USER_NAME_IDENTIFIER]=$this->userId;
							return true;
			}
			else {
				$error= new ErrorMsg("EAUTH");
				$this->lastError=$error->toString();
				return false;
			}
		}
		else {
			$error=new ErrorMsg("EEMAIL");
			$this->lastError=$error->toString();
			return false;
		}
	}
	//tested:buggy:almost-cleared
	private function getClientData(){
		if(isset($_COOKIE[ACTIVE_USER_TOKEN_IDENTIFIER]) && !empty($_COOKIE[ACTIVE_USER_TOKEN_IDENTIFIER])) {
			$this->sessUID=$_COOKIE[ACTIVE_USER_TOKEN_IDENTIFIER];
		}
		if(isset($_POST[ACTIVE_USER_TOKEN_IDENTIFIER])&& !empty($_POST[ACTIVE_USER_TOKEN_IDENTIFIER])) {
			$this->sessUID=$_POST[ACTIVE_USER_TOKEN_IDENTIFIER];
		}
		if(isset($_COOKIE[ACTIVE_USER_NAME_IDENTIFIER]) && !empty($_COOKIE[ACTIVE_USER_NAME_IDENTIFIER])) {
			$this->sessUName=$_COOKIE[ACTIVE_USER_NAME_IDENTIFIER];
		}
		if(isset($_POST[ACTIVE_USER_NAME_IDENTIFIER])&& !empty($_POST[ACTIVE_USER_NAME_IDENTIFIER])) {
			$this->sessUName=$_POST[ACTIVE_USER_NAME_IDENTIFIER];
		}
	}
	//tested:buggy:ok
	public function isLoggedIn() {
		$session_key=$this->getUserData($this->userId,"sessionKey");
		$loggedDevice=$this->getUserData($this->userId,"deviceType");
		$logged=$this->getUserData($this->userId,"loggedIn");
		if($this->sessUID == $session_key && $logged=='Y' && $this->deviceType == $loggedDevice) {
			if(!$this->freshLogin){
					$_SESSION[ACTIVE_USER_TOKEN_IDENTIFIER]=$session_key;
					$_SESSION[ACTIVE_USER_NAME_IDENTIFIER]=$this->userId;
			}
			return true;

		}
		else {
			return false;
		}
	}
	//ok
	public function getUserFromMail($email) {
			$stmt=$this->dbConn->prepare("SELECT * FROM tbl_users WHERE userEmail=:umail LIMIT 1");
			try{
				$stmt->execute(array(":umail" => $email));
			}
			catch(\Exception $e){
				return false;
			}
			if($stmt->rowCount() > 0) {
				$userRow=$stmt->fetch(PDO::FETCH_ASSOC);
				if($userRow)
					$userRow["dataRowCount"]=1;
				return $userRow;
			}
			return null;
	}
	public function userExistForId($id) {
			$stmt=$this->dbConn->prepare("SELECT * FROM tbl_users WHERE userId=:uid LIMIT 1");
			try{
				$stmt->execute(array(":uid" => $id));
			}
			catch(\Exception $e){
				return false;
			}
			if($stmt->rowCount() > 0) {

				return true;
			}
			return false;
	}
	//ok
	private function getUserData($user,$col) {
			$stmt=$this->dbConn->prepare("SELECT $col FROM $this->userTable WHERE userId=:uid LIMIT 1");
			try{
				$stmt->execute(array(":uid" => $user));
			}
			catch(\Exception $e){
				return false;
			}
			if($stmt->rowCount() > 0) {
				$userRow=$stmt->fetch(PDO::FETCH_ASSOC);
				return $userRow[$col];

			}
			return null;
	}
	//getters
	public function getAccessLevel() {
			$res=null;
			if($this->accessLevel!=null) {
					$res=$this->accessLevel;
			}
			else{
				$res=$this->getUserData($this->userId,"accessLevel");
			}
			return $res;
	}
	public function getUserType() {
			$res=null;
			if($this->userType != null) {
					$res=$this->userType;
			}
			else{
				$res=$this->getUserData($this->userId,"userType");
			}
			return $res;
	}
	public function getUserEmail() {
			$res=null;
			if($this->userEmail!=null) {
					$res=$this->userEmail;
			}
			else{
				$res=$this->getUserData($this->userId,"userEmail");
			}
			return $res;
	}
	public function getUserFullName() {
			$res=null;
			if($this->userFullName!=null) {
					$res=$this->userFullName;
			}
			else{
				$res=$this->getUserData($this->userId,"userFullName");
			}
			return $res;
	}
	public function getDeviceType() {
			$res=null;
			if($this->deviceType!=null) {
					$res=$this->deviceType;
			}
			else{
				$res=$this->getUserData($this->userId,"deviceType");
			}
			return $res;
	}
	public function getUserId() {
				return $this->userId;
	}
	public function getresponseData() {
				return $this->responseData;
	}
	public function isAccountsAdmin() {
			$accessLevel=$this->getAccessLevel();
			if($accessLevel==ADVANCED_USER) {
				return true;
			}
			return false;
	}
	public function isAdminMax() {
			$accessLevel=$this->getAccessLevel();
			if($accessLevel==ADMIN_USER) {
				return true;
			}
			return false;
	}
	public function isStdUser() {
			$accessLevel=$this->getAccessLevel();
			if($accessLevel==STD_USER) {
				return true;
			}
			return false;
	}
	public function isNaiveUser() {
			$accessLevel=$this->getAccessLevel();
			if($accessLevel==NAIVE_USER) {
				return true;
			}
			return false;
	}
	public function isDataUser() {
			$accessLevel=$this->getAccessLevel();
			if($accessLevel==DATA_USER) {
				return true;
			}
			return false;
	}
	//ok
	private function setDataForUser($field,$data,$user=null) {
		if($user==null){
				$user=$this->userId;
		}
		if(!in_array($field,$this->userTableFields)) {
			return false;
		}
		$stmt=$this->dbConn->prepare("UPDATE  $this->userTable SET $field =:udata WHERE userId=:uid");
		try{
			$res=$stmt->execute(array(":uid" => $user,":udata" => $data));
		}
		catch(\Exception $e){
				return false;
		}
		if($res) {
			return true;
		}
		return false;
	}
	//remove the record from the user table(s)
	public function undoUser($user=null,$tab=USER_DB_SANDBOX_TABLE) {
			if($user==null) {
				$user=$this->userId;
			}
			$stmt=$this->dbConn->prepare("DELETE  FROM $tab WHERE userId=:uid");
			try{
				$res=$stmt->execute(array(":uid" => $user));
			}
			catch(\Exception $e){
				return false;
			}
			if($res) {
				return true;
			}
			return false;
	}
	public function logout() {
		if($this->isLoggedIn()) {
			$stmt=$this->dbConn->prepare("UPDATE  $this->userTable SET `sessionKey` ='empty' , `loggedIn` ='N' WHERE userId=:uid");
			try{
				$res=$stmt->execute(array(":uid"=> $this->userId));
				if($res && $this->isNaiveUser()){
					//enables one time login for a naive user
					// FIXME: removing user row will cause duplicate email address
					//$this->undoUser($this->userId,$this->userTable);
					$this->setDataForUser('state','DISABLED');
				}
			}
			catch(\Exception $e){
				return false;
			}
			setcookie(ACTIVE_USER_TOKEN_IDENTIFIER, "", 0, "/", $domain = null, $secure = null, true);
			setcookie(ACTIVE_USER_NAME_IDENTIFIER, "", 0, "/", $domain = null, $secure = null, true);
			return true;
		}
		return false;
	}
};
?>
