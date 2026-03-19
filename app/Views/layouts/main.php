<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'OTBI Dashboard') ?></title>
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
    <style>
        /* Dark mode transitions */
        html {
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        /* Theme toggle button styles */
        #themeToggle {
            transition: transform 0.2s ease;
        }
        
        #themeToggle:hover {
            transform: scale(1.1);
        }
        
        #themeToggle:active {
            transform: scale(0.95);
        }
        
        /* Chart container theme adjustments */
        [data-theme="dark"] .apexcharts-canvas {
            background: transparent !important;
        }
        
        [data-theme="dark"] .apexcharts-text {
            fill: hsl(var(--bc)) !important;
        }
        
        [data-theme="dark"] .apexcharts-gridline {
            stroke: hsl(var(--b3)) !important;
        }
        
        /* Loading overlay theme adjustments */
        [data-theme="dark"] .loading-overlay {
            background: rgba(0,0,0,0.7);
        }
        
        /* DataTables Custom Styling for DaisyUI */
        .dataTables_wrapper {
            font-family: inherit;
        }
        
        .dataTables_wrapper .dataTables_length {
            margin-bottom: 1rem;
        }
        
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 1rem;
        }
        
        .dataTables_wrapper .dataTables_info {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid hsl(var(--b2));
        }
        
        .dataTables_wrapper .dataTables_paginate {
            margin-top: 1rem;
        }
        
        .dataTables_wrapper select,
        .dataTables_wrapper input {
            border: 1px solid hsl(var(--b2));
            border-radius: 0.5rem;
            padding: 0.25rem 0.5rem;
            background: hsl(var(--b1));
            color: hsl(var(--bc));
        }
        
        .dataTables_wrapper .dt-buttons {
            margin-bottom: 1rem;
        }
        
        .dataTables_wrapper .dt-buttons button {
            margin-right: 0.5rem;
        }
        
        [data-theme="dark"] .dataTables_wrapper select,
        [data-theme="dark"] .dataTables_wrapper input {
            background: hsl(var(--b2));
            color: hsl(var(--bc));
        }
        
        .dataTables_wrapper .table {
            border-collapse: collapse;
        }
        
        .dataTables_wrapper .table th,
        .dataTables_wrapper .table td {
            padding: 0.4rem 0.6rem !important;
            vertical-align: middle !important;
            font-size: 0.7rem !important;
            white-space: nowrap !important;
        }
        
        .dataTables_wrapper .table thead th {
            background: hsl(var(--b2)) !important;
            border-bottom: 2px solid hsl(var(--bc)) !important;
            font-weight: 600 !important;
            font-size: 0.7rem !important;
            padding: 0.4rem 0.6rem !important;
            white-space: nowrap !important;
        }
        
        [data-theme="dark"] .dataTables_wrapper .table thead th {
            background: hsl(var(--b3));
        }
        
        .dataTables_wrapper .table tbody tr:hover {
            background: hsl(var(--b2));
        }
        
        .dataTables_wrapper .sorting_asc,
        .dataTables_wrapper .sorting_desc {
            background: hsl(var(--b2)) !important;
        }
        
        [data-theme="dark"] .dataTables_wrapper .sorting_asc,
        [data-theme="dark"] .dataTables_wrapper .sorting_desc {
            background: hsl(var(--b3)) !important;
        }
    </style>
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    
    <!-- Override DataTables styles -->
    <style>
        .dataTables_wrapper .table th,
        .dataTables_wrapper .table td {
            padding: 0.4rem 0.6rem !important;
            vertical-align: middle !important;
            font-size: 0.7rem !important;
            white-space: nowrap !important;
        }
        
        .dataTables_wrapper .table thead th {
            background: hsl(var(--b2)) !important;
            border-bottom: 2px solid hsl(var(--bc)) !important;
            font-weight: 600 !important;
            font-size: 0.7rem !important;
            padding: 0.4rem 0.6rem !important;
            white-space: nowrap !important;
        }
        
        [data-theme="dark"] .dataTables_wrapper .table thead th {
            background: hsl(var(--b3)) !important;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    
    <?= $this->renderSection('styles') ?>
</head>
<body class="bg-base-200 min-h-screen">

    <!-- Navbar -->
    <div class="navbar bg-primary text-primary-content shadow-lg px-6">
        <div class="navbar-start">
            <div class="dropdown">
                <div tabindex="0" role="button" class="btn btn-ghost lg:hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
                    </svg>
                </div>
                <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52">
                    <li><a href="<?= base_url('/') ?>">Dashboard</a></li>
                    <li><a href="<?= base_url('po') ?>">Laporan PO</a></li>
                </ul>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="text-xl font-bold">OTBI Dashboard</span>
        </div>
        <div class="navbar-center hidden lg:flex">
            <ul class="menu menu-horizontal px-1">
                <li><a href="<?= base_url('/') ?>">Dashboard</a></li>
                <li><a href="<?= base_url('po') ?>">Laporan PO</a></li>
            </ul>
        </div>
        <div class="navbar-end flex items-center gap-4">
            <!-- Dark Mode Toggle -->
            <button id="themeToggle" class="btn btn-ghost btn-circle">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" id="sun-icon">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <svg class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" id="moon-icon">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
            </button>
            <span class="text-sm opacity-75 hidden lg:block" id="lastUpdate"><?= $this->renderSection('header_info') ?></span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto p-4 max-w-screen-2xl">
        <?= $this->renderSection('content') ?>
    </div>

    <!-- Footer -->
    <footer class="footer footer-center p-6 bg-base-300 text-base-content mt-8">
        <p class="text-sm">OTBI Dashboard &copy; <?= date('Y') ?> &mdash; Data diambil dari Oracle OTBI via SOAP</p>
    </footer>

    <?= $this->renderSection('scripts') ?>
    
    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    
    <!-- Theme Toggle Script -->
    <script>
        // Theme management
        const themeToggle = document.getElementById('themeToggle');
        const sunIcon = document.getElementById('sun-icon');
        const moonIcon = document.getElementById('moon-icon');
        const html = document.documentElement;

        // Initialize theme
        function initTheme() {
            const savedTheme = localStorage.getItem('theme');
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (savedTheme === 'dark' || (!savedTheme && systemPrefersDark)) {
                setDarkMode();
            } else {
                setLightMode();
            }
        }

        function setDarkMode() {
            html.setAttribute('data-theme', 'dark');
            sunIcon.classList.add('hidden');
            moonIcon.classList.remove('hidden');
            localStorage.setItem('theme', 'dark');
        }

        function setLightMode() {
            html.setAttribute('data-theme', 'light');
            sunIcon.classList.remove('hidden');
            moonIcon.classList.add('hidden');
            localStorage.setItem('theme', 'light');
        }

        function toggleTheme() {
            if (html.getAttribute('data-theme') === 'dark') {
                setLightMode();
            } else {
                setDarkMode();
            }
            
            // Re-render charts if they exist
            if (typeof window.updateCharts === 'function' && typeof window.chartsData !== 'undefined') {
                setTimeout(() => {
                    window.updateCharts(window.chartsData);
                }, 100);
            }
        }

        // Event listener
        if (themeToggle) {
            themeToggle.addEventListener('click', toggleTheme);
        }

        // Initialize on load
        initTheme();

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('theme')) {
                if (e.matches) {
                    setDarkMode();
                } else {
                    setLightMode();
                }
            }
        });
    </script>
</body>
</html>
