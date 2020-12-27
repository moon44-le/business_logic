<?php
require './vendor/autoload.php';

use League\Csv\Reader;
use League\Csv\Statement;
//use League\Flysystem\Adapter\Local;
//use League\Flysystem\Filesystem;
use Classes\Advertiser;
use Classes\DB;
use Classes\Log;
use Classes\Product;

const logs          = 'logs';
const timestamp     = 'timestamp';
const email         = 'email';
$GLOBALS[logs]      = [];
$GLOBALS[timestamp] = date("Y-m-d H:i:s");
$GLOBALS[email]     = "hosting@tisch.de";
$GLOBALS[logs][] = new Log('info', 'system', 'process', 'getAdvertisersData', 'exec', $GLOBALS[timestamp] );

// --------------
// get AdvertiserData
// --------------

$handle = (new DB)->prepare('select * from advertisers');
try     { $handle->execute(); }
catch   (Exception $e) { die(mail ($GLOBALS[email], "Database Error", $e->getMessage())); }

$confAdvertisers = $handle->fetchAll(PDO::FETCH_OBJ);
$advertisers = [];
foreach($confAdvertisers as $confAdvertiser){
    $advertisers[] = new Advertiser($confAdvertiser);
}

// --------------
// Get productData from advertisers
// --------------

$csvRootPath       = './advertisersData/';
if(!is_dir($csvRootPath)){ mkdir($csvRootPath, 0700);}

foreach ($advertisers as $advertiser){
    if($advertiser->isActive() === true) {
        switch ($advertiser->getDataProtocol()){
            case "https":
                $localFile = $csvRootPath.$advertiser->getMatchCode().".".$advertiser->getDataType();
                if(!file_put_contents($localFile,file_get_contents($advertiser->getDataUrl()))) {
                    $GLOBALS[logs][] = new Log('warning', 'advertiser', 'Datafeed', $advertiser->getDataUrl(), 'not available', $GLOBALS[timestamp] );
                }else{
                    try {
                        $advertiserCsv = Reader::createFromPath($localFile, 'r');
                        $advertiserCsv->setHeaderOffset(0);
//                        $advertiserCsv->setDelimiter($advertiser->getDelimiter());
                        $stmt = (new Statement());
                        $advertiser->setProducts($stmt->process($advertiserCsv));
                    } catch (\League\Csv\Exception $e) {
                        $GLOBALS[logs][] = new Log('error', 'system', 'Feed', $localFile, 'cant read', $GLOBALS[timestamp] );
                    }
                }
                break;
            default:
                $GLOBALS[logs][] = new Log('error', 'system', 'Protocol', $advertiser->getDataProtocol(), 'not defined', $GLOBALS[timestamp] );
                break;
        }
    }
}

// --------------
// Compare & Handle productData
// --------------

$handle = (new DB)->prepare('select * from products');
try     { $handle->execute(); }
catch   (Exception $e) { die(mail ($GLOBALS[email], "Database Error", $e->getMessage())); }
$resultDbProducts = $handle->fetchAll(PDO::FETCH_OBJ);

$dbProducts = [];
foreach ( $resultDbProducts as $result) {
    $dbProducts[$result->id] = new Product($result);
}

$dbInsertValues = [];
$dbUpdateValues = [];
$transmittedProducts = [];

foreach($advertisers as $advertiser){
    foreach ($advertiser->getProducts() as $product){
        $transmittedProducts[$product->getId()] = Null;
        // Insert Product when DB entry is missing
        if(!array_key_exists($product->getId(), $dbProducts)) {
            $dbInsertValues[] = "(
            '" . $product->getId() . "',
            '" . $product->getSku() . "',
            '" . $product->isActive() . "',
            '" . $advertiser->getId() . "',
            '" . $product->getTitle() . "'
            )";
        // Update Product when Attributes is changing
        }else{
            $diffs = array_diff(
                get_object_vars ( $product ),
                get_object_vars ( $dbProducts[$product->getId()] )
            );
            if(count($diffs) > 0){
                $mergedDiffs = [];
                $updateValues = [];
                foreach ($diffs as $type => $diff){
                    $mergedDiffs[][$type] = $diff;
                }
                foreach ($mergedDiffs as $diff){
                    foreach ($diff as $key => $value){
                        $updateValues[] = $key." = '".htmlentities($value)."'";
                    }
                }
                $dbUpdateValues[] = "UPDATE products SET ".implode($updateValues,', ')." WHERE id = '".$product->getId()."'";
            }
        }
    }
}
// Update Product when missed in Advertiser Data
$missedProductIds = array_diff_key($dbProducts, $transmittedProducts);
foreach ($missedProductIds as $missedProductId => $empty){
    if($dbProducts[$missedProductId]->isActive() == '1') {
        $dbUpdateValues[] = "UPDATE products SET active = '0' WHERE id = '" . $missedProductId . "'";
    }
}

function placeholders($text, $count=0, $separator=","){
    $result = array();
    if($count > 0){
        for($x=0; $x<$count; $x++){
            $result[] = $text;
        }
    }

    return implode($separator, $result);
}

//-----------------
// Execute Product Query
//-----------------

if(count($dbInsertValues) > 0) {
    $dbValues = implode($dbInsertValues, ',');
    $query = "INSERT INTO products (id, sku, active, advertiserId, title) VALUES " . $dbValues;
    $handle = (new DB)->prepare($query);
    $logs[] = new Log('info', 'system', 'Product', count($dbInsertValues), 'included', $GLOBALS[timestamp] );
    try     { $handle->execute(); }
    catch   (Exception $e) { die(mail ($GLOBALS[email], "Database Error", $e->getMessage())); }

}
if(count($dbUpdateValues) > 0) {
    $dbValues = implode($dbUpdateValues, '; ');
    $query = $dbValues;
    $handle = (new DB)->prepare($query);
    $logs[] = new Log('info', 'system', 'Product', count($dbUpdateValues), 'changed attributes', $GLOBALS[timestamp] );
    try     { $handle->execute(); }
    catch   (Exception $e) { die(mail ($GLOBALS[email], "Database Error", $e->getMessage())); }

}

//-----------------
// Execute Logger Query
//-----------------

if(count($GLOBALS[logs]) > 0) {
    $dbLogValues = [];
//    $timestamp = date("Y-m-d H:i:s");


    $pdo = new DB();
    $pdo->beginTransaction();
    $insert_values = array();
    foreach($logs as $log){
        $question_marks[] = '('  . placeholders('?', sizeof($log->getArray())) . ')';
        $insert_values = array_merge($insert_values, array_values($log->getArray()));
    }

    $sql = "INSERT INTO importlogs (level, type, target, value, description, timestamp) VALUES "
        .implode(',', $question_marks);

    $stmt = $pdo->prepare ($sql);
    try {
        $stmt->execute($insert_values);
    }
    catch(Exception $e) {
        die(mail ($GLOBALS[email], "Database Error", $e->getMessage())); }
    $pdo->commit();




//    foreach ($logs as $log){
//        $dbLogValues[] = "(".$log->getString().",'".$timestamp."')";
//    }
//
//    $db = new DB();
//
//    $dbValues = implode($dbLogValues, ',');
//    $query = "INSERT INTO importlogs (level, type, target, value, description, timestamp) VALUES :inserts";
//    $handle = $db->prepare($query);
//    $handle->bindParam(':inserts', $dbValues);
//    try     {
//        $handle->execute();
//    }
//    catch   (Exception $e) { die(mail (EMAILADDRESS, "Database Error", $e->getMessage())); }

}

