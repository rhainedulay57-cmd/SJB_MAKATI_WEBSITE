function switchTab(tabKey, button) {
    var tabsContainer = button.closest('.booking-tabs');
    if (!tabsContainer) return;

    var targetId = tabKey + '-tab';
    var current = tabsContainer.nextElementSibling;

    while (current && !current.classList.contains('booking-tabs')) {
        if (current.classList.contains('tab-content')) {
            current.classList.toggle('active', current.id === targetId);
        }
        current = current.nextElementSibling;
    }

    tabsContainer.querySelectorAll('.tab-btn').forEach(function(btn) {
        btn.classList.toggle('active', btn === button);
    });
}

function submitSacramentForm(event) {
    event.preventDefault();
    var form = event.target;
    var formData = new FormData(form);

    fetch('submit-sacrament-booking.php', {
        method: 'POST',
        body: formData
    })
    .then(function(response) {
        return response.text().then(function(text) {
            if (!response.ok) {
                throw { status: response.status, statusText: response.statusText, body: text };
            }
            try {
                return JSON.parse(text);
            } catch (parseError) {
                throw { status: response.status, statusText: response.statusText, body: text, parseError: parseError };
            }
        });
    })
    .then(function(data) {
        if (data.success) {
            showSuccessModal(data.booking_id, formData.get('sacrament_type'));
            form.reset();
        } else {
            console.error('Booking submission returned error:', data);
            alert(data.message || 'Failed to submit sacrament booking.');
        }
    })
    .catch(function(error) {
        console.error('Booking submission failed:', error);
        if (error && error.body) {
            alert('Unable to submit sacrament booking. Server returned an error. Check console for details.');
        } else {
            alert('Unable to submit sacrament booking. Please try again later.');
        }
    });
}

function submitMassRequest(event) {
    event.preventDefault();
    var form = event.target;
    var formData = new FormData(form);

    fetch('mass-request-result.php', {
        method: 'POST',
        body: formData
    })
    .then(function(response) {
        return response.json();
    })
    .then(function(data) {
        if (data.success) {
            // use the existing success modal for consistency
            showSuccessModal(data.request_id, 'mass');
            form.reset();
        } else {
            alert(data.message || 'Failed to submit mass or blessing request.');
        }
    })
    .catch(function(error) {
        console.error(error);
        alert('Unable to submit mass or blessing request. Please try again later.');
    });
}

function showSuccessModal(bookingId, sacramentType) {
    var modal = document.getElementById('successModal');
    var details = document.getElementById('bookingDetails');
    if (details) {
        details.innerHTML = '<p><strong>Booking ID:</strong> ' + bookingId + '</p>' +
                            '<p><strong>Sacrament:</strong> ' + (sacramentType ? sacramentType.charAt(0).toUpperCase() + sacramentType.slice(1) : 'Sacrament') + '</p>';
    }
    if (modal) {
        // adjust title for mass requests vs sacrament bookings
        var titleEl = modal.querySelector('.success-modal h2');
        if (titleEl) {
            titleEl.textContent = sacramentType === 'mass' ? '✓ Request Submitted Successfully!' : '✓ Booking Submitted Successfully!';
        }
        modal.style.display = 'block';
    }
}

function closeModal() {
    var modal = document.getElementById('successModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Check sacrament booking status by POSTing to check-booking-status.php
function showStatusError(message) {
    var errorContainer = document.getElementById('status-error');
    var resultContainer = document.getElementById('status-result');
    if (errorContainer) {
        errorContainer.textContent = message || 'Unable to check booking status right now. Please try again later.';
        errorContainer.style.display = 'block';
    }
    if (resultContainer) {
        resultContainer.style.display = 'none';
    }
}

function clearStatusError() {
    var errorContainer = document.getElementById('status-error');
    if (errorContainer) {
        errorContainer.textContent = '';
        errorContainer.style.display = 'none';
    }
}

function checkBookingStatus() {
    var id = (document.getElementById('booking_id') || {}).value || '';
    var email = (document.getElementById('check_email') || {}).value || '';
    var resultContainer = document.getElementById('status-result');
    var details = document.getElementById('status-details');

    clearStatusError();
    if (resultContainer) {
        resultContainer.style.display = 'none';
    }
    if (details) {
        details.innerHTML = '';
    }

    if (!id || !email) {
        showStatusError('Please enter your Booking ID and email address.');
        return;
    }
    var formData = new FormData();
    formData.append('booking_id', id);
    formData.append('email', email);

    fetch('check-booking-status.php', {
        method: 'POST',
        body: formData
    })
    .then(function(res) {
        return res.json().then(function(data) {
            if (!res.ok) {
                throw data;
            }
            return data;
        });
    })
    .then(function(data) {
        if (!data || !data.success) {
            // Log server debug info when available to help troubleshooting
            if (data && data.debug_info) console.error('Status lookup debug:', data.debug_info);
            showStatusError(data && data.message ? data.message : 'Booking not found or email mismatch.');
            return;
        }

        if (resultContainer) {
            resultContainer.style.display = 'block';
        }
        if (details) {
            var b = data.booking || {};
            var formatLabel = function(value) {
                if (!value) return '';
                return value.charAt(0).toUpperCase() + value.slice(1);
            };
            details.innerHTML = '<p><strong>Booking ID:</strong> ' + (b.booking_id || id) + '</p>' +
                                '<p><strong>Sacrament:</strong> ' + formatLabel(b.sacrament_type || 'Unknown') + '</p>' +
                                '<p><strong>Status:</strong> ' + formatLabel(b.status || 'pending') + '</p>' +
                                '<p><strong>Admin Notes:</strong> ' + (b.admin_notes || 'None') + '</p>';
        }
    })
    .catch(function(err) {
        console.error(err);
        showStatusError((err && err.message) ? err.message : 'Unable to check booking status right now. Please try again later.');
    });
}

// Expose functions globally in case inline onclick handlers are used or scripts are loaded in non-standard contexts
window.checkBookingStatus = checkBookingStatus;
window.checkMassRequestStatus = checkMassRequestStatus;

// Check mass/blessing request status by querying get-details.php and verifying email
function checkMassRequestStatus() {
    var id = (document.getElementById('mass_request_id') || {}).value || '';
    var email = (document.getElementById('mass_check_email') || {}).value || '';
    if (!id || !email) {
        alert('Please enter your Request ID and email address.');
        return;
    }

    var url = 'get-details.php?type=mass&id=' + encodeURIComponent(id);
    console.log('Mass status lookup URL:', url);
    fetch(url)
    .then(function(res) {
        console.log('Mass status response status:', res.status);
        return res.json().catch(function(parseErr) {
            console.error('Failed to parse JSON from get-details.php', parseErr);
            throw parseErr;
        });
    })
    .then(function(data) {
        console.log('Mass status response data:', data);
        if (!data || !data.email) {
            alert('Request not found. Please verify the Request ID.');
            return;
        }
        if ((data.email || '').toLowerCase().trim() !== email.toLowerCase().trim()) {
            alert('Email does not match the request. Please verify your email and Request ID.');
            return;
        }
        var container = document.getElementById('mass-status-result');
        var details = document.getElementById('mass-status-details');
        container.style.display = 'block';
        details.innerHTML = '<p><strong>Request ID:</strong> ' + (data.id || id) + '</p>' +
                    '<p><strong>Status:</strong> ' + (data.status || 'pending') + '</p>' +
                    '<p><strong>Contact Person:</strong> ' + (data.contact_person || '') + '</p>' +
                    '<p><strong>Mobile:</strong> ' + (data.mobile_no || '') + '</p>' +
                    '<p><strong>Intention:</strong> ' + (data.intention || '') + '</p>';
    })
    .catch(function(err) {
        console.error('Mass status lookup error:', err);
        alert('Unable to check request status right now. Please try again later.');
    });
}
