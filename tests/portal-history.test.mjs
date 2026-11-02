import assert from 'node:assert/strict';
import { test } from 'node:test';
import { installPortalHistoryReload } from '../resources/js/portal-history.js';

test('restoring a portal page from history reloads it before use', () => {
    const handlers = {};
    let reloads = 0;
    const targetWindow = {
        addEventListener: (name, handler) => { handlers[name] = handler; },
        location: { reload: () => { reloads++; } },
    };
    installPortalHistoryReload(targetWindow);
    handlers.pageshow({ persisted: false });
    assert.equal(reloads, 0);
    handlers.pageshow({ persisted: true });
    assert.equal(reloads, 1);
});
