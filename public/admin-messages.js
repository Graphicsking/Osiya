const composer = document.querySelector('[data-message-composer]');
if (composer) {
    const body = composer.querySelector('[data-message-body]');
    const preview = composer.querySelector('[data-message-preview]');
    const counter = composer.querySelector('[data-character-count]');
    const updatePreview = () => {
        preview.textContent = body.value || 'Your message will appear here as you write.';
        counter.textContent = `${Array.from(body.value).length.toLocaleString()} / 1,000`;
    };
    body.addEventListener('input', updatePreview);
    updatePreview();

    let activeRequest;
    let debounce;
    const refreshAudience = async () => {
        activeRequest?.abort();
        activeRequest = new AbortController();
        const count = composer.querySelector('[data-audience-count]');
        const note = composer.querySelector('[data-audience-note]');
        count.textContent = 'Checking audience…';
        const params = new URLSearchParams();
        composer.querySelectorAll('[data-audience-filter]').forEach(input => params.set(input.name, input.value));
        try {
            const response = await fetch(`${composer.dataset.audienceUrl}?${params}`, { signal: activeRequest.signal, headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Audience unavailable');
            const audience = await response.json();
            count.textContent = `${audience.count.toLocaleString()} reachable ${audience.count === 1 ? 'contact' : 'contacts'}`;
            note.textContent = `${audience.excluded} duplicate or incomplete phone records excluded. Final audience is saved at review.`;
        } catch (error) {
            if (error.name === 'AbortError') return;
            count.textContent = 'Audience count unavailable';
            note.textContent = 'Your recipient list will be checked when you review the message.';
        }
    };
    composer.querySelectorAll('[data-audience-filter]').forEach(input => input.addEventListener('input', () => {
        activeRequest?.abort();
        clearTimeout(debounce);
        debounce = setTimeout(refreshAudience, input.tagName === 'SELECT' ? 0 : 300);
    }));
    refreshAudience();
}
