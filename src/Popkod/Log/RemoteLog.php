<?php

namespace Popkod\Log;

use Monolog\Logger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
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
     * @uses writeRemote()
     * @uses writeWithLocal()
     * @var string
     */
    protected $writeLog;

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

        if (Config::get('remotelogging.disableForwardingToLocalLog', true)) {
            $this->writeLog = 'writeRemote';
        } else {
            $this->writeLog = 'writeWithLocal';
        }

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

    public function __call($name, $arguments)
    {
        if (array_key_exists($name, $this->levels)) {
            array_unshift($arguments, $name);
            call_user_func_array([$this, 'log'], $arguments);
        }
    }

    /**
     * Log a message to the logs.
     *
     * @uses $this->writeRemote()
     * @uses $this->writeWithLocal()
     * @param  string  $level
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $this->writeLog($level, $message, $context);
    }

    protected function writeRemote($level, $message, $context = [])
    {
        if (!array_key_exists($level, $this->levels) || $this->levels[$level] < $this->minRemoteLogLevelValue) {
            return false;
        }

        reset($this->connectors);
        while ($connector = current($this->connectors)) {
            /* @type Connector $connector */
            $connector->sendMessage($level, $message, $this->levels[$level]);
            next($this->connectors);
        }

        return true;
    }

    protected function writeWithLocal($level, $message, $context = [])
    {
        Log::log($level, $message, $context);
        $this->writeRemote($level, $message, $context);
    }


    /**
     * Format the parameters for the logger.
     *
     * @param  mixed  $message
     * @return mixed
     */
    protected function formatMessage($message)
    {
        if (is_array($message)) {
            return var_export($message, true);
        } elseif ($message instanceof Jsonable) {
            return $message->toJson();
        } elseif ($message instanceof Arrayable) {
            return var_export($message->toArray(), true);
        }

        return $message;
    }
}