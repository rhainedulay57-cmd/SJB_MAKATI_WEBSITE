// Sacrament Booking System - Client-Side Logic

document.addEventListener('DOMContentLoaded', function() {
    attachFormHandlers();
});

function attachFormHandlers() {
    const weddingForm = document.getElementById('weddingForm');
    const baptismForm = document.getElementById('baptismForm');
    const funeralForm = document.getElementById('funeralForm');

    if (weddingForm) {
        weddingForm.addEventListener('submit', function (event) {
            handleBookingSubmit(event, 'wedding', weddingForm);
        });
    }

    if (baptismForm) {
        baptismForm.addEventListener('submit', function (event) {
            handleBookingSubmit(event, 'baptism', baptismForm);
        });
    }

    if (funeralForm) {
        funeralForm.addEventListener('submit', function (event) {
            handleBookingSubmit(event, 'funeral', funeralForm);
        });
    }
}

function handleBookingSubmit(event, type, form) {
    event.preventDefault();
    
    // Validate all required fields
    const requiredFields = form.querySelectorAll('[required]');
    let allFilled = true;
    
    for (let field of requiredFields) {
        const value = field.value.trim();
        if (!value) {
            allFilled = false;
            field.focus();
            field.style.borderColor = '#dc3545';
        } else {
            field.style.borderColor = '';
        }
    }

    if (!allFilled) {
        alert('Please fill in all required fields');
        return;
    }

    const formData = new FormData(form);
    formData.append('sacrament_type', type);
    submitBooking(formData, type, form);
}

function submitBooking(formData, type, form) {
    const submitBtn = form.querySelector('.submit-btn');
    const originalText = submitBtn ? submitBtn.textContent : 'Submitting...';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing...';
    }

    fetch('../PHP/submit-sacrament-booking.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessModal(data.booking_id, type);
            form.reset();
        } else {
            showErrorModal('Booking Error', data.message || 'An error occurred while submitting your booking.');
        }
    })
    .catch(error => {
        console.error('Booking submission error:', error);
        showErrorModal('Submission Error', 'An error occurred while submitting your booking. Please try again later.');
    })
    .finally(() => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
}

function showSuccessModal(bookingId, type) {
    const modal = document.getElementById('successModal');
    const bookingDetails = document.getElementById('bookingDetails');
    if (!modal || !bookingDetails) return;

    const typeLabel = {
        wedding: 'Wedding',
        baptism: 'Baptism',
        funeral: 'Funeral'
    }[type] || 'Sacrament';

    bookingDetails.innerHTML = `
        <div style="margin-bottom: 12px;">
            <strong>Booking ID:</strong> ${bookingId}
        </div>
        <div style="margin-bottom: 12px;">
            <strong>Sacrament:</strong> ${typeLabel}
        </div>
        <div style="margin-bottom: 12px;">
            <strong>Status:</strong> Pending Admin Approval
        </div>
        <div style="font-size: 0.95em; color: #555;">
            Admin will review your booking and respond within 24 hours.
        </div>
    `;

    modal.style.display = 'block';
}

function showErrorModal(title, message) {
    const modal = document.getElementById('successModal');
    if (!modal) return;

    const modalContent = modal.querySelector('.modal-content');
    modalContent.className = 'modal-content';
    modalContent.innerHTML = `
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 style="color: #e74c3c;">${title}</h2>
        <p>${message}</p>
        <button class="submit-btn" onclick="closeModal()" style="background: #e74c3c;">Close</button>
    `;

    modal.style.display = 'block';
}

function closeModal() {
    const modal = document.getElementById('successModal');
    if (!modal) return;

    modal.innerHTML = `
        <div class="modal-content success-modal">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>✓ Booking Submitted Successfully!</h2>
            <p>Your booking has been submitted to our admin team for approval.</p>
            <div id="bookingDetails" class="booking-confirmation"></div>
            <p style="margin-top: 20px; font-weight: bold;">Keep your <strong>Booking ID</strong> for reference. You will receive an email confirmation shortly.</p>
            <button class="submit-btn" onclick="closeModal()">Close</button>
        </div>
    `;
    modal.style.display = 'none';
}

function switchTab(tabName, button) {
    const tabs = document.querySelectorAll('.tab-content');
    const buttons = document.querySelectorAll('.tab-btn');

    tabs.forEach(tab => tab.classList.remove('active'));
    buttons.forEach(btn => btn.classList.remove('active'));

    const selectedTab = document.getElementById(`${tabName}-tab`);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }

    if (button) {
        button.classList.add('active');
    }
}

function checkBookingStatus() {
    const bookingId = document.getElementById('booking_id').value.trim();
    const email = document.getElementById('check_email').value.trim();

    if (!bookingId || !email) {
        alert('Please enter both Booking ID and Email Address');
        return;
    }

    fetch('../PHP/check-booking-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `booking_id=${encodeURIComponent(bookingId)}&email=${encodeURIComponent(email)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.booking) {
            displayStatusResult(data.booking);
        } else {
            alert(data.message || 'Booking not found. Please check your Booking ID and Email Address.');
        }
    })
    .catch(error => {
        console.error('Status check error:', error);
        alert('An error occurred while checking your booking status. Please try again later.');
    });
}

function displayStatusResult(booking) {
    const resultDiv = document.getElementById('status-result');
    const statusDetails = document.getElementById('status-details');
    if (!resultDiv || !statusDetails) return;

    const statusColor = {
        pending: '#fff3cd',
        approved: '#d4edda',
        declined: '#f8d7da',
        completed: '#d1ecf1'
    }[booking.status] || '#e9ecef';

    const statusTextColor = {
        pending: '#856404',
        approved: '#155724',
        declined: '#721c24',
        completed: '#0c5460'
    }[booking.status] || '#000';

    const sacramentLabel = {
        wedding: '💍 Wedding',
        baptism: '👶 Baptism',
        funeral: '🙏 Funeral'
    }[booking.sacrament_type] || 'Sacrament';

    statusDetails.innerHTML = `
        <div style="padding: 20px; background-color: ${statusColor}; border-radius: 8px;">
            <p><strong>Booking ID:</strong> ${booking.booking_id}</p>
            <p><strong>Sacrament Type:</strong> ${sacramentLabel}</p>
            <p><strong>Status:</strong> <span style="color: ${statusTextColor}; font-weight: bold;">${booking.status.toUpperCase()}</span></p>
            <p><strong>Submitted:</strong> ${new Date(booking.created_at).toLocaleString()}</p>
            ${booking.admin_notes ? `<p><strong>Admin Notes:</strong> ${booking.admin_notes}</p>` : ''}
        </div>
    `;

    resultDiv.style.display = 'block';
}

window.switchTab = switchTab;
window.checkBookingStatus = checkBookingStatus;
window.checkMassRequestStatus = checkMassRequestStatus;
window.closeModal = closeModal;
window.submitMassRequest = submitMassRequest;

function submitMassRequest(event) {
    event.preventDefault();
    
    const form = document.getElementById('mass_req_form');
    
    // Validate that at least one service is selected
    const serviceCheckboxes = form.querySelectorAll('input[name="service[]"]');
    const serviceChecked = Array.from(serviceCheckboxes).some(cb => cb.checked);
    if (!serviceChecked) {
        alert('Please select at least one service (Mass or Blessing)');
        return;
    }

    // Validate that at least one mass type is selected
    const massTypeCheckboxes = form.querySelectorAll('input[name="mass_type[]"]');
    const massTypeChecked = Array.from(massTypeCheckboxes).some(cb => cb.checked);
    if (!massTypeChecked) {
        alert('Please select at least one type of mass (Outside or Onsite)');
        return;
    }

    // Validate all other required fields
    const requiredFields = form.querySelectorAll('[required]');
    let allFilled = true;
    for (let field of requiredFields) {
        if (!field.value.trim()) {
            allFilled = false;
            field.focus();
            field.style.borderColor = '#dc3545';
            break;
        }
    }

    if (!allFilled) {
        alert('Please fill in all required fields');
        return;
    }

    const submitBtn = form.querySelector('.submit-btn');
    const originalText = submitBtn ? submitBtn.textContent : 'Submitting...';
    
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing...';
    }

    const formData = new FormData(form);

    fetch('../PHP/mass-request-result.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMassSuccessModal(data.request_id);
            form.reset();
            // Reset border colors
            requiredFields.forEach(field => field.style.borderColor = '');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Submission error:', error);
        alert('An error occurred while submitting your request. Please try again later.');
    })
    .finally(() => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
}

function showMassSuccessModal(requestId) {
    const modal = document.getElementById('successModal');
    if (!modal) {
        // Create modal if it doesn't exist
        const newModal = document.createElement('div');
        newModal.id = 'successModal';
        newModal.innerHTML = `
            <div class="modal-content success-modal">
                <span class="close" onclick="closeMassModal()">&times;</span>
                <h2>✓ Request Submitted Successfully!</h2>
                <p>Your mass or blessing request has been submitted to our admin team for review.</p>
                <div id="massRequestDetails" class="booking-confirmation"></div>
                <p style="margin-top: 20px; font-weight: bold;">Keep your <strong>Request ID</strong> for reference. You can use it to check your request status anytime.</p>
                <button class="submit-btn" onclick="closeMassModal()">Close</button>
            </div>
        `;
        document.body.appendChild(newModal);
    }

    const requestDetails = document.getElementById('massRequestDetails') || 
                          document.querySelector('#successModal .booking-confirmation');
    
    if (requestDetails) {
        requestDetails.innerHTML = `
            <div style="margin-bottom: 12px;">
                <strong>Request ID:</strong> ${requestId}
            </div>
            <div style="margin-bottom: 12px; font-size: 0.95em; color: #555;">
                Admin will review your request and respond within 24 hours.
            </div>
        `;
    }

    const modalElement = document.getElementById('successModal');
    if (modalElement) {
        modalElement.style.display = 'block';
    }
}

function closeMassModal() {
    const modal = document.getElementById('successModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function checkMassRequestStatus() {
    const requestId = document.getElementById('mass_request_id').value.trim();
    const email = document.getElementById('mass_check_email').value.trim();

    if (!requestId || !email) {
        alert('Please enter both Request ID and Email Address');
        return;
    }

    fetch('../PHP/get-details.php?type=mass&id=' + encodeURIComponent(requestId), {
        method: 'GET'
    })
    .then(response => response.json())
    .then(data => {
        if (data && data.id) {
            // Verify email matches
            if (data.email && data.email.toLowerCase() === email.toLowerCase()) {
                displayMassStatusResult(data);
            } else {
                alert('Request ID and Email Address do not match. Please check and try again.');
            }
        } else {
            alert('Request not found. Please check your Request ID and Email Address.');
        }
    })
    .catch(error => {
        console.error('Status check error:', error);
        alert('An error occurred while checking your request status. Please try again later.');
    });
}

function displayMassStatusResult(request) {
    const resultDiv = document.getElementById('mass-status-result');
    const statusDetails = document.getElementById('mass-status-details');
    if (!resultDiv || !statusDetails) return;

    const statusColor = {
        pending: '#fff3cd',
        approved: '#d4edda',
        declined: '#f8d7da'
    }[request.status] || '#e9ecef';

    const statusTextColor = {
        pending: '#856404',
        approved: '#155724',
        declined: '#721c24'
    }[request.status] || '#000';

    const serviceList = request.service ? (typeof request.service === 'string' ? request.service : request.service.join(', ')) : 'N/A';
    const massTypeList = request.mass_type ? (typeof request.mass_type === 'string' ? request.mass_type : request.mass_type.join(', ')) : 'N/A';

    statusDetails.innerHTML = `
        <div style="padding: 20px; background-color: ${statusColor}; border-radius: 8px; border-left: 4px solid ${statusTextColor};">
            <p><strong>Request ID:</strong> ${request.id}</p>
            <p><strong>Service:</strong> ${serviceList}</p>
            <p><strong>Mass Type:</strong> ${massTypeList}</p>
            <p><strong>Preferred Schedule:</strong> ${request.pref_sched || 'N/A'} at ${request.pref_time || 'N/A'}</p>
            <p><strong>Status:</strong> <span style="color: ${statusTextColor}; font-weight: bold; font-size: 1.1em;">${request.status.toUpperCase()}</span></p>
            <p style="margin-top: 15px; font-size: 0.9em; color: #666;">
                ${request.status === 'pending' ? 'Your request is being reviewed by our admin team. You will receive an email notification once it has been processed.' : ''}
                ${request.status === 'approved' ? 'Your request has been approved! Our team will contact you soon with further details.' : ''}
                ${request.status === 'declined' ? 'Unfortunately, your request could not be approved. Please contact us for more information.' : ''}
            </p>
        </div>
    `;

    resultDiv.style.display = 'block';
}
