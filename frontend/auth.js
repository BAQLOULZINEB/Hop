// Sélection des éléments du formulaire
const loginForm = document.querySelector('form.login');
const signupForm = document.querySelector('form.signup');
const loginInputs = loginForm.querySelectorAll('input[type="text"], input[type="password"]');
const signupInputs = signupForm.querySelectorAll('input[type="text"], input[type="password"]');

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
    errorDiv.textContent = message;
    errorDiv.style.color = '#ff4444';
    errorDiv.style.fontSize = '14px';
    errorDiv.style.marginTop = '5px';
    errorDiv.style.marginLeft = '10px';
    errorDiv.style.textAlign = 'left';
    errorDiv.style.fontWeight = 'bold';
    
    field.style.position = 'relative';
    errorDiv.style.position = 'absolute';
    errorDiv.style.bottom = '-20px';
    errorDiv.style.left = '10px';
    errorDiv.style.width = 'calc(100% - 20px)';
    errorDiv.style.whiteSpace = 'nowrap';
    errorDiv.style.overflow = 'hidden';
    errorDiv.style.textOverflow = 'ellipsis';

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

// Validation en temps réel
function setupRealTimeValidation(inputs) {
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            clearError(input);
            const type = input.type === 'password' ? 'password' : 'email';
            const validation = validateField(input, type);
            
            if (!validation.isValid) {
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

// Appliquer la validation en temps réel aux deux formulaires
setupRealTimeValidation(loginInputs);
setupRealTimeValidation(signupInputs);

// Gestion des erreurs de serveur
function handleServerError(error) {
    const errorNotification = document.createElement('div');
    errorNotification.className = 'server-error-notification';
    errorNotification.innerHTML = `
        <div class="error-content">
            <i class="fas fa-exclamation-circle"></i>
            <span>${error.message}</span>
            <button class="close-error">&times;</button>
        </div>
    `;

    // Style pour la notification d'erreur serveur
    const style = document.createElement('style');
    style.textContent = `
        .server-error-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #ff4444;
            color: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
            animation: slideIn 0.5s ease-out;
        }
        .server-error-notification .error-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .server-error-notification .close-error {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 20px;
            padding: 0 5px;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
    `;
    document.head.appendChild(style);
    document.body.appendChild(errorNotification);

    // Gérer la fermeture de la notification
    const closeButton = errorNotification.querySelector('.close-error');
    closeButton.addEventListener('click', () => {
        errorNotification.remove();
    });

    // Supprimer automatiquement après 5 secondes
    setTimeout(() => {
        errorNotification.remove();
    }, 5000);
} 