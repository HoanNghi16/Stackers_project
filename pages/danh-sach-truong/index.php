<div class="">
    <main class="bg-blue-600 pt-24 gap-2 mx-auto rounded-3xl w-full px-28 py-10">
            <h1 class="text-2xl font-bold text-white">Danh sách trường tuyển sinh lớp 10</h1>
            <p class="mt-1 text-sm text-gray-200">
                Tra cứu các trường tham gia tuyển sinh và thông tin tuyển sinh liên quan.
            </p>
            <!-- Bộ lọc -->
            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                    <div class="md:col-span-6">
                        <label for="schoolSearch" class="mb-1.5 block text-sm font-medium text-slate-700">
                            Tìm kiếm theo tên trường
                        </label>
                        <input id="schoolSearch" type="text" placeholder="VD: Nguyễn Thượng Hiền..."
                            oninput="filterSchools()"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    </div>

                    <div class="md:col-span-3">
                        <label for="areaFilter" class="mb-1.5 block text-sm font-medium text-slate-700">Phường</label>
                        <select id="areaFilter" onchange="filterSchools()"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                            <option value="">--Chọn phường--</option>
                            <option value="Tân Bình">Tân Bình</option>
                            <option value="Quận 1">Quận 1</option>
                            <option value="Quận 3">Quận 3</option>
                        </select>
                    </div>

                    <div class="md:col-span-3">
                        <label for="typeFilter" class="mb-1.5 block text-sm font-medium text-slate-700">Loại trường</label>
                        <select id="typeFilter" onchange="filterSchools()"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                            <option value="">--Chọn loại trường--</option>
                            <option value="thcs">THPT</option>
                            <option value="thcs">THCS</option>
                        </select>
                    </div>
                </div>
            </div>


    </main>
    <div class="px-28">
        <div id="noResult"
            class="mt-6 hidden rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Không tìm thấy trường phù hợp.
        </div>

            <!-- Danh sách trường -->
        <div id="schoolGrid" class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3"></div>
    </div>
            
</div>

<!-- Modal chi tiết -->
<div id="schoolModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4"
    onclick="if (event.target === this) closeModal('schoolModal')">
    <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">

        <div class="mb-4 flex items-start justify-between gap-4">
            <h2 id="schoolTitle" class="text-lg font-semibold text-slate-900">Chi tiết trường</h2>
            <button type="button" onclick="closeModal('schoolModal')" aria-label="Đóng"
                class="rounded-md p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                ✕
            </button>
        </div>

        <div id="schoolDetail"></div>

        <div class="mt-6 flex justify-end">
            <button type="button" onclick="closeModal('schoolModal')"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                Quay lại danh sách
            </button>
        </div>
    </div>
</div>

<script>
    const schools = [{
            id: 1,
            name: 'THPT Nguyễn Thượng Hiền',
            type: 'Công lập',
            area: 'Tân Bình',
            address: '649 Hoàng Văn Thụ, Tân Bình, TP.HCM',
            quota: 730,
            method: 'Thi tuyển',
            note: 'Tuyển sinh lớp 10 theo kế hoạch của Sở GD&ĐT.'
        },
        {
            id: 2,
            name: 'THPT Bùi Thị Xuân',
            type: 'Công lập',
            area: 'Quận 1',
            address: '73-75 Bùi Thị Xuân, Quận 1, TP.HCM',
            quota: 700,
            method: 'Thi tuyển',
            note: 'Thông tin minh họa cho giao diện.'
        },
        {
            id: 3,
            name: 'THPT Chuyên Lê Hồng Phong',
            type: 'Chuyên',
            area: 'Quận 3',
            address: '235 Nguyễn Văn Cừ, Quận 5, TP.HCM',
            quota: 500,
            method: 'Thi tuyển + môn chuyên',
            note: 'Thông tin minh họa cho giao diện.'
        }
    ];

    const badgeClass = {
        'Chuyên': 'bg-amber-100 text-amber-800',
        'Công lập': 'bg-blue-100 text-blue-800'
    };

    function openModal(id) {
        const el = document.getElementById(id);
        el.classList.remove('hidden');
        el.classList.add('flex');
    }

    function closeModal(id) {
        const el = document.getElementById(id);
        el.classList.add('hidden');
        el.classList.remove('flex');
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal('schoolModal');
    });

    function renderSchools(list) {
        const grid = document.getElementById('schoolGrid');
        if (!grid) return;

        grid.innerHTML = list.map(school => `
            <article class="flex flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <span class="w-fit rounded-full px-2.5 py-0.5 text-xs font-medium ${badgeClass[school.type] || 'bg-slate-100 text-slate-700'}">
                    ${school.type}
                </span>

                <h3 class="mt-3 text-base font-semibold text-slate-900">${school.name}</h3>

                <dl class="mt-2 space-y-1 text-sm text-slate-600">
                    <div><dt class="inline font-medium text-slate-700">Khu vực:</dt> <dd class="inline">${school.area}</dd></div>
                    <div><dt class="inline font-medium text-slate-700">Địa chỉ:</dt> <dd class="inline">${school.address}</dd></div>
                </dl>

                <div class="mt-auto pt-4">
                    <button type="button" onclick="viewSchool(${school.id})"
                        class="rounded-lg bg-blue-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        Xem chi tiết
                    </button>
                </div>
            </article>
        `).join('');

        document.getElementById('noResult').classList.toggle('hidden', list.length > 0);
    }

    function filterSchools() {
        const keyword = document.getElementById('schoolSearch').value.trim().toLowerCase();
        const area = document.getElementById('areaFilter').value;
        const type = document.getElementById('typeFilter').value;

        renderSchools(schools.filter(school =>
            school.name.toLowerCase().includes(keyword) &&
            (!area || school.area === area) &&
            (!type || school.type === type)
        ));
    }

    function viewSchool(id) {
        const title = document.getElementById('schoolTitle');
        const detail = document.getElementById('schoolDetail');

        if (scenario === 'detail_error') {
            title.textContent = 'Không thể tải thông tin';
            detail.innerHTML = `
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    Không thể tải thông tin chi tiết của trường.
                </div>`;
            openModal('schoolModal');
            return;
        }

        const school = schools.find(s => s.id === id);
        if (!school) return;

        const rows = [
            ['Loại trường', school.type],
            ['Khu vực', school.area],
            ['Địa chỉ', school.address],
            ['Chỉ tiêu', `${school.quota} học sinh`],
            ['Phương thức tuyển sinh', school.method],
            ['Thông tin liên quan', school.note]
        ];

        title.textContent = school.name;
        detail.innerHTML = `
            <table class="w-full text-sm">
                <tbody class="divide-y divide-slate-100">
                    ${rows.map(([label, value]) => `
                        <tr>
                            <th class="w-2/5 py-2.5 pr-4 text-left align-top font-medium text-slate-500">${label}</th>
                            <td class="py-2.5 text-slate-800">${value}</td>
                        </tr>`).join('')}
                </tbody>
            </table>`;

        openModal('schoolModal');
    }

    renderSchools(schools);
</script>
