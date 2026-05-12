<?php
// app/Helpers/color_helpers.php
if (! function_exists('contrast_color')) {
    function contrast_color(string $hex): string
    {
        // Convertit #rrggbb en composantes 0-255
        $r = hexdec(substr($hex, 1, 2));
        $g = hexdec(substr($hex, 3, 2));
        $b = hexdec(substr($hex, 5, 2));

        // Linéarisation sRGB (WCAG 2.1)
        $toLinear = function (int $c): float {
            $s = $c / 255;
            return $s <= 0.04045
                ? $s / 12.92
                : (($s + 0.055) / 1.055) ** 2.4;
        };

        $L = 0.2126 * $toLinear($r)
            + 0.7152 * $toLinear($g)
            + 0.0722 * $toLinear($b);

        return $L > 0.8 ? '#2d2d2d' : '#ffffff';
        // return $L > 0.179 ? '#000000' : '#ffffff';
    }
}