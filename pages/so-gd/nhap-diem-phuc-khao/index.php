<?php
// Giao diện minh họa theo đặc tả use case. Dữ liệu mẫu tại trình duyệt, chưa ghi vào CSDL.
$pageTitle = "Stackers - Nhập điểm phúc khảo";
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
        <span class="block px-3 py-2 rounded border border-gray-400 bg-gray-100 font-medium cursor-pointer">Nhập điểm phúc khảo</span>

        <div class="px-3 py-2 text-gray-500 mt-3">TUYỂN SINH</div>
        <span class="block px-3 py-2 rounded border border-transparent text-gray-700 hover:border-gray-300 cursor-pointer">Xét tuyển</span>
        <span class="block px-3 py-2 rounded border border-transparent text-gray-700 hover:border-gray-300 cursor-pointer">Công bố điểm chuẩn</span>

        <div class="px-3 py-2 text-gray-500 mt-3">DANH SÁCH &amp; BÁO CÁO</div>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Xem danh sách thí sinh</a>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Thống kê, báo cáo</a>

        <div class="px-3 py-2 text-gray-500 mt-3">HỆ THỐNG</div>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Quản lý tài khoản</a>
      </nav>
    </aside>
<main class="flex-1 min-w-0 p-6"><div class="text-sm text-gray-500 mb-2">Sở GD&amp;ĐT &nbsp;/&nbsp; Điểm số &amp; Tuyển sinh &nbsp;/&nbsp; <span class="text-gray-800">Nhập điểm phúc khảo</span></div><div class="flex flex-wrap justify-between items-start gap-3 mb-6"><div><h1 class="text-xl font-semibold">Nhập điểm phúc khảo</h1><p class="text-sm text-gray-500 mt-1">Đối chiếu điểm thi ban đầu với kết quả chấm phúc khảo và cập nhật.</p></div><span class="text-xs border border-gray-400 px-2 py-1 rounded">Kỳ tuyển sinh: 2026 - 2027</span></div>
<div id="message" class="hidden mb-4 border border-gray-500 bg-white p-4 text-sm rounded" role="status"></div><section class="bg-white border border-gray-300 rounded mb-5"><div class="px-5 py-3 border-b border-gray-300 font-semibold">Kỳ tuyển sinh 2026 - 2027</div><div class="p-5"><div class="grid md:grid-cols-1 gap-4"><input type="hidden" id="ky" value="2026"><label class="block text-sm"><span class="block text-gray-600 mb-1">Hồ sơ đã hoàn tất chấm phúc khảo</span><select id="hoSo" class="w-full border border-gray-400 rounded p-2 bg-white"><option value="PK001">PK001 - Nguyễn Minh Anh - Toán</option><option value="PK002">PK002 - Trần Gia Bảo - Ngữ văn</option></select></label></div><div class="mt-4"><button type="button" onclick="xemHoSo()" class="border border-gray-500 px-4 py-2 rounded text-sm bg-gray-800 text-white">Xem hồ sơ phúc khảo</button></div></div></section><section class="bg-white border border-gray-300 rounded mb-5"><div class="px-5 py-3 border-b border-gray-300 font-semibold">Thông tin kết quả chấm phúc khảo</div><div class="p-5"><div id="thongTin" class="text-sm text-gray-500">Chọn hồ sơ để xem kết quả chấm phúc khảo.</div></div></section><section class="bg-white border border-gray-300 rounded mb-5"><div class="px-5 py-3 border-b border-gray-300 font-semibold">Kiểm tra và xác nhận điểm phúc khảo</div><div class="p-5"><div class="flex flex-wrap gap-2"><button type="button" onclick="kiemTraPK()" class="border border-gray-500 px-4 py-2 rounded text-sm bg-gray-800 text-white">Kiểm tra điểm phúc khảo</button><button type="button" onclick="luuPK()" class="border border-gray-500 px-4 py-2 rounded text-sm bg-gray-800 text-white">Xác nhận lưu kết quả</button><button type="button" onclick="huyPK()" class="border border-gray-500 px-4 py-2 rounded text-sm bg-white">Hủy</button></div><p id="nhatKy" class="text-sm text-gray-600 mt-4">Chưa cập nhật điểm phúc khảo.</p></div><div class="px-5 py-3 border-t border-gray-200 text-xs text-gray-500">Bảo lưu điểm thi ban đầu để đối chiếu; chỉ cập nhật kết quả sau khi xác nhận.</div></section>
<p class="mt-5 text-xs text-gray-500">Lưu ý: Đây là wireframe tương tác sử dụng dữ liệu giả lập, không phải kết quả tuyển sinh/điểm thi chính thức. Dữ liệu chỉ được lưu trong phiên trang hiện tại.</p></main></div>
<script>
const $=id=>document.getElementById(id);
function msg(message,isError=false){const e=$('message');e.textContent=message;e.classList.remove('hidden');e.style.borderColor=isError?'#991b1b':'#6b7280';e.scrollIntoView({behavior:'smooth',block:'nearest'});}
function val(id){return $(id).value;}
function clean(s){return String(s??'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#39;');}
const dossiers={PK001:{ma:'TS001',ten:'Nguyễn Minh Anh',mon:'Toán',old:7.5,recheck:8.0,done:true},PK002:{ma:'TS002',ten:'Trần Gia Bảo',mon:'Ngữ văn',old:6.75,recheck:6.75,done:true}};const saved={};let loadedPK=null,checkedPK=false;
function xemHoSo(){let key=val('hoSo'),d=dossiers[key];if(!d||!d.done)return msg('Hồ sơ phúc khảo chưa có kết quả chấm.',true);loadedPK=key;checkedPK=false;$('thongTin').innerHTML=`<div class="grid md:grid-cols-2 gap-4"><p><b>Mã hồ sơ:</b> ${key}</p><p><b>Thí sinh:</b> ${d.ma} - ${d.ten}</p><p><b>Môn thi:</b> ${d.mon}</p><p><b>Trạng thái hồ sơ:</b> ${saved[key]?'Đã cập nhật kết quả':'Đã hoàn tất chấm'}</p><p><b>Điểm thi ban đầu:</b> ${d.old}</p><p><b>Điểm theo kết quả chấm phúc khảo:</b> ${d.recheck}</p></div><label class="block text-sm mt-4">Nhập điểm phúc khảo đối chiếu với kết quả chấm<input type="number" min="0" max="10" step="0.25" class="block mt-2 border border-gray-400 rounded p-2 w-44" id="newScore" value="${saved[key]??d.recheck}" oninput="checkedPK=false"></label>`;msg('Đã tải hồ sơ phúc khảo hợp lệ '+key+'.');}
function kiemTraPK(){if(!loadedPK)return msg('Vui lòng chọn hồ sơ phúc khảo trước.',true);const d=dossiers[loadedPK],i=$('newScore');if(!i||!i.value.trim()||!Number.isFinite(+i.value)||+i.value<0||+i.value>10)return msg('Điểm phúc khảo không hợp lệ. Vui lòng nhập từ 0 đến 10.',true);if(+i.value!==d.recheck)return msg('Điểm nhập không khớp với kết quả chấm phúc khảo chính thức ('+d.recheck+').',true);checkedPK=true;msg('Đã đối chiếu hợp lệ: điểm ban đầu '+d.old+', điểm phúc khảo '+i.value+'. Vui lòng xác nhận lưu.');}
function luuPK(){if(!loadedPK||!checkedPK)return msg('Vui lòng kiểm tra và đối chiếu điểm phúc khảo trước.',true);if(!confirm('Xác nhận cập nhật điểm phúc khảo theo kết quả chấm?'))return;saved[loadedPK]=+$('newScore').value;const d=dossiers[loadedPK];$('nhatKy').textContent='Hồ sơ '+loadedPK+' • Điểm gốc '+d.old+' → Điểm cập nhật '+saved[loadedPK]+' • Người thực hiện: Sở GD&ĐT • '+new Date().toLocaleString('vi-VN');xemHoSo();msg('Nhập điểm phúc khảo thành công. Hồ sơ chuyển trạng thái “Đã cập nhật kết quả” (mô phỏng).');}
function huyPK(){if(loadedPK)xemHoSo();checkedPK=false;msg('Đã hủy thao tác chưa xác nhận; điểm thi ban đầu không bị thay đổi.');}
</script></body></html> 