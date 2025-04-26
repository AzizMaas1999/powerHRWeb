function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input, select, textarea');

    inputs.forEach(input => {
        const validation = input.getAttribute('data-validation');
        if (!validation) return;

        const rules = validation.split('|');
        const errorMsg = input.getAttribute('data-validation-error-msg');

        rules.forEach(rule => {
            const [ruleName, ruleValue] = rule.split(':');
            
            switch (ruleName) {
                case 'required':
                    if (!input.value.trim()) {
                        showError(input, errorMsg);
                        isValid = false;
                    } else {
                        showSuccess(input);
                    }
                    break;

                case 'min':
                    if (input.value.length < parseInt(ruleValue)) {
                        showError(input, errorMsg);
                        isValid = false;
                    } else {
                        showSuccess(input);
                    }
                    break;

                case 'max':
                    if (input.value.length > parseInt(ruleValue)) {
                        showError(input, errorMsg);
                        isValid = false;
                    } else {
                        showSuccess(input);
                    }
                    break;

                case 'email':
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(input.value)) {
                        showError(input, errorMsg);
                        isValid = false;
                    } else {
                        showSuccess(input);
                    }
                    break;

                case 'numeric':
                    if (!/^\d+$/.test(input.value)) {
                        showError(input, errorMsg);
                        isValid = false;
                    } else {
                        showSuccess(input);
                    }
                    break;
            }
        });
    });

    return isValid;
}

function showError(input, message) {
    input.classList.remove('is-valid');
    input.classList.add('is-invalid');
    
    let feedback = input.nextElementSibling;
    if (!feedback || !feedback.classList.contains('invalid-feedback')) {
        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        input.parentNode.insertBefore(feedback, input.nextSibling);
    }
    
    feedback.textContent = message;
    feedback.style.display = 'block';
}

function showSuccess(input) {
    input.classList.remove('is-invalid');
    input.classList.add('is-valid');
    
    const feedback = input.nextElementSibling;
    if (feedback && feedback.classList.contains('invalid-feedback')) {
        feedback.style.display = 'none';
    }
} 