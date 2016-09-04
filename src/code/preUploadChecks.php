<?php

require_once __DIR__ . '/../../vendor/autoload.php';

require_once __DIR__ . '/getCustomSettings.php';
require_once __DIR__ . '/getDataFromYoutrack.php';
$getDataFromYoutrack = new getDataFromYoutrack;

//  =====================
//    csv to php object
//  =====================

/* command line
 * -id project id
 */
//get command line options
$options = getopt("u:p:i:");

// if not run in terminal (web page)
if (!isset($options['f'])) {
    $GLOBALS["newline"] = "<br/>\n";
}

$projectid  = $options['i'];

//project	summary	description	reporterName	Ticket Type	Priority	Team	Assignee	State	Estimation	Due Date	Scheduled Date	Billing Type	Billing Status	Quote Id	permittedGroup

function attributeFromXml($node,$attribute,$xmlCode){
    $attributeData = [];
    $xml = new XMLReader();
    $xml->xml($xmlCode);
    while( $xml->read() ) {
        if( $node === $xml->name ) {
            array_push($attributeData, $xml->getAttribute($attribute));
            $xml->next();
        }
    }
    return $attributeData;
}

function nodeValueFromXml($node,$xmlCode){
    $attributeData = [];
    $xml = new XMLReader();
    $xml->xml($xmlCode);
    while( $xml->read() ) {
        if( $node === $xml->name ) {
            $xml->read();
            array_push($attributeData, $xml->value);
            $xml->next();
        }
    }
    return $attributeData;
}

function getPojectAssignees($projectid){
    global $getDataFromYoutrack;
    global $youtrackUrl;
    $url = $youtrackUrl.'/rest/admin/project/'.$projectid.'/assignee';
    $response = $getDataFromYoutrack->rest($url,'get');
    return attributeFromXml('assignee','login',$response);
}
    
function getCustomFieldList($projectid){
    global $getDataFromYoutrack;
    global $youtrackUrl;
    $url = $youtrackUrl.'/rest/admin/project/'.$projectid.'/customfield';
    $response = $getDataFromYoutrack->rest($url,'get');   
    return attributeFromXml('projectCustomField','name',$response);
}
function getFieldData($fieldList,$projectid){
    global $getDataFromYoutrack;
    global $youtrackUrl;
    $dataArray = [];
    foreach($fieldList as $field){
        $field = str_replace(' ', '%20', $field);
        $url = $youtrackUrl.'/rest/admin/project/'.$projectid.'/customfield/'.$field;
        $response = $getDataFromYoutrack->rest($url,'get');   
        $bundle = attributeFromXml('param','value',$response);
        $bundleType = attributeFromXml('projectCustomField','type',$response); 
        // if dropdown field and bundle dosnt have a :
        if( strpos($bundleType[0], '[') !== false && strpos( $bundle[0],':') === False ){
            $url = $youtrackUrl.'/rest/admin/customfield/bundle/'.$bundle[0];
           echo $response = $getDataFromYoutrack->rest($url,'get');
            $fieldOptions = nodeValueFromXml('value', $response);
        }else{
            $fieldOptions = ''; 
        }
        if( !isset($dataArray[$field]) ){
            $dataArray[$field] = [];
        }
        array_push($dataArray[$field], $fieldOptions);
    }
    return $dataArray;
}

var_dump(
getPojectAssignees($projectid)
);
    
$customFieldList = getCustomFieldList($projectid);

var_dump(
        getFieldData($customFieldList,$projectid)
);
?>