// Sélection des éléments du formulaire
const loginForm = document.querySelector('form.login');
const signupForm = document.querySelector('form.signup');
const loginInputs = loginForm.querySelectorAll('input[type="text"], input[type="password"]');
const signupInputs = signupForm.querySelectorAll('input[type="text"], input[type="password"]');
const dateNaissanceInput = signupForm.querySelector('input[name="date_naissance"]');

// Fonction de validation des champs
function validateField(input, type) {
    const value = input.value.trim();
    let isValid = true;
    let errorMessage = '';

    switch (type) {
        case 'email':
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            isValid = emailRegex.test(value);
            errorMessage = 'Veuillez entrer une adresse email valide';
            break;
        case 'password':
            isValid = value.length >= 6;
            errorMessage = 'Le mot de passe doit contenir au moins 6 caractères';
            break;
        default:
            isValid = value.length > 0;
            errorMessage = 'Ce champ est requis';
    }

    return { isValid, errorMessage };
}

// Fonction pour afficher les erreurs
function showError(input, message) {
    const field = input.parentElement;
    const errorDiv = field.querySelector('.error-message') || document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.innerHTML = message;
    errorDiv.style.color = '#ff4444';
    errorDiv.style.fontSize = '14px';
    errorDiv.style.marginTop = '5px';
    errorDiv.style.marginLeft = '10px';
    errorDiv.style.textAlign = 'left';
    errorDiv.style.fontWeight = 'bold';
    errorDiv.style.position = 'static';
    errorDiv.style.width = 'auto';
    errorDiv.style.whiteSpace = 'normal';
    errorDiv.style.overflow = 'visible';
    errorDiv.style.textOverflow = 'unset';

    if (!field.querySelector('.error-message')) {
        field.appendChild(errorDiv);
    }

    input.style.borderColor = '#ff4444';
}

// Fonction pour effacer les erreurs
function clearError(input) {
    const field = input.parentElement;
    const errorDiv = field.querySelector('.error-message');
    if (errorDiv) {
        errorDiv.remove();
    }
    input.style.borderColor = '';
}

// Gestionnaire de soumission du formulaire de connexion
loginForm.addEventListener('submit', function(e) {
    e.preventDefault();
    let isValid = true;

    loginInputs.forEach(input => {
        clearError(input);
        const type = input.type === 'password' ? 'password' : 'email';
        const validation = validateField(input, type);
        
        if (!validation.isValid) {
            showError(input, validation.errorMessage);
            isValid = false;
        }
    });

    if (isValid) {
        try {
            // Ici, vous ajouteriez votre logique de connexion réelle
            console.log('Tentative de connexion...');
            // Exemple de gestion d'erreur de connexion
            throw new Error('Email ou mot de passe incorrect');
        } catch (error) {
            showError(loginInputs[0], error.message);
        }
    }
});

// Gestionnaire de soumission du formulaire d'inscription
signupForm.addEventListener('submit', function(e) {
    e.preventDefault();
    let isValid = true;

    signupInputs.forEach(input => {
        clearError(input);
        const type = input.type === 'password' ? 'password' : 'email';
        const validation = validateField(input, type);
        
        if (!validation.isValid) {
            showError(input, validation.errorMessage);
            isValid = false;
        }
    });

    // Vérifier si les mots de passe correspondent
    if (isValid && signupInputs[1].value !== signupInputs[2].value) {
        showError(signupInputs[2], 'Les mots de passe ne correspondent pas');
        isValid = false;
    }

    // Custom date validation
    if (dateNaissanceInput) {
        const selectedDate = dateNaissanceInput.value;
        const today = new Date().toISOString().split('T')[0];
        if (!selectedDate) {
            showError(dateNaissanceInput, 'Please select a date of birth.');
            e.preventDefault();
            return;
        }
        if (selectedDate === today) {
            showError(dateNaissanceInput, 'Date of birth cannot be today.');
            e.preventDefault();
            return;
        }
    }

    if (isValid) {
        try {
            // Ici, vous ajouteriez votre logique d'inscription réelle
            console.log('Tentative d\'inscription...');
            // Exemple de gestion d'erreur d'inscription
            throw new Error('Cette adresse email est déjà utilisée');
        } catch (error) {
            showError(signupInputs[0], error.message);
        }
    }
});

// Only apply custom domain check for signup form
function setupRealTimeValidation(inputs, isSignup = false) {
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            clearError(input);
            let type = input.type === 'password' ? 'password' : 'email';
            let validation = validateField(input, type);

            if (
                isSignup &&
                input.name === 'email'
            ) {
                const value = input.value.trim();
                // Show the error as soon as there's an @ and the domain is not allowed
                if (value.includes('@') && !isAllowedDomain(value)) {
                    showError(
                        input,
                        "Invalid email format. Please use <b>@admin.com</b> for admin, <b>@med.com</b> for doctor, or <b>@pat.com</b> for patient"
                    );
                } else if (!validation.isValid) {
                    showError(input, validation.errorMessage);
                }
            } else if (!validation.isValid) {
                showError(input, validation.errorMessage);
            }
        });

        input.addEventListener('blur', function() {
            clearError(input);
            const type = input.type === 'password' ? 'password' : 'email';
            const validation = validateField(input, type);

            if (!validation.isValid) {
                showError(input, validation.errorMessage);
            }
        });
    });
}

// Apply to login and signup separately
setupRealTimeValidation(loginInputs, false);
setupRealTimeValidation(signupInputs, true);

// Gestion des erreurs de serveur
function handleServerError(error) {
    // Only log to console, never show notification
    console.error('Server error:', error);
}

function isAllowedDomain(email) {
    return (
        email.endsWith('@admin.com') ||
        email.endsWith('@med.com') ||
        email.endsWith('@pat.com')
    );
}

document.addEventListener('DOMContentLoaded', function() {
    const signupEmailInput = document.querySelector('.signup input[name="email"]');
    const specialityField = document.querySelector('.speciality-field');
    const specialitySelect = document.querySelector('.speciality-field select');
    const dateNaissanceField = document.querySelector('.date-naissance-field');
    const dateNaissanceInput = document.querySelector('.date-naissance-field input[name="date_naissance"]');

    function toggleSpecialityField() {
        const email = signupEmailInput ? signupEmailInput.value.trim() : '';

        if (email.endsWith('@med.com')) {
            if (specialityField) {
                specialityField.style.display = 'block';
                specialitySelect && specialitySelect.setAttribute('required', 'required');
            }
            if (dateNaissanceField) {
                dateNaissanceField.style.display = 'none';
                dateNaissanceInput && dateNaissanceInput.removeAttribute('required');
            }
        } else if (email.endsWith('@pat.com')) {
            if (specialityField) {
                specialityField.style.display = 'none';
                specialitySelect && specialitySelect.removeAttribute('required');
            }
            if (dateNaissanceField) {
                dateNaissanceField.style.display = 'block';
                dateNaissanceInput && dateNaissanceInput.setAttribute('required', 'required');
            }
        } else {
            if (specialityField) {
                specialityField.style.display = 'none';
                specialitySelect && specialitySelect.removeAttribute('required');
            }
            if (dateNaissanceField) {
                dateNaissanceField.style.display = 'none';
                dateNaissanceInput && dateNaissanceInput.removeAttribute('required');
            }
        }
    }

    if (signupEmailInput) {
        toggleSpecialityField();
        signupEmailInput.addEventListener('input', toggleSpecialityField);
        signupEmailInput.addEventListener('change', toggleSpecialityField);
    }
}); 