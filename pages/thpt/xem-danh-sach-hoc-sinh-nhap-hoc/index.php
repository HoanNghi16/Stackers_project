<?php
// Wireframe / mockup — Use case: "Xem danh sách học sinh nhập học"
// Actor chính: Trường THPT
// Actor phụ: Không có
// Dữ liệu bên dưới là dữ liệu mẫu để dựng giao diện, chưa kết nối CSDL thật.

$pageTitle = "Stackers - Xem danh sách học sinh nhập học";

// Danh sách học sinh đã xác nhận nhập học vào trường (dữ liệu mẫu)
$danhSachHocSinh = [
    ['hoTen' => 'Nguyễn Văn A',   'soBaoDanh' => 'SBD001234', 'diemXetTuyen' => 34.5, 'nguyenVong' => 'NV1', 'lop' => 'Lớp 10 Thường - Khu vực 1'],
    ['hoTen' => 'Trần Thị B',     'soBaoDanh' => 'SBD002345', 'diemXetTuyen' => 32.0, 'nguyenVong' => 'NV1', 'lop' => 'Lớp 10 Thường - Khu vực 1'],
    ['hoTen' => 'Lê Minh C',      'soBaoDanh' => 'SBD003456', 'diemXetTuyen' => 29.5, 'nguyenVong' => 'NV2', 'lop' => 'Lớp 10 Thường - Khu vực 2'],
    ['hoTen' => 'Phạm Thị D',     'soBaoDanh' => 'SBD004567', 'diemXetTuyen' => 37.0, 'nguyenVong' => 'NV1', 'lop' => 'Lớp 10 Chuyên Toán'],
    ['hoTen' => 'Hoàng Văn E',    'soBaoDanh' => 'SBD005678', 'diemXetTuyen' => 27.0, 'nguyenVong' => 'NV3', 'lop' => 'Lớp 10 Thường - Khu vực 2'],
    ['hoTen' => 'Đỗ Thị F',       'soBaoDanh' => 'SBD006789', 'diemXetTuyen' => 36.0, 'nguyenVong' => 'NV1', 'lop' => 'Lớp 10 Chuyên Tiếng Anh'],
];

// Lấy danh sách lớp duy nhất để đổ vào bộ lọc
$danhSachLop = array_values(array_unique(array_column($danhSachHocSinh, 'lop')));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pageTitle; ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
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
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Đăng ký chỉ tiêu</a>
        <a href="#" class="block px-3 py-2 rounded border border-gray-400 bg-gray-100 font-medium">
          Xem danh sách học sinh nhập học
        </a>

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
        Trường THPT &nbsp;/&nbsp; Tuyển sinh &nbsp;/&nbsp; <span class="text-gray-800">Xem danh sách học sinh nhập học</span>
      </div>

      <!-- Page header -->
      <div class="flex items-start justify-between mb-4">
        <div>
          <h1 class="text-xl font-semibold">Danh sách học sinh nhập học</h1>
          <p class="text-sm text-gray-500 mt-1">
            Danh sách học sinh đã xác nhận nhập học chính thức vào trường trong kỳ tuyển sinh hiện tại.
          </p>
        </div>
        <span class="text-xs border border-gray-400 px-2 py-1 rounded">
          Kỳ tuyển sinh: 2026 - 2027
        </span>
      </div>

      <!-- Error banner: lỗi kết nối CSDL (Exception flow 2.1, ẩn mặc định) -->
      <div id="thongBaoLoiHeThong" class="hidden mb-4 border border-gray-500 bg-gray-100 px-4 py-3 rounded">
        <p class="text-sm font-medium">Không thể tải danh sách học sinh</p>
        <p class="text-sm text-gray-600 mt-1">Hệ thống gặp lỗi khi truy xuất dữ liệu. Vui lòng thử lại sau.</p>
      </div>

      <!-- Khối bộ lọc / tìm kiếm / xuất danh sách -->
      <section id="khoiBoLoc" class="bg-white border border-gray-300 rounded mb-4">
        <div class="px-5 py-3 border-b border-gray-300 flex items-center justify-between">
          <h2 class="font-medium">Tìm kiếm &amp; lọc danh sách</h2>
          <button type="button" id="btnXuatDanhSach" class="border border-gray-400 rounded px-3 py-1.5 text-sm">
            Xuất danh sách
          </button>
        </div>

        <div class="px-5 py-4 flex flex-wrap items-end gap-4 text-sm">
          <div class="flex-1 min-w-[220px]">
            <label class="block text-gray-500 mb-1">Tìm theo họ tên hoặc số báo danh</label>
            <input
              type="text"
              id="oTimKiem"
              placeholder="Nhập họ tên hoặc số báo danh..."
              class="w-full border border-gray-400 rounded px-3 py-2"
            >
          </div>

          <div class="min-w-[220px]">
            <label class="block text-gray-500 mb-1">Lọc theo lớp</label>
            <select id="oLopLoc" class="w-full border border-gray-400 rounded px-3 py-2">
              <option value="">Tất cả các lớp</option>
              <?php foreach ($danhSachLop as $lop): ?>
              <option value="<?php echo htmlspecialchars($lop); ?>"><?php echo htmlspecialchars($lop); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <button type="button" id="btnXoaBoLoc" class="border border-gray-400 rounded px-3 py-2">
              Xóa bộ lọc
            </button>
          </div>
        </div>
      </section>

      <!-- Khối bảng danh sách -->
      <section id="khoiDanhSach" class="bg-white border border-gray-300 rounded">
        <div class="px-5 py-3 border-b border-gray-300 flex items-center justify-between">
          <h2 class="font-medium">Danh sách học sinh</h2>
          <span class="text-xs text-gray-500">Số lượng: <span id="soLuongHienThi" class="font-medium"></span> học sinh</span>
        </div>

        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-gray-500 border-b border-gray-200">
              <th class="px-5 py-2 font-normal w-10">#</th>
              <th class="px-5 py-2 font-normal">Họ và tên</th>
              <th class="px-5 py-2 font-normal">Số báo danh</th>
              <th class="px-5 py-2 font-normal">Lớp</th>
              <th class="px-5 py-2 font-normal w-28">Nguyện vọng</th>
              <th class="px-5 py-2 font-normal w-28">Điểm xét tuyển</th>
            </tr>
          </thead>
          <tbody id="thanDanhSach">
            <!-- Các dòng được render bằng PHP lần đầu, sau đó JS lọc/hiển thị lại -->
            <?php foreach ($danhSachHocSinh as $i => $hs): ?>
            <tr class="dongHocSinh border-b border-gray-100 last:border-b-0"
                data-ho-ten="<?php echo htmlspecialchars(mb_strtolower($hs['hoTen'])); ?>"
                data-so-bao-danh="<?php echo htmlspecialchars(mb_strtolower($hs['soBaoDanh'])); ?>"
                data-lop="<?php echo htmlspecialchars($hs['lop']); ?>">
              <td class="px-5 py-3 text-gray-400 cotStt"><?php echo $i + 1; ?></td>
              <td class="px-5 py-3"><?php echo htmlspecialchars($hs['hoTen']); ?></td>
              <td class="px-5 py-3"><?php echo htmlspecialchars($hs['soBaoDanh']); ?></td>
              <td class="px-5 py-3"><?php echo htmlspecialchars($hs['lop']); ?></td>
              <td class="px-5 py-3"><?php echo htmlspecialchars($hs['nguyenVong']); ?></td>
              <td class="px-5 py-3"><?php echo number_format($hs['diemXetTuyen'], 1); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Không tìm thấy học sinh phù hợp với bộ lọc/tìm kiếm (ẩn mặc định) -->
        <div id="khongTimThayPhuHop" class="hidden px-5 py-6 text-center text-sm text-gray-500">
          Không tìm thấy học sinh phù hợp với điều kiện tìm kiếm/lọc.
        </div>
      </section>

      <!-- Alternative flow 3.1: Chưa có học sinh nào xác nhận nhập học (ẩn mặc định) -->
      <section id="khoiChuaCoHocSinh" class="hidden bg-white border border-gray-300 rounded px-5 py-10 text-center">
        <p class="text-sm text-gray-600 mb-4">Chưa có học sinh nào xác nhận nhập học vào trường.</p>
        <button type="button" class="border border-gray-900 bg-gray-900 text-white rounded px-4 py-2 text-sm">
          Quay lại trang chủ
        </button>
      </section>

    </main>
  </div>

  <script>
    // Dữ liệu mẫu để demo Alternative flow 3.1: đổi thành [] để xem giao diện "Chưa có học sinh nào xác nhận nhập học"
    const CO_DU_LIEU_BAN_DAU = <?php echo count($danhSachHocSinh) > 0 ? 'true' : 'false'; ?>;

    const khoiBoLoc = document.getElementById('khoiBoLoc');
    const khoiDanhSach = document.getElementById('khoiDanhSach');
    const khoiChuaCoHocSinh = document.getElementById('khoiChuaCoHocSinh');
    const khongTimThayPhuHop = document.getElementById('khongTimThayPhuHop');
    const oTimKiem = document.getElementById('oTimKiem');
    const oLopLoc = document.getElementById('oLopLoc');
    const soLuongHienThi = document.getElementById('soLuongHienThi');

    // Alternative flow 3.1: chưa có học sinh nào xác nhận nhập học
    if (!CO_DU_LIEU_BAN_DAU) {
      khoiBoLoc.classList.add('hidden');
      khoiDanhSach.classList.add('hidden');
      khoiChuaCoHocSinh.classList.remove('hidden');
    }

    function locDanhSach() {
      const tuKhoa = oTimKiem.value.trim().toLowerCase();
      const lopChon = oLopLoc.value;
      const dong = document.querySelectorAll('.dongHocSinh');
      let soHienThi = 0;

      dong.forEach(tr => {
        const khopTuKhoa =
          tuKhoa === '' ||
          tr.dataset.hoTen.includes(tuKhoa) ||
          tr.dataset.soBaoDanh.includes(tuKhoa);
        const khopLop = lopChon === '' || tr.dataset.lop === lopChon;

        if (khopTuKhoa && khopLop) {
          tr.classList.remove('hidden');
          soHienThi++;
          tr.querySelector('.cotStt').textContent = soHienThi; // đánh số lại STT theo danh sách đang hiển thị
        } else {
          tr.classList.add('hidden');
        }
      });

      soLuongHienThi.textContent = soHienThi;
      khongTimThayPhuHop.classList.toggle('hidden', soHienThi !== 0);
    }

    oTimKiem.addEventListener('input', locDanhSach);
    oLopLoc.addEventListener('change', locDanhSach);

    document.getElementById('btnXoaBoLoc').addEventListener('click', function () {
      oTimKiem.value = '';
      oLopLoc.value = '';
      locDanhSach();
    });

    // Basic flow bước 4-5: Xuất danh sách (theo kết quả đang lọc/tìm kiếm hiện tại)
    document.getElementById('btnXuatDanhSach').addEventListener('click', function () {
      const dongHienThi = Array.from(document.querySelectorAll('.dongHocSinh')).filter(tr => !tr.classList.contains('hidden'));

      const dongTieuDe = ['Họ và tên', 'Số báo danh', 'Lớp', 'Nguyện vọng', 'Điểm xét tuyển'];
      const dongDuLieu = dongHienThi.map(tr => {
        const td = tr.querySelectorAll('td');
        return [td[1].textContent, td[2].textContent, td[3].textContent, td[4].textContent, td[5].textContent];
      });

      const noiDungCsv = [dongTieuDe, ...dongDuLieu]
        .map(hang => hang.map(o => `"${o.replace(/"/g, '""')}"`).join(','))
        .join('\n');

      const blob = new Blob(['\uFEFF' + noiDungCsv], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'danh-sach-hoc-sinh-nhap-hoc.csv';
      a.click();
      URL.revokeObjectURL(url);
    });

    // Khởi tạo số lượng hiển thị và STT ban đầu
    if (CO_DU_LIEU_BAN_DAU) {
      locDanhSach();
    }
  </script>

</body>
</html>