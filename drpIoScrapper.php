<?php
/**
 * User: vasujain
 * Date: 2/19/15
 * Time: 11:28 AM
 */

/**
 *  @GET
 *  @Path http://api.drp.io/get/file/diZ
 *  @Produces MediaType.APPLICATION_JSON
 *  @SampleResponse:
 *      @Error      {"error":"No file for 000_"}
 *      @Success    {"id":12089,"xid":"diZ","name":"P1000849.JPG","ip":"86.56.115.36","file":"54293be98c00c.JPG","thumbnail":"54293be98c00c_square.JPG","date":"2014-09-29T13:00:58+0200","type":"image","size":"5140245","width":4000,"height":3000,"views":76}
 */

/**
 *  @GET
 *  @Path http://api.drp.io/files/54293be98c00c.JPG
 *  @Produces MediaType.Image
 */

error_reporting(~0);
ini_set('display_errors', 1);

define("URL_PREFIX" , "http://drp.io/");
define("API_URL_PREFIX" , "http://api.drp.io/get/file/");
define("API_FILE_URL_PREFIX" , "http://api.drp.io/files/");
define('AUTH_KEY', "HKX15");
define('BASE', 62);
define('MIN_INDEX', 0);

/*
 * Just to make the script compatible as both Cron / WebScript
 */
if(isset($argv) && (count($argv) == 5)) {
    $_GET['key'] = $argv[1];
    $_GET['range'] =  $argv[2];
    $_GET['to'] =  $argv[3];
    $_GET['from'] =  $argv[4];
}
init();

/**
 * @Desc Checks GET params and redirect to the next Flow
 */
function init() {
    if(isset($_GET['key']) && ($_GET['key'] == AUTH_KEY)) {
        if(isset($_GET['file'])){
            getUrl($_GET['file']);
        } else if (isset($_GET['range']) && ($_GET['range'] == 'true') && isset($_GET['to']) && isset($_GET['from'])) {
            listAllFiles($_GET['to'], $_GET['from']);
        } else {
            listAllFiles();
        }
    } else {
        echo "Authentication failed";
        exit;
    }
}

/**
 * @Desc parse the range if required and call loopOverRange method
 * @param null / $to
 * @param null / $from
 */
function listAllFiles($to = null, $from = null) {
    $setArray = array_merge(range('a', 'z'), range('0', '9'), range('A', 'Z'));
    if(isset($to) && isset($from)) {
        $toArr = getIndexArrayForRange($to);
        $fromArr = getIndexArrayForRange($from);
        if(!ifRangeExists($toArr, $fromArr)) {exit;}
    } else {
        $toArr = array(MIN_INDEX, MIN_INDEX, MIN_INDEX);
        $fromArr = array(count($setArray), count($setArray), count($setArray));
    }
    loopOverRange($toArr, $fromArr, $setArray);
}

/**
 * @Desc Check if Range is Valid
 * @param $toArr
 * @param $fromArr
 * @return bool
 */
function ifRangeExists($toArr, $fromArr) {
    if(intArrayToId($toArr) >= intArrayToId($fromArr)) {
        error_log("Undefined Range for " . implode('.', $toArr) . " to " . implode('.', $fromArr));
        echo "Undefined Range";
        return false;
    }
    return true;
}

/**
 * @Desc Loops over the entire dataSet range to download all files
 * @param $toArr
 * @param $fromArr
 * @param $setArray
 */
function loopOverRange($toArr, $fromArr, $setArray) {
    for($i=$toArr[0]; $i<=$fromArr[0]; $i++) {
        for($j=$toArr[1]; $j<=$fromArr[1]; $j++) {
            for($k=$toArr[2]; $k<=$fromArr[2]; $k++) {
                $currentVal =  $setArray[$i] . $setArray[$j] . $setArray[$k] . "";
                getUrl($currentVal);
            }
        }
    }
}

/**
 * @Desc Loops over the entire dataSet to download all files
 */
function loopEntireSetArray () {
    $setArray = array_merge(range('a', 'z'), range('0', '9'), range('A', 'Z'));
    for($i=0; $i<count($setArray); $i++) {
        for($j=0; $j<count($setArray); $j++) {
            for($k=0; $k<count($setArray); $k++) {
                $currentVal =  $setArray[$i] . $setArray[$j] . $setArray[$k] . "";
                getUrl($currentVal);
            }
        }
    }
}

/**
 * @Desc Get the URL for the current xid
 * @param $currentVal
 */
function getUrl($currentVal) {
    $apiUrl = API_URL_PREFIX . $currentVal;
    $apiContent = file_get_contents($apiUrl);
    $apiJson = json_decode($apiContent);

    //Download only if JSON response does not have error and have an Id/Wall .
    if(isset($apiJson->wall)) {
        foreach($apiJson->wall->files as $fileJson) {
            downloadFile($fileJson);
        }
    } else if(isset($apiJson->id)) {
        downloadFile($apiJson);
    } else {
        error_log("Failed File: " . $currentVal .  " " . json_encode($apiJson));
    }
}

/**
 * @Desc Download File using the API_Url
 * @param $fileJson
 */
function downloadFile($fileJson) {
    $fileUrl = API_FILE_URL_PREFIX . $fileJson->file;
    $content = file_get_contents($fileUrl);
    $fileName = $fileJson->xid . "-" . $fileJson->file;
    $filePath =  dirname(__FILE__) . "/downloaded_files/" . $fileName;
//    copy($filePath, $fileUrl);
    file_put_contents($filePath, $content);
    echo("Downloaded File: " . $fileJson->xid . " \n ");
    error_log("Downloaded File: " . $fileJson->xid . " as " . $fileName . " " . json_encode($fileJson));
}

/**
 * @Desc Get Index Array for the Range
 * @param $rangeString
 * @return array
 */
function getIndexArrayForRange($rangeString) {
    $setArray = array_merge(range('a', 'z'), range('0', '9'), range('A', 'Z'));
    $setArrayString = array();
    foreach($setArray as $setVal) {
        array_push($setArrayString, strval($setVal));
    }
    $strArray = str_split($rangeString);
    $intArray = array();

     /**
     * String Array to int array conversion
     **/
    foreach($strArray as $str) {
        $key = array_search($str, $setArrayString, true);
//        if($key == false) { echo "Invalid Range"; exit;}
        array_push($intArray, $key);
    }
    return($intArray);
}

/**
 * @Desc int Array to Id conversion
 * @param $intArr
 * @return int
 */
function intArrayToId($intArr) {
    $id = 0;
    $len = count($intArr);
    for($i=0; $i<$len; $i++) {
        $baseExp = $len - $i -1;
        $id += $intArr[$i] * pow(BASE, $baseExp);
    }
    return $id;
}

/**
 *
 */
function populateDataBase() {

}