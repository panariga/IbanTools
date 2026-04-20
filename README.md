# IBAN Tools for PHP

A modern, fast, and fully object-oriented PHP library for validating, parsing, and manipulating International Bank Account Numbers (IBAN). 

This library provides lightning-fast validation with zero disk I/O overhead in production, and includes a built-in mechanism to self-update its validation rules directly from official SWIFT registry releases.

## Acknowledgements & Credits

This project is heavily inspired by and based upon the excellent [globalcitizen/php-iban](https://github.com/globalcitizen/php-iban) library. 

**IBAN Tools** modernizes the original procedural code into a single, highly-optimized OOP class (`IbanTools`). It preserves the extensive national checksum algorithms and mistranscription logic developed by the `php-iban` community, while taking advantage of modern PHP features like static constants, strict typing, and OPcache.

---

## Features

- **Blazing Fast:** Uses a pre-compiled class constant for registry data (O(1) lookups, zero disk reads).
- **Deep Validation:** Verifies country length, SWIFT regex formats, standard Mod97 checksums, and **national/domestic checksums** (e.g., Mod11 for Spain, specific algorithms for France, Italy, etc.).
- **Self-Updating:** Can parse official SWIFT `.txt` registries and safely rewrite its own source code to stay up-to-date.
- **Utility Functions:** Extract Bank Codes, Branch Codes, and Account Numbers, or generate obfuscated/human-readable formats.
- **Mistranscription Suggestions:** Suggests valid IBANs if a user makes a common typo (e.g., typing 'O' instead of '0').

---

## Basic Usage

### Validation

```php
require_once __DIR__ . '/IbanTools.php';

$ibanString = 'GB29NWBK60161331926819';

// Static validation (Quick)
$isValid = IbanTools::validate($ibanString);

// Object-oriented validation
$iban = new IbanTools($ibanString);
if ($iban->isValid()) {
    echo "IBAN is valid!";
}
```

### Extracting IBAN Parts

```php
$iban = new IbanTools('GB29NWBK60161331926819');

echo $iban->getCountryCode();   // GB
echo $iban->getBankCode();      // NWBK
echo $iban->getBranchCode();    // 601613
echo $iban->getAccountNumber(); // 31926819

// Get all parts as an array
$parts = $iban->getParts();
```

### Formatting and Obfuscation

```php
$iban = new IbanTools('GB29NWBK60161331926819');

echo $iban->format();     // GB29 NWBK 6016 1331 9268 19
echo $iban->obfuscate();  // GB29 **** **** **** **** 6819
```

---

## Updating the Registry (`update.php`)

SWIFT regularly updates the official IBAN formats (adding new countries, changing lengths, updating SEPA status). `IbanTools` has a built-in function to parse the official TSV registry and **update its own source code**.

1. Download the latest `IBAN_Registry.txt` (Tab-Separated Values format) from the official SWIFT website.
2. Place it in the root directory of this project.
3. Run the provided `update.php` script:

```bash
php update.php
```

**What `update.php` does:**
```php
<?php
// update.php
require_once __DIR__ . '/IbanTools.php';

// Parses the TSV, updates lengths/regexes, preserves custom national checksum 
// rules, and rewrites the REGISTRY constant inside IbanTools.php!
IbanTools::updateRegistry('IBAN_Registry.txt');
```
*Note: Make sure your PHP process has write permissions to `IbanTools.php` when running this script.*

---

## Running Tests (`test.php`)

To ensure 100% backwards compatibility and parity with the original `php-iban` logic, this repository includes a test script (`test.php`). 

The test script compares the output of `IbanTools::validate()` against the original `verify_iban()` function across thousands of real-world sample IBANs.

### Test Requirements
Because the tests rely on the original library for comparison, you will need to download the following files from the [globalcitizen/php-iban](https://github.com/globalcitizen/php-iban) repository and place them in the same directory as `test.php`:

1. `php-iban.php` (The original class)
2. `mistranscriptions.txt` (Required by the original class)
3. The `example-ibans/` folder (Contains the list of test IBANs)

### Running the Test

Once the files are in place, simply run:

```bash
php test.php
```

If successful, the output will confirm that both functions returned the exact same results for all tested IBANs:

```text
Starting IBAN validation tests...
--------------------------------------------------
Processing file: ad-ibans
Processing file: ae-ibans
...
--------------------------------------------------
Test Run Complete!
Total IBANs tested: 1219
✅ SUCCESS: Both functions returned the exact same results for all 1219 IBANs.
```

---

## License

This project is licensed under the LGPLv3 License, keeping in line with the original `globalcitizen/php-iban` project.
```