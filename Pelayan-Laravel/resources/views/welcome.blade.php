<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cafe Ahlisurga — Portal Login</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-page: #ffffff;
            --bg-card: #f9fafb;
            --bg-btn-sec: #ffffff;
            --border-color: #e5e7eb;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --accent: #ea580c;
            --accent-hover: #f97316;
            --accent-soft: rgba(234, 88, 12, 0.12);
        }

        html.dark {
            --bg-page: #09090b;
            --bg-card: #18181b;
            --bg-btn-sec: #18181b;
            --border-color: #27272a;
            --text-primary: #f4f4f5;
            --text-secondary: #a1a1aa;
            --accent: #f97316;
            --accent-hover: #fb923c;
            --accent-soft: rgba(249, 115, 22, 0.15);
        }

        body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-page);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .split-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
            width: 100%;
        }

        .left-pane {
            position: relative;
            overflow: hidden;
            background: #1c1917;
        }

        .left-pane img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .left-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(9, 9, 11, 0.88) 0%, rgba(9, 9, 11, 0.25) 55%, rgba(9, 9, 11, 0.15) 100%);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 48px;
            color: #ffffff;
        }

        .brand-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            align-self: flex-start;
            padding: 6px 14px;
            border-radius: 9999px;
            background: rgba(249, 115, 22, 0.22);
            border: 1px solid rgba(251, 146, 60, 0.45);
            color: #fed7aa;
            font-size: 12.5px;
            font-weight: 600;
            margin-bottom: 14px;
            backdrop-filter: blur(6px);
        }

        .left-overlay h2 {
            font-size: 32px;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }

        .left-overlay p {
            margin-top: 10px;
            font-size: 15px;
            line-height: 1.6;
            color: #d4d4d8;
            max-width: 440px;
        }

        .right-pane {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px 32px;
            background-color: var(--bg-page);
        }

        .theme-toggle {
            position: absolute;
            top: 24px;
            right: 24px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 9999px;
            border: 1px solid var(--border-color);
            background: var(--bg-card);
            color: var(--text-secondary);
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            transition: border-color 0.15s ease, color 0.15s ease;
        }

        .theme-toggle:hover {
            border-color: var(--accent);
            color: var(--text-primary);
        }

        .login-box {
            width: 100%;
            max-width: 400px;
        }

        .logo-badge {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: var(--accent-soft);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .login-box h1 {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.25;
        }

        .login-box .subtitle {
            margin-top: 8px;
            font-size: 14.5px;
            line-height: 1.5;
            color: var(--text-secondary);
        }

        .role-actions {
            margin-top: 28px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .role-btn {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 18px;
            border-radius: 14px;
            text-decoration: none;
            transition: transform 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease, border-color 0.15s ease;
        }

        .role-btn:hover {
            transform: translateY(-2px);
        }

        .role-btn--primary {
            background: var(--accent);
            color: #ffffff;
            border: 1px solid transparent;
            box-shadow: 0 8px 20px rgba(234, 88, 12, 0.25);
        }

        .role-btn--primary:hover {
            background: var(--accent-hover);
            box-shadow: 0 10px 24px rgba(234, 88, 12, 0.35);
        }

        .role-btn--secondary {
            background: var(--bg-btn-sec);
            color: var(--text-primary);
            border: 1.5px solid var(--border-color);
        }

        .role-btn--secondary:hover {
            border-color: var(--accent);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        }

        .role-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .role-btn--primary .role-icon {
            background: rgba(255, 255, 255, 0.18);
            color: #ffffff;
        }

        .role-btn--secondary .role-icon {
            background: var(--accent-soft);
            color: var(--accent);
        }

        .role-text {
            flex: 1;
        }

        .role-title {
            display: block;
            font-size: 15.5px;
            font-weight: 700;
        }

        .role-desc {
            display: block;
            font-size: 12.5px;
            margin-top: 2px;
            opacity: 0.85;
        }

        .role-btn--secondary .role-desc {
            color: var(--text-secondary);
            opacity: 1;
        }

        .arrow {
            flex-shrink: 0;
            opacity: 0.85;
        }

        .footer-note {
            margin-top: 32px;
            font-size: 12px;
            color: var(--text-secondary);
            text-align: center;
        }

        @media (max-width: 900px) {
            .split-layout {
                grid-template-columns: 1fr;
                grid-template-rows: 260px 1fr;
            }
            .left-overlay {
                padding: 24px;
            }
            .left-overlay h2 {
                font-size: 22px;
            }
            .left-overlay p {
                font-size: 13px;
            }
            .right-pane {
                padding: 32px 20px;
            }
        }
    </style>
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body>
    <main class="split-layout">
        <section class="left-pane">
            <img src="{{ asset('images/cafe-hero.jpg') }}" alt="Suasana Cafe Ahlisurga">
            <div class="left-overlay">
                <span class="brand-pill">Sistem Pemesanan QR Code</span>
                <h2>Cafe Ahlisurga</h2>
                <p>Kelola pesanan meja secara real-time, pantau stok menu, dan awasi seluruh transaksi dengan lebih cepat dan terstruktur.</p>
            </div>
        </section>

        <section class="right-pane">
            <button type="button" class="theme-toggle" id="themeToggle" aria-label="Ganti Tema">
                <span id="themeIcon">🌙</span>
                <span id="themeLabel">Mode Gelap</span>
            </button>

            <div class="login-box">
                <div class="logo-badge">
                    <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 8h1a4 4 0 0 1 0 8h-1M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8Zm4-5v2m4-2v2m4-2v2"/>
                    </svg>
                </div>

                <h1>Selamat Datang</h1>
                <p class="subtitle">Pilih peran Anda untuk masuk ke dashboard operasional Cafe Ahlisurga.</p>

                <div class="role-actions">
                    <a href="{{ url('/admin/login') }}" class="role-btn role-btn--primary">
                        <span class="role-icon">
                            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9 2 2 4-4"/>
                            </svg>
                        </span>
                        <span class="role-text">
                            <span class="role-title">Login sebagai Pelayan</span>
                            <span class="role-desc">Kelola pesanan masuk, menu, stok &amp; meja</span>
                        </span>
                        <svg class="arrow" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    <a href="{{ url('/pemilik/login') }}" class="role-btn role-btn--secondary">
                        <span class="role-icon">
                            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                            </svg>
                        </span>
                        <span class="role-text">
                            <span class="role-title">Login sebagai Pemilik</span>
                            <span class="role-desc">Pantau omset, riwayat transaksi &amp; stok</span>
                        </span>
                        <svg class="arrow" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>

                <p class="footer-note">
                    Tim Ahlisurga &bull; Proyek PBO &bull; Universitas Tidar
                </p>
            </div>
        </section>
    </main>

    <script>
        (function () {
            const btn = document.getElementById('themeToggle');
            const icon = document.getElementById('themeIcon');
            const label = document.getElementById('themeLabel');

            function syncButton() {
                const isDark = document.documentElement.classList.contains('dark');
                icon.textContent = isDark ? '☀️' : '🌙';
                label.textContent = isDark ? 'Mode Terang' : 'Mode Gelap';
            }

            syncButton();

            btn.addEventListener('click', function () {
                const isDark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
                syncButton();
            });
        })();
    </script>
</body>
</html>
