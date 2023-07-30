<?php
require_once 'MongoDB.php';

class ApiMethods
{
    /**
     * Constants containing API messages
     * Useful for debugging the API and precisely understanding what is wrong in case of a problem
     */
    public const UNDEFINED_ERROR         = 'Undefined Error';
    public const INVALID_URL             = 'Submitted URL is invalid';
    public const HASH_NOT_EXISTS         = 'Asked hash doesn\'t exists';
    public const CREATE_ERROR            = 'Impossible to create a short link into the DB';
    public const INCREMENT_COUNTER_ERROR = 'Impossible to increment the counter of views';

    private $mongo;
    private $result = ['result' => false];
    private $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; // 62 possibilities per character
    private $maxAttempts = 3;

    public function __construct()
    {
        $this->mongo = MongoDB::getInstance();
    }

    /**
     * Generate a random char with the variable $chars
     */
    private function generateRandomChar()
    {
        return $this->chars[rand(0, strlen($this->chars) - 1)];
    } 

    /**
     * Clean the db (useful before tests)
     * @return void
     */
    public function clearDB() {
        $this->mongo->links->deleteMany([]);
        $this->mongo->logs->deleteMany([]);
    }

    /**
     * Get the list of all links in the collection
     * @return array
     */
    public function getLinks() {
        $this->mongo->links->find([]);
    }

    /**
     * Increment the count column in links collection
     * @param string
     */
    private function incrementCountHash($hash) {
        $this->mongo->links->updateOne(['hash' => $hash], ['$inc' => ['count' => 1]]);
    }

    /**
     * Function who permits to get the longest hash in collection
     * @return int
     */
    private function getLongestHash() {
        $longestHash = $this->mongo->links->findOne([], ['sort' => ['hash' => -1]]);
        if($longestHash) {
            $res = iterator_to_array($longestHash);
            return strlen($res['hash']);
        }

        return 1;
    }

    /**
     * Function who return a document if the specified hash exists
     * @param string hash : The hash we're looking for
     * @return array
     */
    private function hashExists($hash) {
        $ret = $this->mongo->links->findOne(['hash' => $hash], ['projection' => ['_id' => 0]]);

        if($ret) {
            $ret = iterator_to_array($ret);
            $ret['createdAt'] = $ret['createdAt']->toDateTime()->format('Y-m-d H:i');
            $this->result['data'] = $ret;
            $this->result['result'] = true;
        } else {
            $this->result['result'] = false;
            $this->result['message'] = self::HASH_NOT_EXISTS;   
        }
        
        return $this->result;
    }

    /**
     * Function who return a document if the specified url exists
     * @param string url : The hash we're looking for
     * @return array
     */
    private function urlExists($url) {
        $ret = $this->mongo->links->findOne(['url' => $url], ['projection' => ['_id' => 0]]);

        if($ret) {
            $ret = iterator_to_array($ret);
            $ret['createdAt'] = $ret['createdAt']->toDateTime()->format('Y-m-d H:i');
        }
        
        return $ret;
    }

    /**
     * Function who add an url to the collection
     * Check that the url is correct
     * Check if it already exists and returns in this case
     * Add the url and generate an unique hash otherwise
     * @param string $url : asked link to add
     * @return array
     */
    public function addLinkToCollection($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->result['message'] = self::INVALID_URL;
            return $this->result;
        }
        
        if ($data = $this->urlExists($url)) {
            $this->result['result'] = true;
            $this->result['data'] = $data;
            return $this->result;
        }
        
        $data = [
            'url' => $url,
            'hash' => $this->generateUniqueString($this->getLongestHash(), $this->maxAttempts),
            'createdAt' => new \MongoDB\BSON\UTCDateTime(new DateTime),
            'count' => 0,
        ];
        $res = $this->mongo->links->insertOne($data);
        
        if ($res->getInsertedCount() === 1) {
            $data['createdAt'] = $data['createdAt']->toDateTime()->format('Y-m-d H:i');
            $this->result['data'] = $data;
            $this->result['result'] = true;
        } else {
            $this->result['message'] = self::CREATE_ERROR;
        }
        
        return $this->result;        
    }

    /**
     * Function who log the visit of a redirected link
     * @param string $hash : the current hash to log
     * @param string $ip : ip address of the viewer
     * @param string $useragent : useragent of the viewer
     * @return void
     */
    private function logWiew($hash, $ip, $userAgent) {
        $res = $this->mongo->logs->insertOne(
            [
                'ip' => $ip,
                'userAgent' => $userAgent,
                'hash' => $hash,
                'createdAt' => new \MongoDB\BSON\UTCDateTime(new DateTime),
            ]
        );
    }

    /**
     * Function who get an asked hash
     * @param string $hash : the current hash to log
     * @param string $ip : ip address of the viewer
     * @param string $useragent : useragent of the viewer
     * @return array
     */
    public function getHash($hash, $ip, $userAgent) {
        $ret = $this->hashExists($hash);
        if($ret['result']) {
            $this->incrementCountHash($hash);
            $this->logWiew($hash, $ip, $userAgent);
        }

        return $ret;
    }
    
    /**
     * Function who generate an unique string into the collection
     * Use the recursion to increase the size of the hash if the number of collisions exceeds $maxAttempts
     * @param $size : size of the string
     * @param $maxAttempts : Number of attempts before increasing the size of $size and trying again
     * @return $mixed
     */
    public function generateUniqueString($size, $maxAttempts) {
        $attempts = $maxAttempts;
        while ($attempts--) {
            $str = '';
            for ($i = 0; $i < $size; $i++) {
                $str .= $this->generateRandomChar();
            }
    
            if (!$this->hashExists($str)['result']) {
                return $str;
            }
        }

        return $size < 1 ? '' : $this->generateUniqueString($size + 1, $maxAttempts);
    }

    /**
     * Generate a generic error
     *
     * @return array
     */
    public function getUndefinedError()
    {
        $result['message'] = self::UNDEFINED_ERROR;

        return $result;
    }
}