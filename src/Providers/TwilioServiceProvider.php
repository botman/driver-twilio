<?php

namespace BotMan\Drivers\Twilio\Providers;

use Illuminate\Support\ServiceProvider;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Twilio\TwilioVoiceDriver;
use BotMan\Drivers\Twilio\TwilioMessageDriver;
use BotMan\Studio\Providers\StudioServiceProvider;

class TwilioServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        if (! $this->isRunningInBotManStudio()) {
            $this->loadDrivers();

            $this->publishes([
                __DIR__.'/../../stubs/twilio.php' => config_path('botman/twilio.php'),
            ]);

            $this->mergeConfigFrom(__DIR__.'/../../stubs/twilio.php', 'botman.twilio');
        }
    }

    /**
     * Load BotMan drivers.
     */
    protected function loadDrivers()
    {
        DriverManager::loadDriver(TwilioVoiceDriver::class);
        DriverManager::loadDriver(TwilioMessageDriver::class);
    }

    /**
     * @return bool
     */
    protected function isRunningInBotManStudio()
    {
        return class_exists(StudioServiceProvider::class);
    }
}
