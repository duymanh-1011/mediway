export const normalizeLookup = (text) => String(text).normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd').replace(/Đ/g, 'D').toLowerCase().trim();

export function matchingOptions(options, term, limit = 20) {
    const needle = normalizeLookup(term);
    return options.filter((option) => !option.disabled && !option.hidden && String(option.value) !== '' && normalizeLookup(option.label).includes(needle)).slice(0, limit);
}

let nextLookupId = 0;

export function initializeLookups(root = document) {
    const fields = JSON.parse(document.body.dataset.lookupFields || '{}');
    const endpoint = document.body.dataset.lookupUrl;
    root.querySelectorAll('select:not([data-lookup-enhanced])').forEach((select) => {
        if (select.dataset.lookupNative !== undefined) return;
        const optionCount = [...select.options].filter((option) => option.value !== '').length;
        if (optionCount <= 8 && !select.dataset.lookup) return;
        const remoteType = select.dataset.lookup || (select.form?.method.toLowerCase() === 'get' ? fields[select.name] : null);
        enhanceLookup(select, remoteType && endpoint ? { endpoint, type: remoteType } : null);
    });
}

function enhanceLookup(select, remote) {
    select.dataset.lookupEnhanced = 'true';
    const id = `admin-lookup-${++nextLookupId}`;
    const label = select.labels?.[0];
    const labelText = label?.textContent.replace(/\s+/g, ' ').trim() || 'Lựa chọn';
    select.dataset.lookupLabel = labelText;
    const wrapper = document.createElement('div');
    wrapper.className = 'admin-lookup';
    const input = document.createElement('input');
    input.type = 'search'; input.id = id; input.autocomplete = 'off';
    input.dataset.lookupInput = 'true';
    input.placeholder = 'Nhập tên hoặc mã để tìm…';
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-label', labelText);
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-expanded', 'false');
    input.setAttribute('aria-controls', `${id}-list`);
    if (label?.htmlFor === select.id && select.id) label.htmlFor = id;
    const clear = document.createElement('button');
    clear.type = 'button'; clear.className = 'admin-lookup-clear'; clear.textContent = '×';
    clear.setAttribute('aria-label', `Bỏ lựa chọn ${labelText}`);
    const chips = document.createElement('div'); chips.className = 'admin-lookup-chips';
    const list = document.createElement('div'); list.id = `${id}-list`; list.className = 'admin-lookup-list';
    list.setAttribute('role', 'listbox'); list.hidden = true;
    const message = document.createElement('div'); message.className = 'admin-lookup-message';
    message.setAttribute('role', 'status'); message.hidden = true;
    const error = document.createElement('div'); error.className = 'field-error'; error.id = `${id}-error`; error.hidden = true;
    input.setAttribute('aria-describedby', error.id);
    const control = document.createElement('div'); control.className = 'admin-lookup-control';
    control.append(input, clear, list);
    select.before(wrapper); wrapper.append(select, chips, control, message, error);
    select.hidden = true;
    let choices = [], active = -1, timer, aborter, version = 0, editing = false, lastValue;
    const localOptions = () => [...select.options].map((option) => ({ value: option.value, label: option.textContent.trim(), disabled: option.disabled || option.parentElement?.disabled, hidden: option.hidden || option.parentElement?.hidden && option.parentElement !== select, option }));
    const selected = () => [...select.selectedOptions].filter((option) => option.value !== '');
    const close = () => { list.hidden = true; message.hidden = true; input.setAttribute('aria-expanded', 'false'); input.removeAttribute('aria-activedescendant'); };
    const signal = () => { editing = true; select.dispatchEvent(new Event('change', { bubbles: true })); editing = false; };
    const showError = (text = '') => { error.textContent = text; error.hidden = !text; input.setAttribute('aria-invalid', text ? 'true' : 'false'); };
    const sync = () => {
        input.disabled = select.disabled; clear.disabled = select.disabled;
        if (select.disabled) { ++version; aborter?.abort(); close(); }
        const values = selected();
        const fingerprint = values.map((option) => option.value).join(',');
        if (lastValue !== fingerprint) {
            if (!select.multiple) input.value = values[0]?.textContent.trim() || '';
            lastValue = fingerprint;
        }
        chips.replaceChildren(); chips.hidden = !select.multiple || !values.length;
        if (select.multiple) values.forEach((option) => {
            const chip = document.createElement('button'); chip.type = 'button'; chip.className = 'admin-lookup-chip';
            chip.textContent = `${option.textContent.trim()} ×`; chip.disabled = select.disabled;
            chip.setAttribute('aria-label', `Bỏ ${option.textContent.trim()}`);
            chip.addEventListener('click', () => { option.selected = false; signal(); sync(); }); chips.append(chip);
        });
        clear.hidden = !values.length && !input.value;
    };
    const choose = (choice) => {
        let option = [...select.options].find((option) => option.value === String(choice.value));
        if (!option) { option = new Option(choice.label, String(choice.value)); select.add(option); }
        if (choice.specialty_id !== undefined) {
            option.dataset.specialtyId = choice.specialty_id || '';
            option.dataset.isGeneral = choice.is_general ? '1' : '0';
        }
        if (select.multiple) { option.selected = !option.selected; input.value = ''; }
        else { select.value = option.value; input.value = option.textContent.trim(); }
        lastValue = undefined; showError(); signal(); sync(); close(); input.focus();
    };
    const activate = (index) => {
        active = index;
        [...list.children].forEach((node, number) => { node.setAttribute('aria-selected', String(number === active)); });
        const current = list.children[active];
        if (current) { input.setAttribute('aria-activedescendant', current.id); current.scrollIntoView({ block: 'nearest' }); }
    };
    const render = (results, hasMore = false) => {
        choices = results; active = -1; list.replaceChildren(); list.hidden = false;
        input.setAttribute('aria-expanded', 'true');
        results.forEach((choice, index) => {
            const item = document.createElement('div'); item.id = `${id}-option-${index}`; item.className = 'admin-lookup-option';
            item.setAttribute('role', 'option'); item.setAttribute('aria-selected', 'false'); item.textContent = choice.label;
            item.addEventListener('mousedown', (event) => event.preventDefault()); item.addEventListener('click', () => choose(choice)); list.append(item);
        });
        message.hidden = !!results.length && !hasMore;
        message.textContent = !results.length ? 'Không có kết quả phù hợp.' : 'Nhập thêm tên hoặc mã để thu hẹp kết quả.';
    };
    const search = async () => {
        const requestVersion = ++version;
        const term = !select.multiple && selected().length && input.value === selected()[0].textContent.trim() ? '' : input.value;
        aborter?.abort();
        if (!remote) { const matches = matchingOptions(localOptions(), term, 21); render(matches.slice(0, 20), matches.length > 20); return; }
        aborter = new AbortController();
        message.textContent = 'Đang tìm…'; message.hidden = false;
        try {
            const url = new URL(remote.endpoint, window.location.origin);
            url.searchParams.set('type', remote.type); url.searchParams.set('q', term.slice(0, 100));
            url.searchParams.set('scope', select.dataset.lookupScope || 'all');
            const dependency = remote.type === 'doctors' ? 'specialty_id' : remote.type === 'specialties' ? 'doctor_id' : '';
            const related = dependency && select.form?.querySelector(`select[name="${dependency}"]`);
            if (related?.value) url.searchParams.set(dependency, related.value);
            const response = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' }, signal: aborter.signal });
            if (!response.ok) throw new Error('lookup failed');
            const data = await response.json();
            if (requestVersion === version) render(data.results, data.has_more);
        } catch (failure) {
            if (failure.name === 'AbortError' || requestVersion !== version) return;
            list.hidden = true; message.hidden = false; message.textContent = 'Không tải được gợi ý. Vui lòng thử lại.';
        }
    };
    input.addEventListener('input', () => {
        ++version; aborter?.abort(); choices = []; close();
        if (!select.multiple) { select.value = ''; lastValue = ''; signal(); }
        showError(); clear.hidden = !input.value && !selected().length;
        clearTimeout(timer); timer = setTimeout(search, remote ? 200 : 0);
    });
    input.addEventListener('focus', () => { input.select(); search(); });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') { ++version; aborter?.abort(); close(); return; }
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (list.hidden) search();
            else if (choices.length) activate((active + (event.key === 'ArrowDown' ? 1 : -1) + choices.length) % choices.length);
        }
        if (event.key === 'Enter' && !list.hidden && active >= 0) { event.preventDefault(); choose(choices[active]); }
    });
    clear.addEventListener('click', () => { ++version; aborter?.abort(); [...select.options].forEach((option) => { option.selected = false; }); select.value = ''; input.value = ''; lastValue = undefined; showError(); signal(); sync(); close(); input.focus(); });
    select.addEventListener('change', () => { if (!editing) { lastValue = undefined; sync(); } });
    select.addEventListener('admin:options-changed', () => { ++version; aborter?.abort(); close(); lastValue = undefined; sync(); });
    document.addEventListener('pointerdown', (event) => { if (!wrapper.contains(event.target)) { ++version; aborter?.abort(); close(); } });
    select.form?.addEventListener('reset', () => setTimeout(() => { lastValue = undefined; sync(); close(); showError(); }, 0));
    select.form?.addEventListener('submit', (event) => {
        if (select.disabled) return;
        if ((!select.multiple && input.value && !selected().length) || (select.required && !selected().length)) {
            event.preventDefault(); showError('Vui lòng chọn một mục trong danh sách gợi ý.'); input.focus();
        }
    });
    new MutationObserver(() => { ++version; aborter?.abort(); close(); sync(); }).observe(select, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled', 'selected'] });
    sync();
}
