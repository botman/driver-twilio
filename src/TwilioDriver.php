<?php

namespace BotMan\Drivers\Twilio;

use BotMan\BotMan\Users\User;
use Illuminate\Support\Collection;
use BotMan\BotMan\Drivers\HttpDriver;
use Twilio\Security\RequestValidator;
use Symfony\Component\HttpFoundation\Request;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

abstract class TwilioDriver extends HttpDriver
{

    /** @var array */
    protected $messages = [];

    /** @var string */
    protected $requestUri;

    /** @var string */
    protected $signature;

    /**
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
        $this->payload = $request->request->all();
        $this->requestUri = $request->getUri();
        $this->event = Collection::make($this->payload);
        $this->config = Collection::make($this->config->get('twilio', []));
        $this->signature = $request->headers->get('X-Twilio-Signature');
    }

    /**
     * @param IncomingMessage $matchingMessage
     * @return \BotMan\BotMan\Users\User
     */
    public function getUser(IncomingMessage $matchingMessage)
    {
        return new User($matchingMessage->getSender());
    }

    /**
     * @return bool
     */
    protected function isSignatureValid()
    {
        $validator = new RequestValidator($this->config->get('token'));
        return $validator->validate($this->signature, $this->requestUri, $this->payload);
    }

    /**
     * @return bool
     */
    public function isBot()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return true;
    }

    /**
     * Low-level method to perform driver specific API requests.
     *
     * @param string $endpoint
     * @param array $parameters
     * @param IncomingMessage $matchingMessage
     * @return Response
     */
    public function sendRequest($endpoint, array $parameters, IncomingMessage $matchingMessage)
    {
        //
    }
}