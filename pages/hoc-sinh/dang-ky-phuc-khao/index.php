<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['manguoidung']) || empty($_SESSION['vaitro'])) {
    header('Location: /stackers_project/pages/login/'); exit;
}
if (strtoupper((string)$_SESSION['vaitro']) !== 'HOC_SINH') {
    http_response_code(403);
    exit('<!doctype html><html lang="vi"><meta charset="utf-8"><title>Không có quyền truy cập</title><p>Chức năng này dành cho học sinh đã đăng nhập.</p><a href="/stackers_project/pages/">Về trang chủ</a></html>');
}
require_once __DIR__ . '/../../../class/dang-ky-phuc-khao.php';
require_once __DIR__ . '/../../../components/layout/header.php';
require_once __DIR__ . '/../../../components/layout/sidebar.php';
function pkH(mixed $s): string { return PhatDb::escape($s); }
function pkDate(mixed $s): string {
    if (!$s) return 'Chưa cập nhật';
    $t = strtotime((string)$s);
    return $t ? date('d/m/Y', $t) : 'Chưa cập nhật';
}
function pkScore(mixed $v): string {
    return $v === null ? 'Chưa có điểm' : rtrim(rtrim(number_format((float)$v, 2, '.', ''), '0'), '.');
}

$errors = [];
$notice = null;
$student = null;
$papers = [];
$submittedIds = [];
$history = [];
$evidenceNames = [];
$evidenceReady = false;
$canSubmit = false;
$isScorePublished = false;
$allRegistered = false;
$reason = '';
$chosen = [];
if (empty($_SESSION['pk_csrf'])) $_SESSION['pk_csrf'] = bin2hex(random_bytes(32));
if (!empty($_SESSION['pk_message'])) { $notice = $_SESSION['pk_message']; unset($_SESSION['pk_message']); }

try {
    $db = PhatDb::connect();
    $service = new PhatDangKyPhucKhao($db);
    $evidenceReady = $service->evidenceReady();
    $student = $service->student((int)$_SESSION['manguoidung']);
    if ($student) {
        [$canSubmit, $notAllowedReason] = $service->eligibility($student);
        if (!$canSubmit) $notice = $notice ?: $notAllowedReason;
        // Không rò rỉ điểm khi chưa có xác nhận công bố chính thức.
        $isScorePublished = $service->scoreIsPublished((int)$student['makythi']) === true;
        if ($isScorePublished && PhatDb::hasTable($db, 'baithi')) {
            $papers = $service->examPapers((int)$student['mahocsinh']);
        }
        if (PhatDb::hasTable($db, 'phieuphuckhao') && PhatDb::hasTable($db, 'chitietphuckhao')) {
            $submittedIds = $service->existingPaperIds((int)$student['mahocsinh']);
            $history = $service->requests((int)$student['mahocsinh']);
            $evidenceNames = $service->evidenceNames((int)$student['mahocsinh']);
            // Da phuc khao toan bo mon co diem: khong cho nhap va gui them phieu.
            $allRegistered = count($papers) > 0;
            foreach ($papers as $paper) {
                if ($paper['diem'] === null || !in_array((int)$paper['mabaithi'], $submittedIds, true)) {
                    $allRegistered = false;
                    break;
                }
            }
        } else {
            $canSubmit = false;
            $notice = 'Hệ thống chưa sẵn sàng tiếp nhận phiếu phúc khảo. Vui lòng thử lại sau.';
        }
    } else {
        $notice = 'Chưa tìm thấy hồ sơ dự thi gắn với tài khoản của bạn. Vui lòng liên hệ nhà trường.';
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $reason = (string)($_POST['lydo'] ?? '');
        $chosen = is_array($_POST['mabaithi'] ?? null) ? $_POST['mabaithi'] : [];
        if (!hash_equals($_SESSION['pk_csrf'], (string)($_POST['csrf'] ?? ''))) {
            $errors[] = 'Phiên làm việc đã thay đổi. Vui lòng tải lại trang rồi thử lại.';
        } elseif ($allRegistered) {
            $errors[] = 'Bạn đã đăng ký phúc khảo tất cả các môn thi. Không còn môn nào để đăng ký thêm.';
        } elseif (!$canSubmit) {
            $errors[] = $notice ?: 'Hiện chưa thể gửi phiếu phúc khảo.';
        } elseif (($_POST['confirm'] ?? '') !== 'yes') {
            $errors[] = 'Vui lòng xác nhận thông tin trước khi gửi.';
        } else {
            try {
                $requestId = $service->submit((int)$_SESSION['manguoidung'], $chosen, $reason, $_FILES['minhchung'] ?? null);
                $_SESSION['pk_message'] = 'Đã gửi phiếu phúc khảo số ' . $requestId . '. Nhà trường và Sở GD&ĐT sẽ tiếp nhận, xử lý theo quy định.';
                $_SESSION['pk_csrf'] = bin2hex(random_bytes(32));
                header('Location: /stackers_project/pages/hoc-sinh/dang-ky-phuc-khao/');
                exit;
            } catch (DomainException $e) {
                $errors[] = $e->getMessage();
            }
        }
    }
    $db->close();
} catch (Throwable $e) {
    error_log('Phuc khao: ' . get_class($e) . ' - ' . $e->getMessage());
    $errors[] = 'Không tải được dữ liệu phúc khảo. Vui lòng thử lại sau hoặc liên hệ nhà trường.';
    $canSubmit = false;
}
?>
<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Đăng ký phúc khảo | Stackers</title>
<link rel="stylesheet" href="../../../assets/css/phat-tailwind.css">
<style>
.pk-wrap{box-sizing:border-box;margin-left:18rem;padding-top:5rem;min-height:100vh;background:#f7f9fc}.pk-main{box-sizing:border-box;max-width:1100px;margin:auto;padding:28px 24px 70px;color:#1f2937}.pk-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 2px 5px #0f172a0a;padding:22px;margin-top:16px}.pk-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.pk-stat{border:1px solid #e2e8f0;border-radius:12px;padding:14px;background:white;min-width:0}.pk-muted{font-size:13px;color:#64748b}.pk-title{font-size:27px;line-height:1.3;font-weight:750;margin:0 0 6px}.pk-table{width:100%;border-collapse:collapse;min-width:600px}.pk-table th,.pk-table td{padding:13px 10px;text-align:left;border-bottom:1px solid #e5e7eb;font-size:14px}.pk-table th{color:#64748b;background:#f8fafc;font-size:12px}.pk-overflow{overflow-x:auto}.pk-note{border:1px solid #bfdbfe;background:#eff6ff;padding:13px 15px;border-radius:10px;color:#1e40af;margin:14px 0;font-size:14px}.pk-danger{border-color:#fecaca;background:#fff1f2;color:#991b1b}.pk-success{border-color:#bbf7d0;background:#f0fdf4;color:#166534}.pk-button{border:0;border-radius:10px;background:#1d4ed8;color:white;font-size:14px;font-weight:650;padding:12px 20px;cursor:pointer}.pk-button:disabled{background:#94a3b8;cursor:not-allowed}.pk-input{display:block;width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:10px;padding:12px;font:inherit;margin-top:8px}.pk-history{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;padding:14px 0;border-bottom:1px solid #e2e8f0}.pk-history:last-child{border-bottom:none}.pk-file-note{margin:8px 0 0;line-height:1.45}.pk-file-note strong{color:#1d4ed8}.pk-head{display:flex;flex-wrap:wrap;align-items:start;justify-content:space-between;gap:15px;margin-bottom:18px}
@media(max-width:800px){body.pk-page>aside{display:none}.pk-wrap{margin-left:0}}@media(max-width:680px){.pk-main{padding:18px 12px 42px}.pk-grid{grid-template-columns:1fr}.pk-card{padding:15px}}
</style></head>
<body class="pk-page m-0 bg-slate-50 font-sans text-slate-800">
<?php renderHeader(); renderSidebar(); ?>
<div class="pk-wrap"><main class="pk-main">
    <div class="pk-head"><div><h1 class="pk-title">Đăng ký phúc khảo bài thi</h1><p class="pk-muted">Chọn các môn cần phúc khảo, nhập lý do và gửi yêu cầu đến nhà trường.</p></div><a class="pk-muted" href="/stackers_project/pages/hoc-sinh/">Quay lại</a></div>
    <?php if ($student): ?>
    <div class="pk-grid">
        <div class="pk-stat"><div class="pk-muted">Học sinh</div><b><?=pkH($student['hoten'] ?: 'Chưa cập nhật')?></b></div>
        <div class="pk-stat"><div class="pk-muted">Số báo danh</div><b><?=pkH($student['sobaodanh'] ?: 'Chưa được cấp')?></b></div>
        <div class="pk-stat"><div class="pk-muted">Kỳ thi / hạn nhận đơn</div><b><?=pkH($student['namtuyensinh'])?> · <?=pkH(pkDate($student['hanchotdangkyphuckhao']))?></b></div>
    </div><?php endif; ?>
    <?php if ($notice): ?><div class="pk-note <?=str_contains($notice, 'Đã gửi phiếu') ? 'pk-success' : ''?>" role="status"><?=pkH($notice)?></div><?php endif; ?>
    <?php foreach ($errors as $error): ?><div class="pk-note pk-danger" role="alert"><?=pkH($error)?></div><?php endforeach; ?>
    <section class="pk-card"><h2 class="m-0 text-xl font-bold"><?= $allRegistered ? 'Trạng thái đăng ký phúc khảo' : 'Chọn môn cần phúc khảo' ?></h2><p class="pk-muted"><?= $allRegistered ? 'Tất cả các môn thi đã có phiếu phúc khảo.' : 'Chỉ các môn đã có điểm và chưa đăng ký phúc khảo mới được chọn.' ?></p>
    <?php if ($allRegistered): ?>
    <div class="pk-note pk-success" role="status">
        <strong>Bạn đã đăng ký phúc khảo tất cả <?=count($papers)?>/<?=count($papers)?> môn thi.</strong>
        Không còn môn nào để đăng ký thêm. Bạn không cần gửi phiếu mới; hãy theo dõi trạng thái các phiếu tại mục <strong>Phiếu phúc khảo đã gửi</strong> bên dưới.
    </div>
    <?php endif; ?>
    <form method="post" action="" id="pk-form" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?=pkH($_SESSION['pk_csrf'])?>">
        <div class="pk-overflow"><table class="pk-table"><thead><tr><th>Chọn</th><th>Môn thi</th><th>Điểm thi</th><th>Trạng thái</th></tr></thead><tbody>
        <?php if (!$papers): ?><tr><td colspan="4"><?=pkH($isScorePublished ? 'Chưa có bài thi được cập nhật.' : 'Điểm thi chưa được công bố. Danh sách môn và điểm thi sẽ hiển thị sau khi có thông báo chính thức.')?></td></tr><?php endif; ?>
        <?php foreach ($papers as $paper):
            $duplicate = in_array((int)$paper['mabaithi'], $submittedIds, true);
            $noScore = $paper['diem'] === null;
            $disabled = !$canSubmit || $duplicate || $noScore;
            $status = $duplicate ? 'Đã đăng ký' : ($noScore ? 'Chưa có điểm' : ($canSubmit ? 'Có thể đăng ký' : 'Tạm chưa tiếp nhận'));
        ?>
        <tr><td><input aria-label="Chọn môn <?=pkH($paper['tenmonhoc'])?>" data-mon="<?=pkH($paper['tenmonhoc'])?>" type="checkbox" name="mabaithi[]" value="<?=pkH($paper['mabaithi'])?>" <?= $disabled ? 'disabled' : '' ?> <?= in_array((string)$paper['mabaithi'], array_map('strval',$chosen), true) ? 'checked' : '' ?>></td>
        <td><b><?=pkH($paper['tenmonhoc'])?></b></td><td><?=pkH(pkScore($paper['diem']))?></td><td><?=pkH($status)?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
        <?php if (!$allRegistered): ?>
        <label for="pk-reason" class="block font-semibold mt-6">Lý do đề nghị phúc khảo</label>
        <textarea class="pk-input" rows="4" maxlength="500" minlength="10" name="lydo" id="pk-reason" placeholder="Trình bày lý do bạn muốn phúc khảo bài thi..." <?=!$canSubmit?'disabled':''?> required><?=pkH($reason)?></textarea>
        <p class="pk-muted">Từ 10 đến 500 ký tự. Chỉ gửi yêu cầu khi đã kiểm tra thông tin.</p>
        <label for="pk-evidence" class="block font-semibold mt-5">File minh chứng <span class="pk-muted">(không bắt buộc)</span></label>
        <input class="pk-input" type="file" name="minhchung" id="pk-evidence" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" <?=(!$canSubmit || !$evidenceReady)?'disabled':''?>>
        <p class="pk-muted pk-file-note">Chấp nhận PDF, JPG, JPEG, PNG; tối đa <strong>5 MB</strong>. File được lưu riêng tư và gắn với phiếu vừa gửi.</p>
        <?php if (!$evidenceReady): ?><p class="pk-muted pk-file-note">Tính năng đính kèm đang chờ cấu hình bảng <code>minhchungphuckhao</code> và thư mục lưu an toàn. Bạn vẫn có thể gửi phiếu không kèm file.</p><?php endif; ?>
        <label class="block my-5 text-sm"><input name="confirm" value="yes" type="checkbox" <?=!$canSubmit?'disabled':''?> required> Tôi xác nhận các môn và thông tin đăng ký phúc khảo là chính xác.</label>
        <button type="submit" class="pk-button" <?=!$canSubmit?'disabled':''?>>Gửi đăng ký phúc khảo</button>
        <?php endif; ?>
    </form></section>
    <section class="pk-card"><h2 class="m-0 text-xl font-bold">Phiếu phúc khảo đã gửi</h2>
        <?php if (!$history): ?><p class="pk-muted">Bạn chưa có phiếu phúc khảo nào.</p><?php endif; ?>
        <?php foreach ($history as $item): ?><div class="pk-history"><div><b>Phiếu #<?=pkH($item['maphieu'])?></b> · <?=pkH($item['monhoc'])?><div class="pk-muted">Ngày gửi: <?=pkH(date('d/m/Y H:i', strtotime((string)$item['ngaytao'])))?></div><?php if (isset($evidenceNames[(int)$item['maphieu']])): ?><div class="pk-muted">Minh chứng: <?php if ($evidenceReady): ?><a href="tai-minh-chung.php?maphieu=<?=(int)$item['maphieu']?>" class="text-blue-700 underline"><?=pkH($evidenceNames[(int)$item['maphieu']])?></a><?php else: ?><?=pkH($evidenceNames[(int)$item['maphieu']])?><?php endif; ?></div><?php endif; ?></div><b><?=pkH(PhatDangKyPhucKhao::statusName((string)$item['trangthai']))?></b></div><?php endforeach; ?>
    </section>
</main></div>
<script>
document.getElementById('pk-form')?.addEventListener('submit', function (event) {
    const selected = [...this.querySelectorAll('input[name="mabaithi[]"]:checked')].map(el => el.dataset.mon);
    if (!selected.length) {
        event.preventDefault();
        alert('Vui lòng chọn ít nhất một môn cần phúc khảo.');
        return;
    }
    const evidence = this.querySelector('#pk-evidence')?.files[0];
    if (evidence) {
        const ext = evidence.name.split('.').pop().toLowerCase();
        if (!['pdf','jpg','jpeg','png'].includes(ext) || evidence.size === 0 || evidence.size > 5 * 1024 * 1024) {
            event.preventDefault();
            alert('Minh chứng phải là PDF/JPG/PNG và có dung lượng từ 1 byte đến 5 MB.');
            return;
        }
    }
    if (!window.confirm('Xác nhận gửi phiếu phúc khảo cho: ' + selected.join(', ') + (evidence ? ' (kèm ' + evidence.name + ')' : '') + '?')) {
        event.preventDefault();
        return;
    }
    const button = this.querySelector('button[type=submit]');
    if (button) { button.disabled = true; button.textContent = 'Đang gửi yêu cầu...'; }
});
</script>
</body></html>
