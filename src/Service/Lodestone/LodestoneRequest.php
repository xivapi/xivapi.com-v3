<?php

namespace App\Service\Lodestone;

use App\Common\Service\RabbitMQ\RabbitMQ;
use Ramsey\Uuid\Uuid;

class LodestoneRequest
{
    /**
     * Send a request to the lodestone rabbit message queue server
     */
    public static function handle(string $queue, string $action, array $params)
    {
        $rabbit = new RabbitMQ();
        $rabbit->connect($queue .'_request');
        $rabbit->sendMessage([
            'id'     => Uuid::uuid4()->toString(),
            'queue'  => $queue,
            'added'  => time(),
            'action' => $action,
            'params' => $params,
        ]);

        $rabbit->close();
    }
}
