<?php

/** Biểu đồ dùng chính các dòng báo cáo, không truy vấn hoặc lọc lại dữ liệu. */
function renderBieuDoThongKe(array $rows): void
{
    if (!$rows) return;
    $escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    $count = count($rows);
    $width = max(640, 80 + $count * 144);
    $left = 58;
    $right = $width - 24;
    $top = 36;
    $bottom = 256;
    $step = ($right - $left) / $count;
    $maximum = max(1, ...array_map(static fn($row) => (int)$row['luot'], $rows));
    $tick = max(1, (int)ceil($maximum / 4));
    $ceiling = $tick * 4;
    $x = static fn($i) => round($left + ($i + 0.5) * $step, 2);
    $y = static fn($value, $max) => round($bottom - ($value / $max) * ($bottom - $top), 2);
    $labels = static function () use ($rows, $x, $escape): void {
        foreach ($rows as $i => $row) {
            $words = preg_split('/\s+/u', trim($row['nhom']));
            $lines = [''];
            foreach ($words as $word) {
                $last = count($lines) - 1;
                if ($lines[$last] !== '' && mb_strlen($lines[$last] . ' ' . $word) > 20) $lines[] = $word;
                else $lines[$last] = trim($lines[$last] . ' ' . $word);
            }
            if (count($lines) > 3) $lines = [$lines[0], $lines[1], mb_substr($lines[2], 0, 17) . '…'];
            echo '<text x="' . $x($i) . '" y="282" text-anchor="middle" class="chart-label"><title>' . $escape($row['nhom']) . '</title>';
            foreach ($lines as $line => $text) echo '<tspan x="' . $x($i) . '" dy="' . ($line ? 18 : 0) . '">' . $escape($text) . '</tspan>';
            echo '</text>';
        }
    };
    ?>
    <div class="report-charts">
        <section class="candidate-card report-chart" aria-labelledby="bar-heading">
            <h3 id="bar-heading">Lượt đăng ký và trúng tuyển</h3>
            <p class="chart-description">So sánh số lượt nguyện vọng giữa các nhóm.</p>
            <div class="chart-legend"><span><i class="legend-registered"></i>Lượt đăng ký</span><span><i class="legend-admitted"></i>Lượt trúng tuyển</span></div>
            <div class="chart-scroll" tabindex="0" role="region" aria-label="Biểu đồ cột; cuộn ngang để xem tất cả nhóm">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 <?= $width ?> 348" style="min-width:<?= $width ?>px" role="img" aria-labelledby="bar-chart-title bar-chart-desc">
                    <title id="bar-chart-title">Biểu đồ cột lượt đăng ký và trúng tuyển</title>
                    <desc id="bar-chart-desc">Trục dọc là số lượt, trục ngang là các nhóm theo bộ lọc. Số liệu đầy đủ nằm trong bảng bên dưới. Trúng tuyển bao gồm đã xác nhận nhập học.</desc>
                    <text x="8" y="18" class="chart-label">Số lượt</text>
                    <?php for ($i = 0; $i <= 4; $i++): $value = $tick * $i; $cy = $y($value, $ceiling); ?>
                        <line x1="<?= $left ?>" x2="<?= $right ?>" y1="<?= $cy ?>" y2="<?= $cy ?>" class="chart-grid"/>
                        <text x="<?= $left - 10 ?>" y="<?= $cy + 4 ?>" text-anchor="end" class="chart-label"><?= number_format($value, 0, ',', '.') ?></text>
                    <?php endfor; ?>
                    <?php foreach ($rows as $i => $row): ?>
                        <?php foreach (['luot' => ['#2563eb', -30, 'Lượt đăng ký'], 'dau' => ['#059669', 4, 'Lượt trúng tuyển']] as $key => [$color, $offset, $label]): $value = (int)$row[$key]; $cy = $y($value, $ceiling); ?>
                            <g tabindex="0" class="chart-mark" aria-label="<?= $escape($row['nhom'] . ': ' . $label . ' ' . $value) ?>">
                                <title><?= $escape($row['nhom'] . ' · ' . $label . ': ' . $value) ?></title>
                                <rect x="<?= $x($i) + $offset ?>" y="<?= $cy ?>" width="26" height="<?= $bottom - $cy ?>" rx="4" fill="<?= $color ?>"/>
                                <text x="<?= $x($i) + $offset + 13 ?>" y="<?= $cy - 8 ?>" text-anchor="middle" class="chart-value" fill="<?= $color ?>"><?= number_format($value, 0, ',', '.') ?></text>
                            </g>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    <?php $labels(); ?>
                </svg>
            </div>
        </section>
        <section class="candidate-card report-chart" aria-labelledby="line-heading">
            <h3 id="line-heading">Tỷ lệ trúng tuyển theo nhóm</h3>
            <p class="chart-description">Lượt trúng tuyển / lượt đăng ký × 100%.</p>
            <div class="chart-legend"><span><i class="legend-rate"></i>Tỷ lệ trúng tuyển</span></div>
            <div class="chart-scroll" tabindex="0" role="region" aria-label="Biểu đồ đường; cuộn ngang để xem tất cả nhóm">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 <?= $width ?> 348" style="min-width:<?= $width ?>px" role="img" aria-labelledby="line-chart-title line-chart-desc">
                    <title id="line-chart-title">Biểu đồ đường tỷ lệ trúng tuyển theo nhóm</title>
                    <desc id="line-chart-desc">So sánh các nhóm theo cùng thứ tự với bảng, không biểu diễn thay đổi theo thời gian. Tỷ lệ tính theo lượt nguyện vọng, không phải tỷ lệ thí sinh trúng tuyển.</desc>
                    <text x="8" y="18" class="chart-label">Tỷ lệ (%)</text>
                    <?php for ($value = 0; $value <= 100; $value += 25): $cy = $y($value, 100); ?>
                        <line x1="<?= $left ?>" x2="<?= $right ?>" y1="<?= $cy ?>" y2="<?= $cy ?>" class="chart-grid"/>
                        <text x="<?= $left - 10 ?>" y="<?= $cy + 4 ?>" text-anchor="end" class="chart-label"><?= $value ?>%</text>
                    <?php endfor; ?>
                    <?php
                    $points = [];
                    $rates = [];
                    foreach ($rows as $i => $row) {
                        $rates[$i] = (int)$row['luot'] > 0 ? (int)$row['dau'] / (int)$row['luot'] * 100 : 0;
                        $points[] = $x($i) . ',' . $y($rates[$i], 100);
                    }
                    ?>
                    <polyline points="<?= implode(' ', $points) ?>" fill="none" stroke="#7c3aed" stroke-width="3" stroke-linejoin="round"/>
                    <?php foreach ($rows as $i => $row): $cy = $y($rates[$i], 100); $rate = number_format($rates[$i], 1, ',', '.') . '%'; ?>
                        <g tabindex="0" class="chart-mark" aria-label="<?= $escape($row['nhom'] . ': ' . $rate) ?>">
                            <title><?= $escape($row['nhom'] . ' · ' . $row['dau'] . '/' . $row['luot'] . ' lượt · ' . $rate) ?></title>
                            <circle cx="<?= $x($i) ?>" cy="<?= $cy ?>" r="5" fill="#fff" stroke="#7c3aed" stroke-width="3"/>
                            <text x="<?= $x($i) ?>" y="<?= $cy - 12 ?>" text-anchor="middle" class="chart-value" fill="#7c3aed"><?= $rate ?></text>
                        </g>
                    <?php endforeach; ?>
                    <?php $labels(); ?>
                </svg>
            </div>
        </section>
    </div>
    <p class="report-help">Biểu đồ dùng cùng bộ lọc và thứ tự nhóm với bảng bên dưới. Đường nối giúp so sánh các nhóm, không thể hiện xu hướng theo thời gian. Có thể cuộn ngang khi có nhiều nhóm.</p>
    <?php
}
