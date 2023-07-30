<?php
require 'vendor/autoload.php'; // include Composer's autoloader
require_once 'ApiMethods.php';

$apiMethods = new ApiMethods();
$jsonResult = $apiMethods->getUndefinedError();

if (isset($_REQUEST['action'])) {
    switch ($_REQUEST['action']) {
        case 'addLink':
            if (isset($_REQUEST['url'])) {
                $jsonResult = $apiMethods->addLinkToCollection($_REQUEST['url']);
            }
        break;

        case 'getHash':
            if (isset($_REQUEST['hash'])) {
                $jsonResult = $apiMethods->getHash($_REQUEST['hash'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
            }
        break;
    }
}

echo json_encode($jsonResult);