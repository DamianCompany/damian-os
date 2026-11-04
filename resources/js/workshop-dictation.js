// Browser speech recognition may use an online service. Manual entry always works.
const enhance = () => {
    document.querySelectorAll('[data-dictation]').forEach((wrapper) => {
        if (wrapper.dataset.dictationReady) return;
        const input = wrapper.matches('textarea,input') ? wrapper : wrapper.querySelector('textarea,input');
        if (!input) return;
        wrapper.dataset.dictationReady = 'true';
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = '🎙 Dictar';
        button.className = 'workshop-mic';
        const status = document.createElement('span');
        status.setAttribute('role', 'status');
        status.className = 'workshop-speech-status';
        input.parentElement.append(button, status);
        const Speech = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!Speech || !window.isSecureContext) {
            button.disabled = true;
            status.textContent = 'Dictado no disponible aquí. Puedes escribir; por IP HTTP local el navegador suele requerir HTTPS.';
            return;
        }
        let recognition;
        button.addEventListener('click', () => {
            if (recognition) { recognition.stop(); return; }
            const active = new Speech();
            recognition = active;
            active.lang = 'es-PE'; active.continuous = true; active.interimResults = false;
            active.onstart = () => { button.textContent = '■ Detener'; status.textContent = 'Escuchando. El texto no se guarda automáticamente.'; };
            active.onresult = (event) => {
                let transcript = '';
                for (let i = event.resultIndex; i < event.results.length; i++) transcript += event.results[i][0].transcript + ' ';
                input.value = [input.value, transcript.trim()].filter(Boolean).join(' ');
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            };
            active.onerror = (event) => { status.textContent = event.error === 'not-allowed' ? 'Permiso de micrófono denegado. Puedes escribir.' : 'No se pudo dictar. Revisa conexión y micrófono o escribe.'; };
            active.onend = () => { recognition = null; button.textContent = '🎙 Dictar'; if (status.textContent.startsWith('Escuchando')) status.textContent = 'Dictado detenido. Revisa el texto antes de guardar.'; };
            try { active.start(); } catch { recognition = null; status.textContent = 'No se pudo iniciar. Puedes escribir.'; }
        });
    });
};
new MutationObserver(enhance).observe(document.documentElement, { childList: true, subtree: true });
document.addEventListener('livewire:navigated', enhance);
enhance();
