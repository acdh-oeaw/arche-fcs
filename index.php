<?php

require_once 'vendor/autoload.php';

use acdhOeaw\arche\fcs\Endpoint;

$cfg      = json_decode(json_encode(yaml_parse_file('config.yaml')));
$endpoint = new Endpoint($cfg);
$endpoint->handleRequest();
