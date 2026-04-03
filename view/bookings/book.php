<?php
if (!isset($_GET['car'])) {
    echo "<script>window.location.href = 'home';</script>";
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

    .map-controls {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 1000;
        display: flex;
        gap: 5px;
    }

    .map-controls .btn {
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid #ddd;
        backdrop-filter: blur(5px);
        font-size: 12px;
        padding: 5px 10px;
    }

    .map-controls .btn:hover {
        background: rgba(255, 255, 255, 1);
        transform: translateY(-1px);
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

    /* Custom marker styles */
    .custom-marker {
        background: transparent;
        border: none;
    }

    .marker-content {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 14px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        position: relative;
    }

    .pickup-marker .marker-content {
        background: linear-gradient(135deg, #1cc88a, #17a673);
    }

    .dropoff-marker .marker-content {
        background: linear-gradient(135deg, #e74a3b, #c0392b);
    }

    .marker-content i {
        font-size: 16px;
        margin-right: 2px;
    }

    .marker-content span {
        font-size: 12px;
        font-weight: bold;
    }

    /* Marker selection modal */
    .marker-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    }

    .marker-modal-content {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
        text-align: center;
        max-width: 300px;
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

                        <div class="alert alert-info mb-3">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Map Instructions:</strong>
                            <ul class="mb-0 mt-2">
                                <li><strong>Click on the map to drop a pin</strong> for pickup (first click) and
                                    drop-off (second click)</li>
                                <li>Drag markers to fine-tune positions</li>
                                <li>Optionally type addresses in the fields to search</li>
                                <li>Service area is limited to <strong>Bataan province</strong></li>
                            </ul>
                        </div>

                        <div class="map-container">
                            <div id="map"></div>
                            <div class="map-controls">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="center-map">
                                    <i class="bi bi-geo-alt"></i> Center Map
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success" id="clear-markers">
                                    <i class="bi bi-trash"></i> Clear Markers
                                </button>
                            </div>
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
                                        <span>Base Price:</span>
                                        <span>₱<?= number_format($selectedVehicle['baseprice'], 2) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2">
                                        <span>Distance Rate:</span>
                                        <span>₱<?= number_format($selectedVehicle['rateperkm'], 2) ?>/km</span>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between">
                                        <strong><i class="bi bi-currency-dollar info-icon"></i>Estimated Price:</strong>
                                        <strong id="price-estimate" class="text-primary fs-5">₱0.00</strong>
                                    </div>
                                </div>
                            </div>
                        </div>




                    </div>
                </div>

                <!-- Booking Form Section -->
                <div class="col-lg-5">
                    <div class="booking-card">
                        <h3 class="section-title"><i class="bi bi-calendar-check me-2"></i>Booking Details</h3>

                        <form method="post" action="controller/booking/create-booking.php" id="create-booking-form"
                            class="needs-validation" novalidate>
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
                                        <small
                                            class="text-muted"><?= htmlspecialchars($selectedVehicle['platenumber']) ?>
                                            | <?= htmlspecialchars($selectedVehicle['type']) ?></small>
                                    </div>
                                    <div class="text-end">
                                        <div>Capacity: <?= htmlspecialchars($selectedVehicle['totalcapacitykg']) ?>kg
                                        </div>
                                        <small class="text-muted">Base:
                                            ₱<?= number_format($selectedVehicle['baseprice'], 2) ?></small>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="pickup" class="form-label"><i class="bi bi-geo-alt me-1"></i>Pickup
                                    Location</label>
                                <input type="text" id="pickup" name="pickup_location"
                                    class="form-control form-control-lg" placeholder="Enter complete pickup address (e.g., 123 Main St, Brgy. Centro, Balanga, Bataan)" required maxlength="500">
                                <div class="invalid-feedback">Please select a pickup location</div>
                                <small class="text-muted d-block mt-1"><i class="bi bi-lightbulb"></i> Tip: Select a broad area or pin on the map first, then manually add your specific house and street details.</small>

                                <div class="form-check current-location-checkbox">
                                    <input class="form-check-input" type="checkbox" id="useCurrentLocation">
                                    <label class="form-check-label" for="useCurrentLocation">
                                        <i class="bi bi-geo me-1"></i>Use my current location for pickup
                                    </label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="dropoff" class="form-label"><i class="bi bi-geo-alt-fill me-1"></i>Drop-off
                                    Location</label>
                                <input type="text" id="dropoff" name="dropoff_location"
                                    class="form-control form-control-lg" placeholder="Enter complete drop-off address (e.g., 456 Rizal Ave, Brgy. Poblacion, Dinalupihan, Bataan)" required maxlength="500">
                                <div class="invalid-feedback">Please select a drop-off location</div>
                                <small class="text-muted d-block mt-1"><i class="bi bi-lightbulb"></i> Tip: Select a broad area or pin on the map first, then manually add your specific house and street details.</small>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="date" class="form-label"><i class="bi bi-calendar me-1"></i>Move
                                        Date</label>
                                    <input type="date" id="date" name="date" class="form-control form-control-lg"
                                        min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+3 months')) ?>"
                                        required onchange="updateTimeSlots()">
                                    <small class="text-muted d-block mt-1">You can only book up to 3 months in
                                        advance.</small>
                                    <div class="invalid-feedback">Please select a date within the next 3 months</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="time" class="form-label"><i class="bi bi-clock me-1"></i>Preferred
                                        Time</label>
                                    <select id="time" name="time" class="form-select form-select-lg" required>
                                        <option value="" disabled selected>Select time</option>
                                        <?php
                                        $start = strtotime('07:00');
                                        $end = strtotime('20:00');
                                        for ($t = $start; $t <= $end; $t += 1800) {
                                            $val = date('H:i', $t);
                                            $label = date('g:i A', $t);
                                            echo "<option value=\"$val\" data-time=\"$val\">$label</option>";
                                        }
                                        ?>
                                    </select>
                                    <small class="text-muted d-block mt-1" id="time-hint"></small>
                                    <div class="invalid-feedback">Please select a preferred time</div>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="total_weight" class="form-label"><i
                                            class="bi bi-box-seam me-1"></i>Total Weight</label>
                                    <div class="input-group">
                                        <input type="number" id="total_weight" name="total_weight"
                                            class="form-control form-control-lg" min="1" required>
                                        <select id="weight_unit" class="form-select form-select-lg"
                                            style="max-width: 110px;">
                                            <option value="kg" selected>kg</option>
                                            <option value="lots">lots</option>
                                        </select>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2">
                                        <small class="instruction-text">Max capacity:
                                            <?= htmlspecialchars($selectedVehicle['totalcapacitykg']) ?>kg</small>
                                        <small id="weight-percentage">0%</small>
                                    </div>
                                    <div class="progress-container mt-1">
                                        <div class="progress-bar" id="weight-progress" style="width: 0%"></div>
                                    </div>
                                    <div class="invalid-feedback">Weight exceeds vehicle capacity or is invalid.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="items_count" class="form-label"><i class="bi bi-boxes me-1"></i>Number
                                        of Items</label>
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
                                <label for="payment_method" class="form-label">
                                    <i class="bi bi-credit-card me-1"></i>Payment Method
                                </label>
                                <select id="payment_method" name="payment_method" class="form-select form-select-lg"
                                    required>
                                    <option value="" disabled selected>Select payment method</option>
                                    <option value="paymongo">Online Payment (GCash, Maya, GrabPay)</option>
                                    <option value="qr_code">QR Code Payment</option>
                                    <option value="cash">Cash on Delivery (COD)</option>
                                </select>
                                <div class="invalid-feedback">Please select a payment method</div>
                            </div>

                            <!-- QR Code Payment Info -->
                            <div id="qr_payment_info" class="alert alert-info mt-3 d-none">
                                <strong><i class="bi bi-qr-code me-1"></i>QR Code Payment:</strong>
                                <p class="mb-2 mt-2">After confirming your booking, a QR code will be generated for you to scan and pay using your preferred e-wallet or banking app.</p>
                                <ul class="mb-0">
                                    <li>Supports GCash, Maya, bank apps, and any QR Ph-compatible app.</li>
                                    <li>Payment must be completed within <strong>30 minutes</strong> of booking confirmation.</li>
                                    <li>Your booking will be confirmed once payment is verified.</li>
                                </ul>
                            </div>

                            <div id="cod_policy" class="alert alert-warning mt-3 d-none">
                                <strong><i class="bi bi-exclamation-triangle me-1"></i>Cash on Delivery (COD) Policy:</strong>
                                <p class="mb-2 mt-2">For bookings using Cash on Delivery, please read and agree to the following:</p>
                                <ul class="mb-2">
                                    <li>Prepare the <strong>exact amount</strong> upon delivery or rental handover.</li>
                                    <li>COD is only available within approved service areas in Bataan.</li>
                                    <li>If unable to pay the full amount on delivery, you must notify BMoveXpress <strong>at least 2 hours before</strong> the scheduled pickup time.</li>
                                    <li>Failure to pay on delivery will result in a <strong>COD surcharge of ₱150</strong> and may lead to account restrictions.</li>
                                    <li>Repeated COD failures (3 or more) will result in <strong>permanent suspension of COD privileges</strong>.</li>
                                    <li>In unexpected situations where COD cannot be fulfilled, the driver will attempt to contact you. If unreachable within 15 minutes, the booking will be <strong>marked as failed</strong>.</li>
                                    <li>For amounts exceeding ₱5,000, online payment is strongly recommended.</li>
                                </ul>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="cod_agree" required>
                                    <label class="form-check-label" for="cod_agree">
                                        I have read, understood, and agree to the Cash on Delivery policy
                                    </label>
                                    <div class="invalid-feedback">You must agree to the COD policy before proceeding.
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" id="total_price" name="total_price" class="form-control">
                            <input type="hidden" id="total_distance" name="total_distance" class="form-control">

                            <div class="mb-3 form-check mt-3">
                                <input type="checkbox" class="form-check-input" id="privacy_agreement"
                                    name="privacy_agreement" required>
                                <label class="form-check-label" for="privacy_agreement">
                                    I agree to the <a href="privacy-policy" target="_blank" class="text-primary">Privacy
                                        Policy</a>
                                </label>
                                <div class="invalid-feedback">You must agree to the Privacy Policy before booking.</div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 mt-2 py-3 fw-bold"
                                id="submit-btn">
                                <i class="bi bi-check-circle me-2"></i>Confirm Booking
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Disable past time slots and slots that overlap with existing bookings
        async function updateTimeSlots() {
            const dateInput = document.getElementById('date');
            const timeSelect = document.getElementById('time');
            const timeHint = document.getElementById('time-hint');
            const vehicleId = document.querySelector('input[name="vehicle_id"]').value;
            const selectedDate = dateInput.value;
            const today = new Date().toISOString().split('T')[0];
            const options = timeSelect.querySelectorAll('option[data-time]');

            if (!selectedDate) return;

            // Preparation time gap from current time
            const PREP_TIME_GAP = 60;
            // Gap between different bookings (e.g., 60 mins before and 60 mins after)
            const BOOKING_GAP = 60;

            let bookedSlots = [];
            try {
                const response = await fetch(`controller/booking/get-booked-slots.php?vehicle_id=${vehicleId}&date=${selectedDate}`);
                const result = await response.json();
                if (result.status === 'success') {
                    bookedSlots = result.data.map(b => {
                        const [h, m] = b.time.split(':').map(Number);
                        return h * 60 + m;
                    });
                }
            } catch (err) {
                console.error("Failed to fetch booked slots", err);
            }

            const now = new Date();
            const currentMinutes = now.getHours() * 60 + now.getMinutes();
            const minimumSlotMinutes = (selectedDate === today) ? currentMinutes + PREP_TIME_GAP : -1;
            
            let disabledCount = 0;

            options.forEach(opt => {
                const [h, m] = opt.dataset.time.split(':').map(Number);
                const slotMinutes = h * 60 + m;
                
                let isDisabled = false;

                // 1. Check if it's in the past or within prep gap
                if (slotMinutes <= minimumSlotMinutes) {
                    isDisabled = true;
                }

                // 2. Check if it overlaps with any existing booking
                bookedSlots.forEach(bookedMins => {
                    if (Math.abs(slotMinutes - bookedMins) < BOOKING_GAP) {
                        isDisabled = true;
                    }
                });

                if (isDisabled) {
                    opt.disabled = true;
                    opt.classList.add('text-muted');
                    disabledCount++;
                    if (opt.selected) {
                        timeSelect.value = '';
                    }
                } else {
                    opt.disabled = false;
                    opt.classList.remove('text-muted');
                }
            });

            if (disabledCount > 0) {
                timeHint.textContent = 'Some time slots are disabled to allow for preparation and avoid overlapping bookings.';
            } else {
                timeHint.textContent = '';
            }
        }

        // Run on page load if date is pre-filled
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('date').value) {
                updateTimeSlots();
            }
        });

        document.getElementById('payment_method').addEventListener('change', function () {
            const codPolicy = document.getElementById('cod_policy');
            const codAgree = document.getElementById('cod_agree');
            const qrInfo = document.getElementById('qr_payment_info');

            if (this.value === 'cash') {
                codPolicy.classList.remove('d-none');
                codAgree.setAttribute('required', 'required');
                qrInfo.classList.add('d-none');
            } else {
                codPolicy.classList.add('d-none');
                codAgree.removeAttribute('required');
                codAgree.checked = false;
            }

            if (this.value === 'qr_code') {
                qrInfo.classList.remove('d-none');
            } else {
                qrInfo.classList.add('d-none');
            }
        });
    </script>

    <!-- Leaflet & Routing Libraries -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

    <script>
        // Bataan province center and bounds globally accessible
        const devDefaultCenter = [14.6417, 120.4818];
        const bataanBounds = L.latLngBounds([14.42, 120.30], [14.90, 120.70]);

        // Custom booking form handler for PayMongo integration
        class BookingFormHandler {
            constructor() {
                this.form = document.getElementById('create-booking-form');
                this.submitButton = document.getElementById('submit-btn');
                this.originalButtonText = this.submitButton.innerHTML;
                this.init();
            }

            init() {
                this.form.addEventListener('submit', (e) => this.handleSubmit(e));
            }

            async handleSubmit(e) {
                e.preventDefault();

                // Show confirmation dialog
                const confirmed = await this.showConfirmation();
                if (!confirmed) return;

                // Validate form
                if (!this.validateForm()) return;

                // Show loading state
                this.setLoadingState(true);

                try {
                    // Prepare form data
                    const formData = new FormData(this.form);

                    // Make API request
                    const response = await fetch(this.form.action, {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        await this.handleSuccess(data);
                    } else {
                        await this.handleError(data);
                    }
                } catch (error) {
                    console.error('Form submission error:', error);
                    await this.handleError({ message: 'Network error. Please try again.' });
                } finally {
                    this.setLoadingState(false);
                }
            }

            async showConfirmation() {
                return new Promise((resolve) => {
                    Swal.fire({
                        title: "Confirm Booking",
                        text: "Are you sure you want to create this booking?",
                        icon: "question",
                        showCancelButton: true,
                        confirmButtonText: "Yes, book it!",
                        cancelButtonText: "Cancel",
                        confirmButtonColor: "#6f42c1"
                    }).then((result) => {
                        resolve(result.isConfirmed);
                    });
                });
            }

            validateForm() {
                // Check if Privacy Policy is accepted
                const privacyAgreement = document.getElementById('privacy_agreement');
                if (privacyAgreement && !privacyAgreement.checked) {
                    Swal.fire({
                        title: 'Required',
                        text: 'You must agree to the Privacy Policy before booking.',
                        icon: 'warning',
                        confirmButtonText: 'OK'
                    });
                    return false;
                }

                // Check if pickup location is selected
                const pickupLocation = document.getElementById('pickup').value;
                if (!pickupLocation || pickupLocation.trim() === '') {
                    if (typeof showNotification === 'function') {
                        showNotification('Please enter a pickup location', 'error');
                    } else {
                        Swal.fire({ icon: 'error', text: 'Please enter a pickup location' });
                    }
                    return false;
                }

                // Check if coordinates are set
                const pickupLat = document.getElementById('pickup_lat').value;
                const pickupLng = document.getElementById('pickup_lng').value;
                const dropoffLat = document.getElementById('dropoff_lat').value;
                const dropoffLng = document.getElementById('dropoff_lng').value;

                if (!pickupLat || !pickupLng || !dropoffLat || !dropoffLng) {
                    showNotification('Please select valid pickup and drop-off locations on the map', 'error');
                    return false;
                }

                // Check Bataan bounds
                const pLatLng = L.latLng(pickupLat, pickupLng);
                const dLatLng = L.latLng(dropoffLat, dropoffLng);
                if (typeof bataanBounds !== 'undefined') {
                    if (!bataanBounds.contains(pLatLng) || !bataanBounds.contains(dLatLng)) {
                        showNotification('Both pickup and drop-off locations must be within Bataan province.', 'error');
                        return false;
                    }
                }

                return true;
            }

            setLoadingState(loading) {
                if (loading) {
                    this.submitButton.disabled = true;
                    this.submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Booking...';
                } else {
                    this.submitButton.disabled = false;
                    this.submitButton.innerHTML = this.originalButtonText;
                }
            }

            async handleSuccess(data) {
                console.log("Booking created successfully:", data);

                if (data.payment_method === 'paymongo' && data.checkout_url) {
                    // Show redirect message and redirect to PayMongo
                    await Swal.fire({
                        title: "Redirecting to Payment",
                        text: "Please complete your payment to confirm your booking.",
                        icon: "info",
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });

                    // Redirect to PayMongo checkout
                    window.location.href = data.checkout_url;
                } else if (data.payment_method === 'qr_code' && data.booking_id) {
                    // Redirect to QR code payment page
                    await Swal.fire({
                        title: "QR Code Payment",
                        text: "Your booking has been created. Redirecting to QR code payment...",
                        icon: "info",
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });

                    window.location.href = 'qr-payment?booking_id=' + data.booking_id;
                } else {
                    // Show success message for cash payments
                    await Swal.fire({
                        title: "Booking Created!",
                        text: data.message || "Your booking has been created successfully!",
                        icon: "success",
                        confirmButtonText: "View Dashboard",
                        confirmButtonColor: "#28a745"
                    });

                    console.log('Redirecting to dashboard...');
                    window.location.href = 'customer-dashboard';
                }
            }

            async handleError(data) {
                console.error("Booking creation failed:", data);

                await Swal.fire({
                    title: "Booking Failed",
                    text: data.message || "Something went wrong. Please try again later.",
                    icon: "error",
                    confirmButtonText: "Try Again",
                    confirmButtonColor: "#dc3545"
                });
            }
        }

        // Initialize the custom booking form handler
        const bookingHandler = new BookingFormHandler();

        // Debug: Log form data before submission (for debugging purposes)
        document.getElementById('create-booking-form').addEventListener('submit', function (e) {
            const formData = new FormData(this);
            console.log('Form data being submitted:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
        });

        // Notification function
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show position-fixed`;
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

                    // Validate bounds before accepting the suggestion
                    const latLng = L.latLng(result.geometry.lat, result.geometry.lng);
                    if (typeof bataanBounds !== 'undefined' && !bataanBounds.contains(latLng)) {
                        showNotification(`The selected location "${result.formatted}" is outside Bataan.`, 'error');
                        input.value = '';
                        hideSuggestions();
                        return;
                    }

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
            input.addEventListener('input', function () {
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
                const url = `https://api.opencagedata.com/geocode/v1/json?q=${encodeURIComponent(query)}&key=${OPENCAGE_API_KEY}&limit=5&countrycode=ph&bounds=120.30,14.42,120.70,14.90`;

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

                            suggestion.addEventListener('click', function () {
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
            input.addEventListener('keydown', function (e) {
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
            document.addEventListener('click', function (e) {
                if (!container.contains(e.target)) {
                    hideSuggestions();
                }
            });

            // Also hide suggestions when input loses focus
            input.addEventListener('blur', function () {
                setTimeout(hideSuggestions, 200);
            });
        }


        function initMap() {
            let defaultCenter = devDefaultCenter;

            map = L.map('map', {
                maxBounds: bataanBounds.pad(0.1),
                minZoom: 10
            }).setView(defaultCenter, 11);

            // Add multiple tile layers for better map experience
            const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            });

            const cartoLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>'
            });

            // Add layer control
            const baseMaps = {
                "OpenStreetMap": osmLayer,
                "CartoDB": cartoLayer
            };

            L.control.layers(baseMaps).addTo(map);
            osmLayer.addTo(map);

            // Create custom marker icons with better styling
            const pickupIcon = L.divIcon({
                className: 'custom-marker pickup-marker',
                html: '<div class="marker-content"><i class="bi bi-geo-alt-fill"></i><span>A</span></div>',
                iconSize: [40, 40],
                iconAnchor: [20, 40],
                popupAnchor: [0, -40]
            });

            const dropoffIcon = L.divIcon({
                className: 'custom-marker dropoff-marker',
                html: '<div class="marker-content"><i class="bi bi-geo-alt-fill"></i><span>B</span></div>',
                iconSize: [40, 40],
                iconAnchor: [20, 40],
                popupAnchor: [0, -40]
            });

            pickupMarker = L.marker(defaultCenter, {
                draggable: true,
                icon: pickupIcon
            }).addTo(map);

            dropoffMarker = L.marker(defaultCenter, {
                draggable: true,
                icon: dropoffIcon
            }).addTo(map);

            // Initially hide markers
            pickupMarker.setOpacity(0);
            dropoffMarker.setOpacity(0);

            // Add click event to map for pinning
            map.on('click', function (e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;

                // Validate clicked point is within Bataan bounds
                if (!bataanBounds.contains(e.latlng)) {
                    showNotification('Please select a location within Bataan province.', 'error');
                    return;
                }

                // Determine which marker to place based on current state
                if (!pickupMarker.getLatLng().equals(defaultCenter) && !dropoffMarker.getLatLng().equals(defaultCenter)) {
                    // Both markers are set, ask user which one to replace
                    showMarkerSelectionModal(lat, lng);
                } else if (pickupMarker.getLatLng().equals(defaultCenter)) {
                    // Place pickup marker
                    placePickupMarker(lat, lng);
                } else {
                    // Place dropoff marker
                    placeDropoffMarker(lat, lng);
                }
            });


            routingControl = L.Routing.control({
                waypoints: [],
                routeWhileDragging: false,
                showAlternatives: false,
                fitSelectedRoutes: true,
                show: false,
                lineOptions: {
                    styles: [{
                        color: '#4e73df',
                        weight: 5,
                        opacity: 0.8
                    }]
                },
                createMarker: function () {
                    return null;
                }
            }).addTo(map);

            // Marker drag events
            pickupMarker.on('dragend', function (e) {
                if (!bataanBounds.contains(pickupMarker.getLatLng())) {
                    showNotification('Selected location is outside Bataan province.', 'error');
                    pickupMarker.setLatLng(devDefaultCenter).setOpacity(0);
                    document.getElementById('pickup_lat').value = '';
                    document.getElementById('pickup_lng').value = '';
                    document.getElementById('pickup').value = '';
                    routingControl.setWaypoints([]);
                    updatePriceEstimate(0);
                    return;
                }
                updatePositionFromMarker('pickup', pickupMarker);
                calculateRouteAndPrice();
            });

            dropoffMarker.on('dragend', function (e) {
                if (!bataanBounds.contains(dropoffMarker.getLatLng())) {
                    showNotification('Selected location is outside Bataan province.', 'error');
                    dropoffMarker.setLatLng(devDefaultCenter).setOpacity(0);
                    document.getElementById('dropoff_lat').value = '';
                    document.getElementById('dropoff_lng').value = '';
                    document.getElementById('dropoff').value = '';
                    routingControl.setWaypoints([]);
                    updatePriceEstimate(0);
                    return;
                }
                updatePositionFromMarker('dropoff', dropoffMarker);
                calculateRouteAndPrice();
            });





            const useCurrentLocationCheckbox = document.getElementById('useCurrentLocation');
            if (useCurrentLocationCheckbox) {
                useCurrentLocationCheckbox.addEventListener('change', handleUseCurrentLocationChange);
            }


            const weightInput = document.getElementById('total_weight');
            const weightUnit = document.getElementById('weight_unit');

            function getWeightInKg() {
                const val = parseFloat(weightInput.value) || 0;
                const unit = weightUnit.value;
                if (unit === 'tons') return val * 1000;
                if (unit === 'lots') return val * 500;
                return val;
            }

            function updateWeightProgress() {
                const weightKg = getWeightInKg();
                const percentage = Math.min(100, (weightKg / vehicleMaxCapacity) * 100);
                document.getElementById('weight-progress').style.width = `${percentage}%`;
                document.getElementById('weight-percentage').textContent = `${Math.round(percentage)}%`;
            }

            if (weightInput) {
                weightInput.addEventListener('input', updateWeightProgress);
            }
            if (weightUnit) {
                weightUnit.addEventListener('change', updateWeightProgress);
            }

            // Convert weight to kg before form submit
            document.getElementById('create-booking-form').addEventListener('submit', function () {
                const weightKg = getWeightInKg();
                weightInput.value = Math.round(weightKg);
                weightUnit.value = 'kg';
            });

            L.control.zoom({
                position: 'topright'
            }).addTo(map);


            L.control.scale({
                position: 'bottomright'
            }).addTo(map);

            setupAutocomplete('pickup');
            setupAutocomplete('dropoff');

            // Auto-geocode when user finishes typing and leaves the input
            document.getElementById('pickup').addEventListener('change', function() {
                if (this.value.trim().length > 5 && !document.getElementById('pickup_lat').value) {
                    geocodeAddress(this.value.trim(), 'pickup');
                }
            });
            document.getElementById('dropoff').addEventListener('change', function() {
                if (this.value.trim().length > 5 && !document.getElementById('dropoff_lat').value) {
                    geocodeAddress(this.value.trim(), 'dropoff');
                }
            });

            // Add map control event listeners
            document.getElementById('center-map').addEventListener('click', function () {
                if (pickupMarker.getOpacity() > 0 && dropoffMarker.getOpacity() > 0) {
                    // Center on both markers
                    const group = new L.featureGroup([pickupMarker, dropoffMarker]);
                    map.fitBounds(group.getBounds().pad(0.1));
                } else {
                    // Center on default location
                    map.setView(defaultCenter, 13);
                }
            });

            document.getElementById('clear-markers').addEventListener('click', function () {
                pickupMarker.setLatLng(defaultCenter).setOpacity(0);
                dropoffMarker.setLatLng(defaultCenter).setOpacity(0);
                document.getElementById('pickup_lat').value = '';
                document.getElementById('pickup_lng').value = '';
                document.getElementById('dropoff_lat').value = '';
                document.getElementById('dropoff_lng').value = '';
                document.getElementById('pickup').value = '';
                document.getElementById('dropoff').value = '';
                routingControl.setWaypoints([]);
                updatePriceEstimate(0);
            });
        }

        // Helper functions for map pinning
        function placePickupMarker(lat, lng) {
            pickupMarker.setLatLng([lat, lng]).setOpacity(1);
            document.getElementById('pickup_lat').value = lat;
            document.getElementById('pickup_lng').value = lng;
            reverseGeocode({ lat: lat, lng: lng }, 'pickup');
            calculateRouteAndPrice();
            showNotification('Pickup location pinned!', 'success');
        }

        function placeDropoffMarker(lat, lng) {
            dropoffMarker.setLatLng([lat, lng]).setOpacity(1);
            document.getElementById('dropoff_lat').value = lat;
            document.getElementById('dropoff_lng').value = lng;
            reverseGeocode({ lat: lat, lng: lng }, 'dropoff');
            calculateRouteAndPrice();
            showNotification('Drop-off location pinned!', 'success');
        }

        function showMarkerSelectionModal(lat, lng) {
            const modal = document.createElement('div');
            modal.className = 'marker-modal';
            modal.innerHTML = `
                <div class="marker-modal-content">
                    <h5>Select Marker to Replace</h5>
                    <p class="text-muted">Click on the map to place a marker. Which marker would you like to replace?</p>
                    <div class="d-grid gap-2">
                        <button class="btn btn-success" onclick="replacePickupMarker(${lat}, ${lng})">
                            <i class="bi bi-geo-alt me-2"></i>Replace Pickup (A)
                        </button>
                        <button class="btn btn-danger" onclick="replaceDropoffMarker(${lat}, ${lng})">
                            <i class="bi bi-geo-alt-fill me-2"></i>Replace Drop-off (B)
                        </button>
                        <button class="btn btn-outline-secondary" onclick="closeMarkerModal()">
                            Cancel
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        function replacePickupMarker(lat, lng) {
            placePickupMarker(lat, lng);
            closeMarkerModal();
        }

        function replaceDropoffMarker(lat, lng) {
            placeDropoffMarker(lat, lng);
            closeMarkerModal();
        }

        function closeMarkerModal() {
            const modal = document.querySelector('.marker-modal');
            if (modal) {
                modal.remove();
            }
        }





        function geocodeAddress(address, type) {
            const url = `controller/geocoding-proxy.php?action=geocode&address=${encodeURIComponent(address)}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        const result = data[0];
                        const lat = parseFloat(result.lat);
                        const lng = parseFloat(result.lon);
                        const latLng = L.latLng(lat, lng);

                        if (!bataanBounds.contains(latLng)) {
                            showNotification(`The location "${address}" is outside Bataan province. Please select a valid Bataan location.`, 'error');
                            return;
                        }

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
            const url = `controller/geocoding-proxy.php?action=reverse&lat=${latLng.lat}&lng=${latLng.lng}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data && data.display_name) {
                        const inputEl = document.getElementById(type);
                        // Only overwrite if input is empty, contains "Fetching", or starts with the previous geocoded result
                        // Actually, to ensure they can enter custom addresses, we will append it if empty, 
                        // or just set it if it's currently empty or has the default placeholder text.
                        if (!inputEl.value || inputEl.value === "Fetching current location...") {
                            inputEl.value = data.display_name;
                        } else {
                            // If they already typed something, don't overwrite it!
                            // This allows them to type "House 42, San Jose" and then pin the map
                            // without losing their custom address.
                            console.log("Preserved custom address: " + inputEl.value);
                            
                            // Optionally, if the text is very short (just a broad search), we could overwrite
                            // but safer to preserve their manual entry.
                        }
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

            const ll = L.latLng(latLng.lat, latLng.lng);
            if (typeof bataanBounds !== 'undefined' && !bataanBounds.contains(ll)) {
                showNotification('Your current location is outside Bataan province.', 'error');
                pickupInput.value = "";
                pickupInput.readOnly = false;
                document.getElementById('pickup_lat').value = '';
                document.getElementById('pickup_lng').value = '';
                if (typeof pickupMarker !== 'undefined' && pickupMarker) {
                    pickupMarker.setOpacity(0);
                }
                const checkbox = document.getElementById('useCurrentLocation');
                if (checkbox) checkbox.checked = false;
                calculateRouteAndPrice();
                return;
            }

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
            routingControl.on('routesfound', function (e) {
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
                form.addEventListener('submit', function (event) {
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

                let warningEl = document.getElementById('weight-warning');
                if (!warningEl) {
                    warningEl = document.createElement('div');
                    warningEl.id = 'weight-warning';
                    warningEl.className = 'text-danger mt-1 small fw-bold';
                    weightInput.parentNode.appendChild(warningEl);
                }

                // Reference to Next button in multi-step form (step 1 -> step 2)
                const nextBtn = document.querySelector('button[onclick="nextStep(2)"], .btn-next') || document.querySelector('button[type="button"].btn-primary');
                const submitBtn = document.querySelector('button[type="submit"]');

                if (isNaN(currentWeight) || currentWeight <= 0) {
                    weightInput.setCustomValidity('Please enter a valid weight.');
                    warningEl.textContent = '';
                    return false;
                }

                if (currentWeight > vehicleMaxCapacity) {
                    weightInput.setCustomValidity(`Weight exceeds vehicle capacity of ${vehicleMaxCapacity}kg.`);
                    warningEl.textContent = 'Within capacity only';
                    if (nextBtn) nextBtn.disabled = true;
                    if (submitBtn) submitBtn.disabled = true;
                    return false;
                }

                warningEl.textContent = '';
                if (nextBtn) nextBtn.disabled = false;
                if (submitBtn) submitBtn.disabled = false;
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