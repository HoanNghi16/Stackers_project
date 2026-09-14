<?php
// Wireframe / mockup — Use case: "Đăng ký chỉ tiêu"
// Actor chính: Trường THPT
// Actor phụ: Sở GD&ĐT (phê duyệt chỉ tiêu)
// Dữ liệu bên dưới là dữ liệu mẫu để dựng giao diện, chưa kết nối CSDL thật.

$pageTitle = "Stackers - Đăng ký chỉ tiêu";

// Danh sách lớp/khu vực tuyển sinh + giới hạn phân bổ + chỉ tiêu đã đăng ký trước đó (nếu có)
$danhSachChiTieu = [
    ['ma' => 'THUONG_KV1', 'ten' => 'Lớp 10 Thường - Khu vực 1', 'gioiHanPhanBo' => 350, 'daDangKy' => 320, 'trangThai' => 'Chờ duyệt'],
    ['ma' => 'THUONG_KV2', 'ten' => 'Lớp 10 Thường - Khu vực 2', 'gioiHanPhanBo' => 300, 'daDangKy' => null,  'trangThai' => null],
    ['ma' => 'CHUYEN_TOAN','ten' => 'Lớp 10 Chuyên Toán',        'gioiHanPhanBo' => 35,  'daDangKy' => null,  'trangThai' => null],
    ['ma' => 'CHUYEN_ANH', 'ten' => 'Lớp 10 Chuyên Tiếng Anh',   'gioiHanPhanBo' => 35,  'daDangKy' => null,  'trangThai' => null],
];

$thoiGianBatDau = '01/03/2027';
$thoiGianKetThuc = '31/03/2027';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pageTitle; ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  /* Wireframe mode: no color accents, grayscale only */
  body { font-family: Arial, Helvetica, sans-serif; }
</style>
</head>
<body class="bg-gray-100 text-gray-900">

  <!-- Top bar -->
  <header class="bg-white border-b border-gray-300">
    <div class="max-w-7xl mx-auto px-6 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 border border-gray-400 flex items-center justify-center text-xs font-bold">
          LOGO
        </div>
        <span class="font-semibold text-lg">Stackers</span>
        <span class="text-gray-400">|</span>
        <span class="text-sm text-gray-600">Hệ thống Quản lý Tuyển sinh 10</span>
      </div>
      <div class="flex items-center gap-4 text-sm text-gray-600">
        <span>Trường THPT Nguyễn Thị Minh Khai</span>
        <div class="w-8 h-8 rounded-full border border-gray-400 flex items-center justify-center text-xs">
          THPT
        </div>
      </div>
    </div>
  </header>

  <div class="max-w-7xl mx-auto flex">

    <!-- Sidebar: các chức năng của Trường THPT -->
    <aside class="w-64 min-h-screen bg-white border-r border-gray-300 p-4 hidden md:block">
      <nav class="space-y-1 text-sm">
        <div class="px-3 py-2 text-gray-500">TỔNG QUAN</div>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Trang chủ</a>

        <div class="px-3 py-2 text-gray-500 mt-3">TUYỂN SINH</div>
        <a href="#" class="block px-3 py-2 rounded border border-gray-400 bg-gray-100 font-medium">
          Đăng ký chỉ tiêu
        </a>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Xem danh sách học sinh nhập học</a>

        <div class="px-3 py-2 text-gray-500 mt-3">HỘI ĐỒNG THI</div>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Chọn giáo viên tham gia hội đồng thi</a>

        <div class="px-3 py-2 text-gray-500 mt-3">TRA CỨU</div>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Xem danh sách trường</a>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Xem danh sách điểm thi</a>

        <div class="px-3 py-2 text-gray-500 mt-3">HỆ THỐNG</div>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Đổi mật khẩu</a>
      </nav>
    </aside>

    <!-- Main content -->
    <main class="flex-1 p-6">

      <!-- Breadcrumb -->
      <div class="text-sm text-gray-500 mb-2">
        Trường THPT &nbsp;/&nbsp; Tuyển sinh &nbsp;/&nbsp; <span class="text-gray-800">Đăng ký chỉ tiêu</span>
      </div>

      <!-- Page header -->
      <div class="flex items-start justify-between mb-4">
        <div>
          <h1 class="text-xl font-semibold">Đăng ký chỉ tiêu</h1>
          <p class="text-sm text-gray-500 mt-1">
            Đăng ký số lượng chỉ tiêu tuyển sinh theo từng lớp/khu vực tuyển sinh cho kỳ tuyển sinh hiện tại.
          </p>
        </div>
        <span class="text-xs border border-gray-400 px-2 py-1 rounded">
          Kỳ tuyển sinh: 2026 - 2027
        </span>
      </div>

      <!-- Thông báo thời gian cho phép đăng ký (Tiền điều kiện) -->
      <div class="mb-4 border border-gray-300 bg-white px-4 py-3 rounded text-sm text-gray-600">
        Thời gian cho phép đăng ký chỉ tiêu: <span class="font-medium text-gray-800"><?php echo $thoiGianBatDau; ?> — <?php echo $thoiGianKetThuc; ?></span>
      </div>

      <!-- Error banner (ẩn mặc định, hiện khi dữ liệu không hợp lệ hoặc lỗi hệ thống) -->
      <div id="thongBaoLoi" class="hidden mb-4 border border-gray-500 bg-gray-100 px-4 py-3 rounded">
        <p class="text-sm font-medium" id="tieuDeLoi">Không thể lưu chỉ tiêu</p>
        <p id="noiDungLoi" class="text-sm text-gray-600 mt-1">Vui lòng kiểm tra lại số lượng chỉ tiêu đã nhập.</p>
      </div>

      <form id="formChiTieu" class="space-y-6" novalidate>

        <!-- Khối: Bảng đăng ký chỉ tiêu theo lớp/khu vực -->
        <section class="bg-white border border-gray-300 rounded">
          <div class="px-5 py-3 border-b border-gray-300">
            <h2 class="font-medium">Chỉ tiêu theo lớp / khu vực tuyển sinh</h2>
          </div>

          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-gray-500 border-b border-gray-200">
                <th class="px-5 py-2 font-normal">Lớp / Khu vực tuyển sinh</th>
                <th class="px-5 py-2 font-normal w-32">Giới hạn phân bổ</th>
                <th class="px-5 py-2 font-normal w-40">Số lượng chỉ tiêu đăng ký</th>
                <th class="px-5 py-2 font-normal w-36">Trạng thái</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($danhSachChiTieu as $ct): ?>
              <tr class="border-b border-gray-100 last:border-b-0">
                <td class="px-5 py-3"><?php echo htmlspecialchars($ct['ten']); ?></td>
                <td class="px-5 py-3 text-gray-500"><?php echo $ct['gioiHanPhanBo']; ?></td>
                <td class="px-5 py-3">
                  <?php if ($ct['trangThai'] === 'Chờ duyệt'): ?>
                    <input
                      type="number"
                      value="<?php echo $ct['daDangKy']; ?>"
                      disabled
                      class="w-28 border border-gray-300 bg-gray-100 text-gray-500 rounded px-2 py-1 cursor-not-allowed"
                    >
                    <p class="text-gray-400 text-xs mt-1">Đang chờ Sở GD&ĐT phê duyệt, không thể chỉnh sửa.</p>
                  <?php else: ?>
                    <input
                      type="number"
                      step="1"
                      min="0"
                      max="<?php echo $ct['gioiHanPhanBo']; ?>"
                      name="chiTieu[<?php echo $ct['ma']; ?>]"
                      value="<?php echo $ct['daDangKy'] ?? ''; ?>"
                      placeholder="Nhập số lượng"
                      data-ten-lop="<?php echo htmlspecialchars($ct['ten']); ?>"
                      data-gioi-han="<?php echo $ct['gioiHanPhanBo']; ?>"
                      class="chiTieuInput w-28 border border-gray-400 rounded px-2 py-1"
                    >
                    <p class="loiChiTieuDong hidden text-red-600 text-xs mt-1"></p>
                  <?php endif; ?>
                </td>
                <td class="px-5 py-3">
                  <?php if ($ct['trangThai']): ?>
                    <span class="text-xs border border-gray-400 px-2 py-1 rounded whitespace-nowrap"><?php echo $ct['trangThai']; ?></span>
                  <?php else: ?>
                    <span class="text-xs text-gray-400 whitespace-nowrap">Chưa đăng ký</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <div class="px-5 py-3 border-t border-gray-200 text-xs text-gray-500">
            Lưu ý: số lượng chỉ tiêu đăng ký không được vượt quá giới hạn phân bổ của từng lớp/khu vực và không được là số âm.
          </div>
        </section>

        <!-- Khối: hành động -->
        <div class="flex items-center justify-end gap-3">
          <button type="button" class="border border-gray-400 rounded px-4 py-2 text-sm">
            Hủy
          </button>
          <button type="submit" class="border border-gray-900 bg-gray-900 text-white rounded px-4 py-2 text-sm">
            Đăng ký chỉ tiêu
          </button>
        </div>

      </form>

    </main>
  </div>

  <!-- Modal: Đăng ký thành công (ẩn mặc định) -->
  <div id="modalThanhCong" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="bg-white border border-gray-400 rounded w-full max-w-sm mx-4">
      <div class="px-5 py-4 border-b border-gray-300 flex items-center justify-between">
        <h3 class="font-medium">Đăng ký chỉ tiêu thành công</h3>
        <button type="button" onclick="dongModalThanhCong()" class="text-gray-400 text-sm">✕</button>
      </div>
      <div class="px-5 py-4 text-sm text-gray-700">
        Chỉ tiêu tuyển sinh của trường đã được ghi nhận vào hệ thống ở trạng thái "Chờ duyệt". Sở GD&ĐT sẽ xem xét và phê duyệt.
      </div>
      <div class="px-5 py-4 border-t border-gray-200 flex justify-end">
        <button type="button" onclick="dongModalThanhCong()" class="border border-gray-900 bg-gray-900 text-white rounded px-4 py-2 text-sm">
          Đóng
        </button>
      </div>
    </div>
  </div>

  <script>
    function layDanhSachInputChiTieu() {
      return document.querySelectorAll('.chiTieuInput');
    }

    // Kiểm tra thời gian thực cho 1 ô chỉ tiêu (Alternative flow 4.1: vượt quá giới hạn phân bổ)
    function kiemTraChiTieuDong(input) {
      const p = input.nextElementSibling; // .loiChiTieuDong
      const giaTri = parseFloat(input.value);
      const gioiHan = parseFloat(input.dataset.gioiHan);

      if (input.value.trim() === '') {
        p.classList.add('hidden');
        return;
      }
      if (isNaN(giaTri) || giaTri < 0) {
        p.textContent = `Số lượng chỉ tiêu của "${input.dataset.tenLop}" không được là số âm.`;
        p.classList.remove('hidden');
      } else if (giaTri > gioiHan) {
        p.textContent = `Chỉ tiêu vượt quá giới hạn phân bổ (tối đa ${gioiHan}) cho "${input.dataset.tenLop}".`;
        p.classList.remove('hidden');
      } else {
        p.classList.add('hidden');
      }
    }

    layDanhSachInputChiTieu().forEach(inp => {
      inp.addEventListener('input', () => kiemTraChiTieuDong(inp));
    });

    function moModalThanhCong() {
      document.getElementById('modalThanhCong').classList.remove('hidden');
    }
    function dongModalThanhCong() {
      document.getElementById('modalThanhCong').classList.add('hidden');
    }

    document.getElementById('formChiTieu').addEventListener('submit', function (e) {
      e.preventDefault();

      const loi = document.getElementById('thongBaoLoi');
      const tieuDeLoi = document.getElementById('tieuDeLoi');
      const noiDungLoi = document.getElementById('noiDungLoi');
      const danhSachLoi = [];

      // Basic flow bước 4 / Alternative flow 4.1: kiểm tra tính hợp lệ, không vượt giới hạn phân bổ
      layDanhSachInputChiTieu().forEach(inp => {
        const giaTri = parseFloat(inp.value);
        const gioiHan = parseFloat(inp.dataset.gioiHan);

        if (inp.value.trim() === '') return; // cho phép bỏ trống lớp/khu vực chưa muốn đăng ký

        if (isNaN(giaTri) || giaTri < 0) {
          danhSachLoi.push(`Số lượng chỉ tiêu của "${inp.dataset.tenLop}" không được là số âm.`);
        } else if (giaTri > gioiHan) {
          danhSachLoi.push(`Chỉ tiêu vượt quá giới hạn phân bổ (tối đa ${gioiHan}) cho "${inp.dataset.tenLop}".`);
        }
      });

      if (danhSachLoi.length > 0) {
        tieuDeLoi.textContent = 'Chỉ tiêu vượt quá giới hạn cho phép';
        noiDungLoi.innerHTML = danhSachLoi.join('<br>');
        loi.classList.remove('hidden');
        return;
      }

      // Basic flow bước 5: lưu chỉ tiêu, chuyển trạng thái "chờ duyệt"
      loi.classList.add('hidden');
      document.querySelectorAll('#formChiTieu tbody tr').forEach(tr => {
        const input = tr.querySelector('.chiTieuInput');
        const trangThaiTd = tr.children[3];
        if (input && input.value.trim() !== '') {
          trangThaiTd.innerHTML = '<span class="text-xs border border-gray-400 px-2 py-1 rounded whitespace-nowrap">Chờ duyệt</span>';

          // Khóa ô nhập vì chỉ tiêu đã chuyển sang trạng thái chờ duyệt, không cho sửa nữa
          const chiTieuTd = tr.children[2];
          chiTieuTd.innerHTML = `
            <input type="number" value="${input.value}" disabled
                   class="w-28 border border-gray-300 bg-gray-100 text-gray-500 rounded px-2 py-1 cursor-not-allowed">
            <p class="text-gray-400 text-xs mt-1">Đang chờ Sở GD&ĐT phê duyệt, không thể chỉnh sửa.</p>
          `;
        }
      });

      moModalThanhCong();
    });
  </script>

</body>
</html>