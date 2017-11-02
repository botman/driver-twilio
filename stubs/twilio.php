<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Twilio Token
    |--------------------------------------------------------------------------
    |
    | Your Twilio Auth Token.
    |
    */
    'token' => env('TWILIO_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Twilio Voice
    |--------------------------------------------------------------------------
    |
    | Twilio allows two separate voice engines. The first with the voices man and
    | woman supports the English, Spanish, French, German, and Italian languages
    | in both genders. The second, alice, speaks even more languages with
    | support for several different locales in a female voice.
    | See: https://www.twilio.com/docs/api/twiml/say#attributes-alice
    |
    */
    'voice' => \BotMan\Drivers\Twilio\TwilioSettings::VOICE_MAN,

    /*
    |--------------------------------------------------------------------------
    | Twilio Language
    |--------------------------------------------------------------------------
    |
    | The 'language' attribute allows you to specify a language and locale --
    | with the affiliated accent and pronunciations. Twilio supports separate
    | languages depending on the voice you choose.
    | See: https://www.twilio.com/docs/api/twiml/say#attributes-language
    |
    */
    'language' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Twilio Input Type
    |--------------------------------------------------------------------------
    |
    | A list of inputs that Twilio should accept for gathering question answers.
    | Can be INPUT_DTMF, INPUT_SPEECH or INPUT_DTMF_SPEECH.
    | See: https://www.twilio.com/docs/api/twiml/gather#attributes-input
    |
    */
    'input' => \BotMan\Drivers\Twilio\TwilioSettings::INPUT_DTMF,
];
