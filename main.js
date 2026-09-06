/**
 * RODAX — JavaScript Principal da Aplicação
 * "RODAX — Seu próximo veículo está aqui."
 */

function toggleMobileMenu() {
    const nav = document.getElementById('rodaxNav');
    const btn = document.getElementById('mobileMenuBtn');
    if (nav) {
        nav.classList.toggle('active');
        if (btn) {
            btn.classList.toggle('active');
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    console.log('RODAX — Marketplace de Veículos inicializado.');

    // Fechar menu mobile ao clicar fora dele
    document.addEventListener('click', function(e) {
        const nav = document.getElementById('rodaxNav');
        const btn = document.getElementById('mobileMenuBtn');
        if (nav && nav.classList.contains('active')) {
            if (!nav.contains(e.target) && !btn.contains(e.target)) {
                nav.classList.remove('active');
                if (btn) btn.classList.remove('active');
            }
        }
    });

    // Auto-dismiss alertas de sessão após 5 segundos
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // Drag and Drop para upload de imagens
    const dropzone = document.querySelector('.dropzone-form');
    if (dropzone) {
        const fileInput = dropzone.querySelector('input[type="file"]');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, () => dropzone.classList.add('highlight-drop'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, () => dropzone.classList.remove('highlight-drop'), false);
        });

        dropzone.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (fileInput && files.length > 0) {
                fileInput.files = files;
                const fileNotice = dropzone.querySelector('.file-count-notice') || document.createElement('div');
                fileNotice.className = 'file-count-notice margin-top-xs text-accent font-weight-bold';
                fileNotice.innerText = `✓ ${files.length} foto(s) selecionada(s) para envio!`;
                dropzone.appendChild(fileNotice);
            }
        }, false);
    }

    // Suporte para Enter enviar mensagem no chat
    const chatTextarea = document.querySelector('.chat-textarea');
    if (chatTextarea) {
        chatTextarea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                const form = this.closest('form');
                if (form && this.value.trim() !== '') {
                    form.submit();
                }
            }
        });
    }
});
