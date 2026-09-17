<?php
    function renderHeader(){
        echo '
                <nav class="fixed top-0 z-[1000] h-20 w-full bg-white/80 shadow-md backdrop-blur-xl">
                    <div class="mx-auto flex h-full max-w-7xl items-center justify-between px-8 font-headline antialiased">

                        <!-- Logo -->
                        <a href="/stackers_project/pages/" class="flex items-center gap-3 no-underline">
                            <img
                                src="/stackers_project/assets/image/stackers_logo.png"
                                alt="Logo"
                                class="h-12 w-12 object-contain"
                            >

                            <div class="flex flex-col">
                                <span class="text-[10px] font-bold uppercase tracking-widest leading-tight text-slate-500">
                                    Stackers - Hội anh em ngăn xếp
                                </span>

                                <span class="text-lg font-extrabold tracking-tight leading-tight text-primary md:text-xl">
                                    TUYỂN SINH LỚP 10
                                </span>
                            </div>
                        </a>

                        <!-- Navigation -->
                        <div class="hidden items-center space-x-8 text-sm font-semibold md:flex">

                            <a
                                href="#calendar"
                                class="text-slate-600 no-underline transition-colors duration-200 hover:text-primary"
                            >
                                Lịch
                            </a>

                            <a
                                href="#targets"
                                class="text-slate-600 no-underline transition-colors duration-200 hover:text-primary"
                            >
                                Chỉ tiêu
                            </a>

                            <a
                                href="#schools"
                                class="text-slate-600 no-underline transition-colors duration-200 hover:text-primary"
                            >
                                Trường học
                            </a>

                            <a
                                href="#guides"
                                class="text-slate-600 no-underline transition-colors duration-200 hover:text-primary"
                            >
                                Hướng dẫn
                            </a>

                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-4">
                            <a
                                href="/stackers_project/pages/login"
                                class="hidden items-center justify-center rounded-full bg-amber-700 px-4 py-1.5 text-center text-sm font-semibold leading-[1.6] text-white no-underline transition-all hover:bg-amber-800 md:inline-flex"
                            >
                                Đăng nhập
                            </a>

                        </div>

                    </div>
                </nav>
                ';
    }
?>