<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['manguoidung']) || empty($_SESSION['vaitro'])) {
    header('Location: /stackers_project/pages/login/');
    exit;
}
if (strtoupper((string)$_SESSION['vaitro']) !== 'HOC_SINH') {
    http_response_code(403);
    echo '<!doctype html><html lang="vi"><meta charset="utf-8"><title>Không có quyền truy cập</title><body style="font-family:Arial,sans-serif;padding:36px">'
        . '<h2>Không có quyền truy cập</h2><p>Chức năng này dành cho học sinh đã đăng nhập.</p>'
        . '<a href="/stackers_project/pages/">Quay lại trang chủ</a></body></html>';
    exit;
}

require_once __DIR__ . '/../../../class/xem-lich-thi.php';
require_once __DIR__ . '/../../../components/layout/header.php';
require_once __DIR__ . '/../../../components/layout/sidebar.php';

function xltEscape(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function xltDate(mixed $value): string
{
    if (!$value || !is_string($value)) return 'Chưa cập nhật';
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10));
    return $date ? $date->format('d/m/Y') : 'Chưa cập nhật';
}
function xltSession(mixed $value): string
{
    return match ((string)$value) {
        '1' => 'Buổi sáng',
        '2' => 'Buổi chiều',
        default => 'Chưa cập nhật',
    };
}
function xltClock(mixed $value): string
{
    if ($value === null || trim((string)$value) === '') return 'Chưa cập nhật';
    $v = (string)$value;
    if (preg_match('/^\d\d:\d\d/', $v)) return substr($v, 0, 5);
    return 'Chưa cập nhật';
}

$view = ['state' => 'error'];
try {
    $database = PhatXemLichThi::connect();
    $service = new PhatXemLichThi($database);
    $view = $service->getScheduleForUser((int)$_SESSION['manguoidung']);
    $database->close();
} catch (Throwable $e) {
    // Không hiển thị thông tin kết nối hoặc câu SQL ra trình duyệt.
    error_log('Xem lich thi - ' . get_class($e) . ': ' . $e->getMessage());
}
$state = $view['state'] ?? 'error';
$candidate = $view['candidate'] ?? [];
$schedules = $view['schedules'] ?? [];
$notice = match ($state) {
    'no_profile' => ['warning', 'Không tìm thấy hồ sơ dự thi', 'Tài khoản của bạn chưa được liên kết với hồ sơ thí sinh. Vui lòng liên hệ nhà trường để được hỗ trợ.'],
    'not_published' => ['warning', 'Lịch thi chưa được công bố', 'Bạn vui lòng quay lại sau khi Sở GD&ĐT thông báo lịch thi chính thức.'],
    'no_schedule' => ['warning', 'Chưa có lịch thi', 'Hiện chưa có buổi thi nào được cập nhật cho kỳ tuyển sinh này.'],
    'ready' => null,
    default => ['error', 'Không thể tải lịch thi', 'Hệ thống đang gặp sự cố. Vui lòng thử lại sau.'],
};
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Xem lịch thi | Stackers</title>
    <link rel="stylesheet" href="../../../assets/css/phat-tailwind.css">
    <style>
        /* Bố cục riêng trang Xem lịch thi; không ảnh hưởng giao diện dùng chung. */
        .xlt-content {
            box-sizing: border-box;
            min-width: 0;
            margin-left: 18rem; /* Bằng đúng w-72 của thanh menu. */
            padding-top: 5rem; /* Bằng chiều cao h-20 của header. */
        }
        .xlt-main {
            box-sizing: border-box;
            width: 100%;
            max-width: 1180px;
            min-width: 0;
            margin: 0 auto;
            padding: 28px 24px 64px;
        }
        .xlt-overview {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }
        .xlt-overview > * { min-width: 0; }
        .xlt-wide-card { grid-column: span 2; }
        .xlt-overview p { overflow-wrap: anywhere; }
        .xlt-schedule-panel { min-width: 0; padding: 24px; }
        .xlt-table-wrap { width: 100%; max-width: 100%; overflow-x: auto; }
        .xlt-schedule-table { width: 100%; min-width: 690px; }
        @media (max-width: 1050px) and (min-width: 801px) {
            .xlt-overview { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 800px) {
            /* Sidebar của project luôn fixed; trên màn hẹp ẩn riêng tại trang này. */
            body.xlt-page > aside { display: none; }
            .xlt-content { margin-left: 0; }
        }
        @media (max-width: 640px) {
            .xlt-main { padding: 20px 12px 40px; }
            .xlt-overview { grid-template-columns: 1fr; }
            .xlt-wide-card { grid-column: auto; }
            .xlt-schedule-panel { padding: 16px; }
        }
    </style>
</head>
<body class="xlt-page m-0 bg-[#f5f7fb] font-sans text-slate-800">
    <?php renderHeader(); ?>
    <?php renderSidebar(); ?>
    <div class="xlt-content min-h-screen bg-slate-50">
        <main class="xlt-main">
            <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="m-0 text-3xl font-bold text-slate-900">Lịch thi tuyển sinh lớp 10</h1>
                    <p class="mt-2 text-sm text-slate-500">Thông tin lịch thi của bạn do Sở Giáo dục và Đào tạo công bố.</p>
                </div>
                <a class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                    href="/stackers_project/pages/hoc-sinh/">Quay lại</a>
            </div>

            <?php if ($candidate): ?>
                <div class="xlt-overview mb-5">
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="m-0 text-xs text-slate-500">Thí sinh</p>
                        <p class="mb-0 mt-2 text-lg font-bold"><?= xltEscape($candidate['hoten'] ?: 'Chưa cập nhật họ tên') ?></p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="m-0 text-xs text-slate-500">Số báo danh</p>
                        <p class="mb-0 mt-2 text-lg font-bold"><?= xltEscape($candidate['sobaodanh'] ?: 'Chưa được cấp') ?></p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="m-0 text-xs text-slate-500">Kỳ tuyển sinh</p>
                        <p class="mb-0 mt-2 text-lg font-bold"><?= xltEscape($candidate['namtuyensinh']) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($notice): ?>
                <div role="status" class="mb-5 rounded-xl border p-5 <?= $notice[0] === 'warning' ? 'border-amber-200 bg-amber-50 text-amber-900' : 'border-red-200 bg-red-50 text-red-800' ?>">
                    <p class="m-0 text-lg font-bold"><?= xltEscape($notice[1]) ?></p>
                    <p class="mb-0 mt-2 text-sm"><?= xltEscape($notice[2]) ?></p>
                </div>
            <?php else: ?>
                <?php $first = $schedules[0]; ?>
                <div class="xlt-overview mb-5">
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="m-0 text-xs text-slate-500">Số buổi thi</p>
                        <p class="mb-0 mt-2 text-2xl font-extrabold text-blue-700"><?= count($schedules) ?></p>
                    </div>
                    <div class="xlt-wide-card rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="m-0 text-xs text-slate-500">Điểm thi</p>
                        <p class="mb-0 mt-2 text-lg font-bold"><?= xltEscape($first['tentruong'] ?: 'Chưa xếp điểm thi') ?></p>
                        <?php if (!empty($first['diachi'])): ?>
                            <p class="mb-0 mt-1 text-sm text-slate-500"><?= xltEscape($first['diachi']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <section class="xlt-schedule-panel rounded-xl border border-slate-200 bg-white shadow-sm" aria-labelledby="schedule-heading">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <h2 id="schedule-heading" class="m-0 text-xl font-bold">Danh sách các buổi thi</h2>
                        <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-800">Lịch đã công bố</span>
                    </div>
                    <div class="xlt-table-wrap">
                        <table class="xlt-schedule-table border-collapse text-sm">
                            <thead>
                                <tr class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                    <th class="border-b border-slate-200 px-3 py-3">Môn thi</th>
                                    <th class="border-b border-slate-200 px-3 py-3">Ngày thi</th>
                                    <th class="border-b border-slate-200 px-3 py-3">Buổi thi</th>
                                    <th class="border-b border-slate-200 px-3 py-3">Giờ bắt đầu</th>
                                    <th class="border-b border-slate-200 px-3 py-3">Phòng thi</th>
                                    <th class="border-b border-slate-200 px-3 py-3">Chi tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $s): ?>
                                    <tr>
                                        <td class="border-b border-slate-100 px-3 py-4 font-bold"><?= xltEscape($s['mon']) ?></td>
                                        <td class="border-b border-slate-100 px-3 py-4"><?= xltEscape(xltDate($s['ngaythi'])) ?></td>
                                        <td class="border-b border-slate-100 px-3 py-4"><?= xltEscape(xltSession($s['buoithi'])) ?></td>
                                        <td class="border-b border-slate-100 px-3 py-4"><?= xltEscape(xltClock($s['giobatdau'])) ?></td>
                                        <td class="border-b border-slate-100 px-3 py-4"><?= xltEscape($s['tenphong'] ?: 'Chưa xếp phòng') ?></td>
                                        <td class="border-b border-slate-100 px-3 py-4">
                                            <details class="group">
                                                <summary class="cursor-pointer select-none whitespace-nowrap font-semibold text-blue-700 hover:underline">Xem chi tiết</summary>
                                                <div class="mt-3 min-w-[230px] rounded-lg border border-blue-100 bg-blue-50 p-3 text-slate-700">
                                                    <p class="m-0"><b>Địa điểm:</b> <?= xltEscape($s['tentruong'] ?: 'Chưa xếp điểm thi') ?></p>
                                                    <?php if (!empty($s['diachi'])): ?>
                                                        <p class="mb-0 mt-2"><b>Địa chỉ:</b> <?= xltEscape($s['diachi']) ?></p>
                                                    <?php endif; ?>
                                                    <p class="mb-0 mt-2"><b>Phòng:</b> <?= xltEscape($s['tenphong'] ?: 'Chưa xếp phòng') ?></p>
                                                    <p class="mb-0 mt-2"><b>Giờ bắt đầu:</b> <?= xltEscape(xltClock($s['giobatdau'])) ?></p>
                                                    <p class="mb-0 mt-2"><b>Giờ kết thúc:</b> <?= xltEscape(xltClock($s['gioketthuc'])) ?></p>
                                                </div>
                                            </details>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="mb-0 mt-4 text-xs text-slate-500">Nếu chưa có phòng thi hoặc giờ thi, bạn vui lòng theo dõi thông báo tiếp theo của nhà trường.</p>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
