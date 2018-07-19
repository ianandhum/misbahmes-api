<?php

//DB connection
require_once('config.php');
//require_once('../../vendor/autoload.php');

//PostManager for managing all posts
//Usage PostManager::member()
abstract Class PostManager{
    private static $dbConn;
    public static function getPost($id){
        $post=new Post($id);
        return $post;
    }
    public static function addNew(){
        $post=new Post(Post::$ADDNEW);
        return $post;
    }
    public static function remove($post_id){
        $post=new Post($post_id);
        return $post->remove();
    }
    public static function fetch($pdoMode=PDO::FETCH_ASSOC,$limit="1000",$sort="DESC",$col='timestamp'){
        self::$dbConn=new DB(DB_NAME);
        self::$dbConn=self::$dbConn->dbConnection();
        $query="SELECT * FROM tbl_data ORDER BY `$col` $sort , post_id DESC LIMIT $limit";
        $dataHandle=self::$dbConn->prepare($query);
        try{
            $dataHandle->execute();
        }
        catch(\Exception $e){
            return false;
        }
        $result=$dataHandle->fetchAll($pdoMode);
        if($result){
            return $result;
        }
        return false;

    }
    public static function uploadAssets()
    {
        $result=array();
        $options=array(
            "dir"=>__DIR__ ."/". DIR_THUMB,
            'object_name'=>"thumb_img",
            "prefix"=>"thumb_",
            "mimetypes"=>array("image/png","image/jpg","image/jpeg"),
            "size"=>"10M"
          );
        $result['thumb']=PostManager::uploadFile($options);

        $options=array(
            "dir"=>__DIR__ ."/". DIR_DOC,
            'object_name'=>"doc_pdf",
            "prefix"=>"doc_",
            "mimetypes"=>array("application/pdf"),
            "size"=>"50M"
          );
        $result['doc']=PostManager::uploadFile($options);
        return $result;

    }
    public static function uploadFile($opt)
    {

        $storage = new \Upload\Storage\FileSystem($opt['dir']);
        try{
            $file = new \Upload\File($opt['object_name'], $storage);
        }
        catch(\Exception $e){

            $data['status']=false;
            return $data;
        }


        $new_filename = uniqid($opt['prefix']);

          $file->setName($new_filename);

        $file->addValidations(array(

            new \Upload\Validation\Mimetype($opt['mimetypes']),

            new \Upload\Validation\Size($opt['size'])
        ));

        $data = array(
            'name'       => $file->getNameWithExtension(),
            'md5'        => $file->getMd5()
        );

        try {
            // Success!
            $file->upload();
            $data['status']=true;
        } catch (\Exception $e) {
            // Fail!
            $errors = $file->getErrors();
            $data['status']=false;
            $data['errorInfo']=$errors;
        }
        return $data;
    }
    public static function getLastID()
    {
        self::$dbConn=new DB(DB_NAME);
        self::$dbConn=self::$dbConn->dbConnection();
        $query="SELECT `post_id` FROM tbl_data ORDER BY `timestamp` DESC LIMIT 1";
        $dataHandle=self::$dbConn->prepare($query);
        try{
            $dataHandle->execute();
        }
        catch(\Exception $e){
            return false;
        }
        $result=$dataHandle->fetch(PDO::FETCH_ASSOC);
        if(is_array($result)){
            return explode(POST_PREFIX,$result['post_id'])[1];
        }
        else{
            return INIT_ID;
        }
    }
    public static function isDupli(string $col,$val)
    {
        self::$dbConn=new DB(DB_NAME);
        self::$dbConn=self::$dbConn->dbConnection();
        $query="SELECT `$col` FROM tbl_data WHERE $col=$val";
        $dataHandle=self::$dbConn->prepare($query);
        try{
            $dataHandle->execute();
        }
        catch(\Exception $e){
            return false;
        }
        $result=$dataHandle->fetchAll(PDO::FETCH_ASSOC);
	      return @count($result);
    }
}
?>
