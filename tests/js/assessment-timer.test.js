import test from 'node:test';
import assert from 'node:assert/strict';
import { assessmentTimer } from '../../resources/js/assessment-timer.js';

function makeClock() {
    let nextId = 1;
    const callbacks = new Map();
    const cancelled = [];

    return {
        callbacks,
        cancelled,
        schedule(callback) {
            const id = nextId++;
            callbacks.set(id, callback);
            return id;
        },
        cancel(id) {
            cancelled.push(id);
            callbacks.delete(id);
        },
        tick(id) {
            callbacks.get(id)?.();
        },
    };
}

test('assessment timer announces thresholds and submits once when time expires', () => {
    const scheduler = makeClock();
    let now = 0;
    let submissions = 0;
    const timer = assessmentTimer({
        endsAt: 301_000,
        submit: () => submissions++,
        schedule: scheduler.schedule,
        cancel: scheduler.cancel,
        clock: () => now,
    });

    timer.init();
    const interval = timer.interval;
    assert.equal(timer.remaining, '5:01');

    now = 1_000;
    scheduler.tick(interval);
    assert.equal(timer.announcement, 'Five minutes remaining');
    assert.deepEqual(timer.warned, [300]);

    now = 241_000;
    scheduler.tick(interval);
    assert.equal(timer.announcement, 'One minute remaining');
    assert.deepEqual(timer.warned, [300, 60]);

    now = 301_000;
    scheduler.tick(interval);
    assert.equal(timer.announcement, 'Time is up. Submitting your answers.');
    assert.equal(timer.submissionStarted, true);
    assert.equal(timer.interval, null);
    assert.deepEqual(scheduler.cancelled, [interval]);
    assert.equal(submissions, 1);

    timer.tick();
    assert.equal(submissions, 1);
});

test('assessment timer immediately submits an already-expired attempt only once', () => {
    const scheduler = makeClock();
    let submissions = 0;
    const timer = assessmentTimer({
        endsAt: 500,
        submit: () => submissions++,
        schedule: scheduler.schedule,
        cancel: scheduler.cancel,
        clock: () => 1_000,
    });

    timer.init();

    assert.equal(submissions, 1);
    assert.equal(timer.interval, null);
    assert.equal(timer.remaining, '0:00');
});

test('assessment timer clears its interval when its Alpine component is destroyed', () => {
    const scheduler = makeClock();
    const timer = assessmentTimer({
        endsAt: 60_000,
        submit: () => assert.fail('An active timer must not submit before expiry.'),
        schedule: scheduler.schedule,
        cancel: scheduler.cancel,
        clock: () => 0,
    });

    timer.init();
    const interval = timer.interval;
    timer.destroy();

    assert.equal(timer.interval, null);
    assert.deepEqual(scheduler.cancelled, [interval]);
    assert.equal(scheduler.callbacks.size, 0);
});
