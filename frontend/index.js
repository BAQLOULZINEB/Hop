const toggleButton = document.querySelector(".dashboard .title .toggle");More actions
const dash = document.querySelector(".dashboard");
const logo = document.querySelector(".dashboard .title .logo");
const title = document.querySelector(".dashboard .title h2");
const spans = document.querySelectorAll(".dashboard  span");
const list = document.querySelectorAll(".dashboard .list");
const tog = document.querySelectorAll(".dashboard .links li a .tog");
const doctorsListOne = document.querySelector(".dashboard .links .num1");
const firstList = document.querySelector(".dashboard .links .one");
const doctorsListTwo = document.querySelector(".dashboard .links .num2");
const secondList = document.querySelector(".dashboard .links .two");
const showHide = document.querySelector(".show-hide");
const closeButton = document.querySelector(".show-hide .close");
const incomeButton = document.querySelector(".income-celander .hospital-income .month-income .income-button");

// Variables du calendrier
const header = document.querySelector(".calendar h3");
const dates = document.querySelector(".dates");
const navs = document.querySelectorAll("#prev, #next");
const months = [
    "January", "February", "March", "April", "May", "June",
    "July", "August", "September", "October", "November", "December"
];
let date = new Date();
let month = date.getMonth();
let year = date.getFullYear();

// Fonction utilitaire pour la gestion des erreurs
function handleError(error, context) {
    // Only log errors to the console, never show notifications or modify the DOM
    console.error(`Erreur dans ${context}:`, error);
}

// Wrapper pour les fonctions avec gestion d'erreur
function withErrorHandling(fn, context) {
    return function(...args) {
        try {
            return fn.apply(this, args);
        } catch (error) {
            handleError(error, context);
        }
    };
}

// Gestionnaire du bouton toggle
toggleButton.addEventListener('click', withErrorHandling(() => {
    toggleButton.classList.toggle("toggled");

    if (toggleButton.classList.contains("toggled")) {
        dash.style.width = "50px"; 
        logo.style.display = "none";
        title.style.display = "none";
        spans.forEach((el) => {
            el.style.display = "none";
        });
        list.forEach((el) => {
            el.style.display = "none";
        });
        tog.forEach((el) => {
            el.style.display = "none";
        });
    } else {
        dash.style.width = "250px";
        logo.style.display = "block";
        title.style.display = "block";
        spans.forEach((el) => {
            el.style.display = "inline";
        });
        tog.forEach((el) => {
            el.style.display = "block";
        });
    }
}, 'toggleButton click handler'));

// Gestion des listes de médecins
if (doctorsListOne.classList.contains("active")) {
    firstList.style.display = "block";
    tog[0].classList.replace("fa-angle-right", "fa-angle-down");
}

if (doctorsListTwo.classList.contains("active")) {
    secondList.style.display = "block";
    tog[1].classList.replace("fa-angle-right", "fa-angle-down");
}

// Gestionnaires des boutons income et close
incomeButton.onclick = withErrorHandling(() => {
    showHide.style.display = "block";
}, 'income button click');



closeButton.onclick = withErrorHandling(() => {
    showHide.style.display = "none";
}, 'close button click');



// Fonction du calendrier
function renderCalendar() {
    try {
        const start = new Date(year, month, 1).getDay();
        const endDate = new Date(year, month + 1, 0).getDate();
        const end = new Date(year, month, endDate).getDay();
        const endDatePrev = new Date(year, month, 0).getDate();

        let datesHtml = "";

        for (let i = start; i > 0; i--) {
            datesHtml += `<li class="inactive">${endDatePrev - i + 1}</li>`;
        }

        for (let i = 1; i <= endDate; i++) {
            let className =
                i === date.getDate() &&
                month === new Date().getMonth() &&
                year === new Date().getFullYear()
                    ? ' class="today"'
                    : "";
            datesHtml += `<li${className}>${i}</li>`;
        }

        for (let i = end; i < 6; i++) {
            datesHtml += `<li class="inactive">${i - end + 1}</li>`;
        }

        dates.innerHTML = datesHtml;
        header.textContent = `${months[month]} ${year}`;
    } catch (error) {
        handleError(error, 'renderCalendar');
    }
}

// Gestionnaires d'événements du calendrier
navs.forEach((nav) => {
    nav.addEventListener("click", withErrorHandling((e) => {
        const btnId = e.target.id;

        if (btnId === "prev" && month === 0) {
            year--;
            month = 11;
        } else if (btnId === "next" && month === 11) {
            year++;
            month = 0;
        } else {
            month = btnId === "next" ? month + 1 : month - 1;
        }

        date = new Date(year, month, new Date().getDate());
        year = date.getFullYear();
        month = date.getMonth();

        renderCalendar();
    }, 'calendar navigation'));
});

// Initialisation du calendrier
renderCalendar();

// Gestionnaire DOMContentLoaded
document.addEventListener("DOMContentLoaded", withErrorHandling(() => {
    const dropdownToggle = document.getElementById("dropdownToggle");
    const profileDropdown = document.getElementById("profileDropdown");

    // Only execute dropdown logic if both elements exist
    if (dropdownToggle && profileDropdown) {
        dropdownToggle.addEventListener("click", withErrorHandling((event) => {
            event.stopPropagation();
            profileDropdown.style.display =
                profileDropdown.style.display === "block" ? "none" : "block";
        }, 'dropdown toggle'));

        document.addEventListener("click", withErrorHandling(() => {
            profileDropdown.style.display = "none";
        }, 'document click'));
    }

    const toggles = document.querySelectorAll(".listted");
    toggles.forEach((toggle) => {
        toggle.addEventListener("click", withErrorHandling((e) => {
            e.preventDefault();
            const submenu = toggle.nextElementSibling;
            if (submenu) {
                submenu.style.display = submenu.style.display === "block" ? "none" : "block";
            }
            toggles.forEach((otherToggle) => {
                if (otherToggle !== toggle) {
                    const otherSubmenu = otherToggle.nextElementSibling;
                    if (otherSubmenu) {
                        otherSubmenu.style.display = "none";
                    }
                }
            });
        }, 'submenu toggle'));
    });
}, 'DOMContentLoaded'));