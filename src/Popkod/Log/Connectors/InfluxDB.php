<?php

namespace Popkod\Log\Connectors;

use Popkod\Log\Interfaces\ConnectorInterface;
use Illuminate\Support\Facades\Config;

class InfluxDB implements ConnectorInterface
{
    protected $instance;
    protected $influxDBHost;
    protected $influxDBPort;
    protected $influxDBDB;

    public function __construct($instance)
    {
        $this->instance     = $instance;
        $this->influxDBHost = Config::get('remotelogging.influxdb.host', 'localhost');
        $this->influxDBPort = Config::get('remotelogging.influxdb.port', '25826');
        $this->influxDBDB   = Config::get('remotelogging.influxdb.database', 'laravel');
    }

    public function sendMessage($level, $message)
    {

    }
}