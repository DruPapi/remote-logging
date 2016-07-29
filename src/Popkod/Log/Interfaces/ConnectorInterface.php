<?php

namespace Popkod\Log\Interfaces;

interface ConnectorInterface
{
    public function __construct($instance);

    public function sendMessage($level, $message, $levelValue);
}