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
    protected $remoteUrl;

    public function __construct($instance)
    {
        $this->instance     = $this->escape($instance);
        $this->influxDBHost = Config::get('remotelogging.influxdb.host', 'http://localhost');
        $this->influxDBPort = Config::get('remotelogging.influxdb.port', '25826');
        $this->influxDBDB   = Config::get('remotelogging.influxdb.database', 'laravel');
        $this->remoteUrl    = $this->influxDBHost . ':' . $this->influxDBPort;
    }

    public function sendMessage($level, $message, $levelValue)
    {
        $level = $this->escape($level);
        $message = $this->escape($message);
        $this->request(
            '/write?db=' . $this->influxDBDB,
            'logs,host=' . $this->instance . ',level=' . $level . ',message=' . $message . ',value=' . $levelValue
        );
    }

    protected function request($path, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->remoteUrl . $path);
        curl_setopt($ch, CURLOPT_PORT, $this->influxDBPort);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        dd(curl_exec($ch));
//        dd(curl_error($ch));
        curl_close($ch);
    }

    protected function escape($str)
    {
        return preg_replace('/([, =])/', '\\\$1', $str);
    }
}