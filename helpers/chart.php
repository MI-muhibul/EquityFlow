<?php
declare(strict_types=1);

/**
 * helpers/chart.php
 * -----------------------------------------------------------------------------
 * Zero-JavaScript Accessible Responsive Inline SVG Bar Chart Helper.
 *
 * Rules:
 *   - Strictly zero JavaScript.
 *   - SVG scalable via viewBox.
 *   - High-contrast dark styling consistent with EquityFlow theme.
 *   - Uses <title> and aria attributes for screen readers.
 *   - Formats amounts as ৳ with 2 decimals.
 * -----------------------------------------------------------------------------
 */

require_once __DIR__ . '/format.php';

/**
 * Render a horizontal or stacked comparison bar breakdown in pure inline SVG.
 *
 * @param array<string, array{label: string, amount: float, color: string}> $items
 *        e.g. [
 *           'credit' => ['label' => 'Credit Given', 'amount' => 15000.0, 'color' => '#ef4444'],
 *           'payment' => ['label' => 'Payments Received', 'amount' => 12500.0, 'color' => '#10b981'],
 *           'profit' => ['label' => 'Estimated Profit', 'amount' => 3400.0, 'color' => '#3b82f6'],
 *        ]
 * @param array{
 *     width?: int,
 *     height?: int,
 *     title?: string,
 *     unit?: string
 * } $options
 * @return string Inline SVG markup
 */
function render_bar_breakdown(array $items, array $options = []): string
{
    $width  = (int)($options['width'] ?? 600);
    $unit   = (string)($options['unit'] ?? '৳');
    $title  = (string)($options['title'] ?? 'Financial Breakdown Chart');

    // Filter valid positive amounts for scaling
    $amounts = [];
    foreach ($items as $k => $item) {
        $amounts[$k] = max(0.0, (float)($item['amount'] ?? 0.0));
    }

    $maxAmount = max($amounts ?: [0.0]);
    if ($maxAmount <= 0.0) {
        $maxAmount = 1.0; // avoid division by zero
    }

    $rowHeight = 36;
    $paddingTop = 16;
    $paddingBottom = 16;
    $labelWidth = 160;
    $valueWidth = 140;
    $barMaxWidth = $width - $labelWidth - $valueWidth - 24;

    $rowCount = count($items);
    $totalHeight = $paddingTop + ($rowCount * $rowHeight) + $paddingBottom;

    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

    $svg = "<svg viewBox=\"0 0 {$width} {$totalHeight}\" width=\"100%\" height=\"{$totalHeight}\" "
         . "role=\"img\" aria-label=\"{$safeTitle}\" style=\"display: block; overflow: visible;\">\n";
    $svg .= "  <title>{$safeTitle}</title>\n";

    $y = $paddingTop;
    foreach ($items as $key => $item) {
        $label = htmlspecialchars((string)($item['label'] ?? $key), ENT_QUOTES, 'UTF-8');
        $color = htmlspecialchars((string)($item['color'] ?? '#3b82f6'), ENT_QUOTES, 'UTF-8');
        $amount = (float)($item['amount'] ?? 0.0);
        $ratio = max(0.0, $amount) / $maxAmount;
        $barWidth = max(4.0, round($ratio * $barMaxWidth, 1));
        $formattedAmount = format_money($amount);

        $barY = $y + 8;
        $textY = $y + 20;

        // Label text (left-aligned)
        $svg .= "  <text x=\"0\" y=\"{$textY}\" fill=\"#94a3b8\" font-size=\"13\" "
             . "font-family=\"-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif\">{$label}</text>\n";

        // Background track
        $trackX = $labelWidth;
        $svg .= "  <rect x=\"{$trackX}\" y=\"{$barY}\" width=\"{$barMaxWidth}\" height=\"16\" "
             . "rx=\"8\" fill=\"#1e293b\" />\n";

        // Progress bar
        $svg .= "  <rect x=\"{$trackX}\" y=\"{$barY}\" width=\"{$barWidth}\" height=\"16\" "
             . "rx=\"8\" fill=\"{$color}\">\n";
        $svg .= "    <title>{$label}: {$formattedAmount}</title>\n";
        $svg .= "  </rect>\n";

        // Value text (right-aligned)
        $valX = $trackX + $barMaxWidth + 12;
        $svg .= "  <text x=\"{$valX}\" y=\"{$textY}\" fill=\"#f8fafc\" font-size=\"13\" font-weight=\"bold\" "
             . "font-family=\"ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace\">{$formattedAmount}</text>\n";

        $y += $rowHeight;
    }

    $svg .= "</svg>\n";

    return $svg;
}
