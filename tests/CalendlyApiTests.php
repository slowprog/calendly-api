<?php

use Calendly\CalendlyApi;
use Calendly\CalendlyApiException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class CalendlyApiTest extends TestCase
{
    /**
     * @var CalendlyApi
     */
    private $calendlyApi;

    /**
     * @var MockHandler
     */
    private $mockHandler;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();

        $httpClient = new Client([
            'handler' => HandlerStack::create($this->mockHandler),
        ]);

        $this->calendlyApi = new CalendlyApi(null, $httpClient);
    }

    public function testEcho()
    {
        $this->mockHandler->append(new Response(200, ['content-type' => 'application/json; charset=utf-8'], '{"email": "email@site.test"}'));

        $echo = $this->calendlyApi->echo();

        $this->assertTrue(isset($echo['email']));
        $this->assertEquals('email@site.test', $echo['email']);
    }

    public function testInvalidJsonCalendlyApiException()
    {
        $this->expectException(CalendlyApiException::class);

        $this->mockHandler->append(new Response(200, ['content-type' => 'application/json; charset=utf-8'], '{"email": "email'));

        $this->calendlyApi->echo();
    }

    public function testInvalidResponse400CalendlyApiException()
    {
        $this->expectException(CalendlyApiException::class);

        $this->mockHandler->append(new Response(400));

        $this->calendlyApi->echo();
    }

    public function testInvalidResponse500CalendlyApiException()
    {
        $this->expectException(CalendlyApiException::class);

        $this->mockHandler->append(new Response(500));

        $this->calendlyApi->echo();
    }

    public function testInvalidHeadersCalendlyApiException()
    {
        $this->expectException(TypeError::class);

        $this->mockHandler->append(new Response(200, [], '{"email": "email'));

        $this->calendlyApi->echo();
    }

    public function testCreateWebhook()
    {
        $this->mockHandler->append(new Response(201, ['content-type' => 'application/json; charset=utf-8'], '{"id":543118}'));

        $webhook = $this->calendlyApi->createWebhook('https://asdf', [
            CalendlyApi::EVENT_CANCELED,
            CalendlyApi::EVENT_CREATED,
        ]);

        $this->assertTrue(isset($webhook['id']));
        $this->assertEquals(543118, $webhook['id']);
    }

    public function testInvalidEventCreateWebhook()
    {
        $this->expectException(CalendlyApiException::class);

        $this->calendlyApi->createWebhook('https://asdf', ['asdf']);
    }

    public function testCreateWebhookExists()
    {
        $this->expectException(CalendlyApiException::class);

        $this->mockHandler->append(new Response(409, ['content-type' => 'application/json; charset=utf-8'], '{"type":"conflict_error","message":"Hook with this url already exists"}'));

        $this->calendlyApi->createWebhook('https://asdf', [
            CalendlyApi::EVENT_CANCELED,
            CalendlyApi::EVENT_CREATED,
        ]);
    }

    public function testCreateWebhookExistsCheckException()
    {
        $this->mockHandler->append(new Response(409, ['content-type' => 'application/json; charset=utf-8'], '{"type":"conflict_error","message":"Hook with this url already exists"}'));

        try {
            $this->calendlyApi->createWebhook('https://asdf', [
                CalendlyApi::EVENT_CANCELED,
                CalendlyApi::EVENT_CREATED,
            ]);
        } catch (CalendlyApiException $e) {
            $this->assertEquals(409, $e->getCode());
            $this->assertEquals('Hook with this url already exists', $e->getMessage());
        }
    }

    public function testGetWebhook()
    {
        $this->mockHandler->append(new Response(200, ['content-type' => 'application/json; charset=utf-8'], '{"data":[{"type":"hooks","id":543118,"attributes":{"url":"https:\/\/blah.foo\/b1ar","created_at":"2019-11-24T09:36:29Z","events":["invitee.created"],"state":"active"}}]}'));

        $webhook = $this->calendlyApi->getWebhook(543118);

        $this->assertTrue(isset($webhook['data'][0]['id']));
        $this->assertEquals(543118, $webhook['data'][0]['id']);
    }

    public function testGetWebhooks()
    {
        $this->mockHandler->append(new Response(200, ['content-type' => 'application/json; charset=utf-8'], '{"data":[{"type":"hooks","id":543118,"attributes":{"url":"https:\/\/blah.foo\/b1ar","created_at":"2019-11-24T09:36:29Z","events":["invitee.created"],"state":"active"}},{"type":"hooks","id":543113,"attributes":{"url":"https:\/\/blah.foo\/bar","created_at":"2019-11-24T08:58:08Z","events":["invitee.created"],"state":"active"}}]}'));

        $webhook = $this->calendlyApi->getWebhooks();

        $this->assertTrue(isset($webhook['data'][0]));
        $this->assertCount(2, $webhook['data']);
        $this->assertEquals(543118, $webhook['data'][0]['id']);
        $this->assertEquals(543113, $webhook['data'][1]['id']);
    }

    public function testDeleteWebhook()
    {
        $this->mockHandler->append(new Response(200, ['content-type' => 'text/html']));

        $webhook = $this->calendlyApi->deleteWebhook(543113);

        $this->assertNull($webhook);
    }

    public function testDeleteNotExistsWebhook()
    {
        $this->mockHandler->append(new Response(404, ['content-type' => 'application/json; charset=utf-8'], '{"type":"not_found","message":"Couldn\'t find Hook"}'));

        $webhook = $this->calendlyApi->deleteWebhook(543113);

        $this->assertNull($webhook);
    }

    public function testSomeInvalidDeleteWebhook()
    {
        $this->expectException(CalendlyApiException::class);

        $this->mockHandler->append(new Response(500));

        $this->calendlyApi->deleteWebhook(543113);
    }

    // /**
    //  * @test
    //  */
    // public function retrieves_students_collection()
    // {
    //     // 409
    //     // {"type":"conflict_error","message":"Hook with this url already exists"}
    //     $this->mockHandler->append(new Response(200, [], "{'email': 'email@site.test'}"));
    //
    //     $products = $this->calendlyApi->echo();
    //
    //     $this->assertCount(5, $products);
    // }
}
