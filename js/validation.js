function isValidEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

function isValidURL(url) {
    try {
        const urlObj = new URL(url);
        return ['http:', 'https:'].includes(urlObj.protocol);
    } catch (_) {
        return false;
    }
}

function isValidPhone(phone) {
    const re = /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/;
    return re.test(String(phone));
}

function sanitizeInput(input) {
    if (typeof input !== 'string') return '';
    return input.trim()
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#x27;')
        .replace(/\//g, '&#x2F;');
}

function encodeHTML(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function validateFormFields(formId, validations) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        form.querySelectorAll('.error-feedback').forEach(el => {
            el.textContent = '';
        });
        
        for (const [fieldId, rules] of Object.entries(validations)) {
            const field = document.getElementById(fieldId);
            if (!field) continue;
            
            const value = sanitizeInput(field.value.trim());
            let errorMessage = '';
            
            if (rules.required && value === '') {
                errorMessage = rules.requiredMessage || 'This field is required';
            } 
            else if (rules.minLength && value.length < rules.minLength) {
                errorMessage = rules.minLengthMessage || `Minimum length is ${rules.minLength} characters`;
            } 
            else if (rules.maxLength && value.length > rules.maxLength) {
                errorMessage = rules.maxLengthMessage || `Maximum length is ${rules.maxLength} characters`;
            } 
            else if (rules.email && value !== '' && !isValidEmail(value)) {
                errorMessage = rules.emailMessage || 'Please enter a valid email address';
            } 
            else if (rules.url && value !== '' && !isValidURL(value)) {
                errorMessage = rules.urlMessage || 'Please enter a valid URL (http or https only)';
            } 
            else if (rules.phone && value !== '' && !isValidPhone(value)) {
                errorMessage = rules.phoneMessage || 'Please enter a valid phone number';
            } 
            else if (rules.pattern && value !== '' && !new RegExp(rules.pattern).test(value)) {
                errorMessage = rules.patternMessage || 'Invalid format';
            }
            else if (rules.custom && !rules.custom.validator(value)) {
                errorMessage = rules.custom.message || 'Invalid input';
            }
            
            if (errorMessage) {
                isValid = false;
                const errorElement = field.nextElementSibling;
                if (errorElement && errorElement.classList.contains('error-feedback')) {
                    errorElement.textContent = errorMessage;
                }
            }
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });
}

function validateLoginForm() {
    const loginForm = document.getElementById('loginForm');
    if (!loginForm) return;
    
    loginForm.addEventListener('submit', function(e) {
        let isValid = true;
        const usernameEmail = document.getElementById('username_email');
        const password = document.getElementById('password');
        
        document.querySelectorAll('.error-feedback').forEach(el => el.textContent = '');
        
        if (usernameEmail && usernameEmail.value.trim() === '') {
            const errorElement = usernameEmail.nextElementSibling;
            if (errorElement && errorElement.classList.contains('error-feedback')) {
                errorElement.textContent = 'Username or Email is required';
            }
            isValid = false;
        } else if (usernameEmail && usernameEmail.value.trim().length > 100) {
            const errorElement = usernameEmail.nextElementSibling;
            if (errorElement && errorElement.classList.contains('error-feedback')) {
                errorElement.textContent = 'Username or Email must be less than 100 characters';
            }
            isValid = false;
        }
        
        if (password && password.value === '') {
            const errorElement = password.nextElementSibling;
            if (errorElement && errorElement.classList.contains('error-feedback')) {
                errorElement.textContent = 'Password is required';
            }
            isValid = false;
        } else if (password && password.value.length > 100) {
            const errorElement = password.nextElementSibling;
            if (errorElement && errorElement.classList.contains('error-feedback')) {
                errorElement.textContent = 'Password is too long';
            }
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('contactForm')) {
        validateFormFields('contactForm', {
            'name': {
                required: true,
                requiredMessage: 'Please enter your name',
                maxLength: 100,
                maxLengthMessage: 'Name must be less than 100 characters',
                pattern: '^[a-zA-Z\\s]+$',
                patternMessage: 'Name should contain only letters and spaces'
            },
            'email': {
                required: true,
                requiredMessage: 'Please enter your email address',
                email: true,
                emailMessage: 'Please enter a valid email address',
                maxLength: 100,
                maxLengthMessage: 'Email must be less than 100 characters'
            },
            'subject': {
                required: true,
                requiredMessage: 'Please enter a subject',
                maxLength: 200,
                maxLengthMessage: 'Subject must be less than 200 characters'
            },
            'message': {
                required: true,
                requiredMessage: 'Please enter your message',
                minLength: 10,
                minLengthMessage: 'Your message should be at least 10 characters',
                maxLength: 1000,
                maxLengthMessage: 'Message must be less than 1000 characters'
            }
        });
    }
    
    validateLoginForm();
});

function setupSessionTimeout(timeoutMinutes = 30) {
    if (!document.querySelector('[data-logged-in="true"]')) return;
    
    let timeoutWarningShown = false;
    let inactivityTimeout;
    
    const resetTimer = function() {
        clearTimeout(inactivityTimeout);
        
        if (timeoutWarningShown) {
            const warningElement = document.getElementById('session-timeout-warning');
            if (warningElement) {
                warningElement.style.display = 'none';
                timeoutWarningShown = false;
            }
        }
        
        inactivityTimeout = setTimeout(function() {
            showTimeoutWarning();
        }, (timeoutMinutes - 5) * 60 * 1000);
    };
    
    const showTimeoutWarning = function() {
        timeoutWarningShown = true;
        
        let warningElement = document.getElementById('session-timeout-warning');
        if (!warningElement) {
            warningElement = document.createElement('div');
            warningElement.id = 'session-timeout-warning';
            warningElement.style.position = 'fixed';
            warningElement.style.top = '10px';
            warningElement.style.right = '10px';
            warningElement.style.backgroundColor = '#fff3cd';
            warningElement.style.color = '#856404';
            warningElement.style.padding = '15px';
            warningElement.style.borderRadius = '5px';
            warningElement.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';
            warningElement.style.zIndex = '9999';
            warningElement.innerHTML = `
                <p><strong>Warning:</strong> Your session will expire in 5 minutes due to inactivity.</p>
                <button id="extend-session" style="background:#4a9fe9;color:white;border:none;padding:5px 10px;border-radius:3px;cursor:pointer;">
                    Extend Session
                </button>
            `;
            document.body.appendChild(warningElement);
            
            document.getElementById('extend-session').addEventListener('click', function() {
                fetch('refresh_session.php', {
                    method: 'POST',
                    credentials: 'same-origin'
                }).then(response => {
                    if (response.ok) {
                        warningElement.style.display = 'none';
                        timeoutWarningShown = false;
                        resetTimer();
                    }
                }).catch(error => {
                    console.error('Error refreshing session:', error);
                });
            });
        } else {
            warningElement.style.display = 'block';
        }
        
        setTimeout(function() {
            window.location.href = 'logout.php?timeout=1';
        }, 5 * 60 * 1000);
    };
    
    ['mousemove', 'keypress', 'load', 'mousedown', 'touchstart', 'click', 'scroll'].forEach(event => {
        document.addEventListener(event, resetTimer, false);
    });
    
    resetTimer();
}

document.addEventListener('DOMContentLoaded', function() {
    setupSessionTimeout(30);
});
