<?php
function renderSidebar() {
    $vaitro = $_SESSION['vaitro'] ?? '';

    $chucNangTheoVaiTro = [
        'THCS' => [
            ['ten' => 'Quản lý hồ sơ thí sinh', 'url' => '/stackers_project/pages/thcs/quan-ly-hs-thi-sinh'],
            ['ten' => 'Giáo viên tham gia hội đồng', 'url' => '/stackers_project/pages/thcs/chon-giao-vien'],
        ],

        'THPT' => [
            ['ten' => 'Đăng ký chỉ tiêu', 'url' => '/stackers_project/pages/thpt/dang-ky-chi-tieu'],
            ['ten' => 'Danh sách học sinh nhập học', 'url' => '/stackers_project/pages/thpt/xem-danh-sach-hoc-sinh-nhap-hoc'],
        ],

        'SO_GD' => [
            ['ten' => 'Công bố điểm chuẩn', 'url' => '/stackers_project/pages/so-gd/cong-bo-diem-chuan'],
            ['ten' => 'Nhập điểm phúc khảo', 'url' => '/stackers_project/pages/so-gd/nhap-diem-phuc-khao'],
            ['ten' => 'Nhập điểm thi', 'url' => '/stackers_project/pages/so-gd/nhap-diem-thi'],
            ['ten' => 'Quản lý bài thi', 'url' => '/stackers_project/pages/so-gd/quan-ly-bai-thi'],
            ['ten' => 'Quản lý địa điểm thi', 'url' => '/stackers_project/pages/so-gd/quan-ly-dd-coi-cham-thi'],
            ['ten' => 'Quản lý hội đồng thi', 'url' => '/stackers_project/pages/so-gd/quan-ly-hd-thi'],
            ['ten' => 'Thiết lập công thức tính điểm', 'url' => '/stackers_project/pages/so-gd/thiet-lap-cong-thuc-tinh-diem'],
            ['ten' => 'Xem danh sách thí sinh', 'url' => '/stackers_project/pages/so-gd/xem-danh-sach-thi-sinh'],
            ['ten' => 'Xét tuyển', 'url' => '/stackers_project/pages/so-gd/xet-tuyen'],
        ],
    ];

    $danhSachChucNang = $chucNangTheoVaiTro[$vaitro] ?? [];

    $tenVaiTro = [
        'THCS' => 'Trường THCS',
        'THPT' => 'Trường THPT',
        'SO_GD' => 'Sở Giáo dục và Đào tạo',
    ];

    $tenNguoiDung = $_SESSION['ten'] ?? 'Người dùng';
    $anhDaiDien = $_SESSION['anhdaidien'] ?? '';
?>

<aside class="fixed left-0 top-20 z-[900] flex h-[calc(100vh-5rem)] w-72 flex-col border-r border-slate-200/70 bg-white/95 px-4 py-6 shadow-lg backdrop-blur-xl">

    <!-- Tổng quan -->
    <nav class="space-y-1">
        <a href="/stackers_project/pages/<?php echo str_replace('_',"-",strtolower($_SESSION['vaitro']));?>"
           class="group flex items-center gap-3 rounded-xl bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-700 transition hover:bg-sky-100">
            <svg class="h-5 w-5 shrink-0" fill="none"
                 stroke="currentColor" stroke-width="2"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 12l9-9 9 9M5 10v10h14V10"/>
            </svg>
            <span>Tổng quan</span>
        </a>
    </nav>

    <!-- Chức năng theo vai trò -->
    <div class="my-6 border-t border-slate-200"></div>

    <p class="mb-3 px-3 text-xs font-bold uppercase tracking-widest text-slate-400">
        Chức năng
    </p>

    <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto">
        <?php foreach ($danhSachChucNang as $chucNang): ?>
            <a href="<?= htmlspecialchars($chucNang['url'], ENT_QUOTES, 'UTF-8') ?>"
               class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-slate-600 transition duration-200 hover:bg-sky-50 hover:text-sky-700">

                <svg class="h-5 w-5 shrink-0 text-slate-400 transition-colors group-hover:text-sky-600"
                     fill="none" stroke="currentColor"
                     stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>
                </svg>

                <span><?= htmlspecialchars($chucNang['ten'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        <?php endforeach; ?>

        <?php if (empty($danhSachChucNang)): ?>
            <p class="px-4 py-3 text-sm text-slate-400">
                Không có chức năng dành cho vai trò này.
            </p>
        <?php endif; ?>
    </nav>

    <!-- Thông tin tài khoản -->
    <div class="mt-4 shrink-0 rounded-2xl border border-sky-100 bg-gradient-to-br from-sky-50 to-white p-3 shadow-sm">
        <div class="flex items-center gap-3">
            <?php if (!empty($anhDaiDien)): ?>
                <img
                    src="<?= htmlspecialchars($anhDaiDien, ENT_QUOTES, 'UTF-8') ?>"
                    alt="Ảnh đại diện"
                    class="h-10 w-10 shrink-0 rounded-full object-cover"
                >
            <?php else: ?>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-sky-600 text-sm font-bold text-white">
                    <?= htmlspecialchars(mb_strtoupper(mb_substr($tenNguoiDung, 0, 1, 'UTF-8'), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <div class="min-w-0">
                <p class="truncate text-sm font-bold text-slate-700">
                    <?= htmlspecialchars($tenNguoiDung, ENT_QUOTES, 'UTF-8') ?>
                </p>

                <p class="truncate text-xs text-slate-400">
                    <?= htmlspecialchars($tenVaiTro[$vaitro] ?? $vaitro, ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
        </div>
    </div>

</aside>

<?php
}
?>