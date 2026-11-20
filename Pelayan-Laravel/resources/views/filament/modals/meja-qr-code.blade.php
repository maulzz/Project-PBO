@php
    $qrUrl = url('/order?meja=' . $meja->qr_code);
@endphp

<div
    x-data="{
        qrText: @js($qrUrl),
        nomorMeja: @js($meja->nomor_meja),
        qrCodeId: @js($meja->qr_code),
        siap: false,
        svgMarkup: '',

        async init() {
            if (typeof window.qrcode === 'undefined') {
                await new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = @js(asset('js/qrcode.min.js'));
                    script.onload = resolve;
                    script.onerror = reject;
                    document.head.appendChild(script);
                });
            }

            this.renderQr();
        },

        renderQr() {
            const qr = window.qrcode(0, 'M');
            qr.addData(this.qrText);
            qr.make();

            const count = qr.getModuleCount();
            const canvas = this.$refs.canvas;
            const ctx = canvas.getContext('2d');

            const size = 480;
            const padding = 40;
            const headerHeight = 64;
            const footerHeight = 56;

            canvas.width = size;
            canvas.height = size + headerHeight + footerHeight;

            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            // Header teks nomor meja
            ctx.fillStyle = '#111827';
            ctx.font = 'bold 28px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('MEJA ' + this.nomorMeja, canvas.width / 2, 48);

            // Gambar modul QR
            const qrArea = size - padding * 2;
            const cell = qrArea / count;
            const offsetY = headerHeight + 8;

            ctx.fillStyle = '#000000';
            for (let r = 0; r < count; r++) {
                for (let c = 0; c < count; c++) {
                    if (qr.isDark(r, c)) {
                        ctx.fillRect(
                            Math.floor(padding + c * cell),
                            Math.floor(offsetY + r * cell),
                            Math.ceil(cell),
                            Math.ceil(cell),
                        );
                    }
                }
            }

            // Footer teks
            ctx.fillStyle = '#ea580c';
            ctx.font = 'bold 18px sans-serif';
            ctx.fillText('Scan QR untuk Memesan', canvas.width / 2, canvas.height - 34);

            ctx.fillStyle = '#6b7280';
            ctx.font = '13px monospace';
            ctx.fillText(this.qrCodeId, canvas.width / 2, canvas.height - 14);

            this.svgMarkup = qr.createSvgTag({ cellSize: 8, margin: 4 });
            this.siap = true;
        },

        unduhPng() {
            const link = document.createElement('a');
            link.download = 'qr-meja-' + this.nomorMeja.toLowerCase() + '.png';
            link.href = this.$refs.canvas.toDataURL('image/png');
            link.click();
        },

        unduhSvg() {
            const blob = new Blob([this.svgMarkup], { type: 'image/svg+xml;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.download = 'qr-meja-' + this.nomorMeja.toLowerCase() + '.svg';
            link.href = url;
            link.click();
            URL.revokeObjectURL(url);
        },
    }"
    class="flex flex-col items-center gap-4 py-2"
>
    <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700">
        <canvas x-ref="canvas" class="mx-auto max-w-[280px] rounded-lg"></canvas>
    </div>

    <div class="w-full rounded-lg bg-gray-50 px-3 py-2 text-center text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">
        <div class="font-medium">URL Pemesanan:</div>
        <div class="mt-0.5 break-all font-mono">{{ $qrUrl }}</div>
    </div>

    <div class="flex flex-wrap items-center justify-center gap-3">
        <button
            type="button"
            x-on:click="unduhPng()"
            x-bind:disabled="! siap"
            class="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-500 disabled:opacity-50"
        >
            <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-4 w-4" />
            Unduh PNG
        </button>

        <button
            type="button"
            x-on:click="unduhSvg()"
            x-bind:disabled="! siap"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
        >
            <x-filament::icon icon="heroicon-o-code-bracket" class="h-4 w-4" />
            Unduh SVG
        </button>
    </div>
</div>
