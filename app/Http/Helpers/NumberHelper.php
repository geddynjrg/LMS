<?php

if (!function_exists('format_smart_number')) {
    function format_smart_number($number) {
        if (!is_numeric($number)) return $number;

        if ($number < 10000000) { // Below 10 Million
            return number_format($number);
        }

        if ($number < 1000000000) { // Below 1 Billion (10M to 999M)
            return number_format($number / 1000000, 3) . ' M';
        }

        // 1 Billion and above
        return number_format($number / 1000000000, 3) . ' B';
    }
}

