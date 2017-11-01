<?php

namespace BotMan\Drivers\Twilio;

use Twilio\Twiml;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Drivers\Events\GenericEvent;
use Symfony\Component\HttpFoundation\Response;
use BotMan\BotMan\Interfaces\DriverEventInterface;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;

class TwilioVoiceDriver extends TwilioDriver
{
    const DRIVER_NAME = 'TwilioVoice';
    const INCOMING_CALL = 'INCOMING_CALL';

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return $this->event->has('CallSid') && $this->isSignatureValid();
    }

    /**
     * @param  IncomingMessage $message
     * @return \BotMan\BotMan\Messages\Incoming\Answer
     */
    public function getConversationAnswer(IncomingMessage $message)
    {
        return Answer::create($message->getText())
            ->setValue($message->getText())
            ->setInteractiveReply(true)
            ->setMessage($message);
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        if (empty($this->messages)) {

            $message = new IncomingMessage($this->event->get('Digits'), $this->event->get('CallSid'), $this->event->get('To'));

            $this->messages = [$message];
        }

        return $this->messages;
    }

    /**
     * @return bool|DriverEventInterface
     */
    public function hasMatchingEvent()
    {
        if ($this->event->has('CallSid') && $this->event->has('Digits') === false) {
            $event = new GenericEvent($this->event);
            $event->setName(self::INCOMING_CALL);

            return $event;
        }

        return false;
    }

    /**
     * @param string|Question|IncomingMessage $message
     * @param IncomingMessage $matchingMessage
     * @param array $additionalParameters
     * @return Response
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        $parameters = $additionalParameters;
        $isQuestion = false;
        $text = '';

        if ($message instanceof Question) {
            $text = $message->getText();
            $isQuestion = true;
            $parameters['buttons'] = $message->getButtons() ?? [];
        } elseif ($message instanceof Twiml) {
            $parameters['twiml'] = $message;
        } elseif ($message instanceof OutgoingMessage) {
            $text = $message->getText();
        } else {
            $text = $message;
        }

        $parameters['text'] = $text;
        $parameters['question'] = $isQuestion;

        return $parameters;
    }

    /**
     * @param mixed $payload
     * @return Response
     */
    public function sendPayload($payload)
    {
        if (isset($payload['twiml'])) {
            return Response::create((string)$payload['twiml'])->send();
        }

        $sayParameters = [
            'voice' => $this->config->get('voice'),
            'language' => $this->config->get('language')
        ];
        if (isset($payload['voice'])) {
            $sayParameters['voice'] = $payload['voice'];
        }
        if (isset($payload['language'])) {
            $sayParameters['language'] = $payload['language'];
        }

        $response = new Twiml();
        if ($payload['question'] === true) {
            $input = $payload['input'] ?? TwilioSettings::INPUT_DTMF;
            $gather = $response->gather(['input' => $input]);
            $gather->say($payload['text'], $sayParameters);
            foreach ((array)$payload['buttons'] as $button) {
                $gather->say($button['text'], $sayParameters);
            }
        } else {
            $response->say($payload['text'], $sayParameters);
        }

        return Response::create((string)$response)->send();
    }
}
