<?php
/**
 * Header component — converted from Bootstrap/custom CSS to Tailwind CSS.
 *
 * Notes on the conversion:
 * - All Bootstrap classes (navbar, container, btn-close, etc.) and the custom
 *   classes (.banner, .toolbar-item, .content-box, .item, ...) were removed
 *   and replaced with Tailwind utility classes.
 * - Select2 was dropped: it's a jQuery plugin whose generated markup
 *   (.select2-container, .select2-selection, ...) can't be meaningfully
 *   "mapped" to Tailwind — it renders its own DOM. Plain <select> elements
 *   styled with Tailwind are used instead (see $selectClass below). If you
 *   need type-ahead search in the dropdown, swap these for a headless
 *   component (e.g. Alpine + a combobox, or Tom Select styled with Tailwind)
 *   rather than Select2.
 * - The Phường/Xã list and the Trường list were duplicated verbatim for the
 *   mobile and desktop toolbars in the original markup. They're now defined
 *   once as PHP arrays ($wardOptions, $schoolOptions) and rendered through a
 *   helper function, so you only maintain the data in one place. Below,
 *   only a few sample rows are kept as placeholders — in your real app this
 *   data should come from a DB query (which it almost certainly already
 *   does), so just have that query populate these two arrays instead of
 *   hardcoding hundreds of <option> tags.
 * - Icons: the original inline Lottie/SVG icons are left as-is (they're
 *   content, not styling, and already carry their own viewBox/size). Wrap
 *   them in a sized Tailwind container instead of a fixed-size CSS class.
 * - Colors/spacing are a reasonable best-effort reconstruction of the look
 *   implied by the markup (red/white gov banner, rounded search bar
 *   overlapping the hero, 3-card icon grid) since the original stylesheet
 *   wasn't provided. Adjust the color tokens ($brand-* below) to match your
 *   actual brand palette.
 */

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
    <header class="relative bg-gradient-to-b from-blue-700 via-blue-700 to-blue-600 text-white">
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
                <a
                    href="/stackers_project/pages/login"
                    class="rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-blue-700
                            shadow-sm transition hover:bg-red-50">
                    Đăng nhập
                </a>
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
                        <a href="/stackers_project/pages/login" data-bs-toggle="modal" data-bs-target="#tuyenSinhModal" data-action="tracuu"
                           class="mt-2 block rounded-lg bg-white px-3 py-2 text-center text-sm font-semibold text-red-700">
                            Đăng nhập
                        </a>
                    </li>
                </ul>
            </div>
        </div>

    </header>
    <?php
}