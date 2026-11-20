<div
    wire:poll.10s.keep-alive="cekPesananBaru"
    x-data="{
        audioCtx: null,
        judulAsli: document.title,
        kedipInterval: null,

        init() {
            const aktifkan = () => {
                this.siapkanAudio();
                if ('Notification' in window && Notification.permission === 'default') {
                    Notification.requestPermission();
                }
            };
            window.addEventListener('click', aktifkan, { once: true });
            window.addEventListener('keydown', aktifkan, { once: true });
            window.addEventListener('focus', () => this.hentikanKedip());
        },

        siapkanAudio() {
            if (! this.audioCtx) {
                const Ctx = window.AudioContext || window.webkitAudioContext;
                if (Ctx) this.audioCtx = new Ctx();
            }
            if (this.audioCtx && this.audioCtx.state === 'suspended') this.audioCtx.resume();
        },

        nada(frekuensi, mulai, durasi) {
            const ctx = this.audioCtx;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = frekuensi;
            gain.gain.setValueAtTime(0.0001, ctx.currentTime + mulai);
            gain.gain.exponentialRampToValueAtTime(0.5, ctx.currentTime + mulai + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + mulai + durasi);
            osc.connect(gain).connect(ctx.destination);
            osc.start(ctx.currentTime + mulai);
            osc.stop(ctx.currentTime + mulai + durasi + 0.05);
        },

        bunyikan() {
            this.siapkanAudio();
            if (! this.audioCtx) return;
            // Nada 'ding-dong' diulang dua kali
            [0, 0.9].forEach((offset) => {
                this.nada(1046.5, offset, 0.35);
                this.nada(784, offset + 0.3, 0.5);
            });
        },

        notifikasiBrowser(detail) {
            if (! ('Notification' in window) || Notification.permission !== 'granted') return;
            const n = new Notification(detail.judul, { body: detail.pesan, tag: 'pesanan-baru', renotify: true });
            n.onclick = () => { window.focus(); window.location.href = detail.url; n.close(); };
        },

        kedipkanJudul(jumlah) {
            this.hentikanKedip();
            let tampil = true;
            this.kedipInterval = setInterval(() => {
                document.title = tampil ? `🔔 (${jumlah}) Pesanan Baru!` : this.judulAsli;
                tampil = ! tampil;
            }, 1000);
        },

        hentikanKedip() {
            if (this.kedipInterval) clearInterval(this.kedipInterval);
            this.kedipInterval = null;
            document.title = this.judulAsli;
        },

        tanganiPesananBaru(detail) {
            this.bunyikan();
            this.notifikasiBrowser(detail);
            if (document.hidden) this.kedipkanJudul(detail.jumlah);
            Livewire.dispatch('pesanan-diperbarui');
        },
    }"
    x-on:pesanan-baru.window="tanganiPesananBaru($event.detail)"
    class="hidden"
></div>
