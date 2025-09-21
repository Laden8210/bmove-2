<?php


// Get booking ID from URL
$booking_id = isset($_GET['booking_id']) ? $_GET['booking_id'] : '';

if (empty($booking_id)) {
    header('Location: ../error/404.php');
    exit;
}

// update payment status to paid
$updatePayment = $conn->prepare("UPDATE payments SET payment_status = 'paid' WHERE booking_id = ?");
$updatePayment->bind_param("s", $booking_id);
$updatePayment->execute();


// Get booking details
$stmt = $conn->prepare("
    SELECT b.*, v.name as vehicle_name, u.full_name, u.email_address 
    FROM bookings b 
    JOIN vehicles v ON b.vehicle_id = v.vehicleid 
    JOIN users u ON b.user_id = u.uid 
    WHERE b.booking_id = ?
");

$stmt->bind_param("s", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();





if (!$booking) {
    header('Location: ../error/404.php');
    exit;
}

$pageTitle = "Payment Success - BMove";

?>

<body>
    <div class="container-fluid">
        <div class="row min-vh-100">
            <div class="col-12 d-flex align-items-center justify-content-center">
                <div class="card shadow-lg" style="max-width: 500px; width: 100%;">
                    <div class="card-body text-center p-5">
                        <!-- Success Icon -->
                        <div class="mb-4">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        </div>
                        
                        <!-- Success Message -->
                        <h2 class="card-title text-success mb-3">Payment Successful!</h2>
                        <p class="card-text text-muted mb-4">
                            Your booking has been confirmed and payment has been processed successfully.
                        </p>
                        
                        <!-- Booking Details -->
                        <div class="alert alert-light text-start mb-4">
                            <h6 class="alert-heading mb-3"><i class="bi bi-calendar-check me-2"></i>Booking Details</h6>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Booking ID:</small><br>
                                    <strong><?= htmlspecialchars($booking['booking_id']) ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Vehicle:</small><br>
                                    <strong><?= htmlspecialchars($booking['vehicle_name']) ?></strong>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Date & Time:</small><br>
                                    <strong><?= date('M d, Y', strtotime($booking['date'])) ?> at <?= date('H:i', strtotime($booking['time'])) ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Total Amount:</small><br>
                                    <strong>₱<?= number_format($booking['total_price'], 2) ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="d-grid gap-2">
                            <a href="customer-dashboard" class="btn btn-primary btn-lg">
                                <i class="bi bi-house me-2"></i>Go to Dashboard
                            </a>
                            <a href="book" class="btn btn-outline-secondary">
                                <i class="bi bi-plus-circle me-2"></i>Make Another Booking
                            </a>
                        </div>
                        
                        <!-- Additional Info -->
                        <div class="mt-4">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                You will receive a confirmation email at <?= htmlspecialchars($booking['email_address']) ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
