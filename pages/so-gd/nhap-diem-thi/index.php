<?php
// Giao diện minh họa theo đặc tả use case. Dữ liệu mẫu tại trình duyệt, chưa ghi vào CSDL.
$pageTitle = "Stackers - Nhập điểm thi";
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
        <span class="block px-3 py-2 rounded border border-gray-400 bg-gray-100 font-medium cursor-pointer">Nhập điểm thi</span>
        <span class="block px-3 py-2 rounded border border-transparent text-gray-700 hover:border-gray-300 cursor-pointer">Nhập điểm phúc khảo</span>

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
<main class="flex-1 min-w-0 p-6"><div class="text-sm text-gray-500 mb-2">Sở GD&amp;ĐT &nbsp;/&nbsp; Điểm số &amp; Tuyển sinh &nbsp;/&nbsp; <span class="text-gray-800">Nhập điểm thi</span></div><div class="flex flex-wrap justify-between items-start gap-3 mb-6"><div><h1 class="text-xl font-semibold">Nhập điểm thi</h1><p class="text-sm text-gray-500 mt-1">Nhập điểm theo kỳ thi, môn thi và thí sinh; kiểm tra trước khi lưu.</p></div><span class="text-xs border border-gray-400 px-2 py-1 rounded">Kỳ tuyển sinh: 2026 - 2027</span></div>
<div id="message" class="hidden mb-4 border border-gray-500 bg-white p-4 text-sm rounded" role="status"></div><section class="bg-white border border-gray-300 rounded mb-5"><div class="px-5 py-3 border-b border-gray-300 font-semibold">Kỳ tuyển sinh 2026 - 2027</div><div class="p-5"><div class="grid md:grid-cols-2 gap-4"><input type="hidden" id="ky" value="2026"><label class="block text-sm"><span class="block text-gray-600 mb-1">Môn thi</span><select id="mon" class="w-full border border-gray-400 rounded p-2 bg-white"><option value="Toán">Toán</option><option value="Ngữ văn">Ngữ văn</option><option value="Tiếng Anh">Tiếng Anh</option></select></label><label class="block text-sm"><span class="block text-gray-600 mb-1">Tìm mã thí sinh</span><input id="search" type="text" value="" placeholder="Nhập mã hoặc họ tên" class="w-full border border-gray-400 rounded p-2"></label></div><div class="mt-4"><button type="button" onclick="loadDanhSach()" class="border border-gray-500 px-4 py-2 rounded text-sm bg-gray-800 text-white">Hiển thị danh sách</button></div></div></section><section class="bg-white border border-gray-300 rounded mb-5"><div class="px-5 py-3 border-b border-gray-300 font-semibold">Nhập điểm thi</div><div class="p-5"><div id="danhsach" class="text-sm text-gray-500">Chọn kỳ thi và môn thi để tải danh sách thí sinh.</div></div></section><section class="bg-white border border-gray-300 rounded mb-5"><div class="px-5 py-3 border-b border-gray-300 font-semibold">Kiểm tra và lưu điểm</div><div class="p-5"><div class="flex flex-wrap gap-2"><button type="button" onclick="kiemTra()" class="border border-gray-500 px-4 py-2 rounded text-sm bg-gray-800 text-white">Kiểm tra điểm</button><button type="button" onclick="luuDiem()" class="border border-gray-500 px-4 py-2 rounded text-sm bg-gray-800 text-white">Xác nhận lưu điểm</button><button type="button" onclick="huyDiem()" class="border border-gray-500 px-4 py-2 rounded text-sm bg-white">Hủy thay đổi</button></div><p id="ghiNhan" class="text-sm text-gray-600 mt-4">Chưa có lần lưu trong phiên này.</p></div><div class="px-5 py-3 border-t border-gray-200 text-xs text-gray-500">Điểm nhập phải đúng định dạng và nằm trong thang 0–10. Có thể nhập cho một hoặc nhiều thí sinh.</div></section>
<p class="mt-5 text-xs text-gray-500">Lưu ý: Đây là wireframe tương tác sử dụng dữ liệu giả lập, không phải kết quả tuyển sinh/điểm thi chính thức. Dữ liệu chỉ được lưu trong phiên trang hiện tại.</p></main></div>
<script>
const $=id=>document.getElementById(id);
function msg(message,isError=false){const e=$('message');e.textContent=message;e.classList.remove('hidden');e.style.borderColor=isError?'#991b1b':'#6b7280';e.scrollIntoView({behavior:'smooth',block:'nearest'});}
function val(id){return $(id).value;}
function clean(s){return String(s??'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#39;');}
const thiSinh=[{ma:'TS001',ten:'Nguyễn Minh Anh'},{ma:'TS002',ten:'Trần Gia Bảo'},{ma:'TS003',ten:'Lê Hoàng Nam'},{ma:'TS004',ten:'Phạm Thảo Vy'}];let scores={};let loaded=false;let checked=false;
function loadDanhSach(){const key=val('ky')+'-'+val('mon');const search=val('search').trim().toLowerCase();const ds=thiSinh.filter(t=>(t.ma+' '+t.ten).toLowerCase().includes(search));loaded=true;checked=false;$('danhsach').innerHTML=ds.length?'<div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="bg-gray-100"><th class="p-2">Mã dự thi</th><th>Họ tên</th><th>Môn</th><th>Điểm (0–10)</th><th>Trạng thái</th></tr></thead><tbody>'+ds.map(t=>`<tr class="border-t"><td class="p-2">${t.ma}</td><td>${t.ten}</td><td>${val('mon')}</td><td><input class="score border rounded p-2 w-24" type="number" step="0.25" min="0" max="10" data-id="${t.ma}" value="${scores[key+'-'+t.ma]??''}" placeholder="0–10"></td><td>${scores[key+'-'+t.ma]!==undefined?'Đã nhập':'Chưa nhập'}</td></tr>`).join('')+'</tbody></table></div>':'Không có thí sinh hoặc bài thi phù hợp.';document.querySelectorAll('.score').forEach(i=>i.addEventListener('input',()=>checked=false));msg('Đã tải '+ds.length+' thí sinh theo môn '+val('mon')+'.');}
function kiemTra(){if(!loaded)return msg('Vui lòng chọn môn và tải danh sách trước.',true);const inputs=[...document.querySelectorAll('.score')];if(!inputs.length)return msg('Không có thí sinh phù hợp để nhập điểm.',true);const errors=inputs.filter(i=>i.value.trim()!==''&&(!Number.isFinite(+i.value)||+i.value<0||+i.value>10));if(errors.length)return msg('Điểm thi không hợp lệ tại: '+errors.map(i=>i.dataset.id).join(', ')+'. Chỉ nhập điểm từ 0 đến 10.',true);if(inputs.every(i=>i.value.trim()===''))return msg('Chưa nhập điểm cho thí sinh nào.',true);checked=true;msg('Điểm hợp lệ. Vui lòng kiểm tra lại và chọn “Xác nhận lưu điểm”.');}
function luuDiem(){if(!checked)return msg('Vui lòng kiểm tra điểm trước khi xác nhận lưu.',true);if(!confirm('Xác nhận lưu điểm thi đã nhập?'))return;let count=0;document.querySelectorAll('.score').forEach(i=>{if(i.value.trim()!==''){scores[val('ky')+'-'+val('mon')+'-'+i.dataset.id]=+i.value;count++;}});$('ghiNhan').textContent='Đã lưu '+count+' điểm • Người nhập: Sở GD&ĐT • '+new Date().toLocaleString('vi-VN');loadDanhSach();msg('Nhập điểm thi thành công (mô phỏng).');}
function huyDiem(){loadDanhSach();msg('Đã hủy các thay đổi chưa lưu. Dữ liệu điểm trước đó được giữ nguyên.');}
</script></body></html>