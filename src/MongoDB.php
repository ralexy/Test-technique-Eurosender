<?php
use MongoDB\Client;

class MongoDB {
    private static $uri = 'mongodb://mongo:27017';
    private static $client = null;
    private static $db = null;
    private static $instance = null;
    private static $dbname = 'linkShortener';
    private static $username = 'root';
    private static $password = 'password';

    private function __construct() {
        try {
            $uri = self::$uri;
            if (self::$username && self::$password) {
                $uri = "mongodb://" . self::$username . ":" . self::$password . "@mongo:27017";
            }
            self::$client = new Client($uri);
            self::$db = self::$client->selectDatabase(self::$dbname);
        } catch (Exception $e) {
            die('Error : ' . $e->getMessage());
        }
    }

    public function __destruct() {
        self::$client = null;
        self::$db = null;
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new MongoDB();
        }

        return self::$db;
    }
}