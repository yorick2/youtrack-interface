<?php
require_once __DIR__ . '/getCustomSettings.php';
require_once __DIR__ . '/getDataFromYoutrack.php';
$getDataFromYoutrack = new getDataFromYoutrack;
$authenticationAndSecurity = new authenticationAndSecurity;

$response = [];

$ticket = htmlspecialchars($authenticationAndSecurity->getGet("ticket"));

$response['summary'] = $getDataFromYoutrack->getTicketSummary($ticket);

$project = explode('-',$ticket)[0] ;
$response['workTypes'] = $getDataFromYoutrack->getTicketWorkTypes($project);

echo json_encode($response);