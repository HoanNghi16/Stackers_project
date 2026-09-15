<?php
// Wireframe / mockup — Use case: "Thiết lập công thức tính điểm"
// Actor chính: Sở GD&ĐT
// Dữ liệu bên dưới là dữ liệu mẫu để dựng giao diện, chưa kết nối CSDL thật.

$monThi = [
    ['ma' => 'TOAN', 'ten' => 'Toán',       'heSo' => 2],
    ['ma' => 'VAN',  'ten' => 'Ngữ văn',    'heSo' => 2],
    ['ma' => 'ANH',  'ten' => 'Tiếng Anh',  'heSo' => 1],
];

$tieuChiUuTien = [
    ['ten' => 'Con liệt sĩ / thương binh',            'diem' => 2.0],
    ['ten' => 'Người dân tộc thiểu số',                'diem' => 1.0],
    ['ten' => 'Có chứng chỉ nghề / năng khiếu',        'diem' => 0.5],
];

$tongHeSo = array_sum(array_column($monThi, 'heSo'));
$pageTitle = "Stackers - Thiết lập công thức tính điểm";
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
        <span>Sở GD&ĐT</span>
        <div class="w-8 h-8 rounded-full border border-gray-400 flex items-center justify-center text-xs">
          SGD
        </div>
      </div>
    </div>
  </header>

  <div class="max-w-7xl mx-auto flex">

    <!-- Sidebar -->
    <aside class="w-64 min-h-screen bg-white border-r border-gray-300 p-4 hidden md:block">
      <nav class="space-y-1 text-sm">
        <div class="px-3 py-2 text-gray-500">TỔNG QUAN</div>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Trang chủ</a>

        <div class="px-3 py-2 text-gray-500 mt-3">HỘI ĐỒNG &amp; ĐỊA ĐIỂM THI</div>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Quản lý các hội đồng thi</a>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Quản lý địa điểm thi/chấm thi</a>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Quản lý bài thi</a>

        <div class="px-3 py-2 text-gray-500 mt-3">ĐIỂM SỐ</div>
        <a href="#" class="block px-3 py-2 rounded border border-gray-400 bg-gray-100 font-medium">
          Thiết lập công thức tính điểm
        </a>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Thiết lập tiêu chuẩn ưu tiên/khuyến khích</a>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Nhập điểm thi</a>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Nhập điểm phúc khảo</a>

        <div class="px-3 py-2 text-gray-500 mt-3">TUYỂN SINH</div>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Xét tuyển</a>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Công bố điểm chuẩn</a>

        <div class="px-3 py-2 text-gray-500 mt-3">DANH SÁCH &amp; BÁO CÁO</div>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Xem danh sách thí sinh</a>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Thống kê, báo cáo</a>

        <div class="px-3 py-2 text-gray-500 mt-3">HỆ THỐNG</div>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Quản lý tài khoản</a>
      </nav>
    </aside>

    <!-- Main content -->
    <main class="flex-1 p-6">

      <!-- Breadcrumb -->
      <div class="text-sm text-gray-500 mb-2">
        Sở GD&ĐT &nbsp;/&nbsp; Điểm số &nbsp;/&nbsp; <span class="text-gray-800">Thiết lập công thức tính điểm</span>
      </div>

      <!-- Page header -->
      <div class="flex items-start justify-between mb-6">
        <div>
          <h1 class="text-xl font-semibold">Thiết lập công thức tính điểm</h1>
          <p class="text-sm text-gray-500 mt-1">
            Cấu hình hệ số môn thi và các mức điểm ưu tiên / khuyến khích áp dụng cho kỳ tuyển sinh hiện tại.
          </p>
        </div>
        <span class="text-xs border border-gray-400 px-2 py-1 rounded">
          Kỳ tuyển sinh: 2026 - 2027
        </span>
      </div>

      <!-- Error banner (ẩn mặc định, hiện khi dữ liệu không hợp lệ) -->
      <div id="thongBaoLoi" class="hidden mb-4 border border-gray-500 bg-gray-100 px-4 py-3 rounded">
        <p class="text-sm font-medium">Không thể lưu công thức</p>
        <p id="noiDungLoi" class="text-sm text-gray-600 mt-1">Vui lòng kiểm tra lại hệ số và điểm ưu tiên đã nhập.</p>
      </div>

      <form id="formCongThuc" class="space-y-6" novalidate>

        <!-- Khối 1: Hệ số môn thi -->
        <section class="bg-white border border-gray-300 rounded">
          <div class="px-5 py-3 border-b border-gray-300 flex items-center justify-between">
            <h2 class="font-medium">Hệ số môn thi</h2>
            <div class="text-right">
              <span class="text-xs text-gray-500">Tổng hệ số hiện tại: <span id="tongHeSoHienThi" class="font-semibold"><?php echo $tongHeSo; ?></span></span>
              <p id="loiTongHeSo" class="hidden text-red-600 text-xs mt-1"></p>
            </div>
          </div>

          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-gray-500 border-b border-gray-200">
                <th class="px-5 py-2 font-normal">Môn thi</th>
                <th class="px-5 py-2 font-normal w-40">Hệ số</th>
                <th class="px-5 py-2 font-normal">Ghi chú</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($monThi as $i => $mon): ?>
              <tr class="border-b border-gray-100 last:border-b-0">
                <td class="px-5 py-3"><?php echo htmlspecialchars($mon['ten']); ?></td>
                <td class="px-5 py-3">
                  <input
                    type="number"
                    step="0.5"
                    min="0"
                    name="heSo[<?php echo $mon['ma']; ?>]"
                    value="<?php echo $mon['heSo']; ?>"
                    data-ten-mon="<?php echo htmlspecialchars($mon['ten']); ?>"
                    class="hesoInput w-24 border border-gray-400 rounded px-2 py-1"
                  >
                  <p class="loiHeSoDong hidden text-red-600 text-xs mt-1"></p>
                </td>
                <td class="px-5 py-3 text-gray-400">Hệ số nhân với điểm thi môn <?php echo htmlspecialchars($mon['ten']); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <div class="px-5 py-3 border-t border-gray-200 text-xs text-gray-500">
            Lưu ý: tổng hệ số phải khớp với quy định của kỳ tuyển sinh (ví dụ: 5) và không được nhập giá trị âm.
          </div>
        </section>

        <!-- Khối 2: Điểm ưu tiên / khuyến khích -->
        <section class="bg-white border border-gray-300 rounded">
          <div class="px-5 py-3 border-b border-gray-300 flex items-center justify-between">
            <h2 class="font-medium">Điểm ưu tiên / khuyến khích</h2>
            <button type="button" id="btnThemTieuChuan" class="text-sm border border-gray-400 rounded px-3 py-1">
              + Thêm tiêu chuẩn
            </button>
          </div>

          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-gray-500 border-b border-gray-200">
                <th class="px-5 py-2 font-normal">Đối tượng áp dụng</th>
                <th class="px-5 py-2 font-normal w-40">Mức điểm</th>
                <th class="px-5 py-2 font-normal w-16"></th>
              </tr>
            </thead>
            <tbody id="dsTieuChuan">
              <?php foreach ($tieuChiUuTien as $i => $tc): ?>
              <tr class="border-b border-gray-100 last:border-b-0">
                <td class="px-5 py-3">
                  <input
                    type="text"
                    name="tieuChi[<?php echo $i; ?>][ten]"
                    value="<?php echo htmlspecialchars($tc['ten']); ?>"
                    class="w-full border border-gray-400 rounded px-2 py-1"
                  >
                </td>
                <td class="px-5 py-3">
                  <input
                    type="number"
                    step="0.25"
                    min="0"
                    name="tieuChi[<?php echo $i; ?>][diem]"
                    value="<?php echo $tc['diem']; ?>"
                    class="tieuChiDiemInput w-24 border border-gray-400 rounded px-2 py-1"
                  >
                  <p class="loiTieuChiDong hidden text-red-600 text-xs mt-1"></p>
                </td>
                <td class="px-5 py-3 text-right">
                  <button type="button" class="btnXoaTieuChuan text-gray-400 text-sm">Xóa</button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </section>

        <!-- Khối 3: hành động -->
        <div class="flex items-center justify-end gap-3">
          <button type="button" class="border border-gray-400 rounded px-4 py-2 text-sm">
            Hủy
          </button>
          <button type="submit" class="border border-gray-900 bg-gray-900 text-white rounded px-4 py-2 text-sm">
            Lưu công thức tính điểm
          </button>
        </div>

      </form>

    </main>
  </div>

  <!-- Modal: Lưu thành công (ẩn mặc định) -->
  <div id="modalThanhCong" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="bg-white border border-gray-400 rounded w-full max-w-sm mx-4">
      <div class="px-5 py-4 border-b border-gray-300 flex items-center justify-between">
        <h3 class="font-medium">Thiết lập thành công</h3>
        <button type="button" onclick="dongModalThanhCong()" class="text-gray-400 text-sm">✕</button>
      </div>
      <div class="px-5 py-4 text-sm text-gray-700">
        Công thức tính điểm đã được lưu và áp dụng cho kỳ tuyển sinh hiện tại.
      </div>
      <div class="px-5 py-4 border-t border-gray-200 flex justify-end">
        <button type="button" onclick="dongModalThanhCong()" class="border border-gray-900 bg-gray-900 text-white rounded px-4 py-2 text-sm">
          Đóng
        </button>
      </div>
    </div>
  </div>

  <script>
    const tongEl = document.getElementById('tongHeSoHienThi');
    const loiTongHeSoEl = document.getElementById('loiTongHeSo');
    const HE_SO_QUY_DINH = 5; // tổng hệ số chuẩn theo quy định kỳ tuyển sinh (dùng để đối chiếu)
    let demTieuChuanMoi = 0;

    function layDanhSachInputHeSo() {
      return document.querySelectorAll('.hesoInput');
    }

    // Kiểm tra thời gian thực cho 1 ô hệ số
    function kiemTraHeSoDong(input) {
      const p = input.nextElementSibling; // .loiHeSoDong
      const giaTri = parseFloat(input.value);
      if (isNaN(giaTri) || giaTri < 0) {
        p.textContent = `Hệ số môn ${input.dataset.tenMon} không được là số âm.`;
        p.classList.remove('hidden');
      } else {
        p.classList.add('hidden');
      }
    }

    function tinhTongHeSo() {
      let tong = 0;
      layDanhSachInputHeSo().forEach(inp => tong += parseFloat(inp.value || 0));
      tongEl.textContent = tong;

      if (tong !== HE_SO_QUY_DINH) {
        loiTongHeSoEl.textContent = `Tổng hệ số hiện tại là ${tong}, chưa khớp với quy định (${HE_SO_QUY_DINH}).`;
        loiTongHeSoEl.classList.remove('hidden');
      } else {
        loiTongHeSoEl.classList.add('hidden');
      }
      return tong;
    }

    layDanhSachInputHeSo().forEach(inp => {
      inp.addEventListener('input', () => {
        kiemTraHeSoDong(inp);
        tinhTongHeSo();
      });
    });

    // Kiểm tra thời gian thực cho 1 ô mức điểm ưu tiên
    function kiemTraTieuChiDong(input) {
      const p = input.nextElementSibling; // .loiTieuChiDong
      const giaTri = parseFloat(input.value);
      if (isNaN(giaTri) || giaTri < 0) {
        p.textContent = 'Mức điểm không được là số âm.';
        p.classList.remove('hidden');
      } else {
        p.classList.add('hidden');
      }
    }

    // Gắn sự kiện thời gian thực cho cả dòng có sẵn lẫn dòng thêm sau này (event delegation)
    document.getElementById('dsTieuChuan').addEventListener('input', function (e) {
      if (e.target.classList.contains('tieuChiDiemInput')) {
        kiemTraTieuChiDong(e.target);
      }
    });

    // Thêm tiêu chuẩn ưu tiên/khuyến khích mới
    document.getElementById('btnThemTieuChuan').addEventListener('click', function () {
      demTieuChuanMoi++;
      const tbody = document.getElementById('dsTieuChuan');
      const tr = document.createElement('tr');
      tr.className = 'border-b border-gray-100 last:border-b-0';
      tr.innerHTML = `
        <td class="px-5 py-3">
          <input type="text" name="tieuChiMoi[${demTieuChuanMoi}][ten]" placeholder="Nhập đối tượng áp dụng"
                 class="w-full border border-gray-400 rounded px-2 py-1">
        </td>
        <td class="px-5 py-3">
          <input type="number" step="0.25" min="0" name="tieuChiMoi[${demTieuChuanMoi}][diem]" value="0"
                 class="tieuChiDiemInput w-24 border border-gray-400 rounded px-2 py-1">
          <p class="loiTieuChiDong hidden text-red-600 text-xs mt-1"></p>
        </td>
        <td class="px-5 py-3 text-right">
          <button type="button" class="btnXoaTieuChuan text-gray-400 text-sm">Xóa</button>
        </td>
      `;
      tbody.appendChild(tr);
    });

    // Xóa một dòng tiêu chuẩn (gắn cho cả dòng có sẵn và dòng thêm mới)
    document.getElementById('dsTieuChuan').addEventListener('click', function (e) {
      if (e.target.classList.contains('btnXoaTieuChuan')) {
        e.target.closest('tr').remove();
      }
    });

    function moModalThanhCong() {
      document.getElementById('modalThanhCong').classList.remove('hidden');
    }
    function dongModalThanhCong() {
      document.getElementById('modalThanhCong').classList.add('hidden');
    }

    document.getElementById('formCongThuc').addEventListener('submit', function (e) {
      e.preventDefault();

      const loi = document.getElementById('thongBaoLoi');
      const noiDungLoi = document.getElementById('noiDungLoi');
      const danhSachLoi = [];

      // Kiểm tra hệ số âm theo từng môn
      layDanhSachInputHeSo().forEach(inp => {
        if (parseFloat(inp.value) < 0) {
          danhSachLoi.push(`Hệ số môn ${inp.dataset.tenMon} không được là số âm.`);
        }
      });

      // Kiểm tra tổng hệ số so với quy định
      const tong = tinhTongHeSo();
      if (tong !== HE_SO_QUY_DINH) {
        danhSachLoi.push(`Tổng hệ số hiện tại là ${tong}, chưa khớp với quy định (${HE_SO_QUY_DINH}).`);
      }

      // Kiểm tra điểm ưu tiên âm hoặc thiếu tên đối tượng
      document.querySelectorAll('#dsTieuChuan tr').forEach(tr => {
        const inputs = tr.querySelectorAll('input');
        const ten = inputs[0].value.trim();
        const diem = parseFloat(inputs[1].value);
        if (ten === '') {
          danhSachLoi.push('Có tiêu chuẩn chưa nhập đối tượng áp dụng.');
        }
        if (diem < 0) {
          danhSachLoi.push(`Mức điểm cho "${ten || 'tiêu chuẩn chưa đặt tên'}" không được là số âm.`);
        }
      });

      if (danhSachLoi.length > 0) {
        noiDungLoi.innerHTML = danhSachLoi.join('<br>');
        loi.classList.remove('hidden');
      } else {
        loi.classList.add('hidden');
        moModalThanhCong();
      }
    });
  </script>

</body>
</html>