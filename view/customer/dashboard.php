<?php
// Helper function to get status color
function getStatusColor($status)
{
    switch (strtolower($status)) {
        case 'completed':
            return 'success';
        case 'cancelled':
            return 'danger';
        case 'pending':
            return 'secondary';
        case 'confirmed':
            return 'primary';
        case 'in_progress':
            return 'warning';
        default:
            return 'info';
    }
}
// Fetch bookings with vehicle and user details
$current_uid = $_SESSION['auth']['user_id'] ?? null;

$sql = "SELECT 
            b.*, 
            v.name AS vehicle_name,
            v.platenumber,
            v.type AS vehicle_type,
            v.model AS vehicle_model,
            v.year AS vehicle_year,
            u.full_name AS customer_name
        FROM bookings b
        LEFT JOIN vehicles v ON b.vehicle_id = v.vehicleid
        LEFT JOIN users u ON b.user_id = u.uid
        WHERE b.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $current_uid);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<style>
    .leaflet-right {
        width: 200px !important;
        background-color: #fff;
        overflow: auto;
    }

    .leaflet-routing-alt {
        overflow: auto !important;
    }

    .leaflet-routing-alt table {
        width: 100% !important;
        border-collapse: collapse !important;
    }

    .leaflet-routing-alt h2 {
        font-size: 1.25rem;
        margin-bottom: 0.5rem;
    }

    .leaflet-routing-alt h3 {
        font-size: 1rem;
        margin-bottom: 0.25rem;
    }

    .leaflet-routing-alternatives-container {
        max-height: 400px;
        overflow-y: auto;
        padding: 10pxz;
    }

    .card {
        border-radius: 10px;
        box-shadow: 0 7px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 20px;
        transition: transform 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
    }


    .booking-card {
        cursor: pointer;
    }

    .booking-card:hover {
        background-color: #f8f9fc;
    }

    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: 500;
    }

    .status-pending {
        background-color: #f6c23e;
        color: #2c2929;
    }

    .status-confirmed {
        background-color: #1cc88a;
        color: white;
    }

    .status-completed {
        background-color: #36b9cc;
        color: white;
    }

    .status-cancelled {
        background-color: #e74a3b;
        color: white;
    }

    #routeMap {
        height: 500px;
        border-radius: 8px;
        z-index: 10;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border: 1px solid #e0e0e0;
    }

    .info-item {
        display: flex;
        margin-bottom: 12px;
    }

    .info-icon {
        width: 32px;
        height: 32px;
        background: rgba(78, 115, 223, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        color: #4e73df;
    }

    .info-content {
        flex: 1;
    }

    .section-title {
        position: relative;
        padding-bottom: 12px;
        margin-bottom: 20px;
        border-bottom: 2px solid #e9ecef;
    }

    .section-title::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 60px;
        height: 3px;
        background: #4e73df;
        border-radius: 3px;
    }

    .modal-xl .modal-content {
        border-radius: 15px;
        overflow: hidden;
    }

    .modal-header {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: white;
    }

    .modal-title {
        font-weight: 600;
    }

    .btn-close {
        filter: invert(1);
    }

    .route-details {
        padding: 20px;
        background: #f8f9fc;
        border-radius: 10px;
    }
</style>

<div class="container mt-4">
    <h2 class="mb-4">My Bookings</h2>

    <?php if (!empty($bookings)): ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($bookings as $booking): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Booking #<?= htmlspecialchars($booking['booking_id']) ?></h5>
                            <span class="badge bg-<?= getStatusColor($booking['status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', $booking['status'])) ?>
                            </span>
                        </div>

                        <div class="card-body">
                            <div class="mb-3">
                                <h6 class="text-muted"><?= htmlspecialchars($booking['vehicle_name']) ?></h6>
                                <div class="text-primary">₱<?= htmlspecialchars(number_format($booking['total_price'], 2)) ?></div>

                                <small class="text-muted">
                                    <?= htmlspecialchars(ucfirst($booking['payment_method'])) ?>
                                </small>

                            </div>

                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    <strong>From:</strong> <?= htmlspecialchars($booking['pickup_location']) ?>
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-flag-checkered me-2"></i>
                                    <strong>To:</strong> <?= htmlspecialchars($booking['dropoff_location']) ?>
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-calendar-day me-2"></i>
                                    <?= htmlspecialchars($booking['date']) ?> at <?= htmlspecialchars($booking['time']) ?>
                                </li>
                                <li>
                                    <i class="fas fa-road me-2"></i>
                                    <?= htmlspecialchars($booking['total_distance']) ?> km
                                </li>
                            </ul>
                        </div>

                        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">

                            <div>
                                <button class="btn btn-sm btn-outline-primary view-details"
                                    data-bs-toggle="modal"
                                    data-bs-target="#bookingModal"
                                    data-booking-id="<?= htmlspecialchars($booking['booking_id']) ?>"
                                    data-pickup-location="<?= htmlspecialchars($booking['pickup_location']) ?>"
                                    data-pickup-lat="<?= htmlspecialchars($booking['pickup_lat']) ?>"
                                    data-pickup-lng="<?= htmlspecialchars($booking['pickup_lng']) ?>"
                                    data-dropoff-location="<?= htmlspecialchars($booking['dropoff_location']) ?>"
                                    data-dropoff-lat="<?= htmlspecialchars($booking['dropoff_lat']) ?>"
                                    data-dropoff-lng="<?= htmlspecialchars($booking['dropoff_lng']) ?>"
                                    data-date="<?= htmlspecialchars($booking['date']) ?>"
                                    data-time="<?= htmlspecialchars($booking['time']) ?>"
                                    data-distance="<?= htmlspecialchars($booking['total_distance']) ?>"
                                    data-price="<?= htmlspecialchars($booking['total_price']) ?>"
                                    data-status="<?= htmlspecialchars($booking['status']) ?>"
                                    data-vehicle-name="<?= htmlspecialchars($booking['vehicle_name']) ?>"
                                    data-plate-number="<?= htmlspecialchars($booking['platenumber']) ?>"
                                    data-vehicle-type="<?= htmlspecialchars($booking['vehicle_type']) ?>"
                                    data-vehicle-model="<?= htmlspecialchars($booking['vehicle_model']) ?>"
                                    data-vehicle-year="<?= htmlspecialchars($booking['vehicle_year']) ?>"
                                    data-customer-name="<?= htmlspecialchars($booking['customer_name']) ?>">
                                    View Details
                                </button>

                                <button class="btn btn-sm btn-outline-success"
                                    onclick="getPayment('<?= $booking['booking_id'] ?>')"
                                    data-bs-toggle="modal" data-bs-target="#paymentModal">
                                    View Payment
                                </button>

                                <button class="btn btn-sm btn-outline-secondary"
                                    data-bs-toggle="modal" data-bs-target="#commentModal"
                                    data-booking-id="<?= htmlspecialchars($booking['booking_id']) ?>"
                                    onclick="viewComments('<?= htmlspecialchars($booking['booking_id']) ?>')">
                                    <i class="fa fa-comment" aria-hidden="true"></i> Add Comment
                                </button>

                            </div>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            No bookings found.
        </div>
    <?php endif; ?>
</div>

<!-- Comment Modal -->

<div class="modal fade" id="commentModal" tabindex="-1" aria-labelledby="commentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="commentModalLabel">Add Comment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="commentForm" action="controller/comment/add-comment.php" method="POST">
                <div class="modal-body">

                    <div id="commentContainer">
                        <h6 class="mb-3">All Comments</h6>
                        <div id="commentList" class="list-group">
                            <!-- Comments will be dynamically inserted here -->
                        </div>
                    </div>

                    <input type="hidden" id="bookingId" value="" name="booking_id">
                    <div class="mb-3">
                        <label for="commentText" class="form-label">Comment</label>
                        <textarea class="form-control" id="commentText" rows="3" required name="comment_text"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="commentRating" class="form-label">Rating</label>
                        <select class="form-select" id="commentRating" required name="comment_rating">
                            <option value="" disabled selected>Select Rating</option>
                            <option value="1">1 Star</option>
                            <option value="2">2 Stars</option>
                            <option value="3">3 Stars</option>
                            <option value="4">4 Stars</option>
                            <option value="5">5 Stars</option>
                        </select>
                    </div>



                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="submitCommentBtn">Submit Comment</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Booking Details Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-geo-alt-fill me-2"></i> Booking Route & Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <!-- Booking & Vehicle Info -->
                <div class="row gx-4 gy-4 mb-4">
                    <!-- Customer & Vehicle Info -->
                    <div class="col-md-6">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body p-3">
                                <h6 class="card-title text-primary mb-3">
                                    <i class="bi bi-person-fill me-1"></i> Customer
                                </h6>
                                <p class="mb-4"><strong>Name:</strong> <span id="modalCustomer"></span></p>

                                <h6 class="card-title text-primary mb-3">
                                    <i class="bi bi-truck-front-fill me-1"></i> Vehicle Details
                                </h6>
                                <ul class="list-unstyled mb-0 ps-0">
                                    <li class="mb-2"><i class="bi bi-tag-fill me-1"></i><strong>Name:</strong> <span id="modalVehicleName"></span></li>
                                    <li class="mb-2"><i class="bi bi-car-front-fill me-1"></i><strong>Plate:</strong> <span id="modalPlateNumber"></span></li>
                                    <li class="mb-2"><i class="bi bi-truck me-1"></i><strong>Type:</strong> <span id="modalVehicleType"></span></li>
                                    <li class="mb-2"><i class="bi bi-cpu-fill me-1"></i><strong>Model:</strong> <span id="modalVehicleModel"></span></li>
                                    <li><i class="bi bi-calendar-event-fill me-1"></i><strong>Year:</strong> <span id="modalVehicleYear"></span></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Info -->
                    <div class="col-md-6">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body p-3">
                                <h6 class="card-title text-primary mb-3">
                                    <i class="bi bi-calendar-check-fill me-1"></i> Booking Information
                                </h6>
                                <ul class="list-unstyled mb-0 ps-0">
                                    <li class="mb-2"><i class="bi bi-geo-fill me-1"></i><strong>Pickup:</strong> <span id="modalPickup"></span></li>
                                    <li class="mb-2"><i class="bi bi-geo-alt-fill me-1"></i><strong>Dropoff:</strong> <span id="modalDropoff"></span></li>
                                    <li class="mb-2"><i class="bi bi-calendar-fill me-1"></i><strong>Date:</strong> <span id="modalDate"></span></li>
                                    <li class="mb-2"><i class="bi bi-clock-fill me-1"></i><strong>Time:</strong> <span id="modalTime"></span></li>
                                    <li class="mb-2"><i class="bi bi-rulers me-1"></i><strong>Distance:</strong> <span id="modalDistance"></span> km</li>
                                    <li class="mb-2"><i class="bi bi-currency-dollar me-1"></i><strong>Price:</strong> ₱<span id="modalPrice"></span></li>
                                    <li>
                                        <i class="bi bi-info-circle-fill me-1"></i><strong>Status:</strong>
                                        <span id="modalStatus" class="badge bg-info"></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map Canvas -->
                <div class="map-container">
                    <div id="map" style="height: 100%; width: 100%;"></div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <p class="mb-1"><strong>Payment ID:</strong> <span id="paymentId" class="text-muted"></span></p>
                            <p class="mb-1"><strong>Booking ID:</strong> <span id="paymentBookingId"></span></p>
                            <p class="mb-1"><strong>User ID:</strong> <span id="paymentUserId"></span></p>
                        </div>

                        <div class="mb-3">
                            <p class="mb-1"><strong>Amount Due:</strong> ₱<span id="paymentAmountDue"></span></p>
                            <p class="mb-1"><strong>Amount Paid:</strong> ₱<span id="paymentAmountPaid"></span></p>
                            <p class="mb-1"><strong>Change:</strong> ₱<span id="paymentChange"></span></p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <p class="mb-1"><strong>Payment Method:</strong> <span id="paymentMethod"></span></p>
                            <p class="mb-1"><strong>Status:</strong> <span id="paymentStatus" class="badge"></span></p>
                            <p class="mb-1"><strong>Payment Date:</strong> <span id="paymentDate"></span></p>
                        </div>

                        <div class="mb-3" id="gatewayInfo" style="display: none;">
                            <p class="mb-1"><strong>Gateway Reference:</strong> <span id="gatewayReference"></span></p>
                            <p class="mb-1"><strong>Gateway URL:</strong> <a id="gatewayUrl" target="_blank" class="text-decoration-none"></a></p>
                        </div>

                        <div class="mb-3">
                            <p class="mb-1"><strong>Receipt Number:</strong> <span id="paymentReceipt"></span></p>
                            <p class="mb-1"><strong>Notes:</strong> <span id="paymentNotes"></span></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>



<script>
    window.viewComments = bookingId => {
        const commentContainer = document.getElementById('commentContainer');
        const commentList = document.getElementById('commentList');
        const commentForm = document.getElementById('commentForm');
        const bookingIdInput = document.getElementById('bookingId');
        bookingIdInput.value = bookingId;

        // Clear previous comments
        commentList.innerHTML = '';

        // Fetch comments for the booking
        new GetRequest({
            getUrl: "controller/comment/get-comments.php",
            params: {
                booking_id: bookingId
            },
            callback: (err, data) => {
                if (err) {
                    showErrorToast("Failed to load comments");
                    console.error("Comment fetch error:", err);
                    return;
                }

                // Populate comments
                data.forEach(comment => {
                    const listItem = document.createElement('div');
                    listItem.className = 'list-group-item d-flex justify-content-between align-items-center';
                    listItem.innerHTML = `
                        <div>
                            <strong>${comment.user_name}</strong> (${comment.comment_rating} stars)
                            <p class="mb-0">${comment.comment}</p>
                        </div>
                        <small class="text-muted">${new Date(comment.created_at).toLocaleString()}</small>
                    `;
                    commentList.appendChild(listItem);
                });
                // Show comment container if there are comments
                if (data.length > 0) {
                    commentContainer.style.display = 'block';
                } else {
                    commentContainer.style.display = 'none';
                }
            }
        }).send();

    };

    const createRequest = new CreateRequest({
        formSelector: "#commentForm",
        submitButtonSelector: "#submitCommentBtn",
        callback: (err, res) => err ? console.error("Form submission error:", err) : console.log(
            "Form submitted successfully:", res)
    });


    // Updated payment handling function


    window.getPayment = paymentId => {
        new GetRequest({
            getUrl: "controller/payment/get-payment.php",
            params: {
                uid: paymentId
            },
            callback: (err, data) => {
                if (err) {
                    showErrorToast("Failed to load payment details");
                    console.error("Payment fetch error:", err);
                    return;
                }

                // Update modal content
                document.getElementById('paymentId').textContent = data.payment_id;
                document.getElementById('paymentBookingId').textContent = data.booking_id;
                document.getElementById('paymentUserId').textContent = data.user_id;

                document.getElementById('paymentAmountDue').textContent = parseFloat(data.amount_due).toFixed(2);
                document.getElementById('paymentAmountPaid').textContent = parseFloat(data.amount_received).toFixed(2);
                document.getElementById('paymentChange').textContent = parseFloat(data.change_amount).toFixed(2);

                const statusBadge = document.getElementById('paymentStatus');
                statusBadge.textContent = data.payment_status;
                statusBadge.className = 'badge bg-' + getPaymentStatusColor(data.payment_status);

                document.getElementById('paymentMethod').textContent = data.payment_method;
                document.getElementById('paymentDate').textContent = data.paid_at || 'N/A';
                document.getElementById('paymentReceipt').textContent = data.receipt_number || 'N/A';
                document.getElementById('paymentNotes').textContent = data.notes || 'No notes';

                // Handle gateway information
                const gatewayInfo = document.getElementById('gatewayInfo');
                if (data.gateway_reference) {
                    gatewayInfo.style.display = 'block';
                    document.getElementById('gatewayReference').textContent = data.gateway_reference;
                    document.getElementById('gatewayUrl').href = data.gateway_url || '#';
                    document.getElementById('gatewayUrl').textContent = data.gateway_url ? 'View Transaction' : 'N/A';
                } else {
                    gatewayInfo.style.display = 'none';
                }

                // Show the modal
                new bootstrap.Modal(document.getElementById('paymentModal')).show();
            }
        }).send();
    };

    function getPaymentStatusColor(status) {
        switch (status.toLowerCase()) {
            case 'paid':
                return 'success';
            case 'pending':
                return 'warning';
            case 'partial':
                return 'info';
            case 'failed':
                return 'danger';
            case 'refunded':
                return 'secondary';
            case 'cancelled':
                return 'dark';
            default:
                return 'primary';
        }
    }

    // Helper function for error messages
    function showErrorToast(message) {
        // Implement your toast notification system here
        console.error('Error:', message);
    }
</script>


<style>
    .card {
        transition: transform 0.2s, box-shadow 0.2s;
        border-radius: 15px;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }

    .badge {
        font-size: 0.85em;
        padding: 0.5em 0.75em;
    }

    #map {
        min-height: 500px;
    }
</style>


<!-- Leaflet & Routing Libraries -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js"></script>

<script>
    // Initialize variables for the map and route
    let routeMap, routeControl;

    let map;

    const apiKey = 'eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6IjRlZWI0MGI3NzljNTQ0MGU5MmQyY2Q1MjkwYTgxNmZlIiwiaCI6Im11cm11cjY0In0=';

    function initMap() {
        // Initialize the map only if it doesn't exist
        if (!map) {
            map = L.map('map').setView([10.3157, 123.8854], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
        }
        return map;
    }

    // Function to show route with Leaflet
    function showRoute(pickupLat, pickupLng, dropoffLat, dropoffLng) {
        const map = initMap();

        // Clear existing route if any
        if (routeControl) {
            map.removeControl(routeControl);
        }

        const pickup = L.latLng(pickupLat, pickupLng);
        const dropoff = L.latLng(dropoffLat, dropoffLng);

        // Create a simple route with markers
        routeControl = L.Routing.control({
            waypoints: [pickup, dropoff],
            routeWhileDragging: false,
            show: true,
            addWaypoints: false,
            draggableWaypoints: false,
            lineOptions: {
                styles: [{
                    color: '#4e73df',
                    opacity: 0.8,
                    weight: 6
                }]
            },
            createMarker: function(i, wp, n) {
                const icon = i === 0 ?
                    L.divIcon({
                        className: 'start-marker',
                        html: '<i class="bi bi-geo-alt-fill text-primary"></i>'
                    }) :
                    L.divIcon({
                        className: 'end-marker',
                        html: '<i class="bi bi-flag-fill text-danger"></i>'
                    });

                return L.marker(wp.latLng, {
                    icon: icon
                });
            }
        }).addTo(map);

        // Fit the map to the route bounds
        const bounds = L.latLngBounds(pickup, dropoff);
        map.fitBounds(bounds, {
            padding: [50, 50]
        });
    }
    // Modal event handlers
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('bookingModal');

        modal.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            const bookingData = {
                pickup: button.dataset.pickupLocation,
                pickupLat: parseFloat(button.dataset.pickupLat),
                pickupLng: parseFloat(button.dataset.pickupLng),
                dropoff: button.dataset.dropoffLocation,
                dropoffLat: parseFloat(button.dataset.dropoffLat),
                dropoffLng: parseFloat(button.dataset.dropoffLng),
                date: button.dataset.date,
                time: button.dataset.time,
                distance: button.dataset.distance,
                price: parseFloat(button.dataset.price).toFixed(2),
                status: button.dataset.status,
                vehicleName: button.dataset.vehicleName,
                plateNumber: button.dataset.plateNumber,
                vehicleType: button.dataset.vehicleType,
                vehicleModel: button.dataset.vehicleModel,
                vehicleYear: button.dataset.vehicleYear,
                customerName: button.dataset.customerName
            };

            // Update modal content
            document.getElementById('modalPickup').textContent = bookingData.pickup;
            document.getElementById('modalDropoff').textContent = bookingData.dropoff;
            document.getElementById('modalDate').textContent = bookingData.date;
            document.getElementById('modalTime').textContent = bookingData.time;
            document.getElementById('modalDistance').textContent = bookingData.distance + ' km';
            document.getElementById('modalPrice').textContent = bookingData.price;
            document.getElementById('modalCustomer').textContent = bookingData.customerName;
            document.getElementById('modalVehicleName').textContent = bookingData.vehicleName;
            document.getElementById('modalPlateNumber').textContent = bookingData.plateNumber;
            document.getElementById('modalVehicleType').textContent = bookingData.vehicleType;
            document.getElementById('modalVehicleModel').textContent = bookingData.vehicleModel;
            document.getElementById('modalVehicleYear').textContent = bookingData.vehicleYear;

            setTimeout(() => {

                showRoute(bookingData.pickupLat, bookingData.pickupLng, bookingData.dropoffLat, bookingData.dropoffLng);
            }, 300);
        });

        modal.addEventListener('hidden.bs.modal', () => {

            if (routeControl) {
                routeMap.removeControl(routeControl);
                routeControl = null;
            }
        });
    });
</script>