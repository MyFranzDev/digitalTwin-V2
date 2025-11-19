// Main JavaScript for Butyrate Pathway Explorer

// API call helper
async function apiCall(endpoint, data = {}) {
    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        console.error('API call failed:', error);
        throw error;
    }
}

// Show loading indicator
function showLoading(element, message = 'Caricamento...') {
    element.innerHTML = `<div class="loading">${message}</div>`;
}

// Show error message
function showError(element, message) {
    element.innerHTML = `<div class="error">${message}</div>`;
}

// Format large numbers
function formatNumber(num) {
    return num.toLocaleString('it-IT');
}

// Debounce function for search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Initialize tooltips (if needed)
document.addEventListener('DOMContentLoaded', () => {
    console.log('Butyrate Pathway Explorer initialized');
});
