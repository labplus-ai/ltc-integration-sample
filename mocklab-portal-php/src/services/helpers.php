<?php
// Small helpers for displaying orders and results.

// Short HTML escape helper.
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Flag for one parameter: 'low', 'high', 'normal', or 'none' when it cannot be judged
// (a text result such as "detected", or no norms at all).
function result_flag(array $param): string
{
    if (!is_numeric($param['value']) || ($param['normLow'] === null && $param['normHigh'] === null)) {
        return 'none';
    }
    if ($param['normLow'] !== null && $param['value'] < $param['normLow']) {
        return 'low';
    }
    if ($param['normHigh'] !== null && $param['value'] > $param['normHigh']) {
        return 'high';
    }
    return 'normal';
}

// Human readable reference range, e.g. "13.5 - 17.5", "< 190" or "> 40".
function reference_text(array $param): string
{
    [$low, $high] = [$param['normLow'], $param['normHigh']];
    if ($low === null && $high === null) {
        return '-';
    }
    if ($low === null) {
        return "< $high";
    }
    return $high === null ? "> $low" : "$low - $high";
}

// All parameters of an order as a flat list.
function order_params(array $order): array
{
    return array_merge(...array_column($order['examinations'], 'params'));
}

// Number of parameters outside their reference range.
function count_out_of_range(array $order): int
{
    $flags = array_map('result_flag', order_params($order));
    return count(array_filter($flags, fn($f) => $f === 'low' || $f === 'high'));
}

// Title of an order for lists and headings: the examination names, or the first one plus "and N more".
function order_title(array $order): string
{
    $names = array_column($order['examinations'], 'name');
    return count($names) <= 2 ? implode(', ', $names) : $names[0] . ' and ' . (count($names) - 1) . ' more';
}
