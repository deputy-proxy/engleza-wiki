export function assessmentTimer({
    endsAt = 0,
    submit,
    messages = {},
    schedule = setInterval,
    cancel = clearInterval,
    clock = Date.now,
} = {}) {
    return {
        endsAt,
        now: clock(),
        announcement: '',
        warned: [],
        interval: null,
        submissionStarted: false,
        messages: {
            fiveMinutes: messages.fiveMinutes ?? 'Five minutes remaining',
            oneMinute: messages.oneMinute ?? 'One minute remaining',
            expired: messages.expired ?? 'Time is up. Submitting your answers.',
        },

        init() {
            this.now = clock();

            if (!this.endsAt || this.interval !== null) {
                return;
            }

            this.interval = schedule(() => this.tick(), 1000);
            this.tick();
        },

        tick() {
            this.now = clock();

            if (!this.endsAt || this.submissionStarted) {
                return;
            }

            const seconds = Math.max(0, Math.floor((this.endsAt - this.now) / 1000));
            const warning = seconds === 0 ? 0 : (seconds <= 60 ? 60 : (seconds <= 300 ? 300 : null));

            if (warning !== null && !this.warned.includes(warning)) {
                this.warned.push(warning);
                this.announcement = warning === 300
                    ? this.messages.fiveMinutes
                    : (warning === 60 ? this.messages.oneMinute : this.messages.expired);
            }

            if (seconds === 0) {
                this.submissionStarted = true;
                this.stop();
                submit();
            }
        },

        stop() {
            if (this.interval !== null) {
                cancel(this.interval);
                this.interval = null;
            }
        },

        destroy() {
            this.stop();
        },

        get remaining() {
            if (!this.endsAt) {
                return null;
            }

            const seconds = Math.max(0, Math.floor((this.endsAt - this.now) / 1000));

            return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
        },
    };
}
