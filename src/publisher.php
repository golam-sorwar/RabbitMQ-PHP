<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

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

$faker = Faker\Factory::create();

$limit = 500;
$iteration = 0;

for ($iteration = 0; $iteration < $limit; $iteration++) {

    $messageBody = json_encode([
        'name' => $faker->name,
        'email' => $faker->email,
        'address' => $faker->address
    ]);

    $message = new AMQPMessage($messageBody, [
        'content_type' => 'application/json',
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
    ]);

    $channel->basic_publish($message, $exchange);

}


$channel->close();
$connection->close();