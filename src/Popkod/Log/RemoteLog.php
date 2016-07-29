<?php

namespace Popkod\Log;

use Monolog\Logger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Popkod\Log\Interfaces\ConnectorInterface as Connector;

class RemoteLog
{
    /**
     * The Log levels.
     *
     * @var array
     */
    protected $levels = [
        'debug'     => Logger::DEBUG,
        'info'      => Logger::INFO,
        'notice'    => Logger::NOTICE,
        'warning'   => Logger::WARNING,
        'error'     => Logger::ERROR,
        'critical'  => Logger::CRITICAL,
        'alert'     => Logger::ALERT,
        'emergency' => Logger::EMERGENCY,
    ];

    protected $availableConnectors = [
        'influxdb' => Connectors\InfluxDB::class,
    ];

    protected $minRemoteLogLevel;
    protected $minRemoteLogLevelValue;
    protected $hostName;
    protected $disableForwardingToLocalLog;

    /**
     * @var Connector[]
     */
    protected $connectors = [];

    public function __construct()
    {
        $this->minRemoteLogLevel = Config::get('remotelogging.minRemoteLogLevel', 'debug');
        $this->minRemoteLogLevelValue = $this->levels[$this->minRemoteLogLevel];

        $this->hostName = Config::get('remotelogging.hostName', false);
        if ($this->hostName === false) {
            $this->hostName = gethostname();
        }

        $this->disableForwardingToLocalLog = Config::get('remotelogging.disableForwardingToLocalLog', true);

        $connectors = Config::get('remotelogging.connectors', false);
        if ($connectors !== false) {
            if(!is_array($connectors)) {
                $connectors = [$connectors];
            }
            reset($connectors);
            while ($current = current($connectors)) {
                if (array_key_exists($current, $this->availableConnectors)) {
                    $this->connectors[] = new $this->availableConnectors[$current]($this->hostName);
                }
                next($connectors);
            }
        }
    }

    public function registerConnector(Connector $connector)
    {
        return array_unshift($this->connectors, $connector);
    }

    /**
     * Log a message to the logs.
     *
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $this->disableForwardingToLocalLog || Log::log($level, $message, $context);
        $this->writeLog($level, $message);
    }

    /**
     * Log a debug message to the remote server.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function debug($message, array $context = [])
    {
        $this->disableForwardingToLocalLog || Log::debug($message, $context);
        $this->writeLog('debug', $message);
    }

    /**
     * Log an informational message to the remote server.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function info($message, array $context = [])
    {
        $this->disableForwardingToLocalLog || Log::info($message, $context);
        $this->writeLog('info', $message);
    }

    /**
     * Log a notice message to the remote server.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function notice($message, array $context = [])
    {
        $this->disableForwardingToLocalLog || Log::notice($message, $context);
        $this->writeLog('notice', $message);
    }

    /**
     * Log a warning message to the remote server.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function warning($message, array $context = [])
    {
        $this->disableForwardingToLocalLog || Log::warning($message, $context);
        $this->writeLog('warning', $message);
    }

    /**
     * Log an error message to the remote server.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function error($message, array $context = [])
    {
        $this->disableForwardingToLocalLog || Log::error($message, $context);
        $this->writeLog('error', $message);
    }

    /**
     * Log a critical message to the remote server.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function critical($message, array $context = [])
    {
        $this->disableForwardingToLocalLog || Log::critical($message, $context);
        $this->writeLog('critical', $message);
    }

    /**
     * Log an alert message to the remote server.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function alert($message, array $context = [])
    {
        $this->disableForwardingToLocalLog || Log::alert($message, $context);
        $this->writeLog('alert', $message);
    }

    /**
     * Log an emergency message to the remote server.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function emergency($message, array $context = [])
    {
        $this->disableForwardingToLocalLog || Log::emergency($message, $context);
        $this->writeLog('emergency', $message);
    }

    protected function writeLog($level, $message)
    {
        if ($this->levels[$level] < $this->minRemoteLogLevelValue) {
            return false;
        }

        reset($this->connectors);
        while ($connector = current($this->connectors)) {
            /* @type Connector $connector */
            $connector->sendMessage($level, $message);
            next($this->connectors);
        }

        return true;
    }
}