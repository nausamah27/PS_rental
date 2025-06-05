// PlayStation Rental Website JavaScript

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize components
    initializeAlerts();
    initializeFormValidation();
    initializeDateInputs();
    initializeTableSorting();
});

// Alert Management
function initializeAlerts() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
}

// Form Validation
function initializeFormValidation() {
    // Password confirmation validation
    const confirmPasswordInputs = document.querySelectorAll('input[name="confirm_password"]');
    confirmPasswordInputs.forEach(input => {
        input.addEventListener('input', function() {
            const passwordInput = document.querySelector('input[name="password"]') || 
                                 document.querySelector('input[name="new_password"]');
            
            if (passwordInput && passwordInput.value !== this.value) {
                this.setCustomValidity('Password tidak sama');
            } else {
                this.setCustomValidity('');
            }
        });
    });

    // Phone number validation
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Remove non-numeric characters
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Validate Indonesian phone number format
            if (this.value.length > 0 && !this.value.match(/^(08|62)/)) {
                this.setCustomValidity('Nomor telepon harus dimulai dengan 08 atau 62');
            } else {
                this.setCustomValidity('');
            }
        });
    });

    // Price input formatting
    const priceInputs = document.querySelectorAll('input[name="price_per_day"]');
    priceInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Format as currency
            let value = this.value.replace(/[^0-9]/g, '');
            if (value) {
                this.value = parseInt(value).toLocaleString('id-ID');
            }
        });

        input.addEventListener('blur', function() {
            // Convert back to number for form submission
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    });
}

// Date Input Management
function initializeDateInputs() {
    const today = new Date().toISOString().split('T')[0];
    
    // Set minimum date for start date inputs
    const startDateInputs = document.querySelectorAll('input[name="start_date"]');
    startDateInputs.forEach(input => {
        input.min = today;
        
        input.addEventListener('change', function() {
            const endDateInput = document.querySelector('input[name="end_date"]');
            if (endDateInput) {
                const selectedDate = new Date(this.value);
                const minEndDate = new Date(selectedDate);
                minEndDate.setDate(minEndDate.getDate() + 1);
                endDateInput.min = minEndDate.toISOString().split('T')[0];
                
                // Clear end date if it's before the new start date
                if (endDateInput.value && new Date(endDateInput.value) <= selectedDate) {
                    endDateInput.value = '';
                }
            }
        });
    });
}

// Table Sorting
function initializeTableSorting() {
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        const headers = table.querySelectorAll('th');
        headers.forEach((header, index) => {
            if (header.textContent.trim() && !header.querySelector('button')) {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => sortTable(table, index));
            }
        });
    });
}

function sortTable(table, columnIndex) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    // Determine sort direction
    const header = table.querySelectorAll('th')[columnIndex];
    const isAscending = !header.classList.contains('sort-desc');
    
    // Remove existing sort classes
    table.querySelectorAll('th').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
    });
    
    // Add new sort class
    header.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
    
    // Sort rows
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        // Try to parse as numbers
        const aNum = parseFloat(aValue.replace(/[^0-9.-]/g, ''));
        const bNum = parseFloat(bValue.replace(/[^0-9.-]/g, ''));
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return isAscending ? aNum - bNum : bNum - aNum;
        }
        
        // Sort as strings
        return isAscending ? 
            aValue.localeCompare(bValue) : 
            bValue.localeCompare(aValue);
    });
    
    // Reorder rows in DOM
    rows.forEach(row => tbody.appendChild(row));
}

// Utility Functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('id-ID');
}

// Confirmation dialogs
function confirmDelete(message = 'Apakah Anda yakin ingin menghapus item ini?') {
    return confirm(message);
}

// Loading states
function showLoading(element) {
    const originalText = element.textContent;
    element.textContent = 'Loading...';
    element.disabled = true;
    
    return function hideLoading() {
        element.textContent = originalText;
        element.disabled = false;
    };
}

// AJAX Helper
function makeRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    return fetch(url, { ...defaultOptions, ...options })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        });
}

// Console rental price calculator
function calculateRentalPrice(pricePerDay, startDate, endDate) {
    if (!startDate || !endDate) return 0;
    
    const start = new Date(startDate);
    const end = new Date(endDate);
    
    if (end <= start) return 0;
    
    const timeDiff = end.getTime() - start.getTime();
    const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
    
    return daysDiff * pricePerDay;
}

// Export functions for global use
window.PlayStationRental = {
    formatCurrency,
    formatDate,
    confirmDelete,
    showLoading,
    makeRequest,
    calculateRentalPrice
};
