<?php

//high level class structure for managing posts 
require_once('config.php');


Class Post{
    private static $dbConn=null;
    
    private $post=array();
    private $isFresh=false;
    private $post_id='';
    public static $ADDNEW=1;
    private static $required=array(
        "post_id","post_head","post_desc","post_thumb","post_doc","pdf_hash","post_year"
    );
    public function __construct($post_id)
    {
        
        $db=new DB(DB_NAME);
        self::$dbConn=$db->dbConnection();
        if(Post::$ADDNEW===$post_id){
            $this->isFresh=true;
        }
        else{
            $this->post_id=$post_id;
            $this->syncData();
        }
       
    }

    private function syncData(){
        $dataHandle=self::$dbConn->prepare("SELECT * FROM tbl_data WHERE post_id=:pid");
        $dataHandle->bindparam(':pid',$this->post_id);
        try{
            $dataHandle->execute();
            $this->post=$dataHandle->fetch(PDO::FETCH_ASSOC);
        }
        catch(\Exception $e){

        } 
    }
    
    public function commit()
    {   

        if($this->isFresh){
            $bindArray="  ";
            $count=0;
            if(!$this->validate()){
                return false;
            }
            foreach($this->post  as $item =>$data){
                if($count>0){
                    $bindArray.=" , ";
                }
                $bindArray.=  ":".$item;
                $count++;
            }
            //die($bindArray);
            $query="INSERT INTO  tbl_data VALUES( $bindArray , CURRENT_TIMESTAMP ) ";
            $dataHandle=self::$dbConn->prepare($query);
           foreach($this->post as $item => $data)
            {   
                $dataArray[":".$item]=$data;
            }

            //die(json_encode(array_merge(array($query),$dataArray)));
            try{
                if($dataHandle->execute($dataArray)){
                    return true;
                }   
            }
            catch(\Exception $e){
                return $e->getMessage();
                
            }
            return false;

        }
        else{
            $bindArray="  ";
            $count=0;
    
            foreach($this->post  as $item =>$data){
                if($count>0){
                    $bindArray.=" , ";
                }
                $bindArray.=  $item ." = :".$item;
                $count++;
            }
            //die($bindArray);
            $query="UPDATE tbl_data SET $bindArray , `timestamp`=CURRENT_TIMESTAMP WHERE post_id=:pid";
            $dataHandle=self::$dbConn->prepare($query);
            
            $dataArray=array(":pid"=>$this->post_id);
           foreach($this->post as $item => $data)
            {   
                $dataArray[":".$item]=$data;
            }
            try{
                if($dataHandle->execute($dataArray)){
                    return true;
                }   
            }
            catch(\Exception $e){
                return false;
                
            }
            return false;
        }
    }
    
    public function validate()
    {
        $data=array();
        foreach($this->post as $key => $value){
            $data[$key]=(strip_tags($this->post[$key]));
        }

        foreach(self::$required as $key){
            if(empty($data[$key])){
                return false;
            }
        }
        $this->post=$data;
        return true;

    }
    public function setData($post){
        if(!is_array($post)){
            return false;
        }
        $this->post=$post;
        //return $this->post;

    }
    public function remove(){
        $query="DELETE  FROM tbl_data  WHERE post_id=:pid";
        $dataHandle=self::$dbConn->prepare($query);
        try{
            if($dataHandle->execute(array(':pid'=>$this->post_id))){
                return true;
            }
        }
        catch(\Exception $e){
        }
        
        return false;   
    }



    //getters and setters
    public function setHeader($data){
       
        $this->post['post_head']=$data;
    } 
    public function setDesc($data){
       
        $this->post['post_head']=$data;
    }
    public function setThumb($data){
       
        $this->post['post_thumb']=$data;
    }
    public function setDocument($data){
       
        $this->post['post_doc']=$data;
    }
    public function setHash($data){
       
        $this->post['post_hash']=$data;
    }
    public function setYear($data){
       
        $this->post['post_year']=$data;
    }
    public function setTimestamp($data){
       
        $this->post['timestamp']=$data;
    }
    public function getData(){
        return $this->post;
    }
    public function getHeader(){
        return $this->post['post_head'];
        
    }

    public function getDesc(){
        return $this->post['post_desc'];
        
    }

    public function getThumb(){
        return $this->post['post_thumb'];
        
    }

    public function getDocument(){
        return $this->post['post_doc'];
        
    }
    public function getYear(){
        return $this->post['post_year'];
        
    }
    public function getTimestamp(){
        return $this->post['timestamp'];
        
    }
    public function getHash(){
        return $this->post['post_hash'];
        
    }


     
};
?>
