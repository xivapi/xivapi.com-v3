<?php

namespace App\Service\Lodestone;

use App\Common\Service\RabbitMQ\RabbitMQ;

class LodestoneResponse
{
    /**
     * Sends a response back
     */
    public static function handle(string $queue, $data)
    {
        $rabbit = new RabbitMQ();
        $rabbit->connect($queue .'_response');
        $rabbit->sendMessage($data);
        $rabbit->close();
    }
}
