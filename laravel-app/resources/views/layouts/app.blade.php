<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CAI LOMBOK 2026') - Precision Event System</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Reverb / Laravel Echo -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:     #0052cc;
            --primary-dk:  #003d99;
            --primary-lt:  #e6f0ff;
            --success:     #00875a;
            --success-lt:  #e3fcef;
            --danger:      #de350b;
            --danger-lt:   #ffebe6;
            --warning:     #ff8b00;
            --warning-lt:  #fffae6;
            --neutral-900: #091e42;
            --neutral-700: #253858;
            --neutral-500: #5e6c84;
            --neutral-200: #dfe1e6;
            --neutral-100: #f4f5f7;
            --neutral-50:  #fafbfc;
            --white:       #ffffff;
            --radius:      10px;
            --shadow:      0 2px 8px rgba(9,30,66,.12);
            --shadow-lg:   0 8px 32px rgba(9,30,66,.16);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--neutral-100);
            color: var(--neutral-900);
            min-height: 100vh;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--neutral-100); }
        ::-webkit-scrollbar-thumb { background: var(--neutral-200); border-radius: 3px; }

        /* Nav */
        .navbar {
            background: var(--white);
            border-bottom: 1px solid var(--neutral-200);
            padding: 0 1.5rem;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow);
        }
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: .75rem;
            font-weight: 800;
            font-size: 1rem;
            color: var(--primary);
            text-decoration: none;
        }
        .navbar-brand .badge {
            background: var(--primary);
            color: white;
            font-size: .6rem;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
            letter-spacing: .05em;
        }
        .navbar-nav { display: flex; gap: .25rem; align-items: center; }
        .nav-link {
            padding: .4rem .85rem;
            border-radius: 6px;
            font-size: .875rem;
            font-weight: 500;
            color: var(--neutral-700);
            text-decoration: none;
            transition: all .15s;
        }
        .nav-link:hover, .nav-link.active { background: var(--primary-lt); color: var(--primary); }

        @media (max-width: 600px) {
            .navbar {
                padding: 0.5rem 0.75rem;
                height: auto;
                flex-direction: column;
                gap: 0.5rem;
                align-items: center;
            }
            .brand-text {
                display: none;
            }
            .navbar-nav {
                width: 100%;
                justify-content: space-around;
            }
            .nav-link {
                padding: 0.35rem 0.6rem;
                font-size: 0.8rem;
            }
        }

        /* Main content */
        .main-content { padding: 1.5rem; max-width: 1400px; margin: 0 auto; }

        /* Card */
        .card {
            background: var(--white);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        .card-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--neutral-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-body { padding: 1.25rem; }
        .card-title { font-size: .9rem; font-weight: 700; color: var(--neutral-700); }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: .7rem;
            font-weight: 600;
        }
        .badge-success  { background: var(--success-lt);  color: var(--success); }
        .badge-danger   { background: var(--danger-lt);   color: var(--danger); }
        .badge-primary  { background: var(--primary-lt);  color: var(--primary); }
        .badge-warning  { background: var(--warning-lt);  color: var(--warning); }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .5rem 1rem;
            border-radius: 6px;
            font-size: .85rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all .15s;
            text-decoration: none;
        }
        .btn-primary   { background: var(--primary);  color: white; }
        .btn-primary:hover   { background: var(--primary-dk); }
        .btn-success   { background: var(--success);  color: white; }
        .btn-danger    { background: var(--danger);   color: white; }
        .btn-outline   { background: transparent; border: 1.5px solid var(--neutral-200); color: var(--neutral-700); }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }
        .btn-sm { padding: .3rem .7rem; font-size: .78rem; }

        /* Alerts/Toast */
        .toast-container {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: .5rem;
        }
        .toast {
            background: var(--white);
            border: 1px solid var(--neutral-200);
            border-radius: var(--radius);
            padding: .85rem 1.1rem;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: .75rem;
            font-size: .875rem;
            min-width: 280px;
            animation: slideIn .3s ease;
        }
        .toast.success { border-left: 4px solid var(--success); }
        .toast.error   { border-left: 4px solid var(--danger); }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to   { transform: translateX(0);    opacity: 1; }
        }
    </style>

    @stack('styles')
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <a href="{{ route('dashboard') }}" class="navbar-brand">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            <span class="brand-text">Precision Event System</span>
            <span class="badge">CAI 2026</span>
        </a>
        <div class="navbar-nav">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                📊 Dashboard
            </a>
            <a href="{{ route('scanner') }}" class="nav-link {{ request()->routeIs('scanner') ? 'active' : '' }}">
                📷 Scanner
            </a>
            <a href="{{ route('admin.participants.index') }}" class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}">
                ⚙️ Admin
            </a>
        </div>
    </nav>

    <!-- Content -->
    @yield('content')

    <!-- Toast Container -->
    <div class="toast-container" id="toast-container"></div>

    <script>
        // Global toast helper
        function showToast(message, type = 'success', duration = 4000) {
            const container = document.getElementById('toast-container');
            const icon = type === 'success' ? '✅' : '❌';
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `<span>${icon}</span><span>${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.transition = 'all .3s';
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }
    </script>

    @stack('scripts')
</body>
</html>
