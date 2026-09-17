<?php
    function renderSidebar(){
        echo '
            <aside class="fixed left-0 top-20 z-[900] h-[calc(100vh-5rem)] w-72 border-r border-slate-200/70 bg-white/90 px-4 py-6 shadow-lg backdrop-blur-xl">
                <nav class="space-y-1">

                    <!-- Dashboard -->
                    <a
                        href="#"
                        class="group flex items-center gap-3 rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-white shadow-md shadow-primary/20 transition-all duration-200"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 12l9-9 9 9"/>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M5 10v10h14V10"/>
                        </svg>

                        <span class="text-black">Tổng quan</span>
                    </a>


                    <!-- Hồ sơ thí sinh -->
                    <a
                        href="#"
                        class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 transition-all duration-200 hover:bg-sky-50 hover:text-primary"
                    >
                        <svg
                            class="h-5 w-5 text-slate-400 transition-colors group-hover:text-primary"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19 8v6M22 11h-6"/>
                        </svg>

                        <span>Quản lý hồ sơ thí sinh</span>
                    </a>


                    <!-- Danh sách nguyện vọng -->
                    <a
                        href="#"
                        class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 transition-all duration-200 hover:bg-sky-50 hover:text-primary"
                    >
                        <svg
                            class="h-5 w-5 text-slate-400 transition-colors group-hover:text-primary"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8 6h13M8 12h13M8 18h13"/>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 6h.01M3 12h.01M3 18h.01"/>
                        </svg>

                        <span>Quản lý nguyện vọng</span>
                    </a>


                    <!-- Trường học -->
                    <a
                        href="#"
                        class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 transition-all duration-200 hover:bg-sky-50 hover:text-primary"
                    >
                        <svg
                            class="h-5 w-5 text-slate-400 transition-colors group-hover:text-primary"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 21h18"/>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M5 21V9l7-4 7 4v12"/>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 21v-6h6v6"/>
                        </svg>

                        <span>Quản lý trường học</span>
                    </a>


                    <!-- Hội đồng thi -->
                    <a
                        href="#"
                        class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 transition-all duration-200 hover:bg-sky-50 hover:text-primary"
                    >
                        <svg
                            class="h-5 w-5 text-slate-400 transition-colors group-hover:text-primary"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 6h16M4 10h16M4 14h10"/>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 18h6"/>
                        </svg>

                        <span>Quản lý hội đồng thi</span>
                    </a>


                    <!-- Điểm thi -->
                    <a
                        href="#"
                        class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 transition-all duration-200 hover:bg-sky-50 hover:text-primary"
                    >
                        <svg
                            class="h-5 w-5 text-slate-400 transition-colors group-hover:text-primary"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 19V5"/>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 19h16"/>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8 16v-4M12 16V8M16 16v-6"/>
                        </svg>

                        <span>Quản lý kết quả thi</span>
                    </a>


                    <!-- Tra cứu -->
                    <a
                        href="#"
                        class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 transition-all duration-200 hover:bg-sky-50 hover:text-primary"
                    >
                        <svg
                            class="h-5 w-5 text-slate-400 transition-colors group-hover:text-primary"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <circle cx="11" cy="11" r="7"/>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M20 20l-4-4"/>
                        </svg>

                        <span>Tra cứu dữ liệu</span>
                    </a>

                </nav>


                <!-- Divider -->
                <div class="my-6 border-t border-slate-200"></div>


                <!-- System -->
                <p class="mb-2 px-3 text-xs font-bold uppercase tracking-widest text-slate-400">
                    Hệ thống
                </p>

                <nav class="space-y-1">

                    <!-- Thông báo -->
                    <a
                        href="#"
                        class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 transition-all duration-200 hover:bg-sky-50 hover:text-primary"
                    >
                        <svg
                            class="h-5 w-5 text-slate-400 transition-colors group-hover:text-primary"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M18 8a6 6 0 00-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M10 21h4"/>
                        </svg>

                        <span>Thông báo</span>

                        <span class="ml-auto rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-bold text-primary">
                            3
                        </span>
                    </a>


                    <!-- Cài đặt -->
                    <a
                        href="#"
                        class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 transition-all duration-200 hover:bg-sky-50 hover:text-primary"
                    >
                        <svg
                            class="h-5 w-5 text-slate-400 transition-colors group-hover:text-primary"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <circle cx="12" cy="12" r="3"/>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.4 15a1.7 1.7 0 00.3 1.9l.1.1-1.8 1.8-.1-.1a1.7 1.7 0 00-1.9-.3 1.7 1.7 0 00-1 1.5V20h-2.6v-.1a1.7 1.7 0 00-1-1.5 1.7 1.7 0 00-1.9.3l-.1.1-1.8-1.8.1-.1a1.7 1.7 0 00.3-1.9 1.7 1.7 0 00-1.5-1H4v-2.6h.1a1.7 1.7 0 001.5-1 1.7 1.7 0 00-.3-1.9l-.1-.1L7 6.6l.1.1a1.7 1.7 0 001.9.3 1.7 1.7 0 001-1.5V5h2.6v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.9-.3l.1-.1 1.8 1.8-.1.1a1.7 1.7 0 00-.3 1.9 1.7 1.7 0 001.5 1h.1v2.6h-.1a1.7 1.7 0 00-1.5 1z"/>
                        </svg>

                        <span>Cài đặt hệ thống</span>
                    </a>

                </nav>


                <!-- User Card -->
                <div class="absolute bottom-5 left-4 right-4 rounded-2xl border border-sky-100 bg-gradient-to-br from-sky-50 to-white p-3 shadow-sm">

                    <div class="flex items-center gap-3">

                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-white shadow-md shadow-primary/20">
                            AD
                        </div>

                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-slate-700">
                                Quản trị viên
                            </p>

                            <p class="truncate text-xs text-slate-400">
                                Sở GD&ĐT
                            </p>
                        </div>

                        <button
                            class="ml-auto rounded-lg p-2 text-slate-400 transition-colors hover:bg-white hover:text-primary"
                            title="Đăng xuất"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10 17l5-5-5-5"/>
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12H3"/>
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 19V5a2 2 0 00-2-2h-6"/>
                            </svg>
                        </button>

                    </div>

                </div>

            </aside>

        ';

    }
?>