<?php
// QR Code Payment page
// This page generates a QR code for customers to scan for payment

$booking_id = isset($_GET['booking_id']) ? $_GET['booking_id'] : '';

if (empty($booking_id)) {
    header('Location: customer-dashboard');
    exit;
}

// Get booking and payment details
$stmt = $conn->prepare("
    SELECT b.*, p.amount_due, p.payment_status, p.gateway_url, p.gateway_reference,
           v.name as vehicle_name, u.full_name, u.email_address 
    FROM bookings b 
    JOIN payments p ON b.booking_id = p.booking_id
    JOIN vehicles v ON b.vehicle_id = v.vehicleid 
    JOIN users u ON b.user_id = u.uid 
    WHERE b.booking_id = ?
");

$stmt->bind_param("s", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    header('Location: customer-dashboard');
    exit;
}

$amount = number_format($booking['amount_due'], 2);
$qrData = "BMoveXpress Payment|Booking:{$booking_id}|Amount:PHP {$amount}";
$qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrData);
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <div class="card-body text-center p-5">
                    <div class="mb-3">
                        <i class="bi bi-qr-code-scan text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h3 class="mb-2">Scan to Pay</h3>
                    <p class="text-muted mb-4">Use your e-wallet or banking app to scan this QR code</p>

                    <!-- QR Code Image -->
                    <div class="mb-4">
                        <img src="<?= htmlspecialchars($qrApiUrl) ?>" alt="Payment QR Code" class="img-fluid rounded" style="max-width: 300px;">
                    </div>

                    <!-- Payment Details -->
                    <div class="alert alert-light text-start mb-4">
                        <div class="row mb-2">
                            <div class="col-6"><small class="text-muted">Booking ID:</small><br><strong><?= htmlspecialchars($booking_id) ?></strong></div>
                            <div class="col-6"><small class="text-muted">Vehicle:</small><br><strong><?= htmlspecialchars($booking['vehicle_name']) ?></strong></div>
                        </div>
                        <hr class="my-2">
                        <div class="row">
                            <div class="col-6"><small class="text-muted">Amount Due:</small><br><strong class="text-primary fs-5">₱<?= $amount ?></strong></div>
                            <div class="col-6"><small class="text-muted">Status:</small><br>
                                <span class="badge bg-<?= $booking['payment_status'] === 'paid' ? 'success' : 'warning' ?>">
                                    <?= ucfirst(htmlspecialchars($booking['payment_status'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info small text-start">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Instructions:</strong>
                        <ol class="mb-0 mt-1">
                            <li>Open your GCash, Maya, or any QR Ph-compatible banking app</li>
                            <li>Scan the QR code above</li>
                            <li>Confirm the payment amount of <strong>₱<?= $amount ?></strong></li>
                            <li>Complete the transaction</li>
                        </ol>
                    </div>

                    <?php if (!empty($booking['gateway_url'])): ?>
                        <div class="mb-3">
                            <p class="text-muted small">Or pay online directly:</p>
                            <a href="<?= htmlspecialchars($booking['gateway_url']) ?>" class="btn btn-primary">
                                <i class="bi bi-credit-card me-2"></i>Pay Online Instead
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="d-grid gap-2 mt-3">
                        <a href="customer-dashboard" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
