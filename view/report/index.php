<?php

$reportType = isset($_GET['report-type']) ? $_GET['report-type'] : 'bookings';
$startDate = isset($_GET['date-start']) ? $_GET['date-start'] : date('Y-m-01');
$endDate = isset($_GET['date-end']) ? $_GET['date-end'] : date('Y-m-d');
$reportData = [];
$reportTitle = '';


switch ($reportType) {
    case 'bookings':
        $reportTitle = 'Bookings Report';
        $result = $conn->prepare("
            SELECT b.booking_id, u.full_name, v.name AS vehicle_name, 
                   b.pickup_location, b.dropoff_location, 
                   b.date, b.time, b.total_price, b.status,
                   DATE_FORMAT(b.created_at, '%Y-%m-%d %H:%i') AS created_at
            FROM bookings b
            JOIN users u ON b.user_id = u.uid
            LEFT JOIN vehicles v ON b.vehicle_id = v.vehicleid
            WHERE DATE(b.created_at) BETWEEN ? AND ?
            ORDER BY b.created_at DESC
        ");
        $result->bind_param("ss", $startDate, $endDate);
        $result->execute();
        $reportData = $result->get_result()->fetch_all(MYSQLI_ASSOC);
        break;

    case 'revenue':
        $reportTitle = 'Revenue Report';
        $result = $conn->prepare("
            SELECT p.payment_id, b.booking_id, u.full_name, 
                   p.amount_due, p.amount_received, p.payment_method, 
                   p.payment_status, p.paid_at, p.receipt_number,
                   DATE_FORMAT(p.paid_at, '%Y-%m-%d %H:%i') AS paid_at_formatted
            FROM payments p
            JOIN bookings b ON p.booking_id = b.booking_id
            JOIN users u ON p.user_id = u.uid
            WHERE DATE(p.paid_at) BETWEEN ? AND ? AND p.payment_status = 'paid'
            ORDER BY p.paid_at DESC
        ");
        $result->bind_param("ss", $startDate, $endDate);
        $result->execute();
        $reportData = $result->get_result()->fetch_all(MYSQLI_ASSOC);
        break;

    case 'vehicles':
        $reportTitle = 'Vehicle Utilization Report';
        $result = $conn->prepare("
            SELECT 
                v.vehicleid, v.name, v.platenumber, v.type, v.model, v.year,
                COUNT(b.booking_id) AS total_bookings,
                SUM(b.total_price) AS total_revenue,
                SUM(b.total_distance) AS total_distance,
                SEC_TO_TIME(AVG(TIMEDIFF(b.dropoff_time, b.pickup_time))) AS avg_usage_time,
                v.status
            FROM vehicles v
            LEFT JOIN bookings b ON v.vehicleid = b.vehicle_id
            WHERE b.date BETWEEN ? AND ? OR b.date IS NULL
            GROUP BY v.vehicleid
            ORDER BY total_bookings DESC
        ");
        $result->bind_param("ss", $startDate, $endDate);
        $result->execute();
        $reportData = $result->get_result()->fetch_all(MYSQLI_ASSOC);
        break;

    case 'customers':
        $reportTitle = 'Customer Activity Report';
        $result = $conn->prepare("
            SELECT 
                u.uid, u.full_name, u.email_address, u.contact_number,
                COUNT(b.booking_id) AS total_bookings,
                SUM(p.amount_received) AS total_spent,
                MAX(b.date) AS last_booking_date
            FROM users u
            LEFT JOIN bookings b ON u.uid = b.user_id
            LEFT JOIN payments p ON b.booking_id = p.booking_id
            WHERE b.date BETWEEN ? AND ? OR b.date IS NULL
            GROUP BY u.uid
            ORDER BY total_bookings DESC
        ");
        $result->bind_param("ss", $startDate, $endDate);
        $result->execute();
        $reportData = $result->get_result()->fetch_all(MYSQLI_ASSOC);
        break;

    case 'ratings':
        $reportTitle = 'Customer Ratings Report';
        $result = $conn->prepare("
            SELECT 
                c.comment_id as rating_id,
                b.booking_id,
                u.full_name AS customer_name,
                v.name AS vehicle_name,
                COALESCE(d.full_name, 'No Driver Assigned') AS driver_name,
                c.comment_rating as overall_rating,
                c.comment AS comments,
                DATE_FORMAT(c.created_at, '%Y-%m-%d %H:%i') AS rated_at,
                DATE_FORMAT(b.date, '%Y-%m-%d') AS booking_date,
                b.pickup_location,
                b.dropoff_location
            FROM comments c
            JOIN bookings b ON c.booking_id = b.booking_id
            JOIN users u ON c.user_id = u.uid
            LEFT JOIN vehicles v ON b.vehicle_id = v.vehicleid
            LEFT JOIN users d ON v.driver_uid = d.uid
            WHERE DATE(c.created_at) BETWEEN ? AND ?
            ORDER BY c.created_at DESC
        ");
        $result->bind_param("ss", $startDate, $endDate);
        $result->execute();
        $reportData = $result->get_result()->fetch_all(MYSQLI_ASSOC);
        break;

    case 'ratings_summary':
        $reportTitle = 'Ratings Summary Report';
        $result = $conn->prepare("
            SELECT 
                'Overall Rating' AS category,
                COUNT(*) AS total_ratings,
                ROUND(AVG(comment_rating), 2) AS average_rating,
                MIN(comment_rating) AS min_rating,
                MAX(comment_rating) AS max_rating,
                SUM(CASE WHEN comment_rating = 5 THEN 1 ELSE 0 END) AS five_stars,
                SUM(CASE WHEN comment_rating = 4 THEN 1 ELSE 0 END) AS four_stars,
                SUM(CASE WHEN comment_rating = 3 THEN 1 ELSE 0 END) AS three_stars,
                SUM(CASE WHEN comment_rating = 2 THEN 1 ELSE 0 END) AS two_stars,
                SUM(CASE WHEN comment_rating = 1 THEN 1 ELSE 0 END) AS one_star,
                ROUND((SUM(CASE WHEN comment_rating >= 4 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) AS satisfaction_percentage
            FROM comments
            WHERE DATE(created_at) BETWEEN ? AND ?
        ");
        $result->bind_param("ss", $startDate, $endDate);
        $result->execute();
        $reportData = $result->get_result()->fetch_all(MYSQLI_ASSOC);
        break;
}

$conn->close();
?>


<style>
    .stat-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: 500;
    }

    .bg-pending {
        background-color: rgba(243, 156, 18, 0.1);
        color: #f39c12;
    }

    .bg-confirmed {
        background-color: rgba(52, 152, 219, 0.1);
        color: #3498db;
    }

    .bg-in_progress {
        background-color: rgba(46, 204, 113, 0.1);
        color: #2ecc71;
    }

    .bg-completed {
        background-color: rgba(41, 128, 185, 0.1);
        color: #2980b9;
    }

    .bg-cancelled {
        background-color: rgba(231, 76, 60, 0.1);
        color: #e74c3c;
    }

    .star-rating {
        color: #ffc107;
        font-size: 14px;
        font-family: monospace;
    }

    .star-rating.small {
        font-size: 12px;
    }

    .star-rating.overall {
        font-weight: bold;
        font-size: 16px;
    }

    .rating-number {
        color: #6c757d;
        font-size: 12px;
        margin-left: 5px;
    }

    .report-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-bottom: 20px;
    }

    .date-range-container {
        background-color: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
    }

    .summary-card {
        text-align: center;
        padding: 20px;
        border-radius: 10px;
        background-color: white;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
    }

    .summary-card .number {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 10px 0;
    }

    .summary-card .title {
        font-size: 1rem;
        color: #6c757d;
    }

    .chart-container {
        height: 300px;
        margin-top: 20px;
    }

    .report-title {
        border-left: 5px solid var(--primary-color);
        padding-left: 15px;
        margin: 25px 0;
    }

    .table-responsive {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        padding: 20px;
    }

    .table {
        margin-bottom: 0;
    }

    .table th {
        background-color: #f8f9fa;
        border-top: none;
        font-weight: 600;
        color: #495057;
        padding: 12px 8px;
    }

    .table td {
        padding: 10px 8px;
        vertical-align: middle;
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.02);
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.05);
    }

    .status-indicator {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 8px;
    }

    .status-pending {
        background-color: #f39c12;
    }

    .status-confirmed {
        background-color: #3498db;
    }

    .status-in_progress {
        background-color: #2ecc71;
    }

    .status-completed {
        background-color: #2980b9;
    }

    .status-cancelled {
        background-color: #e74c3c;
    }
</style>

<main id="main" class="main">
    <div class="pagetitle">
        <h1><?php echo $title ?></h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard">Home</a></li>
                <li class="breadcrumb-item active"><?php echo $title ?></li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="report-card">
                    <div class="card-body">
                        <h5 class="card-title">Generate Report</h5>

                        <form action="" method="GET" class="row">
                            <div class="date-range-container">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="report-type" class="form-label">Report Type:</label>
                                        <select id="report-type" name="report-type" class="form-select">
                                            <option value="bookings" <?= $reportType === 'bookings' ? 'selected' : '' ?>>Bookings Report</option>
                                            <option value="revenue" <?= $reportType === 'revenue' ? 'selected' : '' ?>>Revenue Report</option>
                                            <option value="vehicles" <?= $reportType === 'vehicles' ? 'selected' : '' ?>>Vehicle Utilization</option>
                                            <option value="customers" <?= $reportType === 'customers' ? 'selected' : '' ?>>Customer Activity</option>
                                            <option value="ratings" <?= $reportType === 'ratings' ? 'selected' : '' ?>>User Ratings</option>
                                            <option value="ratings_summary" <?= $reportType === 'ratings_summary' ? 'selected' : '' ?>>Ratings Summary</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="date-start" class="form-label">Start Date:</label>
                                        <input type="text" id="date-start" name="date-start" class="form-control datepicker" placeholder="Select start date" autocomplete="off" value="<?= $startDate ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="date-end" class="form-label">End Date:</label>
                                        <input type="text" id="date-end" name="date-end" class="form-control datepicker" placeholder="Select end date" autocomplete="off" value="<?= $endDate ?>">
                                    </div>
                                    <div class="col-md-3 mb-3 d-flex align-items-end">
                                        <button id="generate-report" class="btn btn-primary w-100">
                                            <i class="bi bi-arrow-repeat me-1"></i> Generate Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="report-actions">
                            <?php if (!empty($reportData)): ?>
                                <button class="btn btn-primary" id="export-pdf">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> Export as PDF
                                </button>
                                <button class="btn btn-success" id="download-pdf">
                                    <i class="bi bi-download me-1"></i> Download PDF
                                </button>
                            <?php endif; ?>
                        </div>

                        <h3 class="report-title"><?= $reportTitle ?>: <?= date('M d, Y', strtotime($startDate)) ?> - <?= date('M d, Y', strtotime($endDate)) ?></h3>

                        <div class="table-responsive">
                            <table class="table table-hover table-striped" id="report-table">
                                <thead>
                                    <?php if ($reportType === 'bookings'): ?>
                                        <tr>
                                            <th>Booking ID</th>
                                            <th>Customer</th>
                                            <th>Vehicle</th>
                                            <th>Pickup</th>
                                            <th>Dropoff</th>
                                            <th>Date & Time</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Created At</th>
                                        </tr>

                                    <?php elseif ($reportType === 'revenue'): ?>
                                        <tr>
                                            <th>Payment ID</th>
                                            <th>Booking ID</th>
                                            <th>Customer</th>
                                            <th>Amount Due</th>
                                            <th>Amount Paid</th>
                                            <th>Method</th>
                                            <th>Status</th>
                                            <th>Paid At</th>
                                            <th>Receipt #</th>
                                        </tr>

                                    <?php elseif ($reportType === 'vehicles'): ?>
                                        <tr>
                                            <th>Vehicle</th>
                                            <th>Plate Number</th>
                                            <th>Type</th>
                                            <th>Bookings</th>
                                            <th>Revenue</th>
                                            <th>Distance</th>
                                            <th>Avg. Usage</th>
                                            <th>Status</th>
                                        </tr>

                                    <?php elseif ($reportType === 'customers'): ?>
                                        <tr>
                                            <th>Customer</th>
                                            <th>Contact</th>
                                            <th>Bookings</th>
                                            <th>Total Spent</th>
                                            <th>Last Booking</th>
                                        </tr>

                                    <?php elseif ($reportType === 'ratings'): ?>
                                        <tr>
                                            <th>Rating ID</th>
                                            <th>Booking ID</th>
                                            <th>Customer</th>
                                            <th>Vehicle</th>
                                            <th>Driver</th>
                                            <th>Rating</th>
                                            <th>Comments</th>
                                            <th>Rated At</th>
                                            <th>Booking Date</th>
                                        </tr>

                                    <?php elseif ($reportType === 'ratings_summary'): ?>
                                        <tr>
                                            <th>Category</th>
                                            <th>Total Ratings</th>
                                            <th>Average</th>
                                            <th>Min</th>
                                            <th>Max</th>
                                            <th>5★</th>
                                            <th>4★</th>
                                            <th>3★</th>
                                            <th>2★</th>
                                            <th>1★</th>
                                            <th>Satisfaction %</th>
                                        </tr>

                                    <?php endif; ?>
                                </thead>
                                <tbody>
                                    <?php foreach ($reportData as $row): ?>
                                        <?php if ($reportType === 'bookings'): ?>
                                            <tr>
                                                <td><?= $row['booking_id'] ?></td>
                                                <td><?= $row['full_name'] ?></td>
                                                <td><?= $row['vehicle_name'] ?? 'N/A' ?></td>
                                                <td><?= $row['pickup_location'] ?></td>
                                                <td><?= $row['dropoff_location'] ?></td>
                                                <td><?= date('M d, Y', strtotime($row['date'])) . ' ' . $row['time'] ?></td>
                                                <td class="fw-bold">₱<?= number_format($row['total_price'], 2) ?></td>
                                                <td>
                                                    <span class="status-indicator status-<?= $row['status'] ?>"></span>
                                                    <?= ucwords(str_replace('_', ' ', $row['status'])) ?>
                                                </td>
                                                <td><?= $row['created_at'] ?></td>
                                            </tr>

                                        <?php elseif ($reportType === 'revenue'): ?>
                                            <tr>
                                                <td><?= $row['payment_id'] ?></td>
                                                <td><?= $row['booking_id'] ?></td>
                                                <td><?= $row['full_name'] ?></td>
                                                <td>₱<?= number_format($row['amount_due'], 2) ?></td>
                                                <td class="fw-bold">₱<?= number_format($row['amount_received'], 2) ?></td>
                                                <td><?= ucfirst($row['payment_method']) ?></td>
                                                <td><?= ucfirst($row['payment_status']) ?></td>
                                                <td><?= $row['paid_at_formatted'] ?></td>
                                                <td><?= $row['receipt_number'] ?? 'N/A' ?></td>
                                            </tr>

                                        <?php elseif ($reportType === 'vehicles'): ?>
                                            <tr>
                                                <td><?= $row['name'] ?> (<?= $row['model'] ?>)</td>
                                                <td><?= $row['platenumber'] ?></td>
                                                <td><?= $row['type'] ?></td>
                                                <td><?= $row['total_bookings'] ?></td>
                                                <td class="fw-bold">₱<?= number_format($row['total_revenue'] ?? 0, 2) ?></td>
                                                <td><?= number_format($row['total_distance'] ?? 0) ?> km</td>
                                                <td><?= $row['avg_usage_time'] ?? 'N/A' ?></td>
                                                <td><?= ucwords($row['status']) ?></td>
                                            </tr>

                                        <?php elseif ($reportType === 'customers'): ?>
                                            <tr>
                                                <td><?= $row['full_name'] ?></td>
                                                <td><?= $row['email_address'] ?><br><?= $row['contact_number'] ?></td>
                                                <td><?= $row['total_bookings'] ?></td>
                                                <td class="fw-bold">₱<?= number_format($row['total_spent'] ?? 0, 2) ?></td>
                                                <td><?= $row['last_booking_date'] ? date('M d, Y', strtotime($row['last_booking_date'])) : 'N/A' ?></td>
                                            </tr>

                                        <?php elseif ($reportType === 'ratings'): ?>
                                            <tr>
                                                <td><?= substr($row['rating_id'], 0, 8) ?>...</td>
                                                <td><?= substr($row['booking_id'], 0, 8) ?>...</td>
                                                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                                <td><?= htmlspecialchars($row['vehicle_name'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($row['driver_name']) ?></td>
                                                <td>
                                                    <span class="star-rating overall">
                                                        <?= str_repeat('*', $row['overall_rating']) ?><?= str_repeat('o', 5 - $row['overall_rating']) ?>
                                                        <span class="rating-number">(<?= $row['overall_rating'] ?>/5)</span>
                                                    </span>
                                                </td>
                                                <td><?= $row['comments'] ? nl2br(htmlspecialchars(substr($row['comments'], 0, 100))) . (strlen($row['comments']) > 100 ? '...' : '') : 'No comments' ?></td>
                                                <td><?= $row['rated_at'] ?></td>
                                                <td><?= $row['booking_date'] ?></td>
                                            </tr>

                                        <?php elseif ($reportType === 'ratings_summary'): ?>
                                            <tr>
                                                <td class="fw-bold"><?= $row['category'] ?></td>
                                                <td><?= $row['total_ratings'] ?></td>
                                                <td>
                                                    <span class="fw-bold text-primary"><?= number_format($row['average_rating'], 1) ?></span>
                                                    <span class="star-rating small">
                                                        <?= str_repeat('*', round($row['average_rating'])) ?><?= str_repeat('o', 5 - round($row['average_rating'])) ?>
                                                    </span>
                                                </td>
                                                <td><?= $row['min_rating'] ?></td>
                                                <td><?= $row['max_rating'] ?></td>
                                                <td class="text-success"><?= $row['five_stars'] ?></td>
                                                <td class="text-info"><?= $row['four_stars'] ?></td>
                                                <td class="text-warning"><?= $row['three_stars'] ?></td>
                                                <td class="text-warning"><?= $row['two_stars'] ?></td>
                                                <td class="text-danger"><?= $row['one_star'] ?></td>
                                                <td class="fw-bold text-success"><?= $row['satisfaction_percentage'] ?>%</td>
                                            </tr>

                                        <?php endif; ?>
                                    <?php endforeach; ?>

                                    <?php if (empty($reportData)): ?>
                                        <tr>
                                            <td colspan="10" class="text-center py-5">
                                                <i class="bi bi-database-exclamation fs-1 text-muted"></i>
                                                <h4 class="mt-3">No data found for selected criteria</h4>
                                                <p class="text-muted">Try adjusting your date range or report type</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if (!empty($reportData)): ?>
                            <div class="row mt-5">
                                <div class="col-md-12">
                                    <div class="report-card">
                                        <div class="card-body">
                                            <h5 class="card-title">Visual Summary</h5>
                                            <div class="chart-container">
                                                <canvas id="report-chart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <style>
                .star-rating {
                    color: #ffc107;
                    font-size: 14px;
                }

                .star-rating.overall {
                    font-size: 16px;
                    font-weight: bold;
                }

                .star-rating.small {
                    font-size: 12px;
                }
            </style>
        </div>
    </section>
</main>
</div>


<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    $(document).ready(function() {

        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true
        });


        // DataTable removed - using normal responsive table


        $('#print-report').click(function() {
            window.print();
        });


        // PDF Export functionality using DOMPDF
        $('#export-pdf').click(function() {
            const button = this;
            const originalText = button.innerHTML;
            
            // Show loading state
            button.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Generating PDF...';
            button.disabled = true;

            const reportData = {
                report_type: '<?= $reportType ?>',
                start_date: '<?= $startDate ?>',
                end_date: '<?= $endDate ?>'
            };

            fetch('controller/report/generate-pdf.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(reportData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Open PDF in new tab
                    window.open(data.download_url, '_blank');
                    
                    // Show success message
                    showNotification('PDF generated successfully!', 'success');
                } else {
                    showNotification('Error generating PDF: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error generating PDF. Please try again.', 'error');
            })
            .finally(() => {
                // Restore button state
                button.innerHTML = originalText;
                button.disabled = false;
            });
        });

        // Download PDF functionality
        $('#download-pdf').click(function() {
            const button = this;
            const originalText = button.innerHTML;
            
            // Show loading state
            button.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Downloading...';
            button.disabled = true;

            const reportData = {
                report_type: '<?= $reportType ?>',
                start_date: '<?= $startDate ?>',
                end_date: '<?= $endDate ?>'
            };

            fetch('controller/report/generate-pdf.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(reportData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Create download link
                    const link = document.createElement('a');
                    link.href = data.download_url;
                    link.download = data.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    showNotification('PDF downloaded successfully!', 'success');
                } else {
                    showNotification('Error generating PDF: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error downloading PDF. Please try again.', 'error');
            })
            .finally(() => {
                // Restore button state
                button.innerHTML = originalText;
                button.disabled = false;
            });
        });

        // Notification function
        function showNotification(message, type) {
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


        <?php if (!empty($reportData)): ?>
            const ctx = document.getElementById('report-chart').getContext('2d');

            <?php if ($reportType === 'bookings'): ?>
                const statusCounts = {
                    pending: <?= count(array_filter($reportData, fn($item) => $item['status'] === 'pending')) ?>,
                    confirmed: <?= count(array_filter($reportData, fn($item) => $item['status'] === 'confirmed')) ?>,
                    in_progress: <?= count(array_filter($reportData, fn($item) => $item['status'] === 'in_progress')) ?>,
                    completed: <?= count(array_filter($reportData, fn($item) => $item['status'] === 'completed')) ?>,
                    cancelled: <?= count(array_filter($reportData, fn($item) => $item['status'] === 'cancelled')) ?>
                };

                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Pending', 'Confirmed', 'In Progress', 'Completed', 'Cancelled'],
                        datasets: [{
                            data: Object.values(statusCounts),
                            backgroundColor: [
                                'rgba(243, 156, 18, 0.7)',
                                'rgba(52, 152, 219, 0.7)',
                                'rgba(46, 204, 113, 0.7)',
                                'rgba(41, 128, 185, 0.7)',
                                'rgba(231, 76, 60, 0.7)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Booking Status Distribution'
                            }
                        }
                    }
                });

            <?php elseif ($reportType === 'revenue'): ?>
                const dailyRevenue = {
                    <?php
                    $revenueByDay = [];
                    foreach ($reportData as $row) {
                        $day = date('Y-m-d', strtotime($row['paid_at_formatted']));
                        if (!isset($revenueByDay[$day])) {
                            $revenueByDay[$day] = 0;
                        }
                        $revenueByDay[$day] += $row['amount_received'];
                    }
                    ksort($revenueByDay);

                    foreach ($revenueByDay as $day => $amount) {
                        echo "'" . date('M d', strtotime($day)) . "': $amount,";
                    }
                    ?>
                };

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: Object.keys(dailyRevenue),
                        datasets: [{
                            label: 'Daily Revenue (₱)',
                            data: Object.values(dailyRevenue),
                            backgroundColor: 'rgba(67, 97, 238, 0.7)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Daily Revenue Breakdown'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '₱' + value;
                                    }
                                }
                            }
                        }
                    }
                });

            <?php elseif ($reportType === 'vehicles'): ?>
                const vehicleRevenue = {
                    <?php

                    usort($reportData, function ($a, $b) {
                        return $b['total_revenue'] <=> $a['total_revenue'];
                    });
                    $topVehicles = array_slice($reportData, 0, 10);

                    foreach ($topVehicles as $vehicle) {
                        echo "'" . $vehicle['name'] . "': " . ($vehicle['total_revenue'] ?? 0) . ",";
                    }
                    ?>
                };

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: Object.keys(vehicleRevenue),
                        datasets: [{
                            label: 'Revenue Generated (₱)',
                            data: Object.values(vehicleRevenue),
                            backgroundColor: 'rgba(46, 204, 113, 0.7)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Top Vehicles by Revenue'
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '₱' + value;
                                    }
                                }
                            }
                        }
                    }
                });

            <?php elseif ($reportType === 'customers'): ?>
                const customerSpending = {
                    <?php

                    usort($reportData, function ($a, $b) {
                        return $b['total_spent'] <=> $a['total_spent'];
                    });
                    $topCustomers = array_slice($reportData, 0, 10);

                    foreach ($topCustomers as $customer) {
                        $name = explode(' ', $customer['full_name'])[0];
                        echo "'$name': " . ($customer['total_spent'] ?? 0) . ",";
                    }
                    ?>
                };

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: Object.keys(customerSpending),
                        datasets: [{
                            label: 'Total Spent (₱)',
                            data: Object.values(customerSpending),
                            backgroundColor: 'rgba(155, 89, 182, 0.7)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Top Customers by Spending'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '₱' + value;
                                    }
                                }
                            }
                        }
                    }
                });
            <?php endif; ?>
        <?php endif; ?>
    });
</script>