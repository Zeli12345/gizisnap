import { sendJson } from '../http';

/**
 * Tampilan chat: kirim pesan tanpa memuat ulang halaman.
 */
export default (config) => ({
    messages: config.messages ?? [],
    conversation: config.conversation,
    storeUrl: config.storeUrl,
    text: '',
    sending: false,
    error: '',
    canRetry: config.pending ?? false,
    max: 1000,

    init() {
        this.$nextTick(() => this.scrollDown());
    },

    get remainingChars() {
        return this.max - this.text.length;
    },

    use(suggestion) {
        this.text = suggestion;
        this.send();
    },

    keydown(event) {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            this.send();
        }
    },

    async send() {
        const text = this.text.trim();
        if (!text || this.sending) return;
        if (text.length > this.max) {
            this.error = 'Pesan paling panjang 1000 karakter.';
            return;
        }

        this.sending = true;
        this.error = '';
        this.canRetry = false;
        const optimistic = { id: `tmp-${Date.now()}`, role: 'user', content: text, html: null, time: '' };
        this.messages.push(optimistic);
        this.text = '';
        this.scrollDown();

        const url = this.conversation ? this.conversation.send_url : this.storeUrl;

        try {
            const data = await sendJson(url, { body: { message: text } });
            this.adopt(data.conversation);
            this.replace(optimistic, data.user);
            this.messages.push(data.assistant);
        } catch (e) {
            this.error = e.message;
            const data = e.data ?? {};
            if (data.conversation) this.adopt(data.conversation);
            if (data.user) {
                this.replace(optimistic, data.user);
                this.canRetry = true;
            } else {
                this.messages = this.messages.filter((m) => m !== optimistic);
                this.text = text;
            }
        } finally {
            this.sending = false;
            this.scrollDown();
        }
    },

    async retry() {
        if (!this.conversation || this.sending) return;
        this.sending = true;
        this.error = '';
        this.canRetry = false;
        try {
            const data = await sendJson(this.conversation.retry_url);
            this.messages.push(data.assistant);
        } catch (e) {
            this.error = e.message;
            this.canRetry = e.status !== 429;
        } finally {
            this.sending = false;
            this.scrollDown();
        }
    },

    adopt(conversation) {
        if (!conversation || this.conversation?.id === conversation.id) return;
        this.conversation = conversation;
        window.history.replaceState({}, '', conversation.url);
        window.dispatchEvent(new CustomEvent('conversation-started', { detail: conversation }));
    },

    replace(optimistic, saved) {
        const index = this.messages.indexOf(optimistic);
        if (index !== -1) this.messages.splice(index, 1, saved);
    },

    scrollDown() {
        this.$nextTick(() => {
            const el = this.$refs.thread;
            if (el) el.scrollTop = el.scrollHeight;
        });
    },
});
