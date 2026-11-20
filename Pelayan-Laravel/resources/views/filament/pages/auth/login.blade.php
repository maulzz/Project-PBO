@php
    $isPemilik = filament()->getId() === 'pemilik';
    $judulRole = $isPemilik ? 'Login Pemilik' : 'Login Pelayan';
    $subjudulRole = $isPemilik
        ? 'Masuk untuk memantau omset, riwayat transaksi, dan stok menu cafe.'
        : 'Masuk untuk mengelola pesanan masuk, menu, stok, dan meja.';
@endphp

<div>
    <style>
        .auth-split-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
            width: 100%;
            background: #ffffff;
            color: #111827;
        }
        html.dark .auth-split-layout {
            background: #09090b;
            color: #f4f4f5;
        }
        .auth-left-pane {
            position: relative;
            overflow: hidden;
            background: #1c1917;
        }
        .auth-left-pane img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .auth-left-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(9, 9, 11, 0.88) 0%, rgba(9, 9, 11, 0.25) 55%, rgba(9, 9, 11, 0.15) 100%);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 48px;
            color: #ffffff;
        }
        .auth-brand-pill {
            display: inline-flex;
            align-items: center;
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
        .auth-left-overlay h2 {
            margin: 0;
            font-size: 32px;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }
        .auth-left-overlay p {
            margin: 10px 0 0;
            font-size: 15px;
            line-height: 1.6;
            color: #d4d4d8;
            max-width: 440px;
        }
        .auth-right-pane {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 72px 32px 40px;
        }
        .auth-topbar {
            position: absolute;
            top: 24px;
            left: 24px;
            right: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .auth-back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
            border-radius: 9999px;
            font-size: 12.5px;
            font-weight: 600;
            color: #6b7280;
            text-decoration: none;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            transition: border-color 0.15s ease, color 0.15s ease;
        }
        .auth-back-link:hover {
            border-color: #ea580c;
            color: #111827;
        }
        html.dark .auth-back-link {
            background: #18181b;
            border-color: #27272a;
            color: #a1a1aa;
        }
        html.dark .auth-back-link:hover {
            border-color: #f97316;
            color: #f4f4f5;
        }
        .auth-form-box {
            width: 100%;
            max-width: 400px;
        }
        .auth-logo-badge {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: rgba(234, 88, 12, 0.12);
            color: #ea580c;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
        }
        html.dark .auth-logo-badge {
            background: rgba(249, 115, 22, 0.15);
            color: #f97316;
        }
        .auth-heading {
            margin: 0;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.25;
        }
        .auth-subtitle {
            margin: 8px 0 24px;
            font-size: 14px;
            line-height: 1.5;
            color: #6b7280;
        }
        html.dark .auth-subtitle {
            color: #a1a1aa;
        }
        .auth-switch-role {
            margin-top: 24px;
            text-align: center;
            font-size: 13px;
            color: #6b7280;
        }
        html.dark .auth-switch-role {
            color: #a1a1aa;
        }
        .auth-switch-role a {
            color: #ea580c;
            font-weight: 600;
            text-decoration: none;
        }
        html.dark .auth-switch-role a {
            color: #f97316;
        }
        .auth-switch-role a:hover {
            text-decoration: underline;
        }
        @media (max-width: 900px) {
            .auth-split-layout {
                grid-template-columns: 1fr;
                grid-template-rows: 220px 1fr;
            }
            .auth-left-overlay {
                padding: 24px;
            }
            .auth-left-overlay h2 {
                font-size: 22px;
            }
            .auth-left-overlay p {
                font-size: 13px;
            }
            .auth-right-pane {
                padding: 72px 20px 32px;
            }
        }
    </style>

    <main class="auth-split-layout">
        <section class="auth-left-pane">
            <img src="{{ asset('images/cafe-hero.jpg') }}" alt="Suasana Cafe Ahlisurga">
            <div class="auth-left-overlay">
                <span class="auth-brand-pill">Sistem Pemesanan QR Code</span>
                <h2>Cafe Ahlisurga</h2>
                <p>Kelola pesanan meja secara real-time, pantau stok menu, dan awasi seluruh transaksi dengan lebih cepat dan terstruktur.</p>
            </div>
        </section>

        <section class="auth-right-pane">
            <div class="auth-topbar">
                <a href="{{ url('/') }}" class="auth-back-link">
                    <span>&larr;</span>
                    <span>Kembali</span>
                </a>

                <button
                    type="button"
                    class="auth-back-link"
                    x-data="{
                        isDark: document.documentElement.classList.contains('dark'),
                        toggle() {
                            this.isDark = document.documentElement.classList.toggle('dark');
                            localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
                        }
                    }"
                    x-on:click="toggle()"
                >
                    <span x-text="isDark ? '☀️' : '🌙'"></span>
                    <span x-text="isDark ? 'Mode Terang' : 'Mode Gelap'"></span>
                </button>
            </div>

            <div class="auth-form-box">
                <div class="auth-logo-badge">
                    <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 8h1a4 4 0 0 1 0 8h-1M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8Zm4-5v2m4-2v2m4-2v2"/>
                    </svg>
                </div>

                <h1 class="auth-heading">{{ $judulRole }}</h1>
                <p class="auth-subtitle">{{ $subjudulRole }}</p>

                {{ $this->content }}

                <p class="auth-switch-role">
                    @if ($isPemilik)
                        Bukan pemilik? <a href="{{ url('/admin/login') }}">Login sebagai Pelayan</a>
                    @else
                        Bukan pelayan? <a href="{{ url('/pemilik/login') }}">Login sebagai Pemilik</a>
                    @endif
                </p>
            </div>
        </section>
    </main>
</div>
