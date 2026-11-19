import test from 'node:test';
import assert from 'node:assert/strict';
import { speakingPractice } from '../../resources/js/speaking-practice.js';

function makeTimer() {
    let nextId = 1;
    const callbacks = new Map();
    const cancelled = [];
    return {
        callbacks, cancelled,
        schedule(callback) { const id = nextId++; callbacks.set(id, callback); return id; },
        cancel(id) { cancelled.push(id); callbacks.delete(id); },
        tick(id) { callbacks.get(id)?.(); },
    };
}

test('speaking timer counts down and ends without going below zero', () => {
    const timer = makeTimer();
    const practice = speakingPractice({ schedule: timer.schedule, cancel: timer.cancel });
    practice.start('speaking', 2);
    assert.equal(practice.phase, 'speaking');
    assert.equal(practice.clock, '0:02');
    timer.tick(practice.interval);
    assert.equal(practice.clock, '0:01');
    const interval = practice.interval;
    timer.tick(interval);
    assert.equal(practice.seconds, 0);
    assert.equal(practice.phase, 'done');
    assert.equal(practice.interval, null);
    assert.deepEqual(timer.cancelled, [interval]);
});

test('starting a new phase cancels the previous timer', () => {
    const timer = makeTimer();
    const practice = speakingPractice({ schedule: timer.schedule, cancel: timer.cancel });
    practice.start('preparation', 30);
    const preparationInterval = practice.interval;
    practice.start('speaking', 90);
    assert.deepEqual(timer.cancelled, [preparationInterval]);
    assert.equal(practice.phase, 'speaking');
    assert.equal(practice.clock, '1:30');
});

test('zero-second preparation ends immediately and reset returns to ready', () => {
    const timer = makeTimer();
    const practice = speakingPractice({ schedule: timer.schedule, cancel: timer.cancel });
    practice.start('preparation', 0);
    assert.equal(practice.phase, 'done');
    assert.equal(practice.interval, null);
    practice.start('speaking', 45);
    const interval = practice.interval;
    practice.reset();
    assert.deepEqual(timer.cancelled, [interval]);
    assert.equal(practice.phase, 'ready');
    assert.equal(practice.seconds, 0);
    assert.equal(practice.clock, '0:00');
});
