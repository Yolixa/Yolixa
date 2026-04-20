<?php
require 'vendor/autoload.php';

try {
    $ref = new ReflectionMethod('Soneso\StellarSDK\TransactionBuilder', '__construct');
    foreach ($ref->getParameters() as $param) {
        echo $param->getName() . " : " . ($param->getType() ? $param->getType()->getName() : 'mixed') . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
