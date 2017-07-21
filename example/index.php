<?php

/**
 * Step 1: Require the Slender Framework using Composer's autoloader
 *
 * If you are not using Composer, you need to load Slender Framework with your own
 * PSR-4 autoloader.
 */
require  __DIR__ . '/../vendor/autoload.php';

/**
 * Step 2: Instantiate a Slender application
 *
 * This example instantiates a Slender application using
 * its default settings. However, you will usually configure
 * your Slender application now by passing an associative array
 * of setting names and values into the application constructor.
 */
$app = new Slender\App();

/**
 * Step 3: Define the Slender application routes
 *
 * Here we define several Slender application routes that respond
 * to appropriate HTTP request methods. In this example, the second
 * argument for `Slender::get`, `Slender:post`, `Slender::put`, `Slender::patch`, and `Slender::delete`
 * is an anonymous function.
 */
$app->get('/', function ($request, $response, $args) {
    $response->write("Welcome to Slender!");
    return $response;
});

$app->get('/hello[/{name}]', function ($request, $response, $args) {
    $response->write("Hello, " . $args['name']);
    return $response;
})->setArgument('name', 'World!');

/**
 * Step 4: Run the Slender application
 *
 * This method should be called last. This executes the Slender application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();
