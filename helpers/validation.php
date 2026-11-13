<?php
declare(strict_types=1);

/**
 * helpers/validation.php
 * -----------------------------------------------------------------------------
 * Input validation and sanitization helpers for EquityFlow.
 * -----------------------------------------------------------------------------
 */

/**
 * Validate Bangladeshi mobile phone number.
 * Format: 01[3-9]XXXXXXXX (11 digits total).
 */
function validate_bd_phone(string $phone): bool
{
    return (bool) preg_match('/^01[3-9]\d{8}$/', trim($phone));
}

/**
 * Validate that an amount is a positive number (> 0).
 */
function validate_positive_amount(mixed $amount): bool
{
    return is_numeric($amount) && (float)$amount > 0.0;
}

/**
 * Validate that an amount/quantity is non-negative (>= 0).
 */
function validate_non_negative(mixed $val): bool
{
    return is_numeric($val) && (float)$val >= 0.0;
}

/**
 * Sanitize plain single-line string input.
 */
function clean_string(mixed $input): string
{
    return trim(strip_tags((string)($input ?? '')));
}
