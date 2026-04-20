<?php

declare(strict_types=1);


/**
 * Copyright (c) 2026 Panariga, All rights reserved.
 * Comprehensive IBAN validator and utility class.
 * Self-updating: Can parse official SWIFT iban-registry-v{version}.txt to update its own source. 
 * https://www.swift.com/standards/data-standards/iban-international-bank-account-number#topic-tabs-menu
 * 
 * @author panariga <panariga@gmail.com>
 */
class IbanTools
{
    private string $iban;
    private string $normalized;
    private ?array $countryInfo = null;

    /**
     * @var array<string, array{n: string, il: int, bl: int, r: string, bks: ?int, bke: ?int, brs: ?int, bre: ?int, s: bool, cs: ?int, ce: ?int}>
     */
    private const REGISTRY = [
        'AD' => ['n' => 'Andorra', 'il' => 24, 'bl' => 20, 'r' => '^AD(\d{2})(\d{4})(\d{4})([A-Za-z0-9]{12})$', 'bks' => 0, 'bke' => 3, 'brs' => 4, 'bre' => 7, 's' => true, 'cs' => NULL, 'ce' => NULL],
        'AE' => ['n' => 'United Arab Emirates (The)', 'il' => 23, 'bl' => 19, 'r' => '^AE(\d{2})(\d{3})(\d{16})$', 'bks' => 0, 'bke' => 2, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'AL' => ['n' => 'Albania', 'il' => 28, 'bl' => 24, 'r' => '^AL(\d{2})(\d{8})([A-Za-z0-9]{16})$', 'bks' => 0, 'bke' => 2, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'AT' => ['n' => 'Austria', 'il' => 20, 'bl' => 16, 'r' => '^AT(\d{2})(\d{5})(\d{11})$', 'bks' => 0, 'bke' => 4, 'brs' => NULL, 'bre' => NULL, 's' => true, 'cs' => NULL, 'ce' => NULL],
        'AZ' => ['n' => 'Azerbaijan', 'il' => 28, 'bl' => 24, 'r' => '^AZ(\d{2})([A-Z]{4})([A-Za-z0-9]{20})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'BA' => ['n' => 'Bosnia and Herzegovina', 'il' => 20, 'bl' => 16, 'r' => '^BA(\d{2})(\d{3})(\d{3})(\d{8})(\d{2})$', 'bks' => 0, 'bke' => 2, 'brs' => 3, 'bre' => 5, 's' => false, 'cs' => 14, 'ce' => 15],
        'BE' => ['n' => 'Belgium', 'il' => 16, 'bl' => 12, 'r' => '^BE(\d{2})(\d{3})(\d{7})(\d{2})$', 'bks' => 0, 'bke' => 2, 'brs' => NULL, 'bre' => NULL, 's' => true, 'cs' => 10, 'ce' => 11],
        'BG' => ['n' => 'Bulgaria', 'il' => 22, 'bl' => 18, 'r' => '^BG(\d{2})([A-Z]{4})(\d{4})(\d{2})([A-Za-z0-9]{8})$', 'bks' => 0, 'bke' => 3, 'brs' => 4, 'bre' => 7, 's' => true, 'cs' => NULL, 'ce' => NULL],
        'BH' => ['n' => 'Bahrain', 'il' => 22, 'bl' => 18, 'r' => '^BH(\d{2})([A-Z]{4})([A-Za-z0-9]{14})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'BI' => ['n' => 'Burundi', 'il' => 27, 'bl' => 23, 'r' => '^BI(\d{2})(\d{5})(\d{5})(\d{11})(\d{2})$', 'bks' => 0, 'bke' => 4, 'brs' => 5, 'bre' => 9, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'BL' => ['n' => 'Saint Barthelemy', 'il' => 27, 'bl' => 23, 'r' => '^BL(\d{2})(\d{5})(\d{5})([A-Za-z0-9]{11})(\d{2})$', 'bks' => 0, 'bke' => 4, 'brs' => 5, 'bre' => 9, 's' => true, 'cs' => 21, 'ce' => 22],
        'BR' => ['n' => 'Brazil', 'il' => 29, 'bl' => 25, 'r' => '^BR(\d{2})(\d{8})(\d{5})(\d{10})([A-Z]{1})([A-Za-z0-9]{1})$', 'bks' => 0, 'bke' => 7, 'brs' => 8, 'bre' => 12, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'BY' => ['n' => 'Belarus', 'il' => 28, 'bl' => 24, 'r' => '^BY(\d{2})([A-Za-z0-9]{4})(\d{4})([A-Za-z0-9]{16})$', 'bks' => 0, 'bke' => 3, 'brs' => 4, 'bre' => 7, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'CH' => ['n' => 'Switzerland', 'il' => 21, 'bl' => 17, 'r' => '^CH(\d{2})(\d{5})([A-Za-z0-9]{12})$', 'bks' => 0, 'bke' => 4, 'brs' => NULL, 'bre' => NULL, 's' => true, 'cs' => NULL, 'ce' => NULL],
        'CR' => ['n' => 'Costa Rica', 'il' => 22, 'bl' => 18, 'r' => '^CR(\d{2})(\d{4})(\d{14})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'CY' => ['n' => 'Cyprus', 'il' => 28, 'bl' => 24, 'r' => '^CY(\d{2})(\d{3})(\d{5})([A-Za-z0-9]{16})$', 'bks' => 0, 'bke' => 2, 'brs' => 3, 'bre' => 7, 's' => true, 'cs' => NULL, 'ce' => NULL],
        'CZ' => ['n' => 'Czechia', 'il' => 24, 'bl' => 20, 'r' => '^CZ(\d{2})(\d{4})(\d{6})(\d{10})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => true, 'cs' => NULL, 'ce' => NULL],
        'DE' => ['n' => 'Germany', 'il' => 22, 'bl' => 18, 'r' => '^DE(\d{2})(\d{8})(\d{10})$', 'bks' => 0, 'bke' => 7, 'brs' => NULL, 'bre' => NULL, 's' => true, 'cs' => NULL, 'ce' => NULL],
        'DJ' => ['n' => 'Djibouti', 'il' => 27, 'bl' => 23, 'r' => '^DJ(\d{2})(\d{5})(\d{5})(\d{11})(\d{2})$', 'bks' => 0, 'bke' => 4, 'brs' => 5, 'bre' => 9, 's' => false, 'cs' => 21, 'ce' => 23],
        'DK' => ['n' => 'Denmark', 'il' => 18, 'bl' => 14, 'r' => '^DK(\d{2})(\d{4})(\d{9})(\d{1})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => true, 'cs' => NULL, 'ce' => NULL],
        'DO' => ['n' => 'Dominican Republic', 'il' => 28, 'bl' => 24, 'r' => '^DO(\d{2})([A-Za-z0-9]{4})(\d{20})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'EE' => ['n' => 'Estonia', 'il' => 20, 'bl' => 16, 'r' => '^EE(\d{2})(\d{2})(\d{14})$', 'bks' => 0, 'bke' => 1, 'brs' => NULL, 'bre' => NULL, 's' => true, 'cs' => 15, 'ce' => 15],
        'EG' => ['n' => 'Egypt', 'il' => 29, 'bl' => 25, 'r' => '^EG(\d{2})(\d{4})(\d{4})(\d{17})$', 'bks' => 0, 'bke' => 3, 'brs' => 4, 'bre' => 7, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'ES' => ['n' => 'Spain', 'il' => 24, 'bl' => 20, 'r' => '^ES(\d{2})(\d{4})(\d{4})(\d{1})(\d{1})(\d{10})$', 'bks' => 0, 'bke' => 3, 'brs' => 4, 'bre' => 7, 's' => true, 'cs' => 8, 'ce' => 9],
        'FI' => ['n' => 'Finland', 'il' => 18, 'bl' => 14, 'r' => '^FI(\d{2})(\d{3})(\d{11})$', 'bks' => 0, 'bke' => 2, 'brs' => NULL, 'bre' => NULL, 's' => true, 'cs' => 13, 'ce' => 13],
        'FK' => ['n' => 'Falkland Islands (Malvinas)', 'il' => 18, 'bl' => 14, 'r' => '^FK(\d{2})([A-Z]{2})(\d{12})$', 'bks' => 0, 'bke' => 1, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'FO' => ['n' => 'Faroe Islands', 'il' => 18, 'bl' => 14, 'r' => '^FO(\d{2})(\d{4})(\d{9})(\d{1})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => 13, 'ce' => 13],
        'FR' => ['n' => 'France', 'il' => 27, 'bl' => 23, 'r' => '^FR(\d{2})(\d{5})(\d{5})([A-Za-z0-9]{11})(\d{2})$', 'bks' => 0, 'bke' => 4, 'brs' => 5, 'bre' => 9, 's' => true, 'cs' => 21, 'ce' => 22],
        'GB' => ['n' => 'United Kingdom', 'il' => 22, 'bl' => 18, 'r' => '^GB(\d{2})([A-Z]{4})(\d{6})(\d{8})$', 'bks' => 0, 'bke' => 3, 'brs' => 4, 'bre' => 9, 's' => true, 'cs' => NULL, 'ce' => NULL],
        'GE' => ['n' => 'Georgia', 'il' => 22, 'bl' => 18, 'r' => '^GE(\d{2})([A-Z]{2})(\d{16})$', 'bks' => 0, 'bke' => 1, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'GF' => ['n' => 'French Guyana', 'il' => 27, 'bl' => 23, 'r' => '^GF(\d{2})(\d{5})(\d{5})([A-Za-z0-9]{11})(\d{2})$', 'bks' => 0, 'bke' => 4, 'brs' => 5, 'bre' => 9, 's' => true, 'cs' => 21, 'ce' => 22],
        'GI' => ['n' => 'Gibraltar', 'il' => 23, 'bl' => 19, 'r' => '^GI(\d{2})([A-Z]{4})([A-Za-z0-9]{15})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => true, 'cs' => NULL, 'ce' => NULL],
        'GL' => ['n' => 'Greenland', 'il' => 18, 'bl' => 14, 'r' => '^GL(\d{2})(\d{4})(\d{9})(\d{1})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'GP' => ['n' => 'Guadelope', 'il' => 27, 'bl' => 23, 'r' => '^GP(\d{2})(\d{5})(\d{5})([A-Za-z0-9]{11})(\d{2})$', 'bks' => 0, 'bke' => 4, 'brs' => 5, 'bre' => 9, 's' => true, 'cs' => 21, 'ce' => 22],
        'GR' => ['n' => 'Greece', 'il' => 27, 'bl' => 23, 'r' => '^GR(\d{2})(\d{3})(\d{4})([A-Za-z0-9]{16})$', 'bks' => 0, 'bke' => 2, 'brs' => 3, 'bre' => 6, 's' => true, 'cs' => NULL, 'ce' => NULL],
        'GT' => ['n' => 'Guatemala', 'il' => 28, 'bl' => 24, 'r' => '^GT(\d{2})([A-Za-z0-9]{4})([A-Za-z0-9]{20})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'HN' => ['n' => 'Honduras', 'il' => 28, 'bl' => 24, 'r' => '^HN(\d{2})([A-Z]{4})(\d{20})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'HR' => ['n' => 'Croatia', 'il' => 21, 'bl' => 17, 'r' => '^HR(\d{2})(\d{7})(\d{10})$', 'bks' => 0, 'bke' => 6, 'brs' => NULL, 'bre' => NULL, 's' => true, 'cs' => NULL, 'ce' => NULL],
        'HU' => ['n' => 'Hungary', 'il' => 28, 'bl' => 24, 'r' => '^HU(\d{2})(\d{3})(\d{4})(\d{1})(\d{15})(\d{1})$', 'bks' => 0, 'bke' => 2, 'brs' => 3, 'bre' => 6, 's' => true, 'cs' => 23, 'ce' => 23],
        'IE' => ['n' => 'Ireland', 'il' => 22, 'bl' => 18, 'r' => '^IE(\d{2})([A-Z]{4})(\d{6})(\d{8})$', 'bks' => 0, 'bke' => 3, 'brs' => 4, 'bre' => 9, 's' => true, 'cs' => NULL, 'ce' => NULL],
        'IL' => ['n' => 'Israel', 'il' => 23, 'bl' => 19, 'r' => '^IL(\d{2})(\d{3})(\d{3})(\d{13})$', 'bks' => 0, 'bke' => 2, 'brs' => 3, 'bre' => 5, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'IQ' => ['n' => 'Iraq', 'il' => 23, 'bl' => 19, 'r' => '^IQ(\d{2})([A-Z]{4})(\d{3})(\d{12})$', 'bks' => 0, 'bke' => 3, 'brs' => 4, 'bre' => 6, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'IS' => ['n' => 'Iceland', 'il' => 26, 'bl' => 22, 'r' => '^IS(\d{2})(\d{4})(\d{2})(\d{6})(\d{10})$', 'bks' => 0, 'bke' => 1, 'brs' => 4, 'bre' => 9, 's' => true, 'cs' => NULL, 'ce' => NULL],
        'IT' => ['n' => 'Italy', 'il' => 27, 'bl' => 23, 'r' => '^IT(\d{2})([A-Z]{1})(\d{5})(\d{5})([A-Za-z0-9]{12})$', 'bks' => 0, 'bke' => 5, 'brs' => 6, 'bre' => 10, 's' => true, 'cs' => 0, 'ce' => 0],
        'JO' => ['n' => 'Jordan', 'il' => 30, 'bl' => 26, 'r' => '^JO(\d{2})([A-Z]{4})(\d{4})([A-Za-z0-9]{18})$', 'bks' => 0, 'bke' => 3, 'brs' => 4, 'bre' => 7, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'KW' => ['n' => 'Kuwait', 'il' => 30, 'bl' => 26, 'r' => '^KW(\d{2})([A-Z]{4})([A-Za-z0-9]{22})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'KZ' => ['n' => 'Kazakhstan', 'il' => 20, 'bl' => 16, 'r' => '^KZ(\d{2})(\d{3})([A-Za-z0-9]{13})$', 'bks' => 0, 'bke' => 2, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'LB' => ['n' => 'Lebanon', 'il' => 28, 'bl' => 24, 'r' => '^LB(\d{2})(\d{4})([A-Za-z0-9]{20})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'LC' => ['n' => 'Saint Lucia', 'il' => 32, 'bl' => 28, 'r' => '^LC(\d{2})([A-Z]{4})([A-Za-z0-9]{24})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'LI' => ['n' => 'Liechtenstein', 'il' => 21, 'bl' => 17, 'r' => '^LI(\d{2})(\d{5})([A-Za-z0-9]{12})$', 'bks' => 0, 'bke' => 4, 'brs' => NULL, 'bre' => NULL, 's' => true, 'cs' => NULL, 'ce' => NULL],
        'LT' => ['n' => 'Lithuania', 'il' => 20, 'bl' => 16, 'r' => '^LT(\d{2})(\d{5})(\d{11})$', 'bks' => 0, 'bke' => 4, 'brs' => NULL, 'bre' => NULL, 's' => true, 'cs' => NULL, 'ce' => NULL],
        'LU' => ['n' => 'Luxembourg', 'il' => 20, 'bl' => 16, 'r' => '^LU(\d{2})(\d{3})([A-Za-z0-9]{13})$', 'bks' => 0, 'bke' => 2, 'brs' => NULL, 'bre' => NULL, 's' => true, 'cs' => 14, 'ce' => 15],
        'LV' => ['n' => 'Latvia', 'il' => 21, 'bl' => 17, 'r' => '^LV(\d{2})([A-Z]{4})([A-Za-z0-9]{13})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => true, 'cs' => NULL, 'ce' => NULL],
        'LY' => ['n' => 'Libya', 'il' => 25, 'bl' => 21, 'r' => '^LY(\d{2})(\d{3})(\d{3})(\d{15})$', 'bks' => 0, 'bke' => 2, 'brs' => 3, 'bre' => 5, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'MC' => ['n' => 'Monaco', 'il' => 27, 'bl' => 23, 'r' => '^MC(\d{2})(\d{5})(\d{5})([A-Za-z0-9]{11})(\d{2})$', 'bks' => 0, 'bke' => 4, 'brs' => 5, 'bre' => 9, 's' => true, 'cs' => 21, 'ce' => 22],
        'MD' => ['n' => 'Moldova, Republic of', 'il' => 24, 'bl' => 20, 'r' => '^MD(\d{2})([A-Za-z0-9]{2})([A-Za-z0-9]{18})$', 'bks' => 0, 'bke' => 1, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'ME' => ['n' => 'Montenegro', 'il' => 22, 'bl' => 18, 'r' => '^ME(\d{2})(\d{3})(\d{13})(\d{2})$', 'bks' => 0, 'bke' => 2, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => 16, 'ce' => 17],
        'MF' => ['n' => 'Saint Martin (French Part)', 'il' => 27, 'bl' => 23, 'r' => '^MF(\d{2})(\d{5})(\d{5})([A-Za-z0-9]{11})(\d{2})$', 'bks' => 0, 'bke' => 4, 'brs' => 5, 'bre' => 9, 's' => true, 'cs' => 21, 'ce' => 22],
        'MK' => ['n' => 'North Macedonia', 'il' => 19, 'bl' => 15, 'r' => '^MK(\d{2})(\d{3})([A-Za-z0-9]{10})(\d{2})$', 'bks' => 0, 'bke' => 2, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => 13, 'ce' => 14],
        'MN' => ['n' => 'Mongolia', 'il' => 20, 'bl' => 16, 'r' => '^MN(\d{2})(\d{4})(\d{12})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'MQ' => ['n' => 'Martinique', 'il' => 27, 'bl' => 23, 'r' => '^MQ(\d{2})(\d{5})(\d{5})([A-Za-z0-9]{11})(\d{2})$', 'bks' => 0, 'bke' => 4, 'brs' => 5, 'bre' => 9, 's' => true, 'cs' => 21, 'ce' => 22],
        'MR' => ['n' => 'Mauritania', 'il' => 27, 'bl' => 23, 'r' => '^MR(\d{2})(\d{5})(\d{5})(\d{11})(\d{2})$', 'bks' => 0, 'bke' => 4, 'brs' => 5, 'bre' => 9, 's' => false, 'cs' => 21, 'ce' => 22],
        'MT' => ['n' => 'Malta', 'il' => 31, 'bl' => 27, 'r' => '^MT(\d{2})([A-Z]{4})(\d{5})([A-Za-z0-9]{18})$', 'bks' => 0, 'bke' => 3, 'brs' => 4, 'bre' => 8, 's' => true, 'cs' => NULL, 'ce' => NULL],
        'MU' => ['n' => 'Mauritius', 'il' => 30, 'bl' => 26, 'r' => '^MU(\d{2})([A-Z]{4})(\d{2})(\d{2})(\d{12})(\d{3})([A-Z]{3})$', 'bks' => 0, 'bke' => 5, 'brs' => 10, 'bre' => 21, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'NC' => ['n' => 'New Caledonia', 'il' => 27, 'bl' => 23, 'r' => '^NC(\d{2})(\d{5})(\d{5})([A-Za-z0-9]{11})(\d{2})$', 'bks' => 0, 'bke' => 4, 'brs' => 5, 'bre' => 9, 's' => true, 'cs' => 21, 'ce' => 22],
        'NI' => ['n' => 'Nicaragua', 'il' => 28, 'bl' => 24, 'r' => '^NI(\d{2})([A-Z]{4})(\d{20})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'NL' => ['n' => 'Netherlands (The)', 'il' => 18, 'bl' => 14, 'r' => '^NL(\d{2})([A-Z]{4})(\d{10})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => true, 'cs' => NULL, 'ce' => NULL],
        'NO' => ['n' => 'Norway', 'il' => 15, 'bl' => 11, 'r' => '^NO(\d{2})(\d{4})(\d{6})(\d{1})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => true, 'cs' => 10, 'ce' => 10],
        'OM' => ['n' => 'Oman', 'il' => 23, 'bl' => 19, 'r' => '^OM(\d{2})(\d{3})([A-Za-z0-9]{16})$', 'bks' => 0, 'bke' => 2, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'PF' => ['n' => 'French Polynesia', 'il' => 27, 'bl' => 23, 'r' => '^PF(\d{2})(\d{5})(\d{5})([A-Za-z0-9]{11})(\d{2})$', 'bks' => 0, 'bke' => 4, 'brs' => 5, 'bre' => 9, 's' => true, 'cs' => 21, 'ce' => 22],
        'PK' => ['n' => 'Pakistan', 'il' => 24, 'bl' => 20, 'r' => '^PK(\d{2})([A-Z]{4})([A-Za-z0-9]{16})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'PL' => ['n' => 'Poland', 'il' => 28, 'bl' => 24, 'r' => '^PL(\d{2})(\d{8})(\d{16})$', 'bks' => 0, 'bke' => 7, 'brs' => NULL, 'bre' => NULL, 's' => true, 'cs' => 7, 'ce' => 7],
        'PM' => ['n' => 'Saint Pierre et Miquelon', 'il' => 27, 'bl' => 23, 'r' => '^PM(\d{2})(\d{5})(\d{5})([A-Za-z0-9]{11})(\d{2})$', 'bks' => 0, 'bke' => 4, 'brs' => 5, 'bre' => 9, 's' => true, 'cs' => 21, 'ce' => 22],
        'PS' => ['n' => 'Palestine, State of', 'il' => 29, 'bl' => 25, 'r' => '^PS(\d{2})([A-Z]{4})([A-Za-z0-9]{21})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'PT' => ['n' => 'Portugal', 'il' => 25, 'bl' => 21, 'r' => '^PT(\d{2})(\d{4})(\d{4})(\d{11})(\d{2})$', 'bks' => 0, 'bke' => 3, 'brs' => 4, 'bre' => 7, 's' => true, 'cs' => 19, 'ce' => 20],
        'QA' => ['n' => 'Qatar', 'il' => 29, 'bl' => 25, 'r' => '^QA(\d{2})([A-Z]{4})(\d{4})([A-Za-z0-9]{17})$', 'bks' => 0, 'bke' => 3, 'brs' => 4, 'bre' => 7, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'RE' => ['n' => 'Reunion', 'il' => 27, 'bl' => 23, 'r' => '^RE(\d{2})(\d{5})(\d{5})([A-Za-z0-9]{11})(\d{2})$', 'bks' => 0, 'bke' => 4, 'brs' => 5, 'bre' => 9, 's' => true, 'cs' => 21, 'ce' => 22],
        'RO' => ['n' => 'Romania', 'il' => 24, 'bl' => 20, 'r' => '^RO(\d{2})([A-Z]{4})([A-Za-z0-9]{16})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => true, 'cs' => NULL, 'ce' => NULL],
        'RS' => ['n' => 'Serbia', 'il' => 22, 'bl' => 18, 'r' => '^RS(\d{2})(\d{3})(\d{13})(\d{2})$', 'bks' => 0, 'bke' => 2, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => 16, 'ce' => 17],
        'RU' => ['n' => 'Russian Federation', 'il' => 33, 'bl' => 29, 'r' => '^RU(\d{2})(\d{9})(\d{5})([A-Za-z0-9]{15})$', 'bks' => 0, 'bke' => 8, 'brs' => 9, 'bre' => 13, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'SA' => ['n' => 'Saudi Arabia', 'il' => 24, 'bl' => 20, 'r' => '^SA(\d{2})(\d{2})([A-Za-z0-9]{18})$', 'bks' => 0, 'bke' => 1, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'SC' => ['n' => 'Seychelles', 'il' => 31, 'bl' => 27, 'r' => '^SC(\d{2})([A-Z]{4})(\d{2})(\d{2})(\d{16})([A-Z]{3})$', 'bks' => 0, 'bke' => 5, 'brs' => 10, 'bre' => 25, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'SD' => ['n' => 'Sudan', 'il' => 18, 'bl' => 14, 'r' => '^SD(\d{2})(\d{2})(\d{12})$', 'bks' => 0, 'bke' => 1, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'SE' => ['n' => 'Sweden', 'il' => 24, 'bl' => 20, 'r' => '^SE(\d{2})(\d{3})(\d{16})(\d{1})$', 'bks' => 0, 'bke' => 2, 'brs' => NULL, 'bre' => NULL, 's' => true, 'cs' => 19, 'ce' => 19],
        'SI' => ['n' => 'Slovenia', 'il' => 19, 'bl' => 15, 'r' => '^SI(\d{2})(\d{5})(\d{8})(\d{2})$', 'bks' => 0, 'bke' => 4, 'brs' => NULL, 'bre' => NULL, 's' => true, 'cs' => 13, 'ce' => 14],
        'SK' => ['n' => 'Slovakia', 'il' => 24, 'bl' => 20, 'r' => '^SK(\d{2})(\d{4})(\d{6})(\d{10})$', 'bks' => 0, 'bke' => 3, 'brs' => 4, 'bre' => 9, 's' => true, 'cs' => 19, 'ce' => 19],
        'SM' => ['n' => 'San Marino', 'il' => 27, 'bl' => 23, 'r' => '^SM(\d{2})([A-Z]{1})(\d{5})(\d{5})([A-Za-z0-9]{12})$', 'bks' => 0, 'bke' => 5, 'brs' => 6, 'bre' => 10, 's' => true, 'cs' => 0, 'ce' => 0],
        'SO' => ['n' => 'Somalia', 'il' => 23, 'bl' => 19, 'r' => '^SO(\d{2})(\d{4})(\d{3})(\d{12})$', 'bks' => 0, 'bke' => 3, 'brs' => 4, 'bre' => 6, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'ST' => ['n' => 'Sao Tome and Principe', 'il' => 25, 'bl' => 21, 'r' => '^ST(\d{2})(\d{4})(\d{4})(\d{11})(\d{2})$', 'bks' => 0, 'bke' => 3, 'brs' => 4, 'bre' => 7, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'SV' => ['n' => 'El Salvador', 'il' => 28, 'bl' => 24, 'r' => '^SV(\d{2})([A-Z]{4})(\d{20})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'TF' => ['n' => 'French Southern Territories', 'il' => 27, 'bl' => 23, 'r' => '^TF(\d{2})(\d{5})(\d{5})([A-Za-z0-9]{11})(\d{2})$', 'bks' => 0, 'bke' => 4, 'brs' => 5, 'bre' => 9, 's' => true, 'cs' => 21, 'ce' => 22],
        'TL' => ['n' => 'Timor-Leste', 'il' => 23, 'bl' => 19, 'r' => '^TL(\d{2})(\d{3})(\d{14})(\d{2})$', 'bks' => 0, 'bke' => 2, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => 17, 'ce' => 18],
        'TN' => ['n' => 'Tunisia', 'il' => 24, 'bl' => 20, 'r' => '^TN(\d{2})(\d{2})(\d{3})(\d{13})(\d{2})$', 'bks' => 0, 'bke' => 1, 'brs' => 2, 'bre' => 4, 's' => false, 'cs' => 18, 'ce' => 19],
        'TR' => ['n' => 'Turkiye', 'il' => 26, 'bl' => 22, 'r' => '^TR(\d{2})(\d{5})(\d{1})([A-Za-z0-9]{16})$', 'bks' => 0, 'bke' => 4, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'UA' => ['n' => 'Ukraine', 'il' => 29, 'bl' => 25, 'r' => '^UA(\d{2})(\d{6})([A-Za-z0-9]{19})$', 'bks' => 0, 'bke' => 5, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'VA' => ['n' => 'Holy See', 'il' => 22, 'bl' => 18, 'r' => '^VA(\d{2})(\d{3})(\d{15})$', 'bks' => 0, 'bke' => 2, 'brs' => NULL, 'bre' => NULL, 's' => true, 'cs' => NULL, 'ce' => NULL],
        'VG' => ['n' => 'Virgin Islands (British)', 'il' => 24, 'bl' => 20, 'r' => '^VG(\d{2})([A-Z]{4})(\d{16})$', 'bks' => 0, 'bke' => 3, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'WF' => ['n' => 'Wallis and Futuna Islands', 'il' => 27, 'bl' => 23, 'r' => '^WF(\d{2})(\d{5})(\d{5})([A-Za-z0-9]{11})(\d{2})$', 'bks' => 0, 'bke' => 4, 'brs' => 5, 'bre' => 9, 's' => true, 'cs' => 21, 'ce' => 22],
        'XK' => ['n' => 'Kosovo', 'il' => 20, 'bl' => 16, 'r' => '^XK(\d{2})(\d{4})(\d{10})(\d{2})$', 'bks' => 0, 'bke' => 1, 'brs' => NULL, 'bre' => NULL, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'YE' => ['n' => 'Yemen', 'il' => 30, 'bl' => 26, 'r' => '^YE(\d{2})([A-Z]{4})(\d{4})([A-Za-z0-9]{18})$', 'bks' => 0, 'bke' => 3, 'brs' => 4, 'bre' => 7, 's' => false, 'cs' => NULL, 'ce' => NULL],
        'YT' => ['n' => 'Mayotte', 'il' => 27, 'bl' => 23, 'r' => '^YT(\d{2})(\d{5})(\d{5})([A-Za-z0-9]{11})(\d{2})$', 'bks' => 0, 'bke' => 4, 'brs' => 5, 'bre' => 9, 's' => true, 'cs' => 21, 'ce' => 22],
    ];

    private const MISTRANSCRIPTIONS = [
        '0' => ['O', '6', 'D', 'G'], '1' => ['I', 'L', '7', '2', 'Z'], '2' => ['Z', '7', 'P', 'E', '1'],
        '3' => ['8', 'B'], '4' => ['G', 'U'], '5' => ['S', '7'], '6' => ['O', '8', 'G', 'C', 'B', 'D'],
        '7' => ['J', 'I', '1', 'L'], '8' => ['B', '3', '6'], '9' => ['G', 'Y', 'O', '0', 'D'],
        'A' => ['G', 'Q', 'O'], 'B' => ['6', '3', '8', 'P', 'O'], 'C' => ['R', '6', 'I', 'L', 'O'],
        'D' => ['O', '9', 'Q', 'G', '6', 'A'], 'E' => ['F', 'G', '2', 'K', 'Z', 'S', 'O'],
        'F' => ['E', 'K', 'T', 'P', 'Y', '4', 'B', '7', '1'], 'G' => ['9', 'Q', '8', '6', 'C', '4', 'O'],
        'H' => ['B', 'N', 'A', '4', '6', 'M', 'W', 'F', 'R', 'T', 'X'], 'I' => ['1', 'L', '7', 'J', '2', 'T', 'Z'],
        'J' => ['I', '7', '2', '9', '1', 'U', 'T', 'Q', 'P', 'Y', 'Z', 'L', 'S'], 'K' => ['F', 'X', 'H', 'R'],
        'L' => ['1', '2', '7', 'C', 'I', 'J', 'R', 'T', 'Y', 'Z'], 'M' => ['H', '8', 'E', '3', 'N', 'V', 'W'],
        'N' => ['H', 'R', 'C', '2', '4', 'M', 'O', 'P', 'K', 'T', 'Z'], 'O' => ['6', '9', 'A', 'D', 'G', 'C', 'E', 'B', 'N', 'P'],
        'P' => ['F', '4', '8', '2', 'B', 'J', 'R', 'N', 'O', 'T', 'Y'], 'Q' => ['O', 'G', '9', 'Y', '1', '7', 'L'],
        'R' => ['K', 'B', 'V', 'C', '1', 'L', '2'], 'S' => ['5', '6', '9', 'B', 'G', 'Q', 'A', 'Y'],
        'T' => ['1', '4', '7', 'F', 'I', 'J', 'L', 'P', 'X', 'Y'], 'U' => ['V', 'N', 'A', '4', '9', 'W', 'Y'],
        'V' => ['U', 'R', 'N'], 'W' => ['M', 'N', 'U', 'V'], 'X' => ['K', 'F', '4', 'T', 'V', 'Y'],
        'Y' => ['G', 'V', 'J', 'I', '4', '9', 'T', 'F', 'Q', '1'], 'Z' => ['2', '1', 'L', 'R', 'I', '7', 'V', '3', '4'],
    ];

    public function __construct(string $iban)
    {
        $this->iban = $iban;
        $this->normalized = self::normalize($iban);
        $countryCode = $this->getCountryCode();
        $this->countryInfo = self::REGISTRY[$countryCode] ?? null;
    }

    public static function validate(string $iban): bool
    {
        return (new self($iban))->isValid();
    }

    public static function normalize(string $iban): string
    {
        $iban = ltrim(strtoupper($iban));
        $iban = preg_replace('/^I?IBAN/', '', $iban) ?? '';
        return preg_replace('/[^A-Z0-9]/', '', $iban) ?? '';
    }

    public function isValid(): bool
    {
        if (!$this->countryInfo) {
            return false;
        }

        if (strlen($this->normalized) !== $this->countryInfo['il']) {
            return false;
        }

        if (!preg_match('/' . $this->countryInfo['r'] . '/', $this->normalized)) {
            return false;
        }

        if (!$this->verifyChecksum()) {
            return false;
        }

        $nationalCheck = $this->verifyNationalChecksum();
        if ($nationalCheck === false) {
            return false;
        }

        return true;
    }

    public function verifyChecksum(): bool
    {
        $temp = substr($this->normalized, 4) . substr($this->normalized, 0, 4);
        $numeric = '';
        foreach (str_split($temp) as $char) {
            $numeric .= ctype_alpha($char) ? (ord($char) - 55) : $char;
        }
        return self::bigModulo97($numeric) === 1;
    }

    public function calculateChecksum(): string
    {
        $tmp = substr($this->normalized, 4) . substr($this->normalized, 0, 2) . '00';
        $numeric = '';
        foreach (str_split($tmp) as $char) {
            $numeric .= ctype_alpha($char) ? (ord($char) - 55) : $char;
        }
        $checksum = self::bigModulo97($numeric, true);
        return str_pad((string)(98 - $checksum), 2, '0', STR_PAD_LEFT);
    }

    public function setChecksum(): string
    {
        return substr($this->normalized, 0, 2) . $this->calculateChecksum() . substr($this->normalized, 4);
    }

    public function getCountryCode(): string
    {
        return substr($this->normalized, 0, 2);
    }

    public function getCountryName(): ?string
    {
        return $this->countryInfo['n'] ?? null;
    }

    public function isSepa(): bool
    {
        return $this->countryInfo['s'] ?? false;
    }

    public function getBban(): string
    {
        return substr($this->normalized, 4);
    }

    public function getBankCode(): ?string
    {
        if (!$this->countryInfo || $this->countryInfo['bks'] === null) return null;
        return substr($this->getBban(), $this->countryInfo['bks'], $this->countryInfo['bke'] - $this->countryInfo['bks'] + 1);
    }

    public function getBranchCode(): ?string
    {
        if (!$this->countryInfo || $this->countryInfo['brs'] === null) return null;
        return substr($this->getBban(), $this->countryInfo['brs'], $this->countryInfo['bre'] - $this->countryInfo['brs'] + 1);
    }

    public function getAccountNumber(): ?string
    {
        if (!$this->countryInfo) return null;
        $start = ($this->countryInfo['bre'] ?? $this->countryInfo['bke'] ?? -1) + 1;
        return substr($this->getBban(), $start);
    }

    public function getNationalChecksum(): ?string
    {
        if (!$this->countryInfo || $this->countryInfo['cs'] === null) return null;
        return substr($this->getBban(), $this->countryInfo['cs'], $this->countryInfo['ce'] - $this->countryInfo['cs'] + 1);
    }

    public function format(bool $machine = false): string
    {
        if ($machine) return $this->normalized;
        return wordwrap($this->normalized, 4, ' ', true);
    }

    public function obfuscate(): string
    {
        $len = strlen($this->normalized);
        if ($len < 8) return $this->normalized;
        $prefix = substr($this->normalized, 0, 2);
        $suffix = substr($this->normalized, -4);
        return wordwrap($prefix . str_repeat('*', $len - 6) . $suffix, 4, ' ', true);
    }

    public function getParts(): array
    {
        return [
            'country' => $this->getCountryCode(),
            'checksum' => substr($this->normalized, 2, 2),
            'bban' => $this->getBban(),
            'bank' => $this->getBankCode(),
            'branch' => $this->getBranchCode(),
            'account' => $this->getAccountNumber(),
            'national_checksum' => $this->getNationalChecksum(),
        ];
    }

    public static function getSuggestions(string $incorrectIban): array
    {
        $normalized = self::normalize($incorrectIban);
        $length = strlen($normalized);
        if ($length < 5 || $length > 34) return [];

        $suggestions = [];
        for ($i = 0; $i < $length; $i++) {
            $char = $normalized[$i];
            if (!isset(self::MISTRANSCRIPTIONS[$char])) continue;

            foreach (self::MISTRANSCRIPTIONS[$char] as $possible) {
                if (($i < 2 && !ctype_alpha($possible)) || ($i >= 2 && $i <= 3 && !ctype_digit($possible))) continue;
                $candidate = substr($normalized, 0, $i) . $possible . substr($normalized, $i + 1);
                if (self::validate($candidate)) $suggestions[] = $candidate;
            }
        }
        return array_unique($suggestions);
    }

    // --- Validation Rules Helpers --- //

    private function verifyNationalChecksum(): ?bool
    {
        $countryCode = $this->getCountryCode();
        $method = 'verify' . $countryCode;
        if (method_exists($this, $method)) return $this->$method();

        $mod9710Countries = ['ME', 'MK', 'PT', 'RS', 'SI', 'TL'];
        if (in_array($countryCode, $mod9710Countries)) return $this->verifyMod9710();

        return null;
    }

    private function verifyMod9710(): bool
    {
        $bban = $this->getBban();
        $checksum = substr($bban, -2);
        $base = substr($bban, 0, -2);
        $numeric = '';
        foreach (str_split($base) as $char) {
            $numeric .= ctype_digit($char) ? $char : (ord(strtoupper($char)) - 55);
        }
        $p = 0;
        foreach (str_split($numeric) as $digit) {
            $p = (($p + (int)$digit) * 10) % 97;
        }
        $p = ($p * 10) % 97;
        $expected = 98 - ($p % 97);
        if ($expected >= 97) $expected -= 97;
        return (int)$checksum === $expected;
    }

    private function verifyIT(): bool
    {
        $bban = $this->getBban();
        $evenList = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28];
        $oddList = [1, 0, 5, 7, 9, 13, 15, 17, 19, 21, 2, 4, 18, 20, 11, 3, 6, 8, 12, 14, 16, 10, 22, 25, 24, 23, 27, 28, 26];
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-. ';
        $sum = 0;
        for ($i = 0; $i < 22; $i++) {
            $pos = strpos($chars, $bban[$i + 1]);
            if ($pos === false) return false;
            $sum += ($i % 2 === 0 ? $oddList[$pos] : $evenList[$pos]);
        }
        return $bban[0] === chr(65 + ($sum % 26));
    }

    private function verifySM(): bool { return $this->verifyIT(); }

    private function verifyNL(): bool
    {
        if ($this->getBankCode() === 'INGB') return true;
        $account = $this->getAccountNumber();
        if (!$account || strlen($account) < 10) return false;
        $sum = 0;
        for ($i = 0; $i < 10; $i++) { $sum += (int)$account[$i] * (10 - $i); }
        return ($sum % 11) === 0;
    }

    private function verifyBE(): bool
    {
        $bban = $this->getBban();
        return ((int)substr($bban, 0, -2) % 97) === (int)substr($bban, -2);
    }

    private function verifyES(): bool
    {
        $bban = $this->getBban();
        if (strlen($bban) !== 20) return false;
        $bank = substr($bban, 0, 4);
        $branch = substr($bban, 4, 4);
        $checksum = substr($bban, 8, 2);
        $account = substr($bban, 10, 10);

        $calculateMod11 = function(string $num): string {
            $weights = [1, 2, 4, 8, 5, 10, 9, 7, 3, 6];
            $sum = 0;
            for ($i = 0; $i < 10; $i++) { $sum += (int)$num[$i] * $weights[$i]; }
            $check = 11 - ($sum % 11);
            if ($check === 11) $check = 0;
            if ($check === 10) $check = 1;
            return (string)$check;
        };

        return $checksum === ($calculateMod11("00" . $bank . $branch) . $calculateMod11($account));
    }

    private function verifyFR(): bool
    {
        $numeric = '';
        $conversion = ['A'=>1,'B'=>2,'C'=>3,'D'=>4,'E'=>5,'F'=>6,'G'=>7,'H'=>8,'I'=>9,'J'=>1,'K'=>2,'L'=>3,'M'=>4,'N'=>5,'O'=>6,'P'=>7,'Q'=>8,'R'=>9,'S'=>2,'T'=>3,'U'=>4,'V'=>5,'W'=>6,'X'=>7,'Y'=>8,'Z'=>9];
        foreach (str_split($this->getBban()) as $char) {
            $numeric .= ctype_digit($char) ? $char : ($conversion[$char] ?? '');
        }
        if (strlen($numeric) !== 23) return false;
        $sum = (89 * (int)substr($numeric, 0, 5)) + (15 * (int)substr($numeric, 5, 5)) + (3 * (int)substr($numeric, 10, 11));
        return (int)substr($numeric, 21, 2) === (97 - ($sum % 97));
    }

    private function verifyRS(): bool
    {
        if ($this->getBankCode() === '908') return true;
        return $this->verifyMod9710();
    }

    private function verifySI(): bool
    {
        if (substr($this->getBban(), 0, 2) === '01') return true;
        return $this->verifyMod9710();
    }

    private static function bigModulo97(string $bigInt): int
    {
        if (function_exists('gmp_init')) {
            return (int)gmp_strval(gmp_mod(gmp_init($bigInt, 10), gmp_init('97', 10)));
        }
        $rest = 0;
        foreach (str_split($bigInt, 7) as $part) {
            $rest = (int)($rest . $part) % 97;
        }
        return $rest;
    }

    public function __toString(): string
    {
        return $this->normalized;
    }

    // --- Auto Updater Subsystem --- //

    /**
     * Parses official SWIFT IBAN_Registry.txt (TSV format) and updates this class file in place.
     */
    public static function updateRegistry(string $txtFilePath): void
    {
        if (!file_exists($txtFilePath)) {
            throw new \RuntimeException("Registry file not found: $txtFilePath");
        }

        $raw_data = file_get_contents($txtFilePath);
        if (function_exists('mb_convert_encoding')) {
            $raw_data = mb_convert_encoding($raw_data, 'UTF-8', 'Windows-1252');
        }

        $fp = fopen('php://temp', 'r+');
        fwrite($fp, $raw_data);
        rewind($fp);
        
        $registry = [];
        while (($row = fgetcsv($fp, 0, "\t")) !== false) {
            if (empty($row[0])) continue;
            $fieldName = trim($row[0]);
            array_shift($row);
            $registry[$fieldName] = $row;
        }
        fclose($fp);

        $countries = $registry['IBAN prefix country code (ISO 3166)'] ?? [];
        if (empty($countries)) {
            throw new \RuntimeException("Could not find country codes in TSV. Ensure you have the raw SWIFT IBAN_Registry.txt.");
        }

        $newRegistryBlocks = [];

        foreach ($countries as $i => $code) {
            $country_code = strtoupper(substr(trim((string)$code), 0, 2));
            if (empty($country_code) || $country_code === 'IB') continue;

            $country_name = $registry['Name of country'][$i] ?? '';
            $bban_structure = preg_replace('/[:;]/', '', $registry['BBAN structure'][$i] ?? '');
            $bban_length = (int)preg_replace('/[^\d]/', '', $registry['BBAN length'][$i] ?? '');
            $iban_structure = preg_replace('/, .*$/', '', $registry['IBAN structure'][$i] ?? '');
            $iban_length = (int)preg_replace('/[^\d]/', '', $registry['IBAN length'][$i] ?? '');
            $sepa_raw = $registry['SEPA country'][$i] ?? '';
            $bban_bi_position = $registry['Bank identifier position within the BBAN'][$i] ?? '';

            if ($country_code === 'KZ') $iban_structure = '2!a2!n3!n13!c';
            if ($country_code === 'QA') {
                $bban_structure = '4!a4!n17!c';
                $iban_structure = 'QA2!n4!a4!n17!c';
            }
            if ($country_code === 'JO') {
                $bban_bi_position = '1-4'; // Hardcode fix
            }

            if (str_starts_with($iban_structure, '2!a')) {
                $iban_structure = $country_code . substr($iban_structure, 3);
            }

            $iban_regex = self::swiftToRegex($iban_structure, $country_code);
            [$bks, $bke, $brs, $bre] = self::parseOffsets($bban_bi_position, $bban_structure);
            $isSepa = strtolower(trim($sepa_raw)) === 'yes' ? 'true' : 'false';

            $to_generate = [$country_code => $country_name];
            if ($country_code === 'DK') {
                $to_generate = ['DK' => $country_name, 'FO' => 'Faroe Islands', 'GL' => 'Greenland'];
            } elseif ($country_code === 'FR') {
                $to_generate = ['FR' => $country_name, 'BL' => 'Saint Barthelemy', 'GF' => 'French Guyana', 'GP' => 'Guadelope', 'MF' => 'Saint Martin (French Part)', 'MQ' => 'Martinique', 'RE' => 'Reunion', 'PF' => 'French Polynesia', 'TF' => 'French Southern Territories', 'YT' => 'Mayotte', 'NC' => 'New Caledonia', 'PM' => 'Saint Pierre et Miquelon', 'WF' => 'Wallis and Futuna Islands'];
            }

            foreach ($to_generate as $cCode => $cName) {
                // Carry over custom CS/CE values
                $cs = self::REGISTRY[$cCode]['cs'] ?? 'NULL';
                $ce = self::REGISTRY[$cCode]['ce'] ?? 'NULL';
                
                $cNameClean = str_replace("'", "\\'", $cName);
                $bksStr = $bks ?? 'NULL';
                $bkeStr = $bke ?? 'NULL';
                $brsStr = $brs ?? 'NULL';
                $breStr = $bre ?? 'NULL';
                $csStr = $cs === 'NULL' || $cs === null ? 'NULL' : $cs;
                $ceStr = $ce === 'NULL' || $ce === null ? 'NULL' : $ce;

                // Adjust Regex for cloned territories (e.g. FR -> GF)
                $clonedRegex = $iban_regex;
                if ($cCode !== $country_code) {
                    $clonedRegex = '^' . $cCode . substr($iban_regex, 3);
                }

                $newRegistryBlocks[$cCode] = "        '$cCode' => ['n' => '$cNameClean', 'il' => $iban_length, 'bl' => $bban_length, 'r' => '$clonedRegex', 'bks' => $bksStr, 'bke' => $bkeStr, 'brs' => $brsStr, 'bre' => $breStr, 's' => $isSepa, 'cs' => $csStr, 'ce' => $ceStr],";
            }
        }

        ksort($newRegistryBlocks);
        $newRegistryString = "[\n" . implode("\n", $newRegistryBlocks) . "\n    ]";

        $classFile = (new \ReflectionClass(self::class))->getFileName();
        $content = file_get_contents($classFile);
        $startToken = 'private const REGISTRY = [';
        $endToken = '    ];';
        $startPos = strpos($content, $startToken);
        $endPos = strpos($content, $endToken, $startPos);

        if ($startPos !== false && $endPos !== false) {
            $updatedContent = substr_replace($content, "private const REGISTRY = " . $newRegistryString . ";", $startPos, $endPos - $startPos + 6);
            file_put_contents($classFile, $updatedContent);
            echo "✅ Successfully synced " . count($newRegistryBlocks) . " countries and updated " . basename($classFile) . "!\n";
        } else {
            throw new \RuntimeException("Failed to locate REGISTRY array block in source file.");
        }
    }

    private static function swiftToRegex(string $structure, string $countryCode): string
    {
        $regex = '^';
        if (preg_match('/^[A-Z]{2}/', $structure)) {
            $regex .= substr($structure, 0, 2);
            $structure = substr($structure, 2);
        } else {
            $regex .= $countryCode;
        }

        preg_match_all('/(\d+)!([anc])/', $structure, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $len = $m[1];
            $type = $m[2];
            if ($type === 'n') $char = '\d';
            elseif ($type === 'c') $char = '[A-Za-z0-9]';
            elseif ($type === 'a') $char = '[A-Z]';
            else $char = '.';
            
            $regex .= "($char{{$len}})";
        }
        return $regex . '$';
    }

    private static function parseOffsets(string $positionText, string $bbanStructure): array
    {
        $bks = null; $bke = null; $brs = null; $bre = null;
        preg_match_all('/(\d)-(\d\d?)/', $positionText, $matches, PREG_SET_ORDER);
        
        $tokens = [];
        foreach ($matches as $m) {
            $from = (int)$m[1];
            $to = (int)$m[2];
            if (!isset($tokens[$from]) || $to < $tokens[$from]) {
                $tokens[$from] = $to;
            }
        }
        
        if (isset($tokens[1])) { $bks = 0; $bke = $tokens[1] - 1; unset($tokens[1]); }
        elseif (isset($tokens[2])) { $bks = 0; $bke = $tokens[2] - 1; unset($tokens[2]); }
        
        $keys = array_keys($tokens);
        if (!empty($keys)) {
            $start = $keys[0];
            $brs = $start - 1;
            $bre = $tokens[$start] - 1;
        } else {
            $reduced = preg_replace('/^\d+![nac]/', '', $bbanStructure);
            preg_match_all('/(\d+)!([anc])/', $reduced ?? '', $structMatches, PREG_SET_ORDER);
            $validTokens = [];
            $currentOffset = $bke !== null ? $bke + 1 : 0;
            
            foreach ($structMatches as $m) {
                $len = (int)$m[1];
                if ($len >= 3) {
                    $validTokens[] = ['s' => $currentOffset, 'e' => $currentOffset + $len - 1];
                }
                $currentOffset += $len;
            }
            if (count($validTokens) >= 2) {
                $brs = $validTokens[0]['s'];
                $bre = $validTokens[0]['e'];
            }
        }
        return [$bks, $bke, $brs, $bre];
    }
}
