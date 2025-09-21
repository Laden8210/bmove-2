/**
 * GPS Location Tracking for Drivers
 * Handles real-time location updates and tracking session management
 */
class DriverLocationTracker {
    constructor() {
        this.isTracking = false;
        this.watchId = null;
        this.updateInterval = 10000; // 10 seconds
        this.intervalId = null;
        this.currentBookingId = null;
        this.currentDriverId = null;
        this.lastLocation = null;
        this.apiBaseUrl = window.location.origin + '/bmove-v2/controller/location';
        
        this.init();
    }
    
    init() {
        this.checkPermissions();
        this.setupEventListeners();
    }
    
    checkPermissions() {
        if (!navigator.geolocation) {
            this.showError('Geolocation is not supported by this browser.');
            return false;
        }
        return true;
    }
    
    setupEventListeners() {
        // Start tracking button
        const startBtn = document.getElementById('start-tracking-btn');
        if (startBtn) {
            startBtn.addEventListener('click', () => this.startTracking());
        }
        
        // Stop tracking button
        const stopBtn = document.getElementById('stop-tracking-btn');
        if (stopBtn) {
            stopBtn.addEventListener('click', () => this.stopTracking());
        }
        
        // Pause tracking button
        const pauseBtn = document.getElementById('pause-tracking-btn');
        if (pauseBtn) {
            pauseBtn.addEventListener('click', () => this.pauseTracking());
        }
        
        // Resume tracking button
        const resumeBtn = document.getElementById('resume-tracking-btn');
        if (resumeBtn) {
            resumeBtn.addEventListener('click', () => this.resumeTracking());
        }
    }
    
    async startTracking() {
        if (this.isTracking) {
            this.showError('Tracking is already active.');
            return;
        }
        
        // Get booking ID from the page
        this.currentBookingId = this.getBookingId();
        this.currentDriverId = this.getDriverId();
        
        if (!this.currentBookingId || !this.currentDriverId) {
            this.showError('Unable to get booking or driver information.');
            return;
        }
        
        try {
            // Request location permission
            const position = await this.getCurrentPosition();
            
            // Start tracking session on server
            await this.controlTracking('start');
            
            // Start watching position
            this.watchId = navigator.geolocation.watchPosition(
                (position) => this.updateLocation(position),
                (error) => this.handleLocationError(error),
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 5000
                }
            );
            
            this.isTracking = true;
            this.updateUI('tracking');
            this.showSuccess('GPS tracking started successfully.');
            
        } catch (error) {
            this.showError('Failed to start tracking: ' + error.message);
        }
    }
    
    async stopTracking() {
        if (!this.isTracking) {
            this.showError('No active tracking session.');
            return;
        }
        
        try {
            // Stop tracking session on server
            await this.controlTracking('stop');
            
            // Stop watching position
            if (this.watchId) {
                navigator.geolocation.clearWatch(this.watchId);
                this.watchId = null;
            }
            
            this.isTracking = false;
            this.updateUI('stopped');
            this.showSuccess('GPS tracking stopped successfully.');
            
        } catch (error) {
            this.showError('Failed to stop tracking: ' + error.message);
        }
    }
    
    async pauseTracking() {
        if (!this.isTracking) {
            this.showError('No active tracking session.');
            return;
        }
        
        try {
            await this.controlTracking('pause');
            this.updateUI('paused');
            this.showSuccess('GPS tracking paused.');
            
        } catch (error) {
            this.showError('Failed to pause tracking: ' + error.message);
        }
    }
    
    async resumeTracking() {
        try {
            await this.controlTracking('resume');
            this.updateUI('tracking');
            this.showSuccess('GPS tracking resumed.');
            
        } catch (error) {
            this.showError('Failed to resume tracking: ' + error.message);
        }
    }
    
    async updateLocation(position) {
        if (!this.isTracking) return;
        
        const locationData = {
            driver_id: this.currentDriverId,
            booking_id: this.currentBookingId,
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
            speed: position.coords.speed,
            heading: position.coords.heading,
            altitude: position.coords.altitude
        };
        
        try {
            const response = await fetch(`${this.apiBaseUrl}/update-location.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(locationData)
            });
            
            const result = await response.json();
            
            if (result.status === 'success') {
                this.lastLocation = locationData;
                this.updateLocationDisplay(locationData);
            } else {
                console.error('Location update failed:', result.message);
            }
            
        } catch (error) {
            console.error('Failed to update location:', error);
        }
    }
    
    async controlTracking(action) {
        const response = await fetch(`${this.apiBaseUrl}/control-tracking.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                driver_id: this.currentDriverId,
                booking_id: this.currentBookingId,
                action: action
            })
        });
        
        const result = await response.json();
        
        if (result.status !== 'success') {
            throw new Error(result.message);
        }
        
        return result.data;
    }
    
    getCurrentPosition() {
        return new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(resolve, reject, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });
        });
    }
    
    handleLocationError(error) {
        let message = 'Location error: ';
        
        switch (error.code) {
            case error.PERMISSION_DENIED:
                message += 'Location access denied by user.';
                break;
            case error.POSITION_UNAVAILABLE:
                message += 'Location information unavailable.';
                break;
            case error.TIMEOUT:
                message += 'Location request timed out.';
                break;
            default:
                message += 'Unknown location error.';
                break;
        }
        
        this.showError(message);
    }
    
    updateUI(state) {
        const startBtn = document.getElementById('start-tracking-btn');
        const stopBtn = document.getElementById('stop-tracking-btn');
        const pauseBtn = document.getElementById('pause-tracking-btn');
        const resumeBtn = document.getElementById('resume-tracking-btn');
        const statusIndicator = document.getElementById('tracking-status');
        
        // Reset all buttons
        [startBtn, stopBtn, pauseBtn, resumeBtn].forEach(btn => {
            if (btn) btn.style.display = 'none';
        });
        
        switch (state) {
            case 'tracking':
                if (stopBtn) stopBtn.style.display = 'inline-block';
                if (pauseBtn) pauseBtn.style.display = 'inline-block';
                if (statusIndicator) {
                    statusIndicator.textContent = 'Tracking Active';
                    statusIndicator.className = 'badge bg-success';
                }
                break;
                
            case 'paused':
                if (resumeBtn) resumeBtn.style.display = 'inline-block';
                if (stopBtn) stopBtn.style.display = 'inline-block';
                if (statusIndicator) {
                    statusIndicator.textContent = 'Tracking Paused';
                    statusIndicator.className = 'badge bg-warning';
                }
                break;
                
            case 'stopped':
                if (startBtn) startBtn.style.display = 'inline-block';
                if (statusIndicator) {
                    statusIndicator.textContent = 'Tracking Stopped';
                    statusIndicator.className = 'badge bg-secondary';
                }
                break;
        }
    }
    
    updateLocationDisplay(location) {
        const latElement = document.getElementById('current-lat');
        const lngElement = document.getElementById('current-lng');
        const accuracyElement = document.getElementById('current-accuracy');
        const speedElement = document.getElementById('current-speed');
        const timestampElement = document.getElementById('last-update');
        
        if (latElement) latElement.textContent = location.latitude.toFixed(6);
        if (lngElement) lngElement.textContent = location.longitude.toFixed(6);
        if (accuracyElement) accuracyElement.textContent = location.accuracy ? location.accuracy.toFixed(2) + 'm' : 'N/A';
        if (speedElement) speedElement.textContent = location.speed ? (location.speed * 3.6).toFixed(2) + ' km/h' : 'N/A';
        if (timestampElement) timestampElement.textContent = new Date().toLocaleTimeString();
    }
    
    getBookingId() {
        // Try to get booking ID from various sources
        const urlParams = new URLSearchParams(window.location.search);
        const bookingId = urlParams.get('booking_id') || 
                         document.getElementById('booking-id')?.value ||
                         document.querySelector('[data-booking-id]')?.dataset.bookingId;
        
        return bookingId;
    }
    
    getDriverId() {
        // Try to get driver ID from various sources
        return document.getElementById('driver-id')?.value ||
               document.querySelector('[data-driver-id]')?.dataset.driverId ||
               window.currentDriverId;
    }
    
    showSuccess(message) {
        this.showNotification(message, 'success');
    }
    
    showError(message) {
        this.showNotification(message, 'error');
    }
    
    showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }
}

// Initialize GPS tracker when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.driverLocationTracker = new DriverLocationTracker();
});
