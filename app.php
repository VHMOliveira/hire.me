<?php

/**
 * @author Victor Hugo Monteiro de Oliveira
 */

// Include database configuration file
require_once 'dbConfig.php';

class Main
{
    protected static $chars = "abcdfghjkmnpqrstvwxyz|ABCDFGHJKLMNPQRSTVWXYZ|0123456789";
    protected static $table = "short_urls";
    protected static $checkUrlExists = false;
    protected static $codeLength = 7;

    protected $pdo;

    public function __construct(PDO $pdo){
        $this->pdo = $pdo;
    }

    public function urlToShortCode($url){
        //empty() - check if it's empty
        if(empty($url)){
            throw new Exception("Nenhuma URL foi fornecido.");
        }

        if($this->validateUrlFormat($url) == false){
            throw new Exception("O URL não tem um formato válido.");
        }

        if(self::$checkUrlExists){
            if (!$this->verifyUrlExists($url)){
                throw new Exception("URL não parece existir.");
            }
        }
        
        $CUSTOM_ALIAS = explode("&CUSTOM_ALIAS=",$url);
        $urlRow = $this->getUrlFromDB($CUSTOM_ALIAS[1]);
        if(!empty($urlRow)){
            $this->incrementCounter($urlRow["id"]);
            return array('errorCode' => '001','value' => 'CUSTOM ALIAS ALREADY EXISTS');
        }

        $shortCode = $this->urlExistsInDB($url);
        if($shortCode == false){
            return array('errorCode' => '002','value' => $this->createShortCode($url));
        }else{
            return array('errorCode' => '002','value' => $shortCode);
        }

        
    }

    //Check if the variable $url is a valid URL
    protected function validateUrlFormat($url){
        //FILTER_VALIDATE_URL - Validates value as URL (according to » http://www.faqs.org/rfcs/rfc2396), optionally with required components
        //FILTER_FLAG_HOST_REQUIRED - URL must include host name (like http://www.example.com)
        return filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED);
    }

    protected function verifyUrlExists($url){
        //Initialize a cURL session
        $ch = curl_init();
        
        /**
         * curl_setopt — Set an option for a cURL transfer
         * CURLOPT_URL - The URL to fetch.
         * CURLOPT_NOBODY - do the download request without getting the body, because it's true
         * CURLOPT_RETURNTRANSFER - return the transfer as a string of the return value of curl_exec() instead of outputting it directly.
         * curl_exec - Execute the given cURL session.
         * curl_getinfo — Get information regarding a specific transfer
         * CURLINFO_HTTP_CODE - Last received HTTP code.
         * curl_close - close the session
         */
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch,  CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return (!empty($response) && $response != 404);
    }

    protected function urlExistsInDB($url){
        $query = "SELECT short_code FROM ".self::$table." WHERE long_url = :long_url LIMIT 1";
        
        //prepare() - does not execute the query immediately awaiting execution
        $stmt = $this->pdo->prepare($query);
        $params = array(
            "long_url" => $url
        );
        $stmt->execute($params);

        //fetch() - Returns a single row of the query.
        $result = $stmt->fetch();
        return (empty($result)) ? false : $result["short_code"];
    }

    protected function createShortCode($url){
        
        //strpos - Finds the position of the first occurrence of a string
        $pos = strpos($url,'CUSTOM_ALIAS');

        if(!$pos){
            $shortCode = $this->generateRandomString(self::$codeLength);
            $id = $this->insertUrlInDB($url, $shortCode);
            return $shortCode;
        }

        $CUSTOM_ALIAS = explode("CUSTOM_ALIAS=",$url);
        
        $shortCode = $CUSTOM_ALIAS[1];
        $id = $this->insertUrlInDB($url, $shortCode);
        return $shortCode;
    }
    
    protected function generateRandomString($length = 6){
        
        //explode - Splits a string into a string vector
        $sets = explode('|', self::$chars);
        $all = '';
        $randomString = '';
        foreach($sets as $set){
            /**
             * str_split - Convert a string to an array
             * array_rand - Choose one or more random elements from an array, by deafult one
             * count - Counts the number of elements of a variable, or properties of an object
             * str_shuffle - Randomly mix a string
             */
            $randomString .= $set[array_rand(str_split($set))];
            $all .= $set;
        }
        $all = str_split($all);
        for($i = 0; $i < $length - count($sets); $i++){
            $randomString .= $all[array_rand($all)];
        }
        $randomString = str_shuffle($randomString);
        return $randomString;
    }

    protected function insertUrlInDB($url, $code){
        $query = "INSERT INTO ".self::$table." (long_url, short_code) VALUES (:long_url, :short_code)";
        $stmnt = $this->pdo->prepare($query);
        $params = array(
            "long_url" => $url,
            "short_code" => $code
        );
        $stmnt->execute($params);
        //lastInsertId() — Returns the ID of the last inserted row or sequence value
        return $this->pdo->lastInsertId();
    }
    
    public function shortCodeToUrl($code, $increment = true){
        if(empty($code)) {
            throw new Exception("Nenhum codigo curto foi fornecido.");
        }

        if($this->validateShortCode($code) == false){
            throw new Exception("O codigo curto nao tem um formato valido.");
        }

        $urlRow = $this->getUrlFromDB($code);
        if(empty($urlRow)){
            throw new Exception("O codigo curto parece nao existir.");
        }
        return $urlRow["long_url"];
    }

    protected function validateShortCode($code){
        //str_replace - Replace all occurrences of the search string with the replacement string
        $rawChars = str_replace('|', '', self::$chars);

        //preg_match — Perform a regular expression match
        return preg_match("|[".$rawChars."]+|", $code);
    }

    protected function getUrlFromDB($code){
        $query = "SELECT id, long_url FROM ".self::$table." WHERE short_code = :short_code LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $params=array(
            "short_code" => $code
        );
        $stmt->execute($params);

        $result = $stmt->fetch();
        return (empty($result)) ? false : $result;
    }

    protected function incrementCounter($id){
        $query = "UPDATE ".self::$table." SET hits = hits + 1 WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $params = array(
            "id" => $id
        );
        $stmt->execute($params);
    }
}

// Initialize Shortener class and pass PDO object
$shortener = new Main($db);

// Prefix of the short URL

$shortURL_Prefix = 'http://shortener/u/';

$isRecover = $_POST['recover'];
if($isRecover == "true"){
    try{
        $longURL = $_POST['urlLong'];
        $splitURL = explode('u/',$longURL);
        
        // Get URL by short code
        $url = $shortener->shortCodeToUrl($splitURL[1]);
        
        echo json_encode(array('url' => $url));
    }catch(Exception $e){
        echo json_encode($e->getMessage());
    }
}else{

    $longURL = $_POST['urlLong'];
    $customAlias = $_POST['urlShort'];
    if(empty($customAlias)){
        $url = $longURL;
    }else{
        $url = $longURL."&CUSTOM_ALIAS=".$customAlias;
    }

    //$urlObject = new shortener();

    try{
        // Get short code of the URL
        $shortCode = $shortener->urlToShortCode($url);
        //$urlObject->__set('url',$shortCode);
        echo json_encode(array(
            'url' => $shortCode,
            'prefix' => $shortURL_Prefix
        ),JSON_FORCE_OBJECT);
        
    }catch(Exception $e){
        // Send to jquery error
        echo json_encode($e->getMessage());
    }
}