<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../class/xem-danh-sach-truong.php';
require_once __DIR__ . '/../../components/layout/header.php';
require_once __DIR__ . '/../../components/layout/sidebar.php';
function dstH(mixed $v): string { return PhatDb::escape($v); }
$search = trim((string)($_GET['q'] ?? ''));
$type = strtoupper(trim((string)($_GET['loai'] ?? '')));
if (!in_array($type, ['THCS','THPT'], true)) $type = '';
// Lọc theo quận/khu vực được ghi TRỰC TIẾP trong diachi. Không sử dụng maphuong sai.
$area = trim((string)($_GET['khuvuc'] ?? ''));
$selected = filter_input(INPUT_GET, 'truong', FILTER_VALIDATE_INT);
$selected = ($selected !== false && $selected !== null && $selected > 0) ? $selected : 0;
$schools = []; $areas = []; $exam = null; $failure = '';
try {
    $db = PhatDb::connect();
    $ctrl = new PhatDanhSachTruong($db);
    $exam = $ctrl->exam();
    $areas = $ctrl->areas();
    $schools = $ctrl->schools($search, $type, $area, (int)($exam['makythi'] ?? 0));
    $db->close();
} catch (Throwable $e) {
    error_log('Danh sach truong: ' . get_class($e) . ' - ' . $e->getMessage());
    $failure = 'Không thể tải danh sách trường. Vui lòng thử lại sau.';
}
// Chỉ hiện khu vực thực sự có trường thuộc loại đang lọc.
$areaOptions = [];
foreach ($areas as $a) {
    $count = $type === 'THPT' ? (int)$a['so_thpt']
           : ($type === 'THCS' ? (int)$a['so_thcs'] : (int)$a['so_truong']);
    if ($count > 0 || (string)$a['khuvuc'] === $area) {
        $a['so_truong_theo_loc'] = $count;
        $areaOptions[] = $a;
    }
}
$selectedSchool = null;
foreach ($schools as $school) {
    if ((int)$school['matruong'] === $selected) { $selectedSchool = $school; break; }
}
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Danh sách trường | Stackers</title>
<script src="https://cdn.tailwindcss.com"></script>
<!-- <link rel="stylesheet" href="../../assets/css/phat-tailwind.css"> -->
<style>
.dst-layout{padding-top:5rem;min-height:100vh;background:#f8fafc;box-sizing:border-box}.dst-main{box-sizing:border-box;margin:auto;}.dst-title{font-size:28px;line-height:1.3;margin:0 0 6px;font-weight:750}.dst-sub{font-size:14px}.dst-panel{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;margin-top:18px;box-shadow:0 2px 6px #0f172a0a}.dst-filter{display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:12px;align-items:end}.dst-field label{display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:7px}.dst-field input,.dst-field select{width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:9px;padding:10px 12px;background:white;font:inherit;font-size:14px}.dst-button{border:0;border-radius:10px;background:#1d4ed8;color:#fff;padding:11px 18px;font-weight:650;cursor:pointer}.dst-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px;margin-top:18px}.dst-school{background:#fff;border:1px solid #e2e8f0;border-radius:13px;padding:18px;min-width:0}.dst-school h2{font-size:18px;margin:8px 0 12px;color:#0f172a}.dst-pill{font-size:12px;font-weight:700;border-radius:99px;padding:5px 9px;background:#dbeafe;color:#1d4ed8}.dst-info{font-size:14px;color:#475569;margin:7px 0;overflow-wrap:anywhere}.dst-link{display:inline-flex;margin-top:8px;color:#1d4ed8;font-size:14px;font-weight:650;text-decoration:none}.dst-detail{border-color:#93c5fd;background:#eff6ff}.dst-notice{border:1px solid #fecaca;background:#fff1f2;color:#991b1b;border-radius:10px;padding:15px;margin-top:16px}
@media(max-width:1000px){.dst-filter{grid-template-columns:1fr 1fr}.dst-grid{grid-template-columns:1fr}}@media(max-width:800px){body.dst-page>aside{display:none}.dst-layout{margin-left:0}}@media(max-width:600px){.dst-main{padding:18px 12px 40px}.dst-filter{grid-template-columns:1fr}.dst-panel{padding:14px}}
</style></head>
<body class="dst-page m-0 bg-slate-50 font-sans">
<div class="bg-blue-600 dst-layout"><main class="dst-main">
    <div class="bg-blue-600 px-24 pb-10 pt-6 rounded-b-2xl">
        <h1 class="dst-title text-white">Danh sách trường tuyển sinh lớp 10</h1>
        <p class="dst-sub text-white">Tìm kiếm trường theo tên, loại và quận/khu vực ghi trong địa chỉ. <?= $exam ? 'Kỳ tuyển sinh '.dstH($exam['namtuyensinh']).'.' : '' ?></p>
        <form class="dst-panel dst-filter" method="get" action="">
            <div class="dst-field"><label for="q">Tên trường</label><input id="q" type="search" maxlength="100" name="q" value="<?=dstH($search)?>" placeholder="Nhập tên trường cần tìm..."></div>
            <div class="dst-field"><label for="loai">Loại trường</label><select name="loai" id="loai" onchange="this.form.elements['khuvuc'].value='';this.form.requestSubmit();"><option value="">Tất cả</option><option value="THCS" <?=$type==='THCS'?'selected':''?>>THCS</option><option value="THPT" <?=$type==='THPT'?'selected':''?>>THPT</option></select></div>
            <div class="dst-field"><label for="khuvuc">Quận / Khu vực</label><select name="khuvuc" id="khuvuc"><option value="">Tất cả</option><?php foreach ($areaOptions as $a): ?><option value="<?=dstH($a['khuvuc'])?>" <?=(string)$a['khuvuc']===$area?'selected':''?>><?=dstH($a['khuvuc'])?> (<?=(int)$a['so_truong_theo_loc']?> trường)</option><?php endforeach;?></select></div>
            <button class="dst-button" type="submit">Tìm kiếm</button>
        </form>
        <p class="dst-sub mt-3 text-white">Lưu ý: Database hiện chỉ ghi quận/khu vực trong địa chỉ, chưa có phường cụ thể để xác minh. Bộ lọc này không dùng giá trị <code>maphuong</code> đang bị gán sai.</p>
        <?php if ($failure): ?><div class="dst-notice"><?=dstH($failure)?></div><?php else: ?>
        <p class="dst-sub mt-5 text-white">Tìm thấy <b><?=count($schools)?></b> trường phù hợp.</p>
        <?php if ($selectedSchool): ?>
            <section class="dst-panel dst-detail" aria-label="Thông tin chi tiết trường">
                <h2 class="text-xl font-bold mb-2"><?=dstH($selectedSchool['tentruong'])?></h2>
                <p class="dst-info"><b>Loại trường:</b> <?=dstH($selectedSchool['loaitruong'])?></p>
                <p class="dst-info"><b>Địa chỉ:</b> <?=dstH($selectedSchool['diachi'] ?: 'Chưa cập nhật')?></p>
                <p class="dst-info"><b>Quận/Khu vực:</b> <?=dstH($selectedSchool['khuvuc'] ?: 'Chưa cập nhật')?></p>
                <p class="dst-info"><b>Điểm thi:</b> <?= (string)$selectedSchool['ladiemthi']==='1' ? 'Có' : 'Không' ?></p>
                <p class="dst-info"><b>Chỉ tiêu tuyển sinh đã duyệt:</b> <?= $selectedSchool['chitieu'] === null ? 'Chưa công bố' : dstH($selectedSchool['chitieu']).' học sinh' ?></p>
                <a class="dst-link" href="?<?=dstH(http_build_query(['q'=>$search,'loai'=>$type,'khuvuc'=>$area]))?>">Thu gọn chi tiết</a>
            </section>
        <?php endif; ?>
    </div>
    <div class="dst-grid px-24">
        <?php if (!$schools): ?><div class="dst-panel">
            <?php if ($area !== ''): ?>
                Không tìm thấy trường <?=dstH($type ?: 'THCS/THPT')?> phù hợp tại khu vực <b><?=dstH($area)?></b>.
                Thử khu vực khác hoặc <a class="dst-link" href="?<?=dstH(http_build_query(['q'=>$search,'loai'=>$type,'khuvuc'=>'']))?>">bỏ lọc khu vực</a>.
            <?php else: ?>
                Không tìm thấy trường phù hợp. Bạn hãy thay đổi từ khóa hoặc bộ lọc.
            <?php endif; ?>
        </div><?php endif; ?>
        <?php foreach ($schools as $s): ?>
        <article class="dst-school"><span class="dst-pill"><?=dstH($s['loaitruong'])?></span>
            <h2><?=dstH($s['tentruong'])?></h2>
            <p class="dst-info"><b>Địa chỉ:</b> <?=dstH($s['diachi'] ?: 'Chưa cập nhật')?></p>
            <p class="dst-info"><b>Quận/Khu vực:</b> <?=dstH($s['khuvuc'] ?: 'Chưa cập nhật')?></p>
            <p class="dst-info"><b>Chỉ tiêu đã duyệt:</b> <?= $s['chitieu'] === null ? 'Chưa công bố' : dstH($s['chitieu']).' học sinh' ?></p>
            <a class="dst-link" href="?<?=dstH(http_build_query(['q'=>$search,'loai'=>$type,'khuvuc'=>$area,'truong'=>$s['matruong']]))?>">Xem chi tiết trường</a>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main></div></body></html>