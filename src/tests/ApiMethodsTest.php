<?php
require __DIR__. '/../vendor/autoload.php'; // include Composer's autoloader
require __DIR__. '/../ApiMethods.php';
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

final class ApiMethodsTest extends TestCase
{
    protected $api;
    protected $concurrentInsert = 1000;

    protected function setUp(): void
    {
        $this->api = new ApiMethods();
    }

    public function testAddLinkToCollection(): void
    {
        $this->api->clearDB();

        $url = 'https://example.com';
        $result = $this->api->addLinkToCollection($url);
        $this->assertTrue($result['result']);
        $this->assertEquals($url, $result['data']['url']);
        $this->assertIsString($result['data']['hash']);
        $this->assertGreaterThan(0, strlen($result['data']['hash']));
        $this->assertEquals(0, $result['data']['count']);

        $url = 'https://example.com/another-url';
        $result = $this->api->addLinkToCollection($url);

        $this->assertTrue($result['result']);
        $this->assertEquals($url, $result['data']['url']);
        $this->assertIsString($result['data']['hash']);
        $this->assertGreaterThan(0, strlen($result['data']['hash']));
        $this->assertEquals(0, $result['data']['count']);

        $url = 'invalid-url';
        $result = $this->api->addLinkToCollection($url);
        $this->assertFalse(false, $result['result']);
        $this->assertEquals(ApiMethods::INVALID_URL, $result['message']);
    }

    public function testGetHash(): void
    {
        $url = 'https://example.com';
        $result = $this->api->addLinkToCollection($url);
        $hash = $result['data']['hash'];

        $result = $this->api->getHash($hash, '127.0.0.1', 'Mozilla/5.0');
        $this->assertTrue($result['result']);
        $this->assertEquals($url, $result['data']['url']);
        $this->assertEquals('0', $result['data']['count']);
        $this->assertArrayHasKey('createdAt', $result['data']);

        $result = $this->api->getHash($hash, '127.0.0.1', 'Mozilla/5.0');
        $this->assertTrue($result['result']);
        $this->assertEquals($url, $result['data']['url']);
        $this->assertEquals('1', $result['data']['count']);
        $this->assertArrayHasKey('createdAt', $result['data']);

        $result = $this->api->getHash('nonexistent-hash', '127.0.0.1', 'Mozilla/5.0');
        $this->assertFalse($result['result']);
        $this->assertEquals(ApiMethods::HASH_NOT_EXISTS, $result['message']);
    }

    /**
     * Add multiple links in parallel to test the load on the database
     */
    public function testAddMultipleLinksToCollectionConcurrently(): void
    {
        $this->api->clearDB();
        $client = new Client();

        $promises = [];

        for ($i = 1; $i <= $this->concurrentInsert; $i++) {
            $url = 'https://example.com/link-' . $i;

            $promises[] = $client->getAsync('http://localhost/Eurosender/api/?action=addLink&url='. urlencode($url));
        }

        Promise\Utils::unwrap($promises);
        //$this->assertCount($this->concurrentInsert, $this->api->getLinks());
    }
}
