/**
 * Customer Driver Location Viewer
 * Displays real-time driver location on a map for customers
 */
class CustomerLocationViewer {
    constructor() {
        this.map = null;
        this.driverMarker = null;
        this.routePolyline = null;
        this.updateInterval = 10000; // 10 seconds
        this.intervalId = null;
        this.currentBookingId = null;
        this.apiBaseUrl = window.location.origin + '/bmove-v2/controller/location';
        this.locationHistory = [];
        
        this.init();
    }
    
    init() {
        this.currentBookingId = this.getBookingId();
        
        if (!this.currentBookingId) {
            this.showError('Unable to get booking information.');
            return;
        }
        
        this.initMap();
        this.startLocationUpdates();
    }
    
    initMap() {
        // Initialize Leaflet map
        this.map = L.map('driver-location-map').setView([14.5995, 120.9842], 13); // Default to Manila
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(this.map);
        
        // Create custom driver marker icon
        const driverIcon = L.divIcon({
            className: 'driver-marker',
            html: '<div class="driver-marker-content"><i class="bi bi-truck-fill"></i></div>',
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });
        
        this.driverMarker = L.marker([0, 0], { icon: driverIcon }).addTo(this.map);
        
        // Create route polyline
        this.routePolyline = L.polyline([], {
            color: '#007bff',
            weight: 3,
            opacity: 0.7
        }).addTo(this.map);
    }
    
    async startLocationUpdates() {
        // Get initial location
        await this.updateDriverLocation();
        
        // Set up periodic updates
        this.intervalId = setInterval(() => {
            this.updateDriverLocation();
        }, this.updateInterval);
        
        this.updateUI('loading');
    }
    
    stopLocationUpdates() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }
    
    async updateDriverLocation() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/get-location.php?booking_id=${this.currentBookingId}`);
            const result = await response.json();
            
            if (result.status === 'success') {
                this.displayDriverLocation(result.data);
            } else {
                this.showError('Failed to get driver location: ' + result.message);
            }
            
        } catch (error) {
            console.error('Failed to update driver location:', error);
            this.showError('Failed to connect to location service.');
        }
    }
    
    displayDriverLocation(data) {
        const location = data.location;
        const driver = data.driver;
        const booking = data.booking;
        const tracking = data.tracking;
        
        // Update driver marker position
        if (location.latitude && location.longitude) {
            const latLng = [location.latitude, location.longitude];
            
            this.driverMarker.setLatLng(latLng);
            
            // Update marker popup
            this.driverMarker.bindPopup(`
                <div class="driver-popup">
                    <h6><i class="bi bi-truck me-2"></i>${driver.name}</h6>
                    <p class="mb-1"><strong>Phone:</strong> ${driver.phone}</p>
                    <p class="mb-1"><strong>Status:</strong> ${booking.status}</p>
                    <p class="mb-1"><strong>Last Update:</strong> ${new Date(location.timestamp).toLocaleString()}</p>
                    ${location.speed ? `<p class="mb-0"><strong>Speed:</strong> ${(location.speed * 3.6).toFixed(1)} km/h</p>` : ''}
                </div>
            `);
            
            // Pan map to driver location
            this.map.panTo(latLng);
            
            // Update route history
            this.updateRouteHistory(data.route_history);
        }
        
        // Update UI elements
        this.updateDriverInfo(driver, booking, tracking);
        this.updateLocationInfo(location);
        
        // Update tracking status
        this.updateTrackingStatus(tracking);
        
        // Show the map and hide loading indicator
        this.updateUI('loaded');
    }
    
    updateRouteHistory(routeHistory) {
        if (routeHistory && routeHistory.length > 0) {
            const routePoints = routeHistory.map(point => [point.lat, point.lng]);
            this.routePolyline.setLatLngs(routePoints);
            
            // Fit map to show entire route
            if (routePoints.length > 1) {
                const group = new L.featureGroup([this.routePolyline, this.driverMarker]);
                this.map.fitBounds(group.getBounds().pad(0.1));
            }
        }
    }
    
    updateDriverInfo(driver, booking, tracking) {
        const driverNameElement = document.getElementById('driver-name');
        const driverPhoneElement = document.getElementById('driver-phone');
        const bookingStatusElement = document.getElementById('booking-status');
        const sessionStatusElement = document.getElementById('session-status');
        
        if (driverNameElement) driverNameElement.textContent = driver.name;
        if (driverPhoneElement) driverPhoneElement.textContent = driver.phone;
        if (bookingStatusElement) bookingStatusElement.textContent = booking.status;
        if (sessionStatusElement) sessionStatusElement.textContent = tracking.session_status;
    }
    
    updateLocationInfo(location) {
        const latElement = document.getElementById('driver-lat');
        const lngElement = document.getElementById('driver-lng');
        const accuracyElement = document.getElementById('location-accuracy');
        const speedElement = document.getElementById('driver-speed');
        const lastUpdateElement = document.getElementById('last-location-update');
        
        if (latElement) latElement.textContent = location.latitude.toFixed(6);
        if (lngElement) lngElement.textContent = location.longitude.toFixed(6);
        if (accuracyElement) accuracyElement.textContent = location.accuracy ? location.accuracy.toFixed(2) + 'm' : 'N/A';
        if (speedElement) speedElement.textContent = location.speed ? (location.speed * 3.6).toFixed(2) + ' km/h' : 'N/A';
        if (lastUpdateElement) lastUpdateElement.textContent = new Date(location.timestamp).toLocaleString();
        
        // Update status indicator
        const statusIndicator = document.getElementById('location-status');
        if (statusIndicator) {
            if (location.is_recent) {
                statusIndicator.textContent = 'Live';
                statusIndicator.className = 'badge bg-success';
            } else {
                statusIndicator.textContent = 'Stale';
                statusIndicator.className = 'badge bg-warning';
            }
        }
    }
    
    updateTrackingStatus(tracking) {
        const trackingStatusElement = document.getElementById('tracking-status');
        const startedAtElement = document.getElementById('tracking-started');
        const distanceElement = document.getElementById('total-distance');
        
        if (trackingStatusElement) {
            trackingStatusElement.textContent = tracking.session_status;
            trackingStatusElement.className = `badge bg-${tracking.session_status === 'active' ? 'success' : 'secondary'}`;
        }
        
        if (startedAtElement) startedAtElement.textContent = new Date(tracking.started_at).toLocaleString();
        if (distanceElement) distanceElement.textContent = tracking.total_distance.toFixed(2) + ' km';
    }
    
    updateUI(state) {
        const loadingElement = document.getElementById('loading-indicator');
        const mapElement = document.getElementById('driver-location-map');
        
        switch (state) {
            case 'loading':
                if (loadingElement) loadingElement.style.display = 'block';
                if (mapElement) mapElement.style.display = 'none';
                break;
                
            case 'loaded':
                if (loadingElement) loadingElement.style.display = 'none';
                if (mapElement) mapElement.style.display = 'block';
                break;
                
            case 'error':
                if (loadingElement) loadingElement.style.display = 'none';
                if (mapElement) mapElement.style.display = 'none';
                break;
        }
    }
    
    getBookingId() {
        // Try to get booking ID from various sources
        const urlParams = new URLSearchParams(window.location.search);
        const bookingId = urlParams.get('booking_id') || 
                         document.getElementById('booking-id')?.value ||
                         document.querySelector('[data-booking-id]')?.dataset.bookingId;
        
        return bookingId;
    }
    
    showError(message) {
        this.showNotification(message, 'error');
        this.updateUI('error');
    }
    
    showSuccess(message) {
        this.showNotification(message, 'success');
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
    
    // Public method to refresh location manually
    refreshLocation() {
        this.updateDriverLocation();
    }
    
    // Public method to stop updates
    destroy() {
        this.stopLocationUpdates();
        if (this.map) {
            this.map.remove();
        }
    }
}

// Initialize location viewer when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.customerLocationViewer = new CustomerLocationViewer();
});
