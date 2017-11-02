<?php

namespace Tests;

use Mockery as m;
use Twilio\Twiml;
use BotMan\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Outgoing\Question;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BotMan\Drivers\Twilio\TwilioMessageDriver;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;

class TwilioMessageDriverTest extends PHPUnit_Framework_TestCase
{
    private function getDriver($parameters = [], $htmlInterface = null)
    {
        $request = Request::create('', 'POST', $parameters, [], [], [
            'Content-Type' => 'application/x-ww-form-urlencoded'
        ]);
        $request->headers->set('X-Twilio-Signature', 'Lo3nfTHrzZ2sr2daOkmKFA9Ce0w=');
        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        return new TwilioMessageDriver($request, [], $htmlInterface);
    }

    private function getValidDriver($htmlInterface = null)
    {
        $parameters = [
            'Called' => '+491234567890',
            'To' => '+492662009090',
            'Caller' => '+431234567890',
            'CallerCity' => 'DE',
            'CallerCountry' => 'DE',
            'CallerState' => '',
            'CallerZip' => '',
            'CalledCountry' => 'DE',
            'CalledZip' => '',
            'CalledCity' => '',
            'CalledState' => '',
            'CallStatus' => 'ringing',
            'Body' => 'This is my test message',
            'From' => '+431234567890',
            'FromCountry' => 'DE',
            'FromZip' => '',
            'FromState' => '',
            'ToCity' => '',
            'FromCity' => '',
            'ToState' => '',
            'ToZip' => '',
            'ToCountry' => 'DE',
            'MessageSid' => 'CA69d45cb4f204d9e790f24e0151e90fa9',
            'AccountSid' => 'AC8d0eaafe76213f5df5ea673a149e',
            'Direction' => 'inbound',
            'ApiVersion' => '2010-04-01',
        ];
        return $this->getDriver($parameters, $htmlInterface);
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver();
        $this->assertSame('TwilioMessage', $driver->getName());
    }

    /** @test */
    public function it_matches_the_request()
    {
        $driver = $this->getDriver();
//        $this->assertFalse($driver->matchesRequest());

        $driver = $this->getValidDriver();
        $this->assertTrue($driver->matchesRequest());
    }

    /** @test */
    public function it_returns_the_message_object()
    {
        $driver = $this->getValidDriver();
        $this->assertTrue(is_array($driver->getMessages()));
    }

    /** @test */
    public function it_returns_the_messages_by_reference()
    {
        $driver = $this->getValidDriver();
        $hash = spl_object_hash($driver->getMessages()[0]);

        $this->assertSame($hash, spl_object_hash($driver->getMessages()[0]));
    }

    /** @test */
    public function it_detects_bots()
    {
        $driver = $this->getValidDriver();
        $this->assertFalse($driver->isBot());
    }

    /** @test */
    public function it_returns_the_user_id()
    {
        $driver = $this->getValidDriver();
        $this->assertSame('CA69d45cb4f204d9e790f24e0151e90fa9', $driver->getMessages()[0]->getSender());
    }

    /** @test */
    public function it_returns_the_channel_id()
    {
        $driver = $this->getValidDriver();
        $this->assertSame('+492662009090', $driver->getMessages()[0]->getRecipient());
    }

    /** @test */
    public function it_returns_the_user_object()
    {
        $driver = $this->getValidDriver();

        $message = $driver->getMessages()[0];
        $user = $driver->getUser($message);

        $this->assertSame($user->getId(), 'CA69d45cb4f204d9e790f24e0151e90fa9');
        $this->assertNull($user->getFirstName());
        $this->assertNull($user->getLastName());
        $this->assertNull($user->getUsername());
    }

    /** @test */
    public function it_is_configured()
    {
        $driver = $this->getValidDriver();
        $this->assertTrue($driver->isConfigured());
    }

    /** @test */
    public function it_can_build_payload()
    {
        $driver = $this->getValidDriver();

        $incomingMessage = new IncomingMessage('text', '123456', '987654');

        $message = 'string';
        $payload = $driver->buildServicePayload($message, $incomingMessage);

        $this->assertSame([
            'buttons' => [],
            'text' => 'string',
        ], $payload);

        $message = new OutgoingMessage('message object');
        $payload = $driver->buildServicePayload($message, $incomingMessage);

        $this->assertSame([
            'buttons' => [],
            'text' => 'message object',
        ], $payload);

        $message = new Question('question object');
        $payload = $driver->buildServicePayload($message, $incomingMessage);

        $this->assertSame([
            'buttons' => [],
            'text' => 'question object',
        ], $payload);
    }

    /** @test */
    public function it_can_send_payload()
    {
        $driver = $this->getValidDriver();

        $payload = [
            'buttons' => [],
            'text' => 'string',
        ];

        /** @var Response $response */
        $response = $driver->sendPayload($payload);
        $expected = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<Response><Message><Body>string</Body></Message></Response>'.PHP_EOL;
        $this->assertSame($expected, $response->getContent());
    }

    /** @test */
    public function it_can_build_and_send_payload()
    {
        $driver = $this->getValidDriver();

        $payload = $driver->buildServicePayload('string', new IncomingMessage('', '', ''), []);

        /** @var Response $response */
        $response = $driver->sendPayload($payload);
        $expected = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<Response><Message><Body>string</Body></Message></Response>'.PHP_EOL;
        $this->assertSame($expected, $response->getContent());
    }

    /** @test */
    public function it_can_build_and_send_custom_twiml()
    {
        $driver = $this->getValidDriver();

        $twiml = new Twiml();
        $message = $twiml->message();
        $message->body('custom twiml');

        $payload = $driver->buildServicePayload($twiml, new IncomingMessage('', '', ''), []);

        /** @var Response $response */
        $response = $driver->sendPayload($payload);
        $expected = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<Response><Message><Body>custom twiml</Body></Message></Response>'.PHP_EOL;
        $this->assertSame($expected, $response->getContent());
    }

    /** @test */
    public function it_can_send_questions()
    {
        $driver = $this->getValidDriver();

        $question = Question::create('This is a question')->addButtons([
            Button::create('Button 1')->value('1'),
            Button::create('Button 2')->value('2')
        ]);

        $payload = $driver->buildServicePayload($question, new IncomingMessage('', '', ''), []);

        /** @var Response $response */
        $response = $driver->sendPayload($payload);
        $expected = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<Response><Message><Body>This is a question'.PHP_EOL.'Button 1'.PHP_EOL.'Button 2</Body></Message></Response>'.PHP_EOL;
        $this->assertSame($expected, $response->getContent());
    }

    /** @test */
    public function it_can_send_image_attachments()
    {
        $driver = $this->getValidDriver();

        $message = OutgoingMessage::create('This has an attachment')->withAttachment(Image::url('https://botman.io/img/logo.png'));

        $payload = $driver->buildServicePayload($message, new IncomingMessage('', '', ''), []);

        /** @var Response $response */
        $response = $driver->sendPayload($payload);
        $expected = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<Response><Message><Body>This has an attachment</Body><Media>https://botman.io/img/logo.png</Media></Message></Response>'.PHP_EOL;
        $this->assertSame($expected, $response->getContent());
    }

    /** @test */
    public function it_no_events_for_regular_messages()
    {
        $driver = $this->getValidDriver();

        $this->assertFalse($driver->hasMatchingEvent());
    }

    /** @test */
    public function it_can_get_conversation_answers()
    {
        $driver = $this->getValidDriver();

        $incomingMessage = new IncomingMessage('This is my test message', '123456', '987654');
        $answer = $driver->getConversationAnswer($incomingMessage);

        $this->assertSame('This is my test message', $answer->getText());
    }
}
