<?php


// Get booking ID from URL
$booking_id = isset($_GET['booking_id']) ? filter_var($_GET['booking_id'], FILTER_SANITIZE_STRING) : '';

if (empty($booking_id)) {
    header('Location: ../error/404.php');
    exit;
}

// Get booking details
$stmt = $conn->prepare("
    SELECT b.*, v.name as vehicle_name, v.platenumber, v.type as vehicle_type,
           u.full_name as driver_name, u.contact_number as driver_phone
    FROM bookings b 
    JOIN vehicles v ON b.vehicle_id = v.vehicleid 
    JOIN users u ON v.driver_uid = u.uid 
    WHERE b.booking_id = ?
");
$stmt->bind_param("s", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    header('Location: ../error/404.php');
    exit;
}

$pageTitle = "Track Driver - BMove";

?>

<body>
    <div class="container-fluid">
        <div class="row min-vh-100">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Booking Details</span>
                    </h6>
                    <div class="px-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">Booking #<?= htmlspecialchars($booking['booking_id']) ?></h6>
                                <p class="card-text">
                                    <strong>Status:</strong> 
                                    <span class="badge bg-primary"><?= ucfirst(str_replace('_', ' ', $booking['status'])) ?></span>
                                </p>
                                <hr>
                                <p class="mb-1"><strong>From:</strong> <?= htmlspecialchars($booking['pickup_location']) ?></p>
                                <p class="mb-1"><strong>To:</strong> <?= htmlspecialchars($booking['dropoff_location']) ?></p>
                                <p class="mb-1"><strong>Date:</strong> <?= date('M d, Y', strtotime($booking['date'])) ?></p>
                                <p class="mb-1"><strong>Time:</strong> <?= date('H:i', strtotime($booking['time'])) ?></p>
                                <p class="mb-0"><strong>Distance:</strong> <?= htmlspecialchars($booking['total_distance']) ?> km</p>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-body">
                                <h6 class="card-title">Driver Information</h6>
                                <p class="mb-1"><strong>Name:</strong> <span id="driver-name"><?= htmlspecialchars($booking['driver_name']) ?></span></p>
                                <p class="mb-1"><strong>Phone:</strong> <span id="driver-phone"><?= htmlspecialchars($booking['driver_phone']) ?></span></p>
                                <p class="mb-1"><strong>Vehicle:</strong> <?= htmlspecialchars($booking['vehicle_name']) ?></p>
                                <p class="mb-0"><strong>Plate:</strong> <?= htmlspecialchars($booking['platenumber']) ?></p>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-body">
                                <h6 class="card-title">Location Status</h6>
                                <p class="mb-1"><strong>Status:</strong> <span id="location-status" class="badge bg-warning">Loading...</span></p>
                                <p class="mb-1"><strong>Session:</strong> <span id="session-status" class="badge bg-secondary">-</span></p>
                                <p class="mb-1"><strong>Last Update:</strong> <span id="last-location-update">-</span></p>
                                <p class="mb-0"><strong>Speed:</strong> <span id="driver-speed">-</span></p>
                            </div>
                        </div>
                        
                        <div class="d-grid mt-3">
                            <button class="btn btn-primary" onclick="window.customerLocationViewer.refreshLocation()">
                                <i class="bi bi-arrow-clockwise me-2"></i>Refresh Location
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Track Your Driver</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.href='customer-dashboard'">
                                <i class="bi bi-house me-1"></i>Dashboard
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Loading indicator -->
                <div id="loading-indicator" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3">Loading driver location...</p>
                </div>
                
                <!-- Map container -->
                <div id="driver-location-map" style="height: 70vh; width: 100%; border-radius: 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);"></div>
                
                <!-- Location details -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Current Location</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-1"><strong>Latitude:</strong> <span id="driver-lat">-</span></p>
                                <p class="mb-1"><strong>Longitude:</strong> <span id="driver-lng">-</span></p>
                                <p class="mb-1"><strong>Accuracy:</strong> <span id="location-accuracy">-</span></p>
                                <p class="mb-0"><strong>Last Update:</strong> <span id="last-location-update">-</span></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Tracking Session</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-1"><strong>Status:</strong> <span id="tracking-status" class="badge bg-secondary">-</span></p>
                                <p class="mb-1"><strong>Started:</strong> <span id="tracking-started">-</span></p>
                                <p class="mb-0"><strong>Distance:</strong> <span id="total-distance">-</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden fields for location tracking -->
    <input type="hidden" id="booking-id" value="<?= htmlspecialchars($booking_id) ?>">
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Leaflet CSS and JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Customer Location Viewer -->
    <script src="public/js/customer-location-viewer.js"></script>
    
    <style>
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }
        
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        .driver-marker {
            background: #007bff;
            border: 3px solid #fff;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .driver-marker-content {
            color: white;
            font-size: 16px;
        }
        
        .driver-popup {
            min-width: 200px;
        }
        
        .driver-popup h6 {
            color: #007bff;
            margin-bottom: 10px;
        }
        
        .driver-popup p {
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        @media (max-width: 767.98px) {
            .sidebar {
                position: static;
                height: auto;
            }
        }
    </style>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Leaflet CSS and JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Customer Location Viewer -->
    <script src="public/js/customer-location-viewer.js"></script>
</body>
</html>
