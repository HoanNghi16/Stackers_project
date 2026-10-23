<?php
// Giao diện minh họa theo đặc tả use case. Dữ liệu mẫu tại trình duyệt, chưa ghi vào CSDL.
$pageTitle = "Stackers - Công bố điểm chuẩn";
?>
<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title><?php echo htmlspecialchars($pageTitle); ?></title>
<script src="https://cdn.tailwindcss.com"></script><style>body{font-family:Arial,Helvetica,sans-serif}button:disabled{opacity:.45;cursor:not-allowed}.tag{border:1px solid #9ca3af;border-radius:4px;padding:2px 8px;font-size:12px}.hidden{display:none}</style></head><body class="bg-gray-100 text-gray-900">
<header class="bg-white border-b border-gray-300"><div class="max-w-7xl mx-auto px-6 py-3 flex items-center justify-between"><div class="flex items-center gap-3"><div class="w-9 h-9 border border-gray-400 flex items-center justify-center text-xs font-bold">LOGO</div><strong class="text-lg">Stackers</strong><span class="text-gray-400">|</span><span class="text-sm text-gray-600">Hệ thống Quản lý Tuyển sinh 10</span></div><div class="flex items-center gap-3 text-sm text-gray-600">Sở GD&amp;ĐT <span class="rounded-full border p-2">SGD</span></div></div></header>
<div class="max-w-7xl mx-auto md:flex"><aside class="w-64 shrink-0 min-h-screen bg-white border-r border-gray-300 p-4 hidden md:block">
      <nav class="space-y-1 text-sm">
        <div class="px-3 py-2 text-gray-500">TỔNG QUAN</div>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Trang chủ</a>

        <div class="px-3 py-2 text-gray-500 mt-3">HỘI ĐỒNG &amp; ĐỊA ĐIỂM THI</div>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Quản lý các hội đồng thi</a>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Quản lý địa điểm thi/chấm thi</a>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Quản lý bài thi</a>

        <div class="px-3 py-2 text-gray-500 mt-3">ĐIỂM SỐ</div>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">
          Thiết lập công thức tính điểm
        </a>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Thiết lập tiêu chuẩn ưu tiên/khuyến khích</a>
        <span class="block px-3 py-2 rounded border border-transparent text-gray-700 hover:border-gray-300 cursor-pointer">Nhập điểm thi</span>
        <span class="block px-3 py-2 rounded border border-transparent text-gray-700 hover:border-gray-300 cursor-pointer">Nhập điểm phúc khảo</span>

        <div class="px-3 py-2 text-gray-500 mt-3">TUYỂN SINH</div>
        <span class="block px-3 py-2 rounded border border-transparent text-gray-700 hover:border-gray-300 cursor-pointer">Xét tuyển</span>
        <span class="block px-3 py-2 rounded border border-gray-400 bg-gray-100 font-medium cursor-pointer">Công bố điểm chuẩn</span>

        <div class="px-3 py-2 text-gray-500 mt-3">DANH SÁCH &amp; BÁO CÁO</div>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Xem danh sách thí sinh</a>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Thống kê, báo cáo</a>

        <div class="px-3 py-2 text-gray-500 mt-3">HỆ THỐNG</div>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Quản lý tài khoản</a>
      </nav>
    </aside>
<main class="flex-1 min-w-0 p-6"><div class="text-sm text-gray-500 mb-2">Sở GD&amp;ĐT &nbsp;/&nbsp; Điểm số &amp; Tuyển sinh &nbsp;/&nbsp; <span class="text-gray-800">Công bố điểm chuẩn</span></div><div class="flex flex-wrap justify-between items-start gap-3 mb-6"><div><h1 class="text-xl font-semibold">Công bố điểm chuẩn</h1><p class="text-sm text-gray-500 mt-1">Kiểm tra danh sách điểm chuẩn, lưu chờ hoặc công bố.</p></div></div>
<div id="message" class="hidden mb-4 border border-gray-500 bg-white p-4 text-sm rounded" role="status"></div><input type="hidden" id="ky" value="2026"><section class="bg-white border border-gray-300 rounded mb-5"><div class="px-5 py-3 border-b border-gray-300 font-semibold">Danh sách điểm chuẩn</div><div class="p-5"><div class="overflow-x-auto"><table class="w-full text-sm" id="dsDiemChuan"><thead><tr class="bg-gray-100 text-left"><th class="border-b px-3 py-3 font-medium">Trường THPT</th><th class="border-b px-3 py-3 font-medium">Điểm chuẩn</th><th class="border-b px-3 py-3 font-medium">Trạng thái</th><th class="border-b px-3 py-3 font-medium">Xem chi tiết</th></tr></thead><tbody><tr class="border-b"><td class="px-3 py-3">THPT Nguyễn Du</td><td class="px-3 py-3">24.50</td><td class="px-3 py-3"><span id="st0">Chưa công bố</span></td><td class="px-3 py-3"><button class="underline" onclick="chiTiet(0)">Xem</button></td></tr><tr class="border-b"><td class="px-3 py-3">THPT Trưng Vương</td><td class="px-3 py-3">21.00</td><td class="px-3 py-3"><span id="st1">Chưa công bố</span></td><td class="px-3 py-3"><button class="underline" onclick="chiTiet(1)">Xem</button></td></tr></tbody></table></div><p id="chitiet" class="mt-3 text-sm text-gray-600"></p></div></section><section class="bg-white border border-gray-300 rounded mb-5"><div class="px-5 py-3 border-b border-gray-300 font-semibold">Xác nhận công bố</div><div class="p-5"><p class="text-sm text-gray-600 mb-4">Kiểm tra thông tin điểm chuẩn trước khi công bố.</p><div class="flex flex-wrap gap-2"><button type="button" onclick="luuCho()" class="border border-gray-500 px-4 py-2 rounded text-sm bg-white">Lưu chờ công bố</button><button type="button" onclick="yeuCauCongBo()" class="border border-gray-500 px-4 py-2 rounded text-sm bg-gray-800 text-white">Công bố điểm chuẩn</button><button type="button" onclick="huy()" class="border border-gray-500 px-4 py-2 rounded text-sm bg-white">Hủy</button></div><p id="thongTinCongBo" class="mt-4 text-sm text-gray-600">Chưa có thao tác công bố.</p></div></section>
<p class="mt-5 text-xs text-gray-500">Lưu ý: Đây là wireframe tương tác sử dụng dữ liệu giả lập, không phải kết quả tuyển sinh/điểm thi chính thức. Dữ liệu chỉ được lưu trong phiên trang hiện tại.</p></main></div>
<script>
const $=id=>document.getElementById(id);
function msg(message,isError=false){const e=$('message');e.textContent=message;e.classList.remove('hidden');e.style.borderColor=isError?'#991b1b':'#6b7280';e.scrollIntoView({behavior:'smooth',block:'nearest'});}
function val(id){return $(id).value;}
function clean(s){return String(s??'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#39;');}
let status='Chưa công bố';const truong=[['THPT Nguyễn Du',24.5],['THPT Trưng Vương',21]];
function chiTiet(i){$('chitiet').textContent='Chi tiết: '+truong[i][0]+' • Điểm chuẩn: '+truong[i][1].toFixed(2)+' • Kỳ '+val('ky')+' - '+(+val('ky')+1);}
function setStatus(s){status=s;[0,1].forEach(i=>$('st'+i).textContent=s);$('thongTinCongBo').textContent='Trạng thái: '+s+(s==='Đã công bố'?' • Người công bố: Sở GD&ĐT • Thời gian: '+new Date().toLocaleString('vi-VN'):'');}
function luuCho(){setStatus('Chờ công bố');msg('Đã lưu bảng điểm chuẩn ở trạng thái “Chờ công bố”, chưa hiển thị công khai.');}
function yeuCauCongBo(){if(truong.some(t=>!Number.isFinite(t[1])))return msg('Một số trường chưa có điểm chuẩn.',true);if(!confirm('Xác nhận công bố điểm chuẩn?'))return;setStatus('Đã công bố');msg('Công bố điểm chuẩn thành công (mô phỏng).');}
function huy(){msg('Đã hủy thao tác. Trạng thái công bố hiện tại được giữ nguyên: '+status+'.');}
</script></body></html>