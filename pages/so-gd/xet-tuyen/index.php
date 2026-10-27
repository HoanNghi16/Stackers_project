<?php
// Giao diện minh họa theo đặc tả use case. Dữ liệu mẫu tại trình duyệt, chưa ghi vào CSDL.
$pageTitle = "Stackers - Xét tuyển";
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
        <a href="../xet-tuyen/index.php" class="block px-3 py-2 rounded border border-gray-400 bg-gray-100 font-medium">Xét tuyển</a>
        <span class="block px-3 py-2 rounded border border-transparent text-gray-700 hover:border-gray-300 cursor-pointer">Công bố điểm chuẩn</span>

        <div class="px-3 py-2 text-gray-500 mt-3">DANH SÁCH &amp; BÁO CÁO</div>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Xem danh sách thí sinh</a>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Thống kê, báo cáo</a>

        <div class="px-3 py-2 text-gray-500 mt-3">HỆ THỐNG</div>
        <a href="#" class="block px-3 py-2 rounded border border-transparent hover:border-gray-300">Quản lý tài khoản</a>
      </nav>
    </aside>
<main class="flex-1 min-w-0 p-6"><div class="text-sm text-gray-500 mb-2">Sở GD&amp;ĐT &nbsp;/&nbsp; Điểm số &amp; Tuyển sinh &nbsp;/&nbsp; <span class="text-gray-800">Xét tuyển</span></div><div class="flex flex-wrap justify-between items-start gap-3 mb-6"><div><h1 class="text-xl font-semibold">Xét tuyển</h1><p class="text-sm text-gray-500 mt-1">Kiểm tra hồ sơ, chạy xét tuyển theo nguyện vọng và xác nhận kết quả.</p></div><span class="text-xs border border-gray-400 px-2 py-1 rounded">Kỳ tuyển sinh: 2026 - 2027</span></div>
<div id="message" class="hidden mb-4 border border-gray-500 bg-white p-4 text-sm rounded" role="status"></div><section class="bg-white border border-gray-300 rounded mb-5"><div class="px-5 py-3 border-b border-gray-300 font-semibold">Kỳ tuyển sinh 2026 - 2027</div><div class="p-5"><div class="max-w-md"><label class="block text-sm"><span class="block text-gray-600 mb-1">Phương thức / đợt xét tuyển</span><select id="dot" class="w-full border border-gray-400 rounded p-2 bg-white"><option value="1">Đợt 1 - Điểm thi</option><option value="2">Đợt 2 - Bổ sung</option></select></label></div><div class="mt-4"><button type="button" onclick="taiHoSo()" class="border border-gray-500 px-4 py-2 rounded text-sm bg-gray-800 text-white">Tải hồ sơ và tiêu chí</button></div></div><div class="px-5 py-3 border-t border-gray-200 text-xs text-gray-500">Chỉ thực hiện xét tuyển khi hồ sơ, công thức điểm và chỉ tiêu đã đầy đủ.</div></section><section class="bg-white border border-gray-300 rounded mb-5"><div class="px-5 py-3 border-b border-gray-300 font-semibold">Hồ sơ đủ điều kiện xét tuyển</div><div class="p-5"><p class="text-sm text-gray-500 mb-3">Danh sách hồ sơ dự kiến theo dữ liệu mẫu.</p><div id="hosos"></div></div></section><section class="bg-white border border-gray-300 rounded mb-5"><div class="px-5 py-3 border-b border-gray-300 font-semibold">Thực hiện và kiểm tra kết quả</div><div class="p-5"><div class="flex flex-wrap gap-2 mb-4"><button type="button" onclick="xetTuyen()" class="border border-gray-500 px-4 py-2 rounded text-sm bg-gray-800 text-white">Thực hiện xét tuyển</button><button type="button" onclick="luuTam()" class="border border-gray-500 px-4 py-2 rounded text-sm bg-white">Lưu tạm</button><button type="button" onclick="xacNhan()" class="border border-gray-500 px-4 py-2 rounded text-sm bg-gray-800 text-white">Xác nhận kết quả</button></div><div id="ketqua" class="text-sm text-gray-500">Chưa thực hiện xét tuyển.</div></div></section>
<p class="mt-5 text-xs text-gray-500">Lưu ý: Đây là wireframe tương tác sử dụng dữ liệu giả lập, không phải kết quả tuyển sinh/điểm thi chính thức. Dữ liệu chỉ được lưu trong phiên trang hiện tại.</p></main></div>
<script>
const $=id=>document.getElementById(id);
function msg(message,isError=false){const e=$('message');e.textContent=message;e.classList.remove('hidden');e.style.borderColor=isError?'#991b1b':'#6b7280';e.scrollIntoView({behavior:'smooth',block:'nearest'});}
function val(id){return $(id).value;}
function clean(s){return String(s??'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#39;');}
const hoSo=[{ma:'HS001',ten:'Nguyễn Minh Anh',diem:26.5,nv:['THPT Nguyễn Du','THPT Trưng Vương']},{ma:'HS002',ten:'Trần Gia Bảo',diem:24.0,nv:['THPT Nguyễn Du','THPT Trưng Vương']},{ma:'HS003',ten:'Lê Hoàng Nam',diem:22.5,nv:['THPT Trưng Vương','THPT Nguyễn Du']},{ma:'HS004',ten:'Phạm Thảo Vy',diem:20.0,nv:['THPT Nguyễn Du','THPT Trưng Vương']}];let loaded=false,processed=false,confirmed=false;
const quotas={'THPT Nguyễn Du':1,'THPT Trưng Vương':2};let results=[];
function taiHoSo(){loaded=true;processed=false;confirmed=false;results=[];$('ketqua').textContent='Chưa thực hiện xét tuyển.';$('hosos').innerHTML='<div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="bg-gray-100"><th class="p-2">Mã hồ sơ</th><th>Họ tên</th><th>Điểm xét tuyển</th><th>Nguyện vọng (ưu tiên)</th></tr></thead><tbody>'+hoSo.map(h=>`<tr class="border-t"><td class="p-2">${h.ma}</td><td>${h.ten}</td><td>${h.diem}</td><td>${h.nv.join(' → ')}</td></tr>`).join('')+'</tbody></table></div>';msg('Đã tải '+hoSo.length+' hồ sơ hợp lệ, công thức tính điểm và chỉ tiêu mẫu.');}
function xetTuyen(){if(!loaded)return msg('Vui lòng tải hồ sơ và tiêu chí trước khi xét tuyển.',true);let used={};results=hoSo.slice().sort((a,b)=>b.diem-a.diem).map(h=>{let truong=h.nv.find(t=>(used[t]||0)<quotas[t]);if(truong)used[truong]=(used[truong]||0)+1;return {...h,truong:truong||'Không trúng tuyển',trangThai:truong?'Trúng tuyển':'Không trúng tuyển'};});processed=true;confirmed=false;draw();msg('Đã xét tuyển và sắp xếp theo điểm, lần lượt xét từng nguyện vọng theo chỉ tiêu mẫu.');}
function draw(){ $('ketqua').innerHTML='<div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="bg-gray-100"><th class="p-2">Mã HS</th><th>Thí sinh</th><th>Điểm</th><th>Kết quả / Trường</th><th>Trạng thái</th></tr></thead><tbody>'+results.map(h=>`<tr class="border-t"><td class="p-2">${h.ma}</td><td>${h.ten}</td><td>${h.diem}</td><td>${h.truong}</td><td>${h.trangThai}</td></tr>`).join('')+'</tbody></table></div><p class="mt-3 text-gray-500">Trạng thái kết quả: '+(confirmed?'Đã xác nhận':results[0]?.saved?'Chờ xác nhận':'Chưa lưu')+'</p>';}
function luuTam(){if(!processed)return msg('Cần thực hiện xét tuyển trước khi lưu tạm.',true);results.forEach(h=>h.saved=true);draw();msg('Đã lưu tạm kết quả ở trạng thái “Chờ xác nhận” (trong trang mẫu).');}
function xacNhan(){if(!processed)return msg('Chưa có kết quả để xác nhận.',true);if(!confirm('Xác nhận kết quả xét tuyển chính thức trên giao diện mẫu?'))return;confirmed=true;draw();msg('Đã xác nhận kết quả và cập nhật trạng thái kỳ xét tuyển (mô phỏng).');}
</script></body></html>