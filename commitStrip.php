<?php
/**
 * Created by PhpStorm.
 * User: vasjain
 * Date: 2/24/15
 * Time: 7:12 PM
 */

define(API_FILE_URL_PREFIX, "http://www.commitstrip.com/en/page/2/");
downloadFile();
libxml_use_internal_errors ( true );

function downloadFile() {
    $dom = new DomDocument();
    $dom = file_get_contents(API_FILE_URL_PREFIX);

    $a = preg_grep("/size-full/", explode("\n", $dom));
    print_r($a);exit;
    //var_dump($dom);exit;
    $finder = new DomXPath($dom);
    $classname="my-class";
    $nodes = $finder->query("//*[contains(@class, '$classname')]");


    $doc = new DOMDocument();
    $doc->loadHTML(API_FILE_URL_PREFIX . "2/");
    $finder = new DOMXPath($doc);
    $classname = "logoMask";
    $nodes = $finder->query("//*[contains(@class, '$classname')]");


    var_dump($nodes);exit;
    $fileUrl = API_FILE_URL_PREFIX . "2/";
    $content = file_get_contents($fileUrl);
    var_dump($content);exit;
    $fileName = $fileJson->xid . ".jpg";
    $filePath =  "downloaded_files/" . $fileName;
    file_put_contents($filePath, $content);
    error_log("Downloaded File: " . $fileJson->xid . " as " . $fileName . " " . json_encode($fileJson));
}