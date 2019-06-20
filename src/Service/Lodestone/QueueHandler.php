<?php

namespace App\Service\Lodestone;

use App\Common\Service\RabbitMQ\RabbitMQ;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class QueueHandler
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var ConsoleOutput */
    private $console;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->console = new ConsoleOutput();
    }

    /**
     * Process incoming requests FROM xivapi, these will be requests
     * to the sync server asking it to parse various pages, these
     * will be in the queue: [$queue]_requests and be saved back to: [$queue]_response
     * once they have been fulfilled.
     */
    public function processRequest(string $queue): void
    {
        $requestRabbit  = new RabbitMQ();
        $responseRabbit = new RabbitMQ();

        // connect to the request and response queue
        $requestRabbit->connect("{$queue}_request");
        $responseRabbit->connect("{$queue}_response");

        // read requests
        $requestRabbit->readMessageAsync(function($request) use ($responseRabbit) {
            // update times
            $request->responses = [];
            $startTime = microtime(true);
            $startDate = date('H:i:s');
            $this->console->writeln("REQUESTS START : ". str_pad($request->queue, 50) ." - ". $startDate);

            // loop through request ids
            $count = 0;
            foreach ($request->ids as $id) {
                $this->now = date('Y-m-d H:i:s');
                $count++;

                $responseRabbit->pingConnection();

                // call the API class dynamically and record any exceptions
                try {

                    // perform request based on the call type


                    $request->responses[$id] = call_user_func_array([new Api(), $request->method], [ $id ]);


                    $this->console->writeln("> ". time() ." {$request->method}  ". str_pad($id, 15) ."  (OK)");
                } catch (\Exception $ex) {
                    $request->responses[$id] = get_class($ex);
                    #$this->console->$this->writeln("> ". time() ." {$request->method}  ". str_pad($id, 15) ."  (". get_class($ex) .")");

                    // if it's not a valid lodestone exception, report it
                    if (strpos(get_class($ex), 'Lodestone\Exceptions') === false) {
                        $this->console->writeln("[10] REQUEST :: ". get_class($ex) ." at: {$this->now} -- {$ex->getMessage()} #{$ex->getLine()} {$ex->getFile()}");
                        $this->console->writeln(json_encode($request, JSON_PRETTY_PRINT));
                        $this->console->writeln($ex->getTraceAsString());
                        break;
                    }
                }
            }

            // send the request back with the response
            $responseRabbit->pingConnection();
            $responseRabbit->sendMessage($request);

            // report duration
            $duration = round(microtime(true) - $startTime, 3);
            $this->console->writeln("REQUESTS END   : ". str_pad($request->queue, 50) ." - ". $startDate ." > ". date('H:i:s') ." = Duration: {$duration} for {$count} ids");
        });

        // close connections
        $this->console->writeln('Closing RabbitMQ Connections...');
        $requestRabbit->close();
        $responseRabbit->close();
    }

    /**
     * Process response messages back from RabbitMQ
     */
    public function processResponse(string $queue): void
    {

    }
}
