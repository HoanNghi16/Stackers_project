<?php
// ---- Sample data (replace with your DB query) ---------------------------

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
    <header class="fixed top-0 z-50 w-full h-20 bg-gradient-to-b from-blue-700 via-blue-700 to-blue-600 text-white">
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
                        $site = "/";
                        if ($_SESSION['vaitro'] == "THPT"){
                            $site = "thpt";
                        }
                        if ($_SESSION['vaitro'] == "THCS"){
                            $site = "thcs";
                        }
                        if ($_SESSION['vaitro'] == "SO_GD"){
                            $site = "so-gd";
                        }
                        if ($_SESSION['vaitro'] == "HOC_SINH"){
                            $site = "hoc-sinh";
                        }
                        $ten = 'UNKNOWN';
                        if (isset($_SESSION['ten'])){
                            $ten = $_SESSION['ten'];
                        }
                        $ten = htmlspecialchars($ten, ENT_QUOTES, 'UTF-8');

                        echo '
                            <div class="relative inline-flex items-center rounded-xl bg-blue-50 text-sm text-blue-700" data-account-dropdown>
                                <a href="/stackers_project/pages/'.$site.'" class="inline-flex items-center gap-2 rounded-l-xl px-3 py-2 hover:bg-blue-100">
                                    <span class="font-medium">Xin chào, '.$ten.'.</span>
                                    <span class="text-xs text-blue-500">Vào dashboard</span>
                                </a>
                                <button type="button" aria-label="Mở menu tài khoản" aria-expanded="false" aria-controls="account-dropdown-panel" class="rounded-r-xl border-l border-blue-200 px-2 py-2 hover:bg-blue-100 focus-visible:outline focus-visible:outline-2" data-account-toggle>
                                    <svg class="h-5 w-5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div id="account-dropdown-panel" hidden class="absolute right-0 top-full mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 text-slate-700 shadow-lg">
                                    <a href="/stackers_project/pages/doi-mat-khau/" class="block rounded-lg px-3 py-2.5 hover:bg-blue-50 hover:text-blue-700">Đổi mật khẩu</a>
                                    <button type="button" disabled class="block w-full cursor-default rounded-lg px-3 py-2.5 text-left text-slate-400" title="Chức năng đang được phát triển">Thông tin cá nhân</button>
                                </div>
                            </div>
                            <a
                                href="/stackers_project/pages/logout/"
                                class="inline-flex items-center gap-2 rounded-xl bg-blue-50 px-3 py-2 text-sm text-blue-700 transition hover:bg-blue-100">
                                Đăng xuất
                            </a>

                        ';
                    }else{
                        echo '
                            <a
                                href="/stackers_project/pages/login"
                                class="inline-flex items-center gap-2 rounded-xl bg-blue-50 px-3 py-2 text-sm text-blue-700 transition hover:bg-blue-100">
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
    <?php require __DIR__ . '/doi-mat-khau-modal.php'; ?>
    <script>
    (() => {
        const dropdown = document.querySelector('[data-account-dropdown]');
        if (!dropdown) return;
        const toggle = dropdown.querySelector('[data-account-toggle]');
        const panel = dropdown.querySelector('#account-dropdown-panel');
        const setOpen = (open) => {
            panel.hidden = !open;
            toggle.setAttribute('aria-expanded', String(open));
            toggle.querySelector('svg').style.transform = open ? 'rotate(180deg)' : '';
        };
        toggle.addEventListener('click', () => setOpen(panel.hidden));
        document.addEventListener('click', event => {
            if (!dropdown.contains(event.target)) setOpen(false);
        });
        dropdown.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                setOpen(false);
                toggle.focus();
            }
        });
        dropdown.addEventListener('focusout', event => {
            if (!dropdown.contains(event.relatedTarget)) setOpen(false);
        });
    })();
    </script>
    <?php
}
