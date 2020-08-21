<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$host = 'localhost';
$port = 5672;
$user = 'guest';
$pass = 'guest';
$vhost = '/';
$exchange = 'queue_exchange';
$route = 'route.queue_route';


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


$faker = Faker\Factory::create();

$limit = 50;
$iteration = 0;

for ($iteration = 0; $iteration < $limit; $iteration++) {

    $messageBody = json_encode([
        'name' => $faker->name,
        'email' => $faker->email,
        'address' => $faker->address
    ]);

    $channel->basic_qos(
        NULL,   #prefetch size - prefetch window size in octets, null meaning "no specific limit"
        1,      #prefetch count - prefetch window in terms of whole messages
        NULL    #global - global=null to mean that the QoS settings should apply per-consumer, global=true to mean that the QoS settings should apply per-channel
    );

    $message = new AMQPMessage($messageBody, [
        'delivery_mode' => 2
    ]);

    $channel->basic_publish($message, $exchange, $route);
}


$channel->close();
$connection->close();
