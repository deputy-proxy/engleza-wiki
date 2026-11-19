export function speakingPractice({ schedule = setInterval, cancel = clearInterval } = {}) {
    return {
        phase: 'ready',
        seconds: 0,
        interval: null,

        start(nextPhase, duration) {
            this.stop();
            this.phase = nextPhase;
            this.seconds = duration;

            if (duration === 0) {
                this.phase = 'done';
                return;
            }

            this.interval = schedule(() => {
                this.seconds = Math.max(0, this.seconds - 1);

                if (this.seconds === 0) {
                    this.stop();
                    this.phase = 'done';
                }
            }, 1000);
        },

        stop() {
            if (this.interval !== null) {
                cancel(this.interval);
                this.interval = null;
            }
        },

        reset() {
            this.stop();
            this.phase = 'ready';
            this.seconds = 0;
        },

        get clock() {
            return `${Math.floor(this.seconds / 60)}:${String(this.seconds % 60).padStart(2, '0')}`;
        },
    };
}
