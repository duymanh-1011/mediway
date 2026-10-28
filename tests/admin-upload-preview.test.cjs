const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

for (const moduleName of ['doctors', 'employees', 'patients']) {
    for (const hasExistingImage of [false, true]) {
        test(`${moduleName}: chọn, bỏ chọn và khôi phục ảnh (${hasExistingImage ? 'đã có ảnh' : 'chưa có ảnh'})`, () => {
            const source = fs.readFileSync(`resources/views/admin/${moduleName}/_form.blade.php`, 'utf8');
            const scripts = [...source.matchAll(/<script\b[^>]*>([\s\S]*?)<\/script>/g)].map(match => match[1]);
            const originalSrc = hasExistingImage ? '/existing-image.png' : '';
            const preview = {src: originalSrc, hidden: !hasExistingImage, getAttribute: () => originalSrc};
            const empty = {hidden: hasExistingImage};
            for (const element of [preview, empty]) {
                element.style = {};
                Object.defineProperty(element.style, 'display', {set(value) { element.hidden = value === 'none'; }});
            }
            const label = {textContent: 'Chưa có tệp', dataset: {}};
            const listeners = {};
            const input = {id: 'avatar', files: [], value: '', dataset: {kind: 'image', preview: 'image'}, addEventListener: (event, handler) => { listeners[event] = handler; }};
            const document = {
                addEventListener: (event, handler) => { if (event === 'DOMContentLoaded') handler(); },
                querySelectorAll: () => [input],
                getElementById: id => id === 'user_id' ? null : id.includes('preview-') ? preview : id.includes('empty-') ? empty : label,
            };
            const context = {document, URL: {createObjectURL: () => 'blob:test-image', revokeObjectURL: () => {}}, alert: () => {}};
            scripts.forEach(script => vm.runInNewContext(script, context));
            input.files = [{name: 'new-image.png', type: 'image/png'}];
            listeners.change();
            assert.equal(preview.src, 'blob:test-image');
            assert.equal(preview.hidden, false);
            assert.equal(empty.hidden, true);
            input.files = [];
            listeners.change();
            assert.equal(preview.src, originalSrc);
            assert.equal(preview.hidden, !hasExistingImage);
            assert.equal(empty.hidden, hasExistingImage);
            input.files = [{name: 'bad.txt', type: 'text/plain'}];
            input.value = 'bad.txt';
            listeners.change();
            assert.equal(input.value, '');
        });
    }
}
