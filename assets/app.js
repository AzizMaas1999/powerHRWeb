import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

// --- SMS Code Verification Modal Logic ---
document.addEventListener('DOMContentLoaded', function() {
    const sendBtn = document.getElementById('send-verification-btn');
    const verifyBtn = document.getElementById('verify-code-btn');
    const modal = document.getElementById('codeVerificationModal');
    const codeInput = document.getElementById('verification-code-input');
    const feedback = document.getElementById('code-verification-feedback');
    const successDiv = document.getElementById('code-verification-success');
    let phoneValue = '';

    // Helper to show modal (Bootstrap 4/5 compatible)
    function showModal() {
        console.log('[DEBUG] showModal called');
        if (typeof $ !== 'undefined' && $(modal).modal) {
            console.log('[DEBUG] Using Bootstrap modal');
            $(modal).modal('show');
        } else {
            console.log('[DEBUG] Bootstrap modal not found, using fallback');
            modal.style.display = 'block';
            modal.classList.add('show');
            modal.style.background = 'rgba(0,0,0,0.5)';
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100vw';
            modal.style.height = '100vh';
            modal.style.zIndex = '9999';
        }
        codeInput.value = '';
        feedback.style.display = 'none';
        successDiv.style.display = 'none';
        codeInput.focus();
    }
    function hideModal() {
        if (typeof $ !== 'undefined' && $(modal).modal) {
            $(modal).modal('hide');
        } else {
            modal.style.display = 'none';
            modal.classList.remove('show');
        }
    }

    // Helper: format Tunisian numbers as E.164
    function formatTunisianPhone(phone) {
        phone = phone.trim().replace(/\D/g, '');
        // If already starts with 216 and is 11 digits, add plus
        if (phone.startsWith('216') && phone.length === 11) return '+' + phone;
        // If 8 digits, assume local and add +216
        if (phone.length === 8) return '+216' + phone;
        // If already starts with +216, return as is
        if (phone.startsWith('+216') && phone.length === 12) return phone;
        // Otherwise, return as is (let backend handle)
        return phone;
    }

    // Send code
    if (sendBtn) {
        sendBtn.addEventListener('click', function() {
            const phoneInput = document.getElementById('entreprise_phone_number');
            phoneValue = phoneInput ? phoneInput.value : '';
            // Format as E.164 for Tunisia
            phoneValue = formatTunisianPhone(phoneValue);
            console.log('[DEBUG] Envoyer le code clicked, phone:', phoneValue);
            // --- DEBUG: Always show modal for testing ---
            showModal();
            // --- END DEBUG ---
            /*
            // Uncomment below for production logic
            if (!phoneValue || phoneValue.length < 8) {
                document.getElementById('send-status').textContent = 'Numéro invalide';
                return;
            }
            // Call API to send code (replace URL with actual endpoint)
            fetch('/api/sms/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ to: phoneValue })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('send-status').textContent = 'Code envoyé !';
                    showModal();
                } else {
                    document.getElementById('send-status').textContent = data.message || 'Erreur lors de l\'envoi';
                }
            })
            .catch(() => {
                document.getElementById('send-status').textContent = 'Erreur réseau';
            });
            */
        });
    }

    // Verify code
    if (verifyBtn) {
        verifyBtn.addEventListener('click', function() {
            const code = codeInput.value.trim();
            // Always use E.164 for verify
            const formattedPhone = formatTunisianPhone(phoneValue);
            if (!code || code.length < 4) {
                feedback.textContent = 'Code invalide';
                feedback.style.display = 'block';
                successDiv.style.display = 'none';
                return;
            }
            // Call API to verify code (replace URL with actual endpoint)
            fetch('/api/sms/verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ to: formattedPhone, code: code })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    feedback.style.display = 'none';
                    successDiv.style.display = 'block';
                    setTimeout(hideModal, 1200);
                } else {
                    feedback.textContent = data.message || 'Code incorrect';
                    feedback.style.display = 'block';
                    successDiv.style.display = 'none';
                }
            })
            .catch(() => {
                feedback.textContent = 'Erreur réseau';
                feedback.style.display = 'block';
                successDiv.style.display = 'none';
            });
        });
    }
});
// --- END SMS Code Verification Modal Logic ---

