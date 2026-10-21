/*
 * Client onboarding questionnaire: one step at a time, autosave, choice
 * limits, extra reference rows and instant uploads. Without JavaScript the
 * form still works: every step is shown and the buttons post normally.
 */

const AUTOSAVE_INTERVAL = 20000;

export function initQuestionnaire() {
    const form = document.querySelector('[data-questionnaire]');
    if (!form) return;

    const token = form.querySelector('input[name="_token"]')?.value ?? '';
    const steps = [...form.querySelectorAll('[data-step]')];
    const keys = steps.map((step) => step.dataset.step);
    const links = [...document.querySelectorAll('[data-step-link]')];
    const stepInput = form.querySelector('[data-step-input]');
    const status = form.querySelector('[data-save-status]');
    const progressBar = document.querySelector('[data-progress-bar]');
    const progressLabel = document.querySelector('[data-progress-label]');
    // The admin preview of the questions: nothing is saved, submitted or uploaded.
    const preview = 'preview' in form.dataset;
    let current = form.dataset.current || keys[0];
    let dirty = false;
    let saving = null;

    if (preview) {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            setStatus('Preview only: answers are not saved or submitted.');
        });
    }

    const setStatus = (message) => {
        if (status) status.textContent = message;
    };

    const isAnswered = (step) =>
        [...step.querySelectorAll('input, textarea, select')].some((field) => {
            if (field.type === 'hidden' || field.type === 'file' || field.name.endsWith('[consent]')) return false;
            if (field.type === 'checkbox' || field.type === 'radio') return field.checked;
            return field.value.trim() !== '';
        }) || step.querySelector('[data-file-item]') !== null;

    const refreshDone = () => {
        steps.forEach((step) => {
            const link = links.find((item) => item.dataset.stepLink === step.dataset.step);
            link?.classList.toggle('is-done', isAnswered(step));
        });
    };

    const show = (key, { focus = true } = {}) => {
        if (!keys.includes(key)) return;
        current = key;
        const index = keys.indexOf(key);

        steps.forEach((step) => step.classList.toggle('is-current', step.dataset.step === key));
        links.forEach((link) => {
            const active = link.dataset.stepLink === key;
            link.classList.toggle('is-current', active);
            active ? link.setAttribute('aria-current', 'step') : link.removeAttribute('aria-current');
        });

        if (stepInput) stepInput.value = key;
        if (progressBar) progressBar.style.width = `${Math.round(((index + 1) / keys.length) * 100)}%`;
        if (progressLabel) progressLabel.textContent = `Step ${index + 1} of ${keys.length}`;

        const url = new URL(window.location.href);
        url.searchParams.set('step', key);
        window.history.replaceState(null, '', url);

        if (focus) {
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            steps[index].querySelector('[data-step-heading]')?.focus({ preventScroll: true });
        }
    };

    const save = async ({ keepalive = false } = {}) => {
        if (preview) {
            dirty = false;
            setStatus('Preview only: answers are not saved.');
            return null;
        }
        if (saving) return saving;

        const data = new FormData(form);
        data.delete('files[]');
        data.delete('remove_file');
        data.set('action', 'save');

        setStatus('Saving…');
        saving = fetch(form.action, {
            method: 'POST',
            body: data,
            keepalive,
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((response) => {
                if (response.ok) {
                    dirty = false;
                    setStatus(`Saved at ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}.`);
                } else if (response.status === 422) {
                    setStatus('Some answers need checking. Press “Save and continue later” to see them highlighted.');
                } else {
                    setStatus('We could not save just now. Your answers are still on this page.');
                }
            })
            .catch(() => setStatus('You appear to be offline. Your answers are still on this page.'))
            .finally(() => {
                saving = null;
            });

        return saving;
    };

    const go = (offset) => {
        const next = keys[keys.indexOf(current) + offset];
        if (!next) return;
        if (dirty) save();
        refreshDone();
        show(next);
    };

    form.querySelectorAll('[data-step-next]').forEach((button) => button.addEventListener('click', () => go(1)));
    form.querySelectorAll('[data-step-prev]').forEach((button) => button.addEventListener('click', () => go(-1)));

    links.forEach((link) =>
        link.addEventListener('click', (event) => {
            event.preventDefault();
            if (dirty) save();
            refreshDone();
            show(link.dataset.stepLink);
        }),
    );

    form.addEventListener('input', () => {
        dirty = true;
    });
    form.addEventListener('change', () => {
        dirty = true;
    });

    window.setInterval(() => {
        if (dirty) save();
    }, AUTOSAVE_INTERVAL);

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden' && dirty) save({ keepalive: true });
    });

    initChoiceLimits(form);
    initReferences(form);
    initUploads(form, token, refreshDone);

    const summary = form.querySelector('[data-error-summary]');
    show(current, { focus: false });
    summary?.focus();
}

/* Disables further options once a "choose up to N" limit is reached. */
function initChoiceLimits(form) {
    form.querySelectorAll('[data-max-choices]').forEach((group) => {
        const max = Number(group.dataset.maxChoices);
        const inputs = [...group.querySelectorAll('input[type="checkbox"]')];

        const update = () => {
            const chosen = inputs.filter((input) => input.checked).length;
            inputs.forEach((input) => {
                input.disabled = !input.checked && chosen >= max;
            });
        };

        group.addEventListener('change', update);
        update();
    });
}

/* Reveals one more reference row at a time. */
function initReferences(form) {
    form.querySelectorAll('[data-reference-add]').forEach((button) => {
        const list = button.parentElement.querySelector('[data-reference-list]');

        const update = () => {
            button.hidden = !list.querySelector('.ob-extra-row');
        };

        button.addEventListener('click', () => {
            const row = list.querySelector('.ob-extra-row');
            if (!row) return;
            row.classList.remove('ob-extra-row');
            row.querySelector('input')?.focus();
            update();
        });

        update();
    });
}

/* Uploads files as soon as they are chosen or dropped, and removes them in place. */
function initUploads(form, token, onChange) {
    const input = form.querySelector('[data-ob-file-input]');
    const list = form.querySelector('[data-file-list]');
    const zone = form.querySelector('[data-dropzone]');
    const error = form.querySelector('[data-upload-error]');
    if (!input || !list) return;

    const showError = (message) => {
        if (!error) return;
        error.textContent = message;
        error.hidden = !message;
    };

    const render = (file, item = document.createElement('li')) => {
        item.className = 'ob-file';
        item.dataset.fileItem = '';
        item.replaceChildren();

        let thumb;
        if (file.is_image) {
            thumb = document.createElement('img');
            thumb.src = file.url;
            thumb.alt = '';
        } else {
            thumb = document.createElement('span');
            thumb.textContent = 'PDF';
            thumb.classList.add('grid', 'place-items-center', 'text-xs', 'font-semibold');
        }
        thumb.classList.add('ob-file-thumb');

        const text = document.createElement('span');
        text.className = 'min-w-0 flex-1';
        const name = document.createElement('span');
        name.className = 'block truncate text-sm font-medium';
        name.textContent = file.name;
        const size = document.createElement('span');
        size.className = 'block text-xs text-subtle';
        size.textContent = file.size;
        text.append(name, size);

        item.append(thumb, text);

        if (file.remove_url) {
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'ob-file-remove';
            remove.dataset.removeUrl = file.remove_url;
            remove.setAttribute('aria-label', `Remove ${file.name}`);
            remove.textContent = '×';
            item.append(remove);
        }

        return item;
    };

    const upload = async (file) => {
        const placeholder = render({ name: file.name, size: 'Uploading…', is_image: false });
        placeholder.classList.add('is-uploading');
        list.append(placeholder);

        const data = new FormData();
        data.append('file', file);
        data.append('_token', token);

        try {
            const response = await fetch(form.dataset.uploadUrl, {
                method: 'POST',
                body: data,
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const body = await response.json().catch(() => ({}));

            if (!response.ok) {
                placeholder.remove();
                showError(body.errors?.file?.[0] ?? body.message ?? `“${file.name}” could not be uploaded.`);
                return;
            }

            placeholder.classList.remove('is-uploading');
            render(body, placeholder);
            onChange();
        } catch {
            placeholder.remove();
            showError(`“${file.name}” could not be uploaded. Please check your connection and try again.`);
        }
    };

    const uploadAll = async (files) => {
        if ('preview' in form.dataset) {
            showError('Uploads are switched off in the preview.');
            return;
        }
        showError('');
        for (const file of files) {
            await upload(file);
        }
    };

    input.addEventListener('change', () => {
        const files = [...input.files];
        input.value = '';
        uploadAll(files);
    });

    if (zone) {
        ['dragenter', 'dragover'].forEach((type) =>
            zone.addEventListener(type, (event) => {
                event.preventDefault();
                zone.classList.add('is-dragging');
            }),
        );
        ['dragleave', 'drop'].forEach((type) =>
            zone.addEventListener(type, () => zone.classList.remove('is-dragging')),
        );
        zone.addEventListener('drop', (event) => {
            event.preventDefault();
            uploadAll([...(event.dataTransfer?.files ?? [])]);
        });
    }

    list.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-remove-url]');
        if (!button) return;
        event.preventDefault();

        const item = button.closest('[data-file-item]');
        item?.classList.add('is-uploading');

        const response = await fetch(button.dataset.removeUrl, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
        }).catch(() => null);

        if (response?.ok) {
            item?.remove();
            onChange();
        } else {
            item?.classList.remove('is-uploading');
            showError('That file could not be removed. Please try again.');
        }
    });
}
