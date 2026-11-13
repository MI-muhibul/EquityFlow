<?php
declare(strict_types=1);

/**
 * helpers/sparkline.php
 * -----------------------------------------------------------------------------
 * Zero-JavaScript Accessible Responsive Inline SVG Sparkline Generator.
 *
 * Rules & Features:
 *   - Strictly zero JavaScript.
 *   - Scales with its parent container via viewBox and responsive width/height.
 *   - Generates an inline SVG <polyline> from an array of numbers.
 *   - Handles empty array gracefully (renders accessible placeholder line/message).
 *   - Handles single-point data gracefully (renders centered dot or flat baseline).
 *   - Includes an accessible <title> element with human-readable summary.
 *   - Dark high-contrast styling matching the EquityFlow UI system.
 * -----------------------------------------------------------------------------
 */

/**
 * Generate an accessible responsive inline SVG sparkline graph.
 *
 * @param array<int|float> $values Array of numeric values
 * @param array{
 *     width?: int,
 *     height?: int,
 *     stroke?: string,
 *     strokeWidth?: float,
 *     fill?: string,
 *     title?: string,
 *     unit?: string
 * } $options
 * @return string Inline SVG markup
 */
function render_sparkline(array $values, array $options = []): string
{
    $width       = (int)($options['width'] ?? 240);
    $height      = (int)($options['height'] ?? 60);
    $stroke      = (string)($options['stroke'] ?? '#10b981'); // emerald
    $strokeWidth = (float)($options['strokeWidth'] ?? 2.5);
    $unit        = (string)($options['unit'] ?? '৳');
    $padding     = 8.0;

    // Filter to purely numeric values and re-index
    $nums = [];
    foreach ($values as $v) {
        if (is_numeric($v)) {
            $nums[] = (float)$v;
        }
    }

    $count = count($nums);

    // Default accessibility title if none provided
    if (isset($options['title']) && trim($options['title']) !== '') {
        $titleText = htmlspecialchars($options['title'], ENT_QUOTES, 'UTF-8');
    } else {
        if ($count === 0) {
            $titleText = 'No data points available';
        } else {
            $first = number_format($nums[0], 2, '.', ',');
            $last  = number_format($nums[$count - 1], 2, '.', ',');
            $min   = number_format(min($nums), 2, '.', ',');
            $max   = number_format(max($nums), 2, '.', ',');
            $titleText = "Trend: {$count} points. Range: {$unit}{$min} to {$unit}{$max}. Latest: {$unit}{$last}.";
            $titleText = htmlspecialchars($titleText, ENT_QUOTES, 'UTF-8');
        }
    }

    $viewBox = "0 0 {$width} {$height}";
    $svgOpen = "<svg viewBox=\"{$viewBox}\" preserveAspectRatio=\"none\" width=\"100%\" height=\"{$height}\" "
             . "role=\"img\" aria-label=\"{$titleText}\" style=\"display: block; overflow: visible;\">"
             . "<title>{$titleText}</title>";

    // Case 1: Empty dataset
    if ($count === 0) {
        $midY = $height / 2.0;
        return $svgOpen
            . "<line x1=\"{$padding}\" y1=\"{$midY}\" x2=\"" . ($width - $padding) . "\" y2=\"{$midY}\" "
            . "stroke=\"#475569\" stroke-width=\"1.5\" stroke-dasharray=\"4 4\" />"
            . "<text x=\"" . ($width / 2.0) . "\" y=\"" . ($midY - 6) . "\" text-anchor=\"middle\" "
            . "fill=\"#94a3b8\" font-size=\"10\" font-family=\"sans-serif\">No trend data</text>"
            . "</svg>";
    }

    // Case 2: Single data point
    if ($count === 1) {
        $midY = $height / 2.0;
        $valFormatted = number_format($nums[0], 2, '.', ',');
        return $svgOpen
            . "<line x1=\"{$padding}\" y1=\"{$midY}\" x2=\"" . ($width - $padding) . "\" y2=\"{$midY}\" "
            . "stroke=\"{$stroke}\" stroke-width=\"{$strokeWidth}\" opacity=\"0.5\" />"
            . "<circle cx=\"" . ($width / 2.0) . "\" cy=\"{$midY}\" r=\"4\" fill=\"{$stroke}\" />"
            . "<text x=\"" . ($width / 2.0) . "\" y=\"" . ($midY - 8) . "\" text-anchor=\"middle\" "
            . "fill=\"#f8fafc\" font-size=\"10\" font-family=\"monospace\" font-weight=\"bold\">{$unit}{$valFormatted}</text>"
            . "</svg>";
    }

    // Case 3: Multiple data points
    $minVal = min($nums);
    $maxVal = max($nums);
    $range  = $maxVal - $minVal;

    // Usable drawing area inside padding
    $usableW = (float)($width - ($padding * 2));
    $usableH = (float)($height - ($padding * 2));

    $points = [];
    $dx = $usableW / ($count - 1);

    for ($i = 0; $i < $count; $i++) {
        $x = $padding + ($i * $dx);

        if ($range == 0.0) {
            // Flat horizontal line if all numbers are identical
            $y = $height / 2.0;
        } else {
            // Invert Y because SVG coordinates origin (0,0) is top-left
            $normalized = ($nums[$i] - $minVal) / $range;
            $y = ($height - $padding) - ($normalized * $usableH);
        }

        $points[] = round($x, 2) . ',' . round($y, 2);
    }

    $pointsAttr = implode(' ', $points);

    // Optional subtle gradient area fill underneath the curve
    $lastX = round($padding + (($count - 1) * $dx), 2);
    $firstX = round($padding, 2);
    $baseY = (float)($height - $padding);
    $fillPolygon = "{$firstX},{$baseY} {$pointsAttr} {$lastX},{$baseY}";

    // Dynamic gradient ID
    $gradId = 'grad_spark_' . substr(md5($pointsAttr), 0, 8);

    // Determine stroke color dynamically if negative profit
    $isNegativeTrend = ($nums[$count - 1] < 0);
    $activeStroke = $isNegativeTrend ? '#ef4444' : $stroke;

    $lastPoint = explode(',', $points[$count - 1]);
    $lastPx = (float)$lastPoint[0];
    $lastPy = (float)$lastPoint[1];

    return $svgOpen
        . "<defs>"
        . "<linearGradient id=\"{$gradId}\" x1=\"0\" y1=\"0\" x2=\"0\" y2=\"1\">"
        . "<stop offset=\"0%\" stop-color=\"{$activeStroke}\" stop-opacity=\"0.28\" />"
        . "<stop offset=\"100%\" stop-color=\"{$activeStroke}\" stop-opacity=\"0.0\" />"
        . "</linearGradient>"
        . "</defs>"
        . "<polygon points=\"{$fillPolygon}\" fill=\"url(#{$gradId})\" />"
        . "<polyline fill=\"none\" stroke=\"{$activeStroke}\" stroke-width=\"{$strokeWidth}\" "
        . "stroke-linecap=\"round\" stroke-linejoin=\"round\" points=\"{$pointsAttr}\" />"
        . "<circle cx=\"{$lastPx}\" cy=\"{$lastPy}\" r=\"3.5\" fill=\"{$activeStroke}\" />"
        . "</svg>";
}
