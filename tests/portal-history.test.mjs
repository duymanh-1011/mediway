import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import assert from 'node:assert/strict';
import { test } from 'node:test';

test('restoring a portal page from history reloads it before use', () => {
    const handlers = {};
    let reloads = 0;
    const context = {
        window: {
            addEventListener: (name, handler) => { handlers[name] = handler; },
            location: { reload: () => { reloads++; } },
        },
        document: { querySelector: () => null, querySelectorAll: () => [] },
    };
    runInNewContext(readFileSync(new URL('../resources/js/portal.js', import.meta.url), 'utf8'), context);
    handlers.pageshow({ persisted: false });
    assert.equal(reloads, 0);
    handlers.pageshow({ persisted: true });
    assert.equal(reloads, 1);
});
