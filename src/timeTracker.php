<?php
/**
 *  return list of projects for rendering the time tracker page.
 */
namespace  Youtrackinterfacer;
require_once __DIR__ . '/../vendor/autoload.php';
use authenticationAndSecurity;
use getDataFromYoutrack;

class timeTracker{
    function getProjectsList()
    {
        $getDataFromYoutrack = new getDataFromYoutrack();
        return $getDataFromYoutrack->getProjectsList();
    }
}
