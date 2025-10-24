/**
 * This script handles the frontend shortcode app.
 * It's loaded by class-sms-shortcode.php
 * * It receives localized data from WordPress via the `sms_app_data` object.
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // Check if the app container and data exist
    const app = document.getElementById('skate-app-container');
    if (!app || typeof sms_app_data === 'undefined') {
        // console.error('Skate App container or data not found.');
        return;
    }

    // --- STATE ---
    let currentView = 'dashboard';
    let html5QrCode = null;
    
    // Get data from WordPress
    const { ajaxUrl, nonce, currentAgentId, currentAgentName, logoutUrl, adminUrlSkates, adminUrlSkatemates } = sms_app_data;

    // --- SELECTORS ---
    const content = app.querySelector('#sms-app-content');
    const tabs = app.querySelectorAll('.sms-tab');
    const views = {
        dashboard: app.querySelector('#sms-view-dashboard'),
        scan: app.querySelector('#sms-view-scan'),
    };
    const modal = app.querySelector('#sms-modal');
    const modalTitle = app.querySelector('#sms-modal-title');
    const modalBody = app.querySelector('#sms-modal-body');
    const modalFooter = app.querySelector('#sms-modal-footer');
    const modalMessage = app.querySelector('#sms-modal-message');

    const qrReaderEl = app.querySelector('#sms-qr-reader');
    const scanResultEl = app.querySelector('#sms-scan-result');
    const stopScanBtn = app.querySelector('#sms-stop-scan-btn');

    // --- INITIALIZE UI ---
    app.querySelector('#sms-welcome-message').textContent = `Welcome, ${currentAgentName}!`;
    app.querySelector('#sms-logout-link').href = logoutUrl;
    app.querySelector('#sms-manage-skates-link').href = adminUrlSkates;
    app.querySelector('#sms-manage-skatemates-link').href = adminUrlSkatemates;

    // --- TABS & NAVIGATION ---
    tabs.forEach(tab => {
        if(tab.tagName.toLowerCase() === 'button') {
            tab.addEventListener('click', () => changeView(tab.dataset.tab));
        }
    });

    function changeView(viewName) {
        if (!views[viewName]) return;
        currentView = viewName;

        // Update tabs
        tabs.forEach(tab => {
            if (tab.dataset.tab === viewName) {
                tab.classList.add('active');
            } else {
                tab.classList.remove('active');
            }
        });

        // Update views
        for (const key in views) {
            if (key === viewName) {
                views[key].classList.remove('sms-hidden');
            } else {
                views[key].classList.add('sms-hidden');
            }
        }

        // Handle scanner logic
        if (viewName === 'scan') {
            startScanner();
        } else {
            stopScanner();
        }
    }

    // --- AJAX HELPER ---
    async function doAjax(action, data = {}) {
        const formData = new FormData();
        formData.append('action', 'sms_' + action);
        formData.append('nonce', nonce); // Use nonce from localized data
        for (const key in data) {
            formData.append(key, data[key]);
        }

        try {
            const response = await fetch(ajaxUrl, { // Use ajaxUrl from localized data
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.data.message || 'Unknown error occurred');
            }
            return result.data;

        } catch (error) {
            console.error('AJAX Error:', error);
            showModalMessage(error.message, 'error');
            throw error; // Re-throw to stop promise chain
        }
    }
    
    // --- DASHBOARD ---
    const refreshBtn = app.querySelector('#sms-refresh-dashboard');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', fetchDashboardData);
    }

    async function fetchDashboardData() {
        try {
            setLoading(true);
            const data = await doAjax('get_dashboard_data');
            
            // Skates
            app.querySelector('#stat-skates-total').textContent = data.skates.total;
            app.querySelector('#stat-skates-available').textContent = data.skates.available;
            app.querySelector('#stat-skates-rented').textContent = data.skates.rented;
            app.querySelector('#stat-skates-maintenance').textContent = data.skates.maintenance;

            // Skatemates
            app.querySelector('#stat-skatemates-total').textContent = data.skatemates.total;
            app.querySelector('#stat-skatemates-available').textContent = data.skatemates.available;
            app.querySelector('#stat-skatemates-rented').textContent = data.skatemates.rented;
            app.querySelector('#stat-skatemates-maintenance').textContent = data.skatemates.maintenance;

            // Lists
            const skateList = app.querySelector('#list-skates');
            skateList.innerHTML = '';
            if (data.skate_list.length > 0) {
                data.skate_list.forEach(item => {
                    skateList.innerHTML += `<li>${item.title} - <span class="font-bold">${item.status}</span></li>`;
                });
            } else {
                skateList.innerHTML = '<li>No skates found.</li>';
            }

            const skatemateList = app.querySelector('#list-skatemates');
            skatemateList.innerHTML = '';
             if (data.skatemate_list.length > 0) {
                data.skatemate_list.forEach(item => {
                    skatemateList.innerHTML += `<li>${item.title} - <span class="font-bold">${item.status}</span></li>`;
                });
            } else {
                skatemateList.innerHTML = '<li>No skatemates found.</li>';
            }

        } catch (error) {
            // Error already shown by doAjax
        } finally {
            setLoading(false);
        }
    }

    function setLoading(isLoading) {
        if(refreshBtn) {
            refreshBtn.disabled = isLoading;
            refreshBtn.textContent = isLoading ? 'Loading...' : 'Refresh Data';
        }
    }

    // --- QR SCANNER ---
    function startScanner() {
        if (html5QrCode && html5QrCode.isScanning) {
            return;
        }
        
        // Check if the element exists
        if (!qrReaderEl) return;
        
        html5QrCode = new Html5Qrcode("sms-qr-reader");
        const config = { fps: 10, qrbox: { width: 250, height: 250 } };
        
        const onScanSuccess = (decodedText, decodedResult) => {
            // Handle the scanned code
            scanResultEl.textContent = `Scanned: ${decodedText}`;
            stopScanner();
            fetchItemByQr(decodedText);
        };

        const onScanFailure = (error) => {
            // console.warn(`QR scan failure: ${error}`);
        };
        
        html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess, onScanFailure)
            .then(() => {
                scanResultEl.textContent = 'Scanner started. Point at a QR code.';
                stopScanBtn.classList.remove('sms-hidden');
            })
            .catch(err => {
                 scanResultEl.textContent = `Error starting scanner: ${err}. Try again.`;
                 console.error(err);
            });
    }

    function stopScanner() {
        if (html5QrCode && html5QrCode.isScanning) {
            html5QrCode.stop()
                .then(() => {
                    scanResultEl.textContent = 'Scanner stopped.';
                    stopScanBtn.classList.add('sms-hidden');
                })
                .catch(err => console.error('Failed to stop scanner', err));
        }
    }
    stopScanBtn.addEventListener('click', stopScanner);

    async function fetchItemByQr(qrCode) {
        try {
            const item = await doAjax('get_item_by_qr', { qr_code: qrCode });
            openModal(item);
        } catch (error) {
            scanResultEl.textContent = `Error: ${error.message}`;
        }
    }

    // --- MODAL ---
    function openModal(item) {
        modalMessage.textContent = '';
        modalMessage.classList.remove('text-red-400', 'text-green-400');
        
        // Populate item data
        app.querySelector('#sms-modal-item-name').textContent = item.title;
        app.querySelector('#sms-modal-item-qr').textContent = item.qr_code;
        app.querySelector('#sms-modal-item-notes').textContent = item.notes || 'No notes on file.';

        const statusEl = app.querySelector('#sms-modal-item-status');
        statusEl.textContent = item.status.charAt(0).toUpperCase() + item.status.slice(1);
        statusEl.className = 'text-lg font-bold px-3 py-1 rounded-full ';
        if (item.status === 'available') {
            statusEl.classList.add('bg-green-600', 'text-white');
        } else if (item.status === 'rented') {
            statusEl.classList.add('bg-yellow-600', 'text-white');
        } else {
            statusEl.classList.add('bg-red-600', 'text-white');
        }

        // Show correct form
        app.querySelector('#sms-form-rent').classList.add('sms-hidden');
        app.querySelector('#sms-form-return').classList.add('sms-hidden');
        app.querySelector('#sms-form-maintenance').classList.add('sms-hidden');
        app.querySelector('#sms-form-complete-maintenance').classList.add('sms-hidden');

        // Clear textareas
        app.querySelector('#sms-rent-notes').value = '';
        app.querySelector('#sms-maintenance-notes').value = '';
        app.querySelector('#sms-complete-maintenance-notes').value = '';
        
        // Clear and add buttons
        modalFooter.innerHTML = '';
        
        if (item.status === 'available') {
            app.querySelector('#sms-form-rent').classList.remove('sms-hidden');
            modalFooter.innerHTML += `<button data-action="rent" class="sms-button sms-button-green">Rent Item</button>`;
            modalFooter.innerHTML += `<button data-action="maintenance" class="sms-button sms-button-yellow">Mark for Maintenance</button>`;
        } else if (item.status === 'rented') {
            app.querySelector('#sms-form-return').classList.remove('sms-hidden');
            modalFooter.innerHTML += `<button data-action="return" class="sms-button sms-button-primary">Return Item</button>`;
            modalFooter.innerHTML += `<button data-action="maintenance" class="sms-button sms-button-yellow">Mark Damaged / Maintenance</button>`;
        } else if (item.status === 'maintenance') {
            app.querySelector('#sms-form-complete-maintenance').classList.remove('sms-hidden');
            modalFooter.innerHTML += `<button data-action="complete_maintenance" class="sms-button sms-button-green">Complete Maintenance</button>`;
        }

        modalFooter.innerHTML += `<button data-action="close" class="sms-button sms-button-gray">Close</button>`;

        // Attach item data to modal for actions
        modal.dataset.itemId = item.id;
        modal.dataset.itemType = item.type;
        modal.dataset.itemNotes = item.notes || '';
        
        modal.classList.remove('sms-hidden');
    }

    function closeModal() {
        modal.classList.add('sms-hidden');
        // If we were on scan tab, go back to dashboard
        if (currentView === 'scan') {
            changeView('dashboard');
        }
    }

    modal.addEventListener('click', (e) => {
        // Close on backdrop click
        if (e.target === modal) {
            closeModal();
        }
    });

    modalFooter.addEventListener('click', (e) => {
        const action = e.target.dataset.action;
        if (action) {
            handleModalAction(action);
        }
    });

    async function handleModalAction(action) {
        const itemId = modal.dataset.itemId;
        const itemType = modal.dataset.itemType;
        const oldNotes = modal.dataset.itemNotes;
        let newNotes = '';
        
        setModalLoading(true);

        try {
            switch (action) {
                case 'close':
                    closeModal();
                    break;
                
                case 'rent':
                    const rentNotes = app.querySelector('#sms-rent-notes').value.trim();
                    newNotes = formatNote(oldNotes, 'Pre-Rental Check', rentNotes);
                    
                    await doAjax('process_action', {
                        item_id: itemId,
                        new_status: 'rented',
                        notes: newNotes,
                        agent_id: currentAgentId,
                        agent_name: currentAgentName,
                        log_message: 'Item Rented.'
                    });
                    
                    showModalMessage('Item successfully rented!', 'success');
                    break;
                
                case 'return':
                     newNotes = formatNote(oldNotes, 'Return Inspection', 'Item returned and inspected. OK.');
                    
                    await doAjax('process_action', {
                        item_id: itemId,
                        new_status: 'available',
                        notes: newNotes,
                        agent_id: currentAgentId,
                        agent_name: currentAgentName,
                        log_message: 'Item Returned.'
                    });
                    showModalMessage('Item successfully returned!', 'success');
                    break;
                
                case 'maintenance':
                    const maintenanceNotes = app.querySelector('#sms-maintenance-notes').value.trim();
                    if (!maintenanceNotes) {
                        throw new Error('Maintenance notes are required.');
                    }
                    
                    newNotes = formatNote(oldNotes, 'Maintenance Log', maintenanceNotes);
                    
                    await doAjax('process_action', {
                        item_id: itemId,
                        new_status: 'maintenance',
                        notes: newNotes,
                        agent_id: currentAgentId,
                        agent_name: currentAgentName,
                        log_message: 'Marked for Maintenance.'
                    });
                    showModalMessage('Item marked for maintenance.', 'success');
                    break;
                
                case 'complete_maintenance':
                    const completeNotes = app.querySelector('#sms-complete-maintenance-notes').value.trim();
                     if (!completeNotes) {
                        throw new Error('Final repair notes are required.');
                    }

                    newNotes = formatNote(oldNotes, 'Repair Complete', completeNotes);

                    await doAjax('process_action', {
                        item_id: itemId,
                        new_status: 'available',
                        notes: newNotes,
                        agent_id: currentAgentId,
                        agent_name: currentAgentName,
                        log_message: 'Maintenance Completed.'
                    });
                    showModalMessage('Maintenance complete! Item is now available.', 'success');
                    break;
            }

            // On success (except close), refresh dashboard and close modal
            if(action !== 'close') {
                fetchDashboardData();
                setTimeout(closeModal, 1500);
            }

        } catch (error) {
            showModalMessage(error.message, 'error');
        } finally {
            setModalLoading(false);
        }
    }

    function formatNote(oldNotes, title, newNote) {
        if (!newNote) {
            return oldNotes; // No change
        }
        const timestamp = new Date().toLocaleString('en-US');
        const noteEntry = `\n------------------------------\n[${timestamp}] - ${title} (by ${currentAgentName})\n${newNote}\n------------------------------`;
        return oldNotes + noteEntry;
    }

    function setModalLoading(isLoading) {
        const buttons = modalFooter.querySelectorAll('button');
        buttons.forEach(btn => btn.disabled = isLoading);
        if (isLoading) {
            modalMessage.textContent = 'Processing...';
            modalMessage.className = 'text-center mt-4 text-blue-400';
        }
    }

    function showModalMessage(message, type = 'success') {
         modalMessage.textContent = message;
         if (type === 'success') {
             modalMessage.className = 'text-center mt-4 text-green-400';
         } else {
             modalMessage.className = 'text-center mt-4 text-red-400';
         }
    }

    // --- INITIALIZE ---
    changeView('dashboard');
    fetchDashboardData();
});
