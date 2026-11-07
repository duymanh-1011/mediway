import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../resources/js/portal/payments-sync.js', import.meta.url), 'utf8');
function setup(snapshot, { hidden = false, failed = false } = {}) {
    const events = {};
    const calls = [];
    let reloads = 0;
    const context = {
        document: {
            hidden,
            querySelector: () => ({ dataset: { paymentSnapshot: 'before', paymentRefreshUrl: '/tai-khoan/thanh-toan' } }),
            addEventListener: (name, fn) => { events[name] = fn; },
        },
        window: {
            addEventListener: (name, fn) => { events[name] = fn; },
            setInterval: (fn, ms) => { events.interval = fn; assert.equal(ms, 15000); },
            location: { reload: () => { reloads++; } },
        },
        fetch: async (url, options) => {
            calls.push({ url, options });
            if (failed) throw new Error('offline');
            return { ok: true, redirected: false, json: async () => ({ snapshot }) };
        },
    };
    runInNewContext(source, context);
    return { events, calls, reloads: () => reloads };
}

test('changed outstanding charges refresh the page through GET only', async () => {
    const app = setup('after');
    await app.events.interval();
    assert.equal(app.reloads(), 1);
    assert.equal(app.calls[0].url, '/tai-khoan/thanh-toan');
    assert.equal(app.calls[0].options.method, undefined);
    assert.equal(app.calls[0].options.cache, 'no-store');
});
test('unchanged state does not reload when navigating back', async () => {
    const app = setup('before');
    await app.events.pageshow();
    assert.equal(app.calls.length, 1);
    assert.equal(app.reloads(), 0);
});
test('hidden pages suspend refresh and focus refreshes visible pages', async () => {
    const app = setup('after', { hidden: true });
    await app.events.interval();
    assert.equal(app.calls.length, 0);
    const visible = setup('after');
    await visible.events.focus();
    assert.equal(visible.reloads(), 1);
});
test('network failure never marks a payment as confirmed', async () => {
    const app = setup('after', { failed: true });
    await app.events.interval();
    assert.equal(app.reloads(), 0);
});
