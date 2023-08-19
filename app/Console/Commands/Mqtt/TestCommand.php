<?php

namespace App\Console\Commands\Mqtt;

use PhpMqtt\Client\MqttClient;
use Illuminate\Console\Command;
use PhpMqtt\Client\ConnectionSettings;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mqtt:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publishes a test payload';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $connectionSettings = (new ConnectionSettings)
            ->setUsername(config('services.mqtt.username'))
            ->setPassword(config('services.mqtt.password'))
            ->setKeepAliveInterval(60)
            ->setUseTls(true)
            ->setTlsCertificateAuthorityFile(storage_path(config('services.mqtt.cafile')));
        $mqtt = new MqttClient(config('services.mqtt.host'), config('services.mqtt.port'), 'cardmonitor_' . rand(5, 15));

        $mqtt->connect($connectionSettings, true);

        $mqtt->publish('cardmonitor', 'Hello from PHP!', 0);
        $mqtt->disconnect();

        return self::SUCCESS;
    }
}
