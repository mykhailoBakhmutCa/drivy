<?php
declare(strict_types=1);

require_once 'vendor/autoload.php';

use Drivy\RentalCalculator;

const INPUT_FILE = 'data.json';
const OUTPUT_FILE = 'output.json';

try {
    if(!file_exists(INPUT_FILE)) {
        throw new Exception('File not found: ' . INPUT_FILE);
    }

    $inputData = json_decode(file_get_contents(INPUT_FILE), true);

    $calculator = new RentalCalculator();

    $outputData = $calculator->calculatePrices($inputData);

    file_put_contents(OUTPUT_FILE, json_encode($outputData, JSON_PRETTY_PRINT));
} catch(\Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
}