<?php
// Wireframe / mockup — Gộp 2 use case:
// 1. "Tra cứu kết quả thi/trúng tuyển" (Actor: Học sinh & Phụ huynh)
// 2. "Xác nhận nhập học" (Actor: Học sinh & Phụ huynh)
// Dữ liệu bên dưới là dữ liệu mẫu để dựng giao diện, chưa kết nối CSDL thật.

$pageTitle = "Stackers - Tra cứu kết quả thi/trúng tuyển";
$soLanToiDa = 5; // số lần nhập sai tối đa trước khi khóa chức năng (Exception flow 4.2)

// Dữ liệu mẫu để JS đối chiếu khi tra cứu (giả lập, không phải xử lý phía server thật)
// Bổ sung: nguyenVong, daXacNhanNhapHoc, chiTietTruong (dùng cho use case Xác nhận nhập học)
$thiSinhMau = [
    'SBD001234' => [
        'hoTen'   => 'Nguyễn Văn A',
        'ngaySinh'=> '2010-05-14',
        'diem'    => ['Toán' => 8.5, 'Ngữ văn' => 7.0, 'Tiếng Anh' => 9.0],
        'trungTuyen' => true,
        'truong'  => 'THPT Nguyễn Thị Minh Khai',
        'nguyenVong' => 'Nguyện vọng 1',
        'daXacNhanNhapHoc' => false,
        'conHanXacNhan' => true, // false => hết hạn xác nhận
        'chiTietTruong' => [
            'diaChi' => '275 Đường Điện Biên Phủ, Phường Vườn Chuối, TP.HCM',
            'chuongTrinhDaoTao' => 'Chương trình chuẩn + Tăng cường Tiếng Anh',
            'hocPhi' => '2.500.000 đ / tháng',
        ],
    ],
    'SBD005678' => [
        'hoTen'   => 'Trần Thị B',
        'ngaySinh'=> '2010-11-02',
        'diem'    => ['Toán' => 5.0, 'Ngữ văn' => 6.0, 'Tiếng Anh' => 4.5],
        'trungTuyen' => false,
        'truong'  => null,
        'nguyenVong' => null,
        'daXacNhanNhapHoc' => false,
        'conHanXacNhan' => false,
        'chiTietTruong' => null,
    ],
];
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
    <div class="max-w-5xl mx-auto px-6 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 border border-gray-400 flex items-center justify-center text-xs font-bold">
          LOGO
        </div>
        <span class="font-semibold text-lg">Stackers</span>
        <span class="text-gray-400">|</span>
        <span class="text-sm text-gray-600">Hệ thống Quản lý Tuyển sinh 10</span>
      </div>
      <div class="flex items-center gap-4 text-sm text-gray-600">
        <span>Học sinh</span>
        <div class="w-8 h-8 rounded-full border border-gray-400 flex items-center justify-center text-xs">
          HS
        </div>
      </div>
    </div>
  </header>

  <div class="max-w-3xl mx-auto px-4">

    <!-- Breadcrumb -->
    <div class="text-sm text-gray-500 mt-4 mb-2">
      Trang chủ &nbsp;/&nbsp; <span class="text-gray-800">Tra cứu kết quả thi/trúng tuyển</span>
    </div>

    <!-- Page header -->
    <div class="mb-6">
      <h1 class="text-xl font-semibold">Tra cứu kết quả thi/trúng tuyển</h1>
      <p class="text-sm text-gray-500 mt-1">
        Nhập số báo danh và thông tin xác thực để xem điểm thi từng môn, kết quả trúng tuyển và xác nhận nhập học.
      </p>
    </div>

    <!-- Khối khóa chức năng (Exception flow 4.2, ẩn mặc định) -->
    <div id="khoiBiKhoa" class="hidden mb-4 border border-gray-500 bg-gray-100 px-4 py-3 rounded">
      <p class="text-sm font-medium">Chức năng tra cứu đã tạm bị khóa</p>
      <p class="text-sm text-gray-600 mt-1">
        Bạn đã nhập sai thông tin xác thực quá <?php echo $soLanToiDa; ?> lần. Vui lòng thử lại sau hoặc liên hệ nhà trường để được hỗ trợ.
      </p>
    </div>

    <!-- Khối form tra cứu -->
    <section id="khoiFormTraCuu" class="bg-white border border-gray-300 rounded mb-6">
      <div class="px-5 py-3 border-b border-gray-300">
        <h2 class="font-medium">Thông tin tra cứu</h2>
      </div>

      <form id="formTraCuu" class="px-5 py-4 space-y-4" novalidate>

        <div>
          <label class="block text-sm mb-1">Số báo danh</label>
          <input
            type="text"
            id="soBaoDanh"
            placeholder="Ví dụ: SBD001234"
            class="w-full border border-gray-400 rounded px-3 py-2 text-sm"
          >
          <p id="loiSoBaoDanh" class="hidden text-red-600 text-xs mt-1"></p>
        </div>

        <div>
          <label class="block text-sm mb-1">Ngày sinh (thông tin xác thực)</label>
          <input
            type="date"
            id="ngaySinh"
            class="w-full border border-gray-400 rounded px-3 py-2 text-sm"
          >
          <p id="loiNgaySinh" class="hidden text-red-600 text-xs mt-1"></p>
        </div>

        <!-- Thông báo không tìm thấy / không khớp (Alternative flow 4.1) -->
        <div id="thongBaoKhongKhop" class="hidden border border-gray-500 bg-gray-50 px-3 py-2 rounded text-sm">
          Không tìm thấy thông tin hoặc thông tin xác thực không khớp. Vui lòng kiểm tra lại.
          <span class="text-gray-500">(Số lần còn lại: <span id="soLanConLai"><?php echo $soLanToiDa; ?></span>)</span>
        </div>

        <div class="flex justify-end">
          <button type="submit" id="btnTraCuu" class="border border-gray-900 bg-gray-900 text-white rounded px-4 py-2 text-sm">
            Tra cứu
          </button>
        </div>

        <p class="text-xs text-gray-400">
          Dữ liệu mẫu để thử: SBD001234 / 2010-05-14 (trúng tuyển, còn hạn xác nhận) — SBD005678 / 2010-11-02 (không trúng tuyển).
        </p>
      </form>
    </section>

    <!-- Khối kết quả (ẩn mặc định, hiện sau khi tra cứu thành công) -->
    <section id="khoiKetQua" class="hidden bg-white border border-gray-300 rounded mb-6">
      <div class="px-5 py-3 border-b border-gray-300 flex items-center justify-between">
        <h2 class="font-medium">Kết quả tra cứu</h2>
        <span id="nhanTrangThai" class="text-xs border border-gray-400 px-2 py-1 rounded"></span>
      </div>

      <div class="px-5 py-4 space-y-4 text-sm">
        <div class="grid grid-cols-2 gap-y-2">
          <div class="text-gray-500">Họ và tên</div>
          <div id="ketQuaHoTen" class="font-medium"></div>
          <div class="text-gray-500">Số báo danh</div>
          <div id="ketQuaSoBaoDanh" class="font-medium"></div>
        </div>

        <div>
          <h3 class="text-sm font-medium mb-2">Điểm thi từng môn</h3>
          <table class="w-full text-sm border border-gray-200">
            <thead>
              <tr class="text-left text-gray-500 border-b border-gray-200">
                <th class="px-3 py-2 font-normal">Môn thi</th>
                <th class="px-3 py-2 font-normal w-24">Điểm</th>
              </tr>
            </thead>
            <tbody id="ketQuaBangDiem"></tbody>
          </table>
        </div>

        <div id="ketQuaTruongTrungTuyen" class="hidden border-t border-gray-200 pt-3 space-y-3">
          <div>
            <span class="text-gray-500">Trường trúng tuyển:</span>
            <span id="ketQuaTenTruong" class="font-medium"></span>
            <span class="text-gray-500">— </span>
            <span id="ketQuaNguyenVong" class="font-medium"></span>
          </div>

          <!-- Trạng thái nhập học đã xác nhận (ẩn mặc định) -->
          <div id="daXacNhanBox" class="hidden border border-gray-400 bg-gray-50 rounded px-3 py-2">
            ✓ Bạn đã xác nhận nhập học tại trường này.
          </div>

          <!-- Thông báo hết hạn xác nhận (Tiền điều kiện không thỏa) -->
          <div id="hetHanXacNhanBox" class="hidden border border-gray-400 bg-gray-50 rounded px-3 py-2 text-gray-600">
            Đã hết thời hạn xác nhận nhập học cho kết quả trúng tuyển này.
          </div>

          <!-- Nút mở khối xác nhận nhập học -->
          <div id="mocXacNhanNhapHoc">
            <button type="button" id="btnMoXacNhan" class="border border-gray-900 bg-gray-900 text-white rounded px-4 py-2 text-sm">
              Xác nhận nhập học
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- ============================================================ -->
    <!-- Use case: Xác nhận nhập học -->
    <!-- ============================================================ -->
    <section id="khoiXacNhanNhapHoc" class="hidden bg-white border border-gray-300 rounded mb-6">
      <div class="px-5 py-3 border-b border-gray-300">
        <h2 class="font-medium">Xác nhận nhập học</h2>
      </div>

      <div class="px-5 py-4 space-y-4 text-sm">
        <p class="text-gray-600">
          Vui lòng kiểm tra thông tin trường và nguyện vọng trúng tuyển trước khi xác nhận nhập học chính thức.
        </p>

        <div class="grid grid-cols-2 gap-y-2 border border-gray-200 rounded px-3 py-3">
          <div class="text-gray-500">Trường</div>
          <div id="xnTenTruong" class="font-medium"></div>
          <div class="text-gray-500">Nguyện vọng trúng tuyển</div>
          <div id="xnNguyenVong" class="font-medium"></div>
        </div>

        <!-- Alternative flow 3.1: Xem chi tiết trường trước khi xác nhận -->
        <div>
          <button type="button" id="btnXemChiTietTruong" class="text-sm underline text-gray-700">
            Xem chi tiết trường
          </button>

          <div id="khoiChiTietTruong" class="hidden mt-2 border border-gray-200 rounded px-3 py-3 space-y-1">
            <div><span class="text-gray-500">Địa chỉ:</span> <span id="ctDiaChi"></span></div>
            <div><span class="text-gray-500">Chương trình đào tạo:</span> <span id="ctChuongTrinh"></span></div>
            <div><span class="text-gray-500">Học phí:</span> <span id="ctHocPhi"></span></div>
          </div>
        </div>

        <!-- Thông báo lỗi khi cập nhật (Exception flow 5.1) -->
        <div id="loiXacNhanNhapHoc" class="hidden border border-gray-500 bg-gray-50 px-3 py-2 rounded text-sm">
          Xác nhận nhập học không thành công, vui lòng thử lại.
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" id="btnHuyXacNhan" class="border border-gray-400 rounded px-4 py-2 text-sm">
            Hủy
          </button>
          <button type="button" id="btnXacNhanNhapHoc" class="border border-gray-900 bg-gray-900 text-white rounded px-4 py-2 text-sm">
            Xác nhận nhập học
          </button>
        </div>
      </div>
    </section>

    <!-- Thông báo thành công sau khi xác nhận nhập học -->
    <div id="thongBaoXacNhanThanhCong" class="hidden mb-10 border border-gray-500 bg-gray-50 px-4 py-3 rounded text-sm">
      Xác nhận nhập học thành công. Chúc mừng bạn đã trở thành học sinh của trường!
    </div>

  </div>

  <script>
    const DU_LIEU_MAU = <?php echo json_encode($thiSinhMau, JSON_UNESCAPED_UNICODE); ?>;
    const SO_LAN_TOI_DA = <?php echo $soLanToiDa; ?>;
    let soLanSai = 0;
    let sbdHienTai = null; // số báo danh đang được xem, dùng cho use case Xác nhận nhập học

    const inputSoBaoDanh = document.getElementById('soBaoDanh');
    const inputNgaySinh = document.getElementById('ngaySinh');
    const loiSoBaoDanh = document.getElementById('loiSoBaoDanh');
    const loiNgaySinh = document.getElementById('loiNgaySinh');
    const thongBaoKhongKhop = document.getElementById('thongBaoKhongKhop');
    const soLanConLaiEl = document.getElementById('soLanConLai');
    const khoiBiKhoa = document.getElementById('khoiBiKhoa');
    const khoiFormTraCuu = document.getElementById('khoiFormTraCuu');
    const khoiKetQua = document.getElementById('khoiKetQua');

    // Xác nhận nhập học
    const khoiXacNhanNhapHoc = document.getElementById('khoiXacNhanNhapHoc');
    const mocXacNhanNhapHoc = document.getElementById('mocXacNhanNhapHoc');
    const daXacNhanBox = document.getElementById('daXacNhanBox');
    const hetHanXacNhanBox = document.getElementById('hetHanXacNhanBox');
    const thongBaoXacNhanThanhCong = document.getElementById('thongBaoXacNhanThanhCong');

    inputSoBaoDanh.addEventListener('input', () => {
      loiSoBaoDanh.classList.toggle('hidden', inputSoBaoDanh.value.trim() !== '');
      loiSoBaoDanh.textContent = 'Vui lòng nhập số báo danh.';
    });
    inputNgaySinh.addEventListener('input', () => {
      loiNgaySinh.classList.toggle('hidden', inputNgaySinh.value !== '');
      loiNgaySinh.textContent = 'Vui lòng nhập ngày sinh để xác thực.';
    });

    function hienThiKetQua(sbd, thiSinh) {
      sbdHienTai = sbd;

      document.getElementById('ketQuaHoTen').textContent = thiSinh.hoTen;
      document.getElementById('ketQuaSoBaoDanh').textContent = sbd;

      const tbody = document.getElementById('ketQuaBangDiem');
      tbody.innerHTML = '';
      Object.entries(thiSinh.diem).forEach(([mon, diem]) => {
        const tr = document.createElement('tr');
        tr.className = 'border-b border-gray-100 last:border-b-0';
        tr.innerHTML = `<td class="px-3 py-2">${mon}</td><td class="px-3 py-2">${diem}</td>`;
        tbody.appendChild(tr);
      });

      const nhan = document.getElementById('nhanTrangThai');
      const khoiTruong = document.getElementById('ketQuaTruongTrungTuyen');

      // Ẩn các khối liên quan tới xác nhận nhập học mỗi lần tra cứu mới
      khoiXacNhanNhapHoc.classList.add('hidden');
      thongBaoXacNhanThanhCong.classList.add('hidden');
      daXacNhanBox.classList.add('hidden');
      hetHanXacNhanBox.classList.add('hidden');
      mocXacNhanNhapHoc.classList.add('hidden');

      if (thiSinh.trungTuyen) {
        nhan.textContent = 'Trúng tuyển';
        khoiTruong.classList.remove('hidden');
        document.getElementById('ketQuaTenTruong').textContent = thiSinh.truong;
        document.getElementById('ketQuaNguyenVong').textContent = thiSinh.nguyenVong;

        // Tiền điều kiện use case Xác nhận nhập học: còn hạn / chưa xác nhận nơi khác
        if (thiSinh.daXacNhanNhapHoc) {
          daXacNhanBox.classList.remove('hidden');
        } else if (!thiSinh.conHanXacNhan) {
          hetHanXacNhanBox.classList.remove('hidden');
        } else {
          mocXacNhanNhapHoc.classList.remove('hidden');
        }
      } else {
        nhan.textContent = 'Không trúng tuyển';
        khoiTruong.classList.add('hidden');
      }

      khoiKetQua.classList.remove('hidden');
      thongBaoKhongKhop.classList.add('hidden');
    }

    document.getElementById('formTraCuu').addEventListener('submit', function (e) {
      e.preventDefault();

      const sbd = inputSoBaoDanh.value.trim().toUpperCase();
      const ngaySinh = inputNgaySinh.value;

      let thieuThongTin = false;
      if (sbd === '') {
        loiSoBaoDanh.textContent = 'Vui lòng nhập số báo danh.';
        loiSoBaoDanh.classList.remove('hidden');
        thieuThongTin = true;
      }
      if (ngaySinh === '') {
        loiNgaySinh.textContent = 'Vui lòng nhập ngày sinh để xác thực.';
        loiNgaySinh.classList.remove('hidden');
        thieuThongTin = true;
      }
      if (thieuThongTin) return;

      khoiKetQua.classList.add('hidden');

      const thiSinh = DU_LIEU_MAU[sbd];
      const hopLe = thiSinh && thiSinh.ngaySinh === ngaySinh;

      if (hopLe) {
        soLanSai = 0;
        hienThiKetQua(sbd, thiSinh);
      } else {
        // Alternative flow 4.1: thông tin không khớp
        soLanSai++;
        const conLai = SO_LAN_TOI_DA - soLanSai;
        soLanConLaiEl.textContent = Math.max(conLai, 0);
        thongBaoKhongKhop.classList.remove('hidden');

        // Exception flow 4.2: sai quá số lần quy định -> khóa chức năng
        if (soLanSai >= SO_LAN_TOI_DA) {
          khoiFormTraCuu.classList.add('hidden');
          khoiBiKhoa.classList.remove('hidden');
        }
      }
    });

    // ================================================================
    // Use case: Xác nhận nhập học
    // ================================================================

    document.getElementById('btnMoXacNhan').addEventListener('click', function () {
      const thiSinh = DU_LIEU_MAU[sbdHienTai];
      if (!thiSinh) return;

      document.getElementById('xnTenTruong').textContent = thiSinh.truong;
      document.getElementById('xnNguyenVong').textContent = thiSinh.nguyenVong;

      const ct = thiSinh.chiTietTruong || {};
      document.getElementById('ctDiaChi').textContent = ct.diaChi || '';
      document.getElementById('ctChuongTrinh').textContent = ct.chuongTrinhDaoTao || '';
      document.getElementById('ctHocPhi').textContent = ct.hocPhi || '';

      document.getElementById('khoiChiTietTruong').classList.add('hidden');
      document.getElementById('loiXacNhanNhapHoc').classList.add('hidden');

      khoiXacNhanNhapHoc.classList.remove('hidden');
      khoiXacNhanNhapHoc.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    // Alternative flow 3.1: Xem chi tiết trường trước khi xác nhận
    document.getElementById('btnXemChiTietTruong').addEventListener('click', function () {
      document.getElementById('khoiChiTietTruong').classList.toggle('hidden');
    });

    // 9.1 (tương tự flow Hủy trong các use case khác của hệ thống): Hủy xác nhận, quay lại kết quả tra cứu
    document.getElementById('btnHuyXacNhan').addEventListener('click', function () {
      khoiXacNhanNhapHoc.classList.add('hidden');
    });

    // Basic flow bước 3-5: Xác nhận nhập học
    document.getElementById('btnXacNhanNhapHoc').addEventListener('click', function () {
      const thiSinh = DU_LIEU_MAU[sbdHienTai];
      if (!thiSinh) return;

      // Mô phỏng Exception flow 5.1: gõ "loi" vào Số báo danh trước khi tra cứu để giả lập lỗi hệ thống khi lưu
      const gioLap5_1 = sbdHienTai && sbdHienTai.includes('LOI');

      if (gioLap5_1) {
        document.getElementById('loiXacNhanNhapHoc').classList.remove('hidden');
        return;
      }

      // Basic flow bước 4-5: Kiểm tra thông tin, cập nhật trạng thái nhập học
      thiSinh.daXacNhanNhapHoc = true;

      khoiXacNhanNhapHoc.classList.add('hidden');
      mocXacNhanNhapHoc.classList.add('hidden');
      daXacNhanBox.classList.remove('hidden');
      thongBaoXacNhanThanhCong.classList.remove('hidden');
      thongBaoXacNhanThanhCong.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  </script>

</body>
</html>