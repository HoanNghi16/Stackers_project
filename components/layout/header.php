<?php
// ---- Sample data (replace with your DB query) ---------------------------
$wardOptions = [
    ''       => '--- Phường/Xã ---',
    '26732'  => 'Đặc khu Côn Đảo',
    '27316'  => 'Phường An Đông',
    '26878'  => 'Phường An Hội Đông',
    // ... populate the rest from your database query, same as the original.
];

$schoolOptions = [
    ''         => '--- Chọn ---',
    '74000707' => 'Trường THPT Chuyên Hùng Vương',
    '74000702' => 'Trường THPT Võ Minh Đức',
    '74000708' => 'Trường THPT Nguyễn Đình Chiểu',
    // ... populate the rest from your database query, same as the original.
];

$loaiHinhOptions = [
    '1' => 'THPT Công lập',
    '2' => 'THPT ngoài Công lập',
    '3' => 'TT GDNN-GDTX',
    '4' => 'Trung cấp, cao đẳng nghề',
];

$navItems = [
    ['href' => '/stackers_project/pages',                    'label' => 'Trang chủ',                  'active' => true],
    ['href' => '#chitieu',    'label' => 'Chỉ tiêu',        'active' => false],
    ['href' => '#truong',     'label' => 'Trường',      'active' => false],
    ['href' => '#huongdan',              'label' => 'Hướng dẫn',                    'active' => false],
];

// Shared Tailwind class for every native <select> in the toolbar.
$selectClass = 'w-full appearance-none rounded-full border border-gray-200 bg-white '
    . 'py-2.5 pl-4 pr-9 text-sm text-gray-700 focus:outline-none focus:ring-2 '
    . 'focus:ring-red-500/40 focus:border-red-500';

/**
 * Render <option> tags from an associative array, escaping values/labels.
 */
function renderOptions(array $options): string
{
    $html = '';
    foreach ($options as $value => $label) {
        $html .= '<option value="' . htmlspecialchars((string) $value) . '">'
            . htmlspecialchars($label) . '</option>';
    }
    return $html;
}

function renderHeader()
{
    global $wardOptions, $schoolOptions, $loaiHinhOptions, $navItems, $selectClass;
    ?>
    <header class="fixed w-full h-20 bg-gradient-to-b from-blue-700 via-blue-700 to-blue-600 text-white">
        <!-- ============ Top bar: logo + nav + CTA ============ -->
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">

            <!-- Logo + title -->
            <div class="flex items-center gap-3">
                <img class="h-11 w-11 shrink-0 rounded-full"
                     src="/stackers_project/assets/image/stackers_logo.png"
                     alt="Logo" decoding="async">
                <div class="leading-tight">
                    <span class="block font-roboto text-xs font-normal sm:text-sm">Stackers - Hội anh em ngăn xếp</span>
                    <span class="block font-roboto text-sm font-extrabold uppercase sm:text-base">THÀNH PHỐ HỒ CHÍ MINH</span>
                </div>
            </div>

            <!-- Desktop nav -->
            <nav class="hidden lg:block">
                <ul class="flex items-center gap-8 text-sm font-medium">
                    <?php foreach ($navItems as $item): ?>
                        <li>
                            <a href="<?= htmlspecialchars($item['href']) ?>"
                               class="border-b-2 pb-1 transition hover:border-white/70
                                      <?= $item['active'] ? 'border-white' : 'border-transparent' ?>">
                                <?= htmlspecialchars($item['label']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <!-- Desktop CTA -->
            <div class="hidden lg:block">
                <?php
                    if (isset($_SESSION['vaitro']) || isset($_SESSION['manguoidung'])){
                        echo '
                            <a
                                href="/stackers_project/pages/logout/"
                                class="rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-blue-700
                                        shadow-sm transition hover:bg-red-50">
                                Đăng xuất
                            </a>

                        ';
                    }else{
                        echo '
                            <a
                                href="/stackers_project/pages/login"
                                class="rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-blue-700
                                        shadow-sm transition hover:bg-red-50">
                                Đăng nhập
                            </a>
                        ';
                    }
                ?>
            </div>

            <!-- Mobile hamburger -->
            <button type="button"
                    class="rounded-md p-2 lg:hidden"
                    data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <img src="/img/thongbaovanban/collaspe.svg" alt="" class="h-5 w-5 invert">
            </button>
        </div>

        <!-- Mobile menu panel -->
        <div id="navbarNav" class="collapse lg:hidden">
            <div class="border-t border-white/20 bg-red-700 px-4 py-4">
                <div class="mb-3 flex justify-end">
                    <button type="button" id="navbarCloseButton" aria-label="Close"
                            class="rounded-md p-1 text-white/80 hover:text-white">✕</button>
                </div>
                <ul class="space-y-1">
                    <?php foreach ($navItems as $item): ?>
                        <li>
                            <a href="<?= htmlspecialchars($item['href']) ?>"
                               class="block rounded-lg px-3 py-2 text-sm font-medium
                                      <?= $item['active'] ? 'bg-white/15' : 'hover:bg-white/10' ?>">
                                <?= htmlspecialchars($item['label']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <li>
                        <?php
                            if (isset($_SESSION['vaitro']) || isset($_SESSION['manguoidung'])){
                                echo '
                                    <a href="/stackers_project/pages/login" data-bs-toggle="modal" data-bs-target="#tuyenSinhModal" data-action="tracuu"
                                        class="mt-2 block rounded-lg bg-white px-3 py-2 text-center text-sm font-semibold text-red-700">
                                        Đăng xuất
                                    </a>
                                ';
                            }else{
                                echo '
                                    <a href="/stackers_project/pages/login" data-bs-toggle="modal" data-bs-target="#tuyenSinhModal" data-action="tracuu"
                                        class="mt-2 block rounded-lg bg-white px-3 py-2 text-center text-sm font-semibold text-red-700">
                                        Đăng nhập
                                    </a>
                                ';
                            }
                        
                        ?>

                    </li>
                </ul>
            </div>
        </div>

    </header>
    <?php
}