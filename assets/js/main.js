

document.addEventListener('DOMContentLoaded', function() {

    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {

            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            } else {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);
    });

    const deleteButtons = document.querySelectorAll('.confirm-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-message') || 'Are you sure you want to delete this item?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const phone = document.getElementById('phone').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }

            const phonePattern = /^[0-9]{10}$/;
            if (!phonePattern.test(phone.replace(/[\s-()]/g, ''))) {
                e.preventDefault();
                alert('Please enter a valid 10-digit phone number.');
                return false;
            }
        });
    }

    const searchInput = document.getElementById('searchFood');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const foodCards = document.querySelectorAll('.food-item-container');

            foodCards.forEach(card => {
                const title = card.querySelector('.food-card-title').textContent.toLowerCase();
                const desc = card.querySelector('.food-card-desc').textContent.toLowerCase();

                if (title.includes(filter) || desc.includes(filter)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
});
