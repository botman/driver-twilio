<?php

namespace Tests;

use Mockery as m;
use Twilio\Twiml;
use BotMan\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use BotMan\BotMan\Messages\Outgoing\Question;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BotMan\Drivers\Twilio\TwilioVoiceDriver;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;

class TwilioVoiceDriverTest extends PHPUnit_Framework_TestCase
{
    private function getDriver($parameters = [], $htmlInterface = null)
    {
        $request = Request::create('', 'POST', $parameters, [], [], [
            'Content-Type' => 'application/x-ww-form-urlencoded'
        ]);
        $request->headers->set('X-Twilio-Signature', '+vqR5LqFQepeHnZIFIuq4jID2ws=');
        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        return new TwilioVoiceDriver($request, [], $htmlInterface);
    }

    private function getValidDriver($withDigits = true, $htmlInterface = null)
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
            'From' => '+431234567890',
            'FromCountry' => 'DE',
            'FromZip' => '',
            'FromState' => '',
            'ToCity' => '',
            'FromCity' => '',
            'ToState' => '',
            'ToZip' => '',
            'ToCountry' => 'DE',
            'CallSid' => 'CA69d45cb4f204d9e790f24e0151e90fa9',
            'AccountSid' => 'AC8d0eaafe76213f5df5ea673a149e',
            'Direction' => 'inbound',
            'ApiVersion' => '2010-04-01',
        ];
        if ($withDigits === true) {
            $parameters['Digits'] = '1';
        }
        return $this->getDriver($parameters, $htmlInterface);
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver();
        $this->assertSame('TwilioVoice', $driver->getName());
    }

    /** @test */
    public function it_matches_the_request()
    {
        $driver = $this->getDriver();
        $this->assertFalse($driver->matchesRequest());

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
    public function it_calls_the_incoming_call_event()
    {
        $driver = $this->getValidDriver(false);
        $event = $driver->hasMatchingEvent();

        $this->assertSame(TwilioVoiceDriver::INCOMING_CALL, $event->getName());
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
            'text' => 'string',
            'question' => false,
        ], $payload);

        $message = new OutgoingMessage('message object');
        $payload = $driver->buildServicePayload($message, $incomingMessage);

        $this->assertSame([
            'text' => 'message object',
            'question' => false,
        ], $payload);

        $message = new Question('question object');
        $payload = $driver->buildServicePayload($message, $incomingMessage);

        $this->assertSame([
            'buttons' => [],
            'text' => 'question object',
            'question' => true,
        ], $payload);
    }

    /** @test */
    public function it_can_send_payload()
    {
        $driver = $this->getValidDriver();

        $payload = [
            'text' => 'string',
            'question' => false
        ];

        /** @var Response $response */
        $response = $driver->sendPayload($payload);
        $expected = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<Response><Say voice="" language="">string</Say></Response>'.PHP_EOL;
        $this->assertSame($expected, $response->getContent());
    }

    /** @test */
    public function it_can_build_and_send_payload()
    {
        $driver = $this->getValidDriver();

        $payload = $driver->buildServicePayload('string', new IncomingMessage('', '', ''), []);

        /** @var Response $response */
        $response = $driver->sendPayload($payload);
        $expected = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<Response><Say voice="" language="">string</Say></Response>'.PHP_EOL;
        $this->assertSame($expected, $response->getContent());
    }

    /** @test */
    public function it_can_build_and_send_custom_twiml()
    {
        $driver = $this->getValidDriver();

        $twiml = new Twiml();
        $twiml->say('custom twiml');

        $payload = $driver->buildServicePayload($twiml, new IncomingMessage('', '', ''), []);

        /** @var Response $response */
        $response = $driver->sendPayload($payload);
        $expected = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<Response><Say>custom twiml</Say></Response>'.PHP_EOL;
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
        $expected = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.'<Response><Gather input="dtmf"><Say voice="" language="">This is a question</Say><Say voice="" language="">Button 1</Say><Say voice="" language="">Button 2</Say></Gather></Response>'.PHP_EOL;
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

        $incomingMessage = new IncomingMessage('1', '123456', '987654');
        $answer = $driver->getConversationAnswer($incomingMessage);

        $this->assertSame('1', $answer->getText());
    }
}
