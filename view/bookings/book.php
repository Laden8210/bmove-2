<?php
if (!isset($_GET['car'])) {
    header('Location: vehicle_selection.php');
    exit();
}

$selectedVehicleId = $_GET['car'];
$stmt = $conn->prepare("SELECT * FROM vehicles WHERE vehicleid = ?");
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("s", $selectedVehicleId);
$stmt->execute();
$result = $stmt->get_result();
$selectedVehicle = $result->fetch_assoc();

if (!$selectedVehicle) {
    die("Selected vehicle not found.");
}

$selectedVehicle['baseprice'] = $selectedVehicle['baseprice'] ?? 0;
$selectedVehicle['rateperkm'] = $selectedVehicle['rateperkm'] ?? 0;
$selectedVehicle['totalcapacitykg'] = $selectedVehicle['totalcapacitykg'] ?? 0;

// retrieve all comments for the selected vehicle

$stmt = $conn->prepare("SELECT comments.*, users.full_name AS user_name FROM comments
 JOIN users ON comments.user_id = users.uid
 JOIN bookings ON comments.booking_id = bookings.booking_id
 WHERE bookings.vehicle_id = ?");
$stmt->bind_param("s", $selectedVehicleId);
$stmt->execute();
$result = $stmt->get_result();
$comments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();



?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />


<style>
    :root {
        --primary: #4e73df;
        --secondary: #6c757d;
        --success: #1cc88a;
        --info: #36b9cc;
        --warning: #f6c23e;
        --danger: #e74a3b;
        --light: #f8f9fc;
        --dark: #5a5c69;
    }

    .booking-container {
        background: linear-gradient(135deg, #f8f9fc 0%, #e9ecef 100%);
        min-height: 100vh;
        padding: 30px 0;
    }

    .booking-header {
        background: linear-gradient(135deg, var(--primary) 0%, #224abe 100%);
        color: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        padding: 25px 30px;
        margin-bottom: 30px;
    }

    .booking-card {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 7px 30px rgba(58, 59, 69, 0.15);
        overflow: hidden;
        transition: transform 0.3s ease;
        height: 100%;
        padding: 25px;
    }

    .booking-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 40px rgba(58, 59, 69, 0.2);
    }

    .map-container {
        position: relative;
        overflow: hidden;
        border-radius: 12px;
        height: 400px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border: 1px solid #e0e0e0;
    }

    #map {
        height: 100%;
        width: 100%;
    }

    .price-estimate-card {
        background: rgba(78, 115, 223, 0.08);
        border-radius: 12px;
        padding: 20px;
        margin-top: 20px;
        border-left: 4px solid var(--primary);
    }

    .form-control:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    .form-label {
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 8px;
    }

    .vehicle-info-card {
        background: rgba(78, 115, 223, 0.08);
        border-radius: 12px;
        padding: 20px;
        margin: 25px 0;
        border-left: 4px solid var(--success);
    }

    .current-location-checkbox {
        margin: 15px 0;
    }

    .location-marker {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }

    .marker-icon {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        color: white;
        font-weight: bold;
    }

    .marker-start {
        background-color: var(--success);
    }

    .marker-end {
        background-color: var(--danger);
    }

    .info-card {
        background: rgba(78, 115, 223, 0.05);
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
    }

    .progress-container {
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        margin-top: 5px;
        overflow: hidden;
    }

    .progress-bar {
        height: 100%;
        background: var(--primary);
        border-radius: 4px;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary) 0%, #224abe 100%);
        border: none;
        padding: 12px 20px;
        font-weight: 600;
        border-radius: 10px;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(58, 59, 69, 0.2);
    }

    .section-title {
        position: relative;
        padding-bottom: 15px;
        margin-bottom: 20px;
    }

    .section-title:after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 60px;
        height: 4px;
        background: var(--primary);
        border-radius: 2px;
    }

    .info-icon {
        width: 24px;
        height: 24px;
        background: rgba(78, 115, 223, 0.1);
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
        color: var(--primary);
    }

    @media (max-width: 992px) {
        .map-container {
            height: 300px;
        }

        .booking-header {
            padding: 15px 20px;
        }
    }

    /* Leaflet customizations */
    .leaflet-container {
        background: #eef2f6;
    }

    .leaflet-control-container {
        position: absolute;
        bottom: 15px;
        right: 15px;
    }

    .leaflet-control {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .leaflet-bar a {
        border-bottom: 1px solid #eee;
        width: 36px;
        height: 36px;
        line-height: 36px;
        text-align: center;
        font-size: 18px;
        color: var(--dark);
    }

    .leaflet-bar a:hover {
        background-color: #f8f9fc;
    }

    .leaflet-top {
        top: 15px;
    }

    .leaflet-left {
        left: 15px;
    }

    .leaflet-right {
        right: 15px;
    }

    .leaflet-bottom {
        bottom: 15px;
    }

    .leaflet-popup-content {
        margin: 10px;
        font-size: 14px;
    }

    .leaflet-popup-content-wrapper {
        border-radius: 8px;
        box-shadow: 0 3px 12px rgba(0, 0, 0, 0.15);
    }

    .leaflet-tooltip {
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: 4px;
        padding: 5px 10px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .leaflet-routing-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 3px 12px rgba(0, 0, 0, 0.15);
        width: 300px;
        max-height: 400px;
        overflow: auto;
    }

    .leaflet-routing-alt {
        max-height: 300px;
        overflow: auto;
    }

    .leaflet-routing-geocoders input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-bottom: 8px;
    }

    .leaflet-routing-geocoders button {
        background: var(--primary);
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 4px;
        cursor: pointer;
        width: 100%;
    }

    .leaflet-routing-geocoders button:hover {
        background: #3a5cc7;
    }

    .leaflet-routing-error {
        color: var(--danger);
        padding: 8px 12px;
    }

    .geocoder-control-suggestions {
        max-height: 200px;
        overflow-y: auto;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        position: absolute;
        width: calc(100% - 30px);
        margin-top: 2px;
    }

    .geocoder-control-suggestion {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
    }

    .geocoder-control-suggestion:last-child {
        border-bottom: none;
    }

    .geocoder-control-suggestion:hover {
        background-color: #f5f5f5;
    }

    .position-relative {
        position: relative;
    }

    .autocomplete-suggestions {
        max-height: 200px;
        overflow-y: auto;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        position: absolute;
        width: calc(100% - 30px);
        margin-top: 2px;
    }

    .autocomplete-suggestion {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
    }

    .autocomplete-suggestion:last-child {
        border-bottom: none;
    }

    .autocomplete-suggestion:hover {
        background-color: #f5f5f5;
    }

    .position-relative {
        position: relative;
    }

    .autocomplete-loading {
        padding: 8px 12px;
        color: #6c757d;
        font-style: italic;
    }
</style>
</head>

<body>
    <div class="booking-container">
        <div class="container">
            <div class="booking-header text-center">
                <h1 class="display-5 fw-bold mb-3"><i class="bi bi-geo-alt-fill me-2"></i>Complete Your Booking</h1>
                <p class="lead mb-0">Fill in the details below to schedule your vehicle move</p>
            </div>

            <div class="row g-4">
                <!-- Map Section -->
                <div class="col-lg-7">
                    <div class="booking-card">
                        <h3 class="section-title"><i class="bi bi-map me-2"></i>Select Locations</h3>

                        <div class="location-marker">
                            <div class="marker-icon marker-start">A</div>
                            <div>Pickup Location</div>
                        </div>
                        <div class="location-marker">
                            <div class="marker-icon marker-end">B</div>
                            <div>Drop-off Location</div>
                        </div>

                        <div class="map-container">
                            <div id="map"></div>
                        </div>

                        <div class="price-estimate-card">
                            <div class="row">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <div class="d-flex justify-content-between">
                                        <span><i class="bi bi-signpost info-icon"></i>Estimated Distance:</span>
                                        <strong id="distance-display">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mt-3">
                                        <span><i class="bi bi-speedometer2 info-icon"></i>Estimated Duration:</span>
                                        <strong id="duration-display">-</strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between">
                                        <span><i class="bi bi-currency-dollar info-icon"></i>Estimated Price:</span>
                                        <strong id="price-estimate">₱0.00</strong>
                                    </div>
                                    <div class="mt-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Base Price:</span>
                                            <span>₱<?= number_format($selectedVehicle['baseprice'], 2) ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Distance Rate:</span>
                                            <span>₱<?= number_format($selectedVehicle['rateperkm'], 2) ?>/km</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <h3 class="section-title mt-4"><i class="bi bi-chat-dots me-2"></i>Comments</h3>
                        <div id="comments-container">
                            <?php if (!empty($comments)): ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="comment mb-3 p-3 rounded shadow-sm bg-light">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="fw-semibold me-2"><?= htmlspecialchars($comment['user_name']) ?></span>
                                            <span class="badge bg-warning text-dark me-2">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= (int)$comment['comment_rating']): ?>
                                                        <i class="bi bi-star-fill"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-star"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </span>
                                            <small class="text-muted ms-auto">
                                                <?= isset($comment['created_at']) ? date('M d, Y', strtotime($comment['created_at'])) : '' ?>
                                            </small>
                                        </div>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted fst-italic">No comments yet for this vehicle.</p>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

                <!-- Booking Form Section -->
                <div class="col-lg-5">
                    <div class="booking-card">
                        <h3 class="section-title"><i class="bi bi-calendar-check me-2"></i>Booking Details</h3>

                        <form method="post" action="controller/booking/create-booking.php" id="create-booking-form" class="needs-validation" novalidate>
                            <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($selectedVehicleId) ?>">
                            <input type="hidden" id="pickup_lat" name="pickup_lat" required>
                            <input type="hidden" id="pickup_lng" name="pickup_lng" required>
                            <input type="hidden" id="dropoff_lat" name="dropoff_lat" required>
                            <input type="hidden" id="dropoff_lng" name="dropoff_lng" required>

                            <div class="info-card">
                                <h5 class="mb-3"><i class="bi bi-car-front me-2"></i>Selected Vehicle</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($selectedVehicle['name']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($selectedVehicle['platenumber']) ?> | <?= htmlspecialchars($selectedVehicle['type']) ?></small>
                                    </div>
                                    <div class="text-end">
                                        <div>Capacity: <?= htmlspecialchars($selectedVehicle['totalcapacitykg']) ?>kg</div>
                                        <small class="text-muted">Base: ₱<?= number_format($selectedVehicle['baseprice'], 2) ?></small>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="pickup" class="form-label"><i class="bi bi-geo-alt me-1"></i>Pickup Location</label>
                                <input type="text" id="pickup" name="pickup_location" class="form-control form-control-lg"
                                    placeholder="Enter pickup address" required>
                                <div class="invalid-feedback">Please select a pickup location</div>

                                <div class="form-check current-location-checkbox">
                                    <input class="form-check-input" type="checkbox" id="useCurrentLocation">
                                    <label class="form-check-label" for="useCurrentLocation">
                                        <i class="bi bi-geo me-1"></i>Use my current location for pickup
                                    </label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="dropoff" class="form-label"><i class="bi bi-geo-alt-fill me-1"></i>Drop-off Location</label>
                                <input type="text" id="dropoff" name="dropoff_location" class="form-control form-control-lg"
                                    placeholder="Enter drop-off address" required>
                                <div class="invalid-feedback">Please select a drop-off location</div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="date" class="form-label"><i class="bi bi-calendar me-1"></i>Move Date</label>
                                    <input type="date" id="date" name="date" class="form-control form-control-lg"
                                        min="<?= date('Y-m-d') ?>" required>
                                    <div class="invalid-feedback">Please select a valid date</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="time" class="form-label"><i class="bi bi-clock me-1"></i>Preferred Time</label>
                                    <input type="time" id="time" name="time" class="form-control form-control-lg"
                                        min="08:00" max="20:00" required>
                                    <div class="invalid-feedback">Please select time between 8AM-8PM</div>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="total_weight" class="form-label"><i class="bi bi-box-seam me-1"></i>Total Weight (kg)</label>
                                    <input type="number" id="total_weight" name="total_weight"
                                        class="form-control form-control-lg" min="1"
                                        max="<?= htmlspecialchars($selectedVehicle['totalcapacitykg']) ?>"
                                        required>
                                    <div class="d-flex justify-content-between mt-2">
                                        <small class="instruction-text">Max capacity: <?= htmlspecialchars($selectedVehicle['totalcapacitykg']) ?>kg</small>
                                        <small id="weight-percentage">0%</small>
                                    </div>
                                    <div class="progress-container mt-1">
                                        <div class="progress-bar" id="weight-progress" style="width: 0%"></div>
                                    </div>
                                    <div class="invalid-feedback">Weight exceeds vehicle capacity or is invalid.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="items_count" class="form-label"><i class="bi bi-boxes me-1"></i>Number of Items</label>
                                    <input type="number" id="items_count" name="items_count"
                                        class="form-control form-control-lg" min="1" required>
                                    <div class="invalid-feedback">Please enter item count</div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="notes"><i class="bi bi-pencil me-1"></i>Notes</label>
                                <textarea id="notes" name="notes" class="form-control form-control-lg" rows="3"
                                    placeholder="Any special instructions or notes"></textarea>
                            </div>

                            <div class="mb-4">
                                <label for="payment_method" class="form-label"><i class="bi bi-credit-card me-1"></i>Payment Method</label>
                                <select id="payment_method" name="payment_method" class="form-select form-select-lg" required>
                                    <option value="" disabled selected>Select payment method</option>
                          
                                    <option value="gcash">GCash</option>
                                    <option value="maya">Maya</option>
                                    <option value="cash">Cash on Delivery (COD)</option>
                                </select>
                                <div class="invalid-feedback">Please select a payment method</div>
                            </div>

                            <input type="hidden" id="total_price" name="total_price" class="form-control">
                            <input type="hidden" id="total_distance" name="total_distance" class="form-control">

                            <button type="submit" class="btn btn-primary btn-lg w-100 mt-2 py-3 fw-bold" id="submit-btn">
                                <i class="bi bi-check-circle me-2"></i>Confirm Booking
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet & Routing Libraries -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

    <script>
        const createRequest = new CreateRequest({
            formSelector: "#create-booking-form",
            submitButtonSelector: "#submit-btn",
            callback: (err, res) => err ? console.error("Form submission error:", err) : console.log(
                "Form submitted successfully:", res)
        });

        const vehicleBasePrice = parseFloat(<?= json_encode($selectedVehicle['baseprice']) ?>);
        const vehicleRatePerKm = parseFloat(<?= json_encode($selectedVehicle['rateperkm']) ?>);
        const vehicleMaxCapacity = parseInt(<?= json_encode($selectedVehicle['totalcapacitykg']) ?>);
        const geocoder = L.Control.Geocoder.nominatim();


        const OPENCAGE_API_KEY = '5246506e7d3141cbaaab53d198f6de47';
        let map, pickupMarker, dropoffMarker, routingControl;


        function setupAutocomplete(inputId) {
            const input = document.getElementById(inputId);
            const container = document.createElement('div');
            container.className = 'position-relative';
            input.parentNode.insertBefore(container, input);
            container.appendChild(input);

            const suggestionsContainer = document.createElement('div');
            suggestionsContainer.className = 'autocomplete-suggestions';
            suggestionsContainer.style.display = 'none';
            container.appendChild(suggestionsContainer);

            let currentController = null;
            let selectedIndex = -1;
            let suggestions = [];

            // Function to show suggestions
            function showSuggestions() {
                suggestionsContainer.style.display = 'block';
            }

            // Function to hide suggestions
            function hideSuggestions() {
                suggestionsContainer.style.display = 'none';
                selectedIndex = -1;
            }

            // Function to select suggestion
            function selectSuggestion(index) {
                if (index >= 0 && index < suggestions.length) {
                    const result = suggestions[index];
                    input.value = result.formatted;
                    hideSuggestions();

                    // Set coordinates
                    document.getElementById(`${inputId}_lat`).value = result.geometry.lat;
                    document.getElementById(`${inputId}_lng`).value = result.geometry.lng;

                    // Update marker
                    const marker = inputId === 'pickup' ? pickupMarker : dropoffMarker;
                    marker.setLatLng([result.geometry.lat, result.geometry.lng]).setOpacity(1);

                    // If it's pickup and current location checkbox is checked, uncheck it
                    if (inputId === 'pickup' && document.getElementById('useCurrentLocation').checked) {
                        document.getElementById('useCurrentLocation').checked = false;
                    }

                    // Pan to location
                    map.panTo([result.geometry.lat, result.geometry.lng]);

                    // Recalculate route
                    calculateRouteAndPrice();
                }
            }

            // Fetch suggestions from OpenCage
            input.addEventListener('input', function() {
                const query = input.value.trim();

                // Abort previous request if exists
                if (currentController) {
                    currentController.abort();
                    currentController = null;
                }

                if (query.length < 3) {
                    hideSuggestions();
                    return;
                }

                // Show loading indicator
                suggestionsContainer.innerHTML = '<div class="autocomplete-loading">Searching locations...</div>';
                showSuggestions();

                // Create new AbortController for this request
                currentController = new AbortController();
                const signal = currentController.signal;

                // Build OpenCage API URL
                const url = `https://api.opencagedata.com/geocode/v1/json?q=${encodeURIComponent(query)}&key=${OPENCAGE_API_KEY}&limit=5&countrycode=ph`;

                fetch(url, {
                        signal
                    })
                    .then(response => response.json())
                    .then(data => {
                        suggestionsContainer.innerHTML = '';
                        suggestions = data.results || [];

                        if (suggestions.length === 0) {
                            suggestionsContainer.innerHTML = '<div class="autocomplete-loading">No results found</div>';
                            return;
                        }

                        // Display suggestions
                        suggestions.forEach((result, index) => {
                            const suggestion = document.createElement('div');
                            suggestion.className = 'autocomplete-suggestion';
                            suggestion.textContent = result.formatted;
                            suggestion.dataset.index = index;

                            suggestion.addEventListener('click', function() {
                                selectSuggestion(index);
                            });

                            suggestionsContainer.appendChild(suggestion);
                        });
                    })
                    .catch(error => {
                        if (error.name !== 'AbortError') {
                            console.error('Geocoding error:', error);
                            suggestionsContainer.innerHTML = '<div class="autocomplete-loading">Error fetching locations</div>';
                        }
                    });
            });

            // Handle keyboard navigation
            input.addEventListener('keydown', function(e) {
                if (suggestionsContainer.style.display === 'none') return;

                switch (e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        selectedIndex = (selectedIndex + 1) % suggestions.length;
                        highlightSuggestion();
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        selectedIndex = (selectedIndex - 1 + suggestions.length) % suggestions.length;
                        highlightSuggestion();
                        break;
                    case 'Enter':
                        e.preventDefault();
                        selectSuggestion(selectedIndex);
                        break;
                    case 'Escape':
                        e.preventDefault();
                        hideSuggestions();
                        break;
                }
            });

            // Highlight current suggestion
            function highlightSuggestion() {
                const allSuggestions = suggestionsContainer.querySelectorAll('.autocomplete-suggestion');
                allSuggestions.forEach((s, i) => {
                    s.classList.toggle('bg-light', i === selectedIndex);
                });
            }

            // Hide suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!container.contains(e.target)) {
                    hideSuggestions();
                }
            });

            // Also hide suggestions when input loses focus
            input.addEventListener('blur', function() {
                setTimeout(hideSuggestions, 200);
            });
        }


        function initMap() {

            let defaultCenter = [14.5995, 120.9842];


            map = L.map('map').setView(defaultCenter, 13);


            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);


            pickupMarker = L.marker(defaultCenter, {
                draggable: true,
                icon: L.divIcon({
                    className: 'marker-icon marker-start',
                    html: 'A',
                    iconSize: [32, 32]
                })
            }).addTo(map);

            dropoffMarker = L.marker(defaultCenter, {
                draggable: true,
                icon: L.divIcon({
                    className: 'marker-icon marker-end',
                    html: 'B',
                    iconSize: [32, 32]
                })
            }).addTo(map);


            pickupMarker.setOpacity(0);
            dropoffMarker.setOpacity(0);


            routingControl = L.Routing.control({
                waypoints: [],
                routeWhileDragging: false,
                showAlternatives: false,
                fitSelectedRoutes: true,
                lineOptions: {
                    styles: [{
                        color: '#4e73df',
                        weight: 5,
                        opacity: 0.8
                    }]
                },
                createMarker: function() {
                    return null;
                }
            }).addTo(map);

            // Marker drag events
            pickupMarker.on('dragend', function(e) {
                updatePositionFromMarker('pickup', pickupMarker);
                calculateRouteAndPrice();
            });

            dropoffMarker.on('dragend', function(e) {
                updatePositionFromMarker('dropoff', dropoffMarker);
                calculateRouteAndPrice();
            });





            const useCurrentLocationCheckbox = document.getElementById('useCurrentLocation');
            if (useCurrentLocationCheckbox) {
                useCurrentLocationCheckbox.addEventListener('change', handleUseCurrentLocationChange);
            }


            const weightInput = document.getElementById('total_weight');
            if (weightInput) {
                weightInput.addEventListener('input', function() {
                    const weight = parseInt(this.value) || 0;
                    const percentage = Math.min(100, (weight / vehicleMaxCapacity) * 100);
                    document.getElementById('weight-progress').style.width = `${percentage}%`;
                    document.getElementById('weight-percentage').textContent = `${Math.round(percentage)}%`;
                });
            }

            L.control.zoom({
                position: 'topright'
            }).addTo(map);


            L.control.scale({
                position: 'bottomright'
            }).addTo(map);

            setupAutocomplete('pickup');
            setupAutocomplete('dropoff');
        }





        function geocodeAddress(address, type) {
            const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        const result = data[0];
                        const lat = parseFloat(result.lat);
                        const lng = parseFloat(result.lon);


                        document.getElementById(`${type}_lat`).value = lat;
                        document.getElementById(`${type}_lng`).value = lng;

                        const marker = type === 'pickup' ? pickupMarker : dropoffMarker;
                        marker.setLatLng([lat, lng]).setOpacity(1);


                        map.panTo([lat, lng]);


                        calculateRouteAndPrice();

                        // If it's pickup and "use current location" is checked, uncheck it
                        if (type === 'pickup' && document.getElementById('useCurrentLocation').checked) {
                            document.getElementById('useCurrentLocation').checked = false;
                        }
                    } else {
                        alert(`Could not find location for "${address}"`);
                    }
                })
                .catch(error => {
                    console.error('Geocoding error:', error);
                    alert('Error looking up address. Please try again.');
                });
        }

        // Update form position from marker
        function updatePositionFromMarker(type, marker) {
            const latLng = marker.getLatLng();
            document.getElementById(`${type}_lat`).value = latLng.lat;
            document.getElementById(`${type}_lng`).value = latLng.lng;

            // Reverse geocode to update address field
            reverseGeocode(latLng, type);
        }

        // Reverse geocode to get address from coordinates
        function reverseGeocode(latLng, type) {
            const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latLng.lat}&lon=${latLng.lng}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data && data.display_name) {
                        document.getElementById(type).value = data.display_name;
                    }
                })
                .catch(error => console.error('Reverse geocoding error:', error));
        }

        // Handle "Use Current Location" checkbox
        function handleUseCurrentLocationChange(event) {
            const pickupInput = document.getElementById('pickup');
            const checkbox = event.target;

            if (checkbox.checked) {
                pickupInput.readOnly = true;
                pickupInput.value = "Fetching current location...";

                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const currentLatLng = {
                                lat: position.coords.latitude,
                                lng: position.coords.longitude
                            };
                            setPickupLocation(currentLatLng);
                        },
                        (error) => {
                            alert("Error getting current location: " + error.message);
                            pickupInput.value = "";
                            pickupInput.readOnly = false;
                            checkbox.checked = false;
                        }, {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 0
                        }
                    );
                } else {
                    alert("Geolocation is not supported by this browser.");
                    pickupInput.value = "";
                    pickupInput.readOnly = false;
                    checkbox.checked = false;
                }
            } else {
                pickupInput.readOnly = false;
                pickupInput.value = "";
                document.getElementById('pickup_lat').value = '';
                document.getElementById('pickup_lng').value = '';
                if (pickupMarker) {
                    pickupMarker.setOpacity(0);
                }
                calculateRouteAndPrice();
            }
        }

        // Set pickup location from coordinates
        function setPickupLocation(latLng) {
            const pickupInput = document.getElementById('pickup');

            // Update form fields
            document.getElementById('pickup_lat').value = latLng.lat;
            document.getElementById('pickup_lng').value = latLng.lng;

            // Update marker
            pickupMarker.setLatLng([latLng.lat, latLng.lng]).setOpacity(1);

            // Pan to location
            map.panTo([latLng.lat, latLng.lng]);

            // Reverse geocode to update address
            reverseGeocode({
                lat: latLng.lat,
                lng: latLng.lng
            }, 'pickup');

            // Calculate route and price
            calculateRouteAndPrice();
        }

        // Calculate route and price estimate
        function calculateRouteAndPrice() {
            const pickupLat = document.getElementById('pickup_lat').value;
            const pickupLng = document.getElementById('pickup_lng').value;
            const dropoffLat = document.getElementById('dropoff_lat').value;
            const dropoffLng = document.getElementById('dropoff_lng').value;

            if (!pickupLat || !pickupLng || !dropoffLat || !dropoffLng) {

                routingControl.setWaypoints([]);
                updatePriceEstimate(0);
                return;
            }

            const origin = L.latLng(parseFloat(pickupLat), parseFloat(pickupLng));
            const destination = L.latLng(parseFloat(dropoffLat), parseFloat(dropoffLng));

            // Update markers if they are not set
            pickupMarker.setOpacity(1);
            dropoffMarker.setOpacity(1);

            // Set the waypoints for routing
            routingControl.setWaypoints([origin, destination]);

            // Listen for the route calculation
            routingControl.on('routesfound', function(e) {
                const routes = e.routes;
                if (routes && routes.length > 0) {
                    const route = routes[0];
                    const distanceInKm = route.summary.totalDistance / 1000;
                    // Calculate duration: 1 hour per 30 km
                    const durationHours = distanceInKm / 30;
                    const durationMinutes = Math.round(durationHours * 60);

                    document.getElementById('distance-display').textContent = `${distanceInKm.toFixed(2)} km`;
                    document.getElementById('duration-display').textContent = `${durationMinutes} mins`;
                    updatePriceEstimate(distanceInKm);

                    // Remove the event listener after first use
                    routingControl.off('routesfound');
                } else {
                    document.getElementById('distance-display').textContent = '-';
                    document.getElementById('duration-display').textContent = '-';
                    updatePriceEstimate(0);
                }
            });
        }

        // Update price estimate based on distance
        function updatePriceEstimate(distanceKm) {
            const totalPrice = vehicleBasePrice + (distanceKm * vehicleRatePerKm);

            document.getElementById('price-estimate').textContent =
                `₱${totalPrice.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            document.getElementById('total_price').value = totalPrice.toFixed(2);
            document.getElementById('total_distance').value = distanceKm.toFixed(2);
        }

        // Form validation
        (() => {
            'use strict'
            const form = document.getElementById('create-booking-form');
            if (form) {
                form.addEventListener('submit', function(event) {
                    let isFormValid = form.checkValidity();

                    if (!validateWeight()) {
                        isFormValid = false;
                    }

                    if (!document.getElementById('pickup_lat').value || !document.getElementById('dropoff_lat').value) {
                        isFormValid = false;
                        alert("Please select valid pickup and drop-off locations on the map.");
                    }

                    if (!isFormValid) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            }

            function validateWeight() {
                const weightInput = document.getElementById('total_weight');
                if (!weightInput) return true;

                const currentWeight = parseInt(weightInput.value);
                weightInput.setCustomValidity('');

                if (isNaN(currentWeight) || currentWeight <= 0) {
                    weightInput.setCustomValidity('Please enter a valid weight.');
                    return false;
                }

                if (currentWeight > vehicleMaxCapacity) {
                    weightInput.setCustomValidity(`Weight exceeds vehicle capacity of ${vehicleMaxCapacity}kg.`);
                    return false;
                }
                return true;
            }

            const weightInput = document.getElementById('total_weight');
            if (weightInput) {
                weightInput.addEventListener('input', validateWeight);
            }
        })();

        // Initialize the map when page loads
        window.addEventListener('DOMContentLoaded', initMap);
    </script>