<?php

namespace Popkod\Log;

use Illuminate\Log\Writer;

class Log extends Writer
{
    /**
     * Log an informational message to the logs.
     *
     * @param  string  $message
     * @param  array  $context
     * @return void
     */
    public function info($message, array $context = [])
    {
        return parent::info('ollée - ' . $message, $context);
    }
}