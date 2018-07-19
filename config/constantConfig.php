<?php
//should be updated sync

//DERPRECIATED::
//server host
define("SERVER_HOST","http://api.misbahmes.com/");

define("SERVER_HOME","http://api.misbahmes.com");

define("SERVER","api.misbahmes.com");


//mysql host name
define("DB_HOST_NAME","localhost");

//mysql user name
define("USER_DB_USER_NAME","data_fetch_acc");

//mysql user password
define("USER_DB_USER_PASSWORD","fire.Lock");

/**
 * static declarations
 *Note if you dont know how to handle these macros be sure you leave it unchanged
 */
//token-identifiers
define("ACTIVE_USER_NAME_IDENTIFIER","user_name");

define("ACTIVE_USER_TOKEN_IDENTIFIER","user_id");



//userLevels

define("NAIVE_USER","PLVL01");

define("DATA_USER","PLVL02");

define("STD_USER","PLVL03");

define("ADVANCED_USER","PLVL04");

define("ADMIN_USER","PLVL05");

//data configurations

define("APP_NAME","MISBAH");

define("DB_NAME","misbah");

define("USER_DB_TABLE","tbl_users");

define("DATA_DB_TABLE","tbl_data");




//php files

define("FILE_PHP_CONFIG","config.php");
define("CLASS_PHP_USER","User.class.php");
define("FILE_PHP_CONSTCONFIG","constantConfig.php");
define("DIR_CONFIG","config/");

//asset storage
define("DIR_STORE","Storage");

define("DIR_THUMB","Storage/thumbnails");

define("DIR_DOC","Storage/pdf_docs");

//Application data

define("INIT_ID","1000");

define("POST_PREFIX","_");

define("DEFAULT_THUMB","thumb_default.png");

?>
