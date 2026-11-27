/**
 * Contoh percakapan di halaman depan. Jawabannya contoh tetap, bukan AI sungguhan;
 * kartunya diberi keterangan itu. Hanya satu tanya-jawab yang tampil supaya tinggi kartu stabil.
 */
export default (config) => ({
    qa: config.qa,
    current: 0,
    shown: true,
    typing: false,
    timer: null,

    ask(index) {
        if (this.typing || index === this.current) return;
        clearTimeout(this.timer);
        this.current = index;
        this.shown = false;
        this.typing = true;
        const instant = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.timer = setTimeout(
            () => {
                this.typing = false;
                this.shown = true;
            },
            instant ? 0 : 1100,
        );
    },
});
