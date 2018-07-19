<?php

   require_once('../vendor/autoload.php');
   require_once('../config/User.class.php');
   require_once('../config/Post.class.php');
   require_once('../config/PostManager.class.php');

    //Security Authentication

   /*

   if(!isset($_SESSION['sess_token']))
   {
        ob_clean();
        header("HTTP/1.1 403 Permission Denied");
        header("Content-Type: application/json");
         $json=array();
        $json['status']=false;
        $json['session']=$_SESSION;
        $json['reason']="You dont have the permission to address this request";
        die(json_encode($json));
    }

    */

   //global  variales




   //Slim REST API

   use Psr\Http\Message\ServerRequestInterface  as Request;

   use Psr\Http\Message\ResponseInterface as Response;

   $app=new Slim\App();

      ////
     ////
    ////     Routes
   ////


     //-----///
    //Standard user Routes
   //-----///


   // entry path to api
   $app->get("/",function(Request $req,Response $res,$args){

    $json=array();
    $json['type']="API";
    $json['methodology']="REST";
    $json['app_name']=APP_NAME;
    $json['home']=SERVER_HOME;
    return $res->withHeader('Content-Type','application/json')->write(json_encode($json));
    });


   // get indiviual post details with postId
   $app->get("/post/{id}",function(Request $req,Response $res,$args){

    $json=array();
    $post=new Post($args['id']);
    $json['status']=true;
    $postData=$post->getData();
    $postNew=array();
    foreach($postData as $key => $value){

        if($key!='post_doc' && ($key!='post_thumb' && $key!="pdf_hash")){
            if($key=='post_desc'){
               $postNew['post_desc']=preg_replace("/[\r][\n]/"," ",$value);
            }
            else{
                $postNew[$key]=$value;
            }

        }

    }
    $json['data']=$postNew;
    return $res->withHeader('Access-Control-Allow-Origin','*')->withHeader('Content-Type','application/json')->write(json_encode($json));
    });


     //get all posts based on the limit
    //BUGGY: INCOMPLETE
    $app->get("/posts/{lim}",function(Request $req,Response $res,$args){
        $json=array();
      $limit="";
      if($args['lim']=="all" ||  $args['lim']==null){
          $limit="1000";
      }
      else{
            // TODO or BUG :: make range of rows accessible
           //         eg:1,3 - meaning rows 1 to 3
          //         modifiy regexp to do that
         //         in this stage it cries that attack stopped
         if(preg_match("(^[0-9]+$)",$args['lim'])){
             $limit=$args['lim'];
         }
         else{
             header("Content-Type:application/json");
             die(
                 json_encode(
                     array(
                        'status'=>false,
                        'data'=>"Invalid range"
                        )
                )
            );
         }
     }
       $json['status']=true;
       $json['data']=array();
       $resData=PostManager::fetch(PDO::FETCH_ASSOC,$limit,"DESC","post_year");
       foreach ($resData as $postData){
            $postNew=array();
            foreach($postData as $key => $value){

                if($key!='post_doc' && ($key!='post_thumb' && $key!="pdf_hash")){
                    if($key=='post_desc'){
                        $postNew['post_desc']=preg_replace("/[\r][\n]/"," ",$value);
                     }
                     else{
                         $postNew[$key]=$value;
                     }
                }
            }
            array_push($json['data'],$postNew);
       }

       return $res->withHeader('Access-Control-Allow-Origin','*')->withHeader('Content-Type','application/json')->write(json_encode($json));
     });


    // get pdf document as pdf
    $app->get("/file/{id}",function(Request $req,Response $res,$args){
        $json=array();
        $pid=explode('.pdf',$args['id'])[0];
        $post=PostManager::getPost($pid);

        $docURI="../config/".DIR_DOC."/".$post->getDocument();
        if(file_exists($docURI) && !is_dir($docURI)){
            ob_clean();
            header("Content-Type:application/pdf");
            header('Access-Control-Allow-Origin: *');
            readfile($docURI);
            exit;
        }
        else{
            $json['status']=false;
            $json['data']="Error loading asset";
            return $res->withHeader('Access-Control-Allow-Origin','*')->withHeader('Content-Type','application/json')->write(json_encode($json));
        }

    });
    // get pdf document as octect-stream to download
    $app->get("/download/{id}",function(Request $req,Response $res,$args){
        $json=array();
        $pid=explode('.pdf',$args['id'])[0];
        $post=new Post($pid);
        $docURI="../config/".DIR_DOC."/".$post->getDocument();
        if(file_exists($docURI) && !is_dir($docURI)){
            ob_clean();
            header("Content-Type:application/octet-stream");
            readfile($docURI);
            exit;
        }
        else{
            $json['status']=false;
            $json['data']="Error loading asset";
            return $res->withHeader('Content-Type','application/json')->write(json_encode($json));
        }

    });
    // get thumb image
    $app->get("/thumb/{id}",function(Request $req,Response $res,$args){
        $json=array();
        $post=new Post($args['id']);

        $docURI="../config/".DIR_THUMB."/".$post->getThumb();
        if(file_exists($docURI) && !is_dir($docURI)){
            ob_clean();
            header("Content-Type:image/png");
            readfile($docURI);
            exit;
        }
        else{
            $json['status']=false;
            $json['data']="Error loading asset";
            return $res->withHeader('Content-Type','application/json')->write(json_encode($json));
        }

    });


      ///-----------------///
     ///Admin user Routes///
    ///-----------------///

    //Require Authentication for the following routes


    //new post creation
    $app->post("/post/new",function(Request $req,Response $res,$args){
        $admin=new User();
        $result=array();
        if($admin->isLoggedIn()){

            $newPost=PostManager::addNew();
            $request=$req->getParsedBody();

            //BUG:
            //      order of entry of each field in $postData should match exactly
            //      with sql relation schema order.

            $postData=array(
                "post_id"   =>  POST_PREFIX.(((int)PostManager::getLastID())+rand(1,9)),
                "post_head" =>  $request['code'],
                "post_desc" =>  $request['desc']

            );
            $fileUploads=PostManager::uploadAssets();
            if($fileUploads['thumb']['status']===true){

                $postData['post_thumb']=$fileUploads['thumb']['name'];

            }
            else{
                $postData['post_thumb']=DEFAULT_THUMB;
            }
            if($fileUploads['doc']['status']===true){

                $postData['post_doc']=$fileUploads['doc']['name'];
                $postData['pdf_hash']=$fileUploads['doc']['md5'];
		            $duplicate=PostManager::isDupli('post_year', $request['year']);
		            // for resolving bi-annual publication error
		            // Resoultion required
                if($duplicate<2){
                    $postData["post_year"]= $request['year'];
                    $newPost->setData($postData);
                    $result['status']=$newPost->commit();
                    if($result['status']){
                        $result['data']=array("post_id"=>$postData['post_id']);
                    }
                    else{
                        $result['data']="invalid entries";
                    }
                }
                else{
                    $result['status']=false;
                    $result['data']="duplicate Year";

                }
            }
            else{
                $result['status']=false;
                $result['data']="upload failed";
                $result['errorInfo']=$fileUploads['doc']['errorInfo'];
            }






        }
        else{
            $result['status']=false;
            $result['data']="permission denied";

        }
        return $res->withHeader("Content-Type","application/json")->write(json_encode($result));

    });

    //delete a post
    $app->delete("/post/{id}",function(Request $req,Response $res,$args){
        $admin=new User();
        $result=array();
        if($admin->isLoggedIn()){
            $result['status']=PostManager::remove($args['id']);
        }
        else{
            $result['status']=false;
            $result['data']="permission denied";

        }
        return $res->withHeader("Content-Type","application/json ")->write(json_encode($result));

    });

    //edit an exisiting post
    $app->post("/post/{id}/edit",function(Request $req,Response $res,$args){
        $admin=new User();
        $result=array();
        if($admin->isLoggedIn()){
            $newPost=PostManager::getPost($args['id']);
            $request=$req->getParsedBody();


            if(!empty($request['code'])){
                $postData["post_head" ]=  $request['code'];
            }
            if(!empty($request['desc'])){
                $postData["post_desc" ]=  $request['desc'];
            }
            if(!empty($request['year'])){
                $postData["post_year" ]=  $request['year'];
            }

            $fileUploads=PostManager::uploadAssets();
            if($fileUploads['thumb']['status']===true){

                $postData['post_thumb']=$fileUploads['thumb']['name'];

            }
            if($fileUploads['doc']['status']===true){

                $postData['post_doc']=$fileUploads['doc']['name'];
                $postData['pdf_hash']=$fileUploads['doc']['md5'];
            }

            $newPost->setData($postData);
            $result['status']=$newPost->commit();
            if($result['status']){
                $result['data']=array("post_id"=>$args['id']);
            }
            else{
                $result['data']="invalid entries";
            }
        }
        else{
            $result['status']=false;
            $result['data']="permission denied";

        }
        return $res->withHeader("Content-Type","application/json")->write(json_encode($result));

    });


  //run app
  $app->run();
