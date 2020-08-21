<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$host = 'localhost';
$port = 5672;
$user = 'guest';
$pass = 'guest';
$vhost = '/';
$exchange = 'subscribers';
$queue = 'customer_email';


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
 * Declares queue, creates if needed
 * @param string $queue name: $queue
 * @param bool $passive false
 * @param bool $durable true - the queue will survive server restarts
 * @param bool $exclusive false - the queue can be accessed in other channels
 * @param bool $auto_delete false - the queue won't be deleted once the channel is closed
 */
$channel->queue_declare($queue, false, true, false, false);


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
 * Binds queue to an exchange
 * @param string $queue
 * @param string $exchange
 */
$channel->queue_bind($queue, $exchange);

/**
 * @param mixed $message
 */
function processMessage($message)
{
    $messageBody = json_decode($message->body);
    $email = $messageBody->email;

    file_put_contents(
        dirname(__DIR__).'/data/'.$email.'.json',
        $message->body
    );

    // $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
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

register_shutdown_function(function ($channel, $connection) {
    $this->shutdown($channel, $connection);
});

while (count($channel->callbacks)) {
    $channel->wait();
}
