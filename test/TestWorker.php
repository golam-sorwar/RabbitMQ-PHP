<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$host = 'localhost';
$port = 5672;
$user = 'guest';
$pass = 'guest';
$vhost = '/';
$exchange = 'test_exchange';
$queue = 'test_queue';
$route = 'route.test_route';


/**
 * @param string $host
 * @param string $port
 * @param string $user
 * @param string $password
 * @param string $vhost
 */
$connection = new AMQPStreamConnection($host, $port, $user, $pass, $vhost);
$channel = $connection->channel();


/**
 * Declares exchange
 * @param string $exchange
 * @param string $type
 * @param bool $passive
 * @param bool $durable
 * @param bool $auto_delete
 */
$channel->exchange_declare($exchange, 'direct', false, true, false);


/**
 * @param mixed $message
 */
function processMessage($message)
{
    $messageBody = json_decode($message->body);
    $email = $messageBody->email;

    file_put_contents(
        dirname(__DIR__) . '/data/' . $email . '.json',
        $message->body
    );

}


/**
 * @param string $queue Queue from where to get the messages
 * @param string $consumer_tag Consumer identifier
 * @param bool $no_local Don't receive messages published by this consumer.
 * @param bool $no_ack If set to true, automatic acknowledgement mode will be used by this consumer
 * @param bool $exclusive Request exclusive consumer access, meaning only this consumer can access the queue
 * @param bool $nowait
 * @param callable|null $callback A PHP Callback
 */
$channel->basic_consume($queue, '', false, false, false, false, 'processMessage');

/**
 * @param \PhpAmqpLib\Channel\AMQPChannel $channel
 * @param \PhpAmqpLib\Connection\AbstractConnection $connection
 */
function shutdown($channel, $connection)
{
    $channel->close();
    $connection->close();
}

register_shutdown_function('shutdown', $channel, $connection);

while (count($channel->callbacks)) {
    $channel->wait(null, false, 10);
}
