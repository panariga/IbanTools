<?php

// Include the required files
# From IbanTools - https://github.com/panariga/IbanTools
require_once __DIR__ . '/IbanTools.php';
# From PHP IBAN - http://github.com/globalcitizen/php-iban
require_once __DIR__ . '/php-iban.php';
# From PHP IBAN - http://github.com/globalcitizen/php-iban
$testDir = __DIR__ . '/example-ibans';

if (!is_dir($testDir)) {
    die("Error: Directory '$testDir' not found.\n");
}

// Statistics
$totalTested = 0;
$mismatches = 0;
$func1TrueCount = 0;
$func2TrueCount = 0;

echo "Starting IBAN validation tests...\n";
echo str_repeat("-", 50) . "\n";

// Iterate through all files in the directory
$files = glob($testDir . '/*');

foreach ($files as $file) {
    if (!is_file($file)) {
        continue;
    }

    $filename = basename($file);
    echo "Processing file: $filename\n";

    // Open the file to read line by line (memory efficient)
    $handle = fopen($file, "r");
    if ($handle) {
        $lineNumber = 0;
        
        while (($line = fgets($handle)) !== false) {
            $lineNumber++;
            
            // Clean the line (remove whitespace, newlines, etc.)
            $iban = trim($line);

            // Skip empty lines or comment lines (if your test files use # or //)
            if (empty($iban) || strpos($iban, '#') === 0 || strpos($iban, '//') === 0) {
                continue;
            }

            $totalTested++;

            // Run both validation functions
            $result1 = verify_iban($iban);
            $result2 = Iban::validate($iban);

            // Track how many passed
            if ($result1) $func1TrueCount++;
            if ($result2) $func2TrueCount++;

            // Compare results
            if ($result1 !== $result2) {
                $mismatches++;
                
                $res1Str = $result1 ? 'TRUE' : 'FALSE';
                $res2Str = $result2 ? 'TRUE' : 'FALSE';
                
                echo "❌ Mismatch found in $filename on line $lineNumber:\n";
                echo "   IBAN: $iban\n";
                echo "   verify_iban(): $res1Str\n";
                echo "   Iban::validate(): $res2Str\n";
            }
        }
        fclose($handle);
    }
}

echo str_repeat("-", 50) . "\n";
echo "Test Run Complete!\n";
echo "Total IBANs tested: $totalTested\n";
echo "verify_iban() returned TRUE: $func1TrueCount times\n";
echo "Iban::validate() returned TRUE: $func2TrueCount times\n";

if ($mismatches === 0) {
    echo "✅ SUCCESS: Both functions returned the exact same results for all $totalTested IBANs.\n";
    exit(0);
} else {
    echo "❌ FAILED: Found $mismatches mismatches between the two functions.\n";
    exit(1);
}