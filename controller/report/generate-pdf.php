<?php

require_once '../../config/config.php';
require_once '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Set session configuration BEFORE starting session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $_SERVER['HTTP_HOST'] !== 'localhost');
ini_set('session.use_strict_mode', 1);

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Request method must be POST', 'http_code' => 405]);
    exit;
}

if (!isset($_SESSION['auth']['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in', 'http_code' => 401]);
    exit;
}

$request_body = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Request body is not valid JSON', 'http_code' => 400]);
    exit;
}

// Validate required fields
$requiredFields = ['report_type', 'start_date', 'end_date'];
foreach ($requiredFields as $field) {
    if (empty($request_body[$field])) {
        echo json_encode(['status' => 'error', 'message' => "Missing required field: $field", 'http_code' => 400]);
        exit;
    }
}

$reportType = filter_var($request_body['report_type'], FILTER_SANITIZE_STRING);
$startDate = filter_var($request_body['start_date'], FILTER_SANITIZE_STRING);
$endDate = filter_var($request_body['end_date'], FILTER_SANITIZE_STRING);

try {
    // Configure DOMPDF options
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    
    $dompdf = new Dompdf($options);

    // Generate report data based on type
    $reportData = [];
    $reportTitle = '';
    $totalAmount = 0;

    switch ($reportType) {
        case 'bookings':
            $reportTitle = 'Bookings Report';
            $stmt = $conn->prepare("
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
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $reportData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            break;

        case 'revenue':
            $reportTitle = 'Revenue Report';
            $stmt = $conn->prepare("
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
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $reportData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Calculate total revenue
            foreach ($reportData as $row) {
                $totalAmount += floatval($row['amount_received']);
            }
            break;

        case 'vehicles':
            $reportTitle = 'Vehicle Utilization Report';
            $stmt = $conn->prepare("
                SELECT 
                    v.vehicleid, v.name, v.platenumber, v.type, v.model, v.year,
                    COUNT(b.booking_id) AS total_bookings,
                    SUM(b.total_price) AS total_revenue,
                    SUM(b.total_distance) AS total_distance,
                    v.status
                FROM vehicles v
                LEFT JOIN bookings b ON v.vehicleid = b.vehicle_id
                WHERE b.date BETWEEN ? AND ? OR b.date IS NULL
                GROUP BY v.vehicleid
                ORDER BY total_bookings DESC
            ");
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $reportData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            break;

        case 'customers':
            $reportTitle = 'Customer Activity Report';
            $stmt = $conn->prepare("
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
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $reportData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            break;

        case 'ratings':
            $reportTitle = 'Customer Ratings Report';
            $stmt = $conn->prepare("
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
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $reportData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            break;

        case 'ratings_summary':
            $reportTitle = 'Ratings Summary Report';
            $stmt = $conn->prepare("
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
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $reportData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            break;

        default:
            throw new Exception('Invalid report type');
    }

    // Generate HTML content for PDF
    $html = generateReportHTML($reportTitle, $reportData, $startDate, $endDate, $totalAmount, $reportType);

    // Load HTML into DOMPDF
    $dompdf->loadHtml($html);

    // Set paper size and orientation
    $dompdf->setPaper('A4', 'landscape');

    // Render the HTML as PDF
    $dompdf->render();

    // Generate filename
    $filename = strtolower($reportType) . '_report_' . date('Ymd_His') . '.pdf';

    // Output the PDF
    $output = $dompdf->output();
    
    // Save to file
    $filepath = '../../uploads/reports/' . $filename;
    if (!file_exists('../../uploads/reports/')) {
        mkdir('../../uploads/reports/', 0755, true);
    }
    file_put_contents($filepath, $output);

    echo json_encode([
        'status' => 'success',
        'message' => 'PDF report generated successfully',
        'filename' => $filename,
        'filepath' => $filepath,
        'download_url' => 'uploads/reports/' . $filename,
        'http_code' => 200
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error generating PDF report',
        'details' => $e->getMessage(),
        'http_code' => 500
    ]);
}

function generateReportHTML($title, $data, $startDate, $endDate, $totalAmount, $reportType) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . htmlspecialchars($title) . '</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                margin: 0;
                padding: 20px;
                color: #333;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #4e73df;
                padding-bottom: 20px;
            }
            .header h1 {
                color: #4e73df;
                margin: 0;
                font-size: 24px;
            }
            .header p {
                margin: 5px 0;
                color: #666;
            }
            .summary {
                background-color: #f8f9fc;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
            }
            .summary h3 {
                margin: 0 0 10px 0;
                color: #4e73df;
            }
            .summary-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 5px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #4e73df;
                color: white;
                font-weight: bold;
            }
            tr:nth-child(even) {
                background-color: #f8f9fc;
            }
            .footer {
                margin-top: 30px;
                text-align: center;
                font-size: 10px;
                color: #666;
                border-top: 1px solid #ddd;
                padding-top: 10px;
            }
            .status-badge {
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 10px;
                font-weight: bold;
            }
            .status-pending { background-color: #f6c23e; color: #000; }
            .status-completed { background-color: #1cc88a; color: #fff; }
            .status-cancelled { background-color: #e74a3b; color: #fff; }
            .status-paid { background-color: #1cc88a; color: #fff; }
            .status-failed { background-color: #e74a3b; color: #fff; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>' . htmlspecialchars($title) . '</h1>
            <p>Period: ' . date('M d, Y', strtotime($startDate)) . ' - ' . date('M d, Y', strtotime($endDate)) . '</p>
            <p>Generated on: ' . date('M d, Y H:i:s') . '</p>
        </div>

        <div class="summary">
            <h3>Report Summary</h3>
            <div class="summary-row">
                <span>Total Records:</span>
                <span><strong>' . count($data) . '</strong></span>
            </div>';

    if ($totalAmount > 0) {
        $html .= '
            <div class="summary-row">
                <span>Total Revenue:</span>
                <span><strong>₱' . number_format($totalAmount, 2) . '</strong></span>
            </div>';
    }

    $html .= '
        </div>

        <table>
            <thead>
                <tr>';

    // Generate table headers based on report type
    switch ($reportType) {
        case 'bookings':
            $html .= '
                    <th>Booking ID</th>
                    <th>Customer</th>
                    <th>Vehicle</th>
                    <th>Pickup Location</th>
                    <th>Drop-off Location</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Created</th>';
            break;
        case 'revenue':
            $html .= '
                    <th>Payment ID</th>
                    <th>Booking ID</th>
                    <th>Customer</th>
                    <th>Amount Due</th>
                    <th>Amount Received</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                    <th>Paid At</th>';
            break;
        case 'vehicles':
            $html .= '
                    <th>Vehicle ID</th>
                    <th>Name</th>
                    <th>Plate Number</th>
                    <th>Type</th>
                    <th>Model</th>
                    <th>Year</th>
                    <th>Total Bookings</th>
                    <th>Total Revenue</th>
                    <th>Total Distance</th>
                    <th>Status</th>';
            break;
        case 'customers':
            $html .= '
                    <th>Customer ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Contact</th>
                    <th>Total Bookings</th>
                    <th>Total Spent</th>
                    <th>Last Booking</th>';
            break;
        case 'ratings':
            $html .= '
                    <th>Rating ID</th>
                    <th>Booking ID</th>
                    <th>Customer</th>
                    <th>Vehicle</th>
                    <th>Driver</th>
                    <th>Rating</th>
                    <th>Comments</th>
                    <th>Rated At</th>
                    <th>Booking Date</th>';
            break;
        case 'ratings_summary':
            $html .= '
                    <th>Category</th>
                    <th>Total Ratings</th>
                    <th>Average Rating</th>
                    <th>Min Rating</th>
                    <th>Max Rating</th>
                    <th>5 Stars</th>
                    <th>4 Stars</th>
                    <th>3 Stars</th>
                    <th>2 Stars</th>
                    <th>1 Star</th>
                    <th>Satisfaction %</th>';
            break;
    }

    $html .= '
                </tr>
            </thead>
            <tbody>';

    // Generate table rows
    foreach ($data as $row) {
        $html .= '<tr>';
        
        switch ($reportType) {
            case 'bookings':
                $html .= '
                    <td>' . htmlspecialchars(substr($row['booking_id'], 0, 8)) . '...</td>
                    <td>' . htmlspecialchars($row['full_name']) . '</td>
                    <td>' . htmlspecialchars($row['vehicle_name']) . '</td>
                    <td>' . htmlspecialchars($row['pickup_location']) . '</td>
                    <td>' . htmlspecialchars($row['dropoff_location']) . '</td>
                    <td>' . htmlspecialchars($row['date']) . '</td>
                    <td>' . htmlspecialchars($row['time']) . '</td>
                    <td>₱' . number_format($row['total_price'], 2) . '</td>
                    <td><span class="status-badge status-' . $row['status'] . '">' . strtoupper($row['status']) . '</span></td>
                    <td>' . htmlspecialchars($row['created_at']) . '</td>';
                break;
            case 'revenue':
                $html .= '
                    <td>' . htmlspecialchars(substr($row['payment_id'], 0, 8)) . '...</td>
                    <td>' . htmlspecialchars(substr($row['booking_id'], 0, 8)) . '...</td>
                    <td>' . htmlspecialchars($row['full_name']) . '</td>
                    <td>₱' . number_format($row['amount_due'], 2) . '</td>
                    <td>₱' . number_format($row['amount_received'], 2) . '</td>
                    <td>' . strtoupper($row['payment_method']) . '</td>
                    <td><span class="status-badge status-' . $row['payment_status'] . '">' . strtoupper($row['payment_status']) . '</span></td>
                    <td>' . htmlspecialchars($row['paid_at_formatted']) . '</td>';
                break;
            case 'vehicles':
                $html .= '
                    <td>' . htmlspecialchars(substr($row['vehicleid'], 0, 8)) . '...</td>
                    <td>' . htmlspecialchars($row['name']) . '</td>
                    <td>' . htmlspecialchars($row['platenumber']) . '</td>
                    <td>' . htmlspecialchars($row['type']) . '</td>
                    <td>' . htmlspecialchars($row['model']) . '</td>
                    <td>' . htmlspecialchars($row['year']) . '</td>
                    <td>' . $row['total_bookings'] . '</td>
                    <td>₱' . number_format($row['total_revenue'] ?? 0, 2) . '</td>
                    <td>' . number_format($row['total_distance'] ?? 0, 2) . ' km</td>
                    <td><span class="status-badge status-' . $row['status'] . '">' . strtoupper($row['status']) . '</span></td>';
                break;
            case 'customers':
                $html .= '
                    <td>' . htmlspecialchars(substr($row['uid'], 0, 8)) . '...</td>
                    <td>' . htmlspecialchars($row['full_name']) . '</td>
                    <td>' . htmlspecialchars($row['email_address']) . '</td>
                    <td>' . htmlspecialchars($row['contact_number']) . '</td>
                    <td>' . $row['total_bookings'] . '</td>
                    <td>₱' . number_format($row['total_spent'] ?? 0, 2) . '</td>
                    <td>' . htmlspecialchars($row['last_booking_date'] ?? 'N/A') . '</td>';
                break;
            case 'ratings':
                $stars = str_repeat('*', $row['overall_rating']) . str_repeat('o', 5 - $row['overall_rating']);
                $html .= '
                    <td>' . htmlspecialchars(substr($row['rating_id'], 0, 8)) . '...</td>
                    <td>' . htmlspecialchars(substr($row['booking_id'], 0, 8)) . '...</td>
                    <td>' . htmlspecialchars($row['customer_name']) . '</td>
                    <td>' . htmlspecialchars($row['vehicle_name'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($row['driver_name']) . '</td>
                    <td>' . $stars . ' (' . $row['overall_rating'] . '/5)</td>
                    <td>' . htmlspecialchars(substr($row['comments'], 0, 50)) . (strlen($row['comments']) > 50 ? '...' : '') . '</td>
                    <td>' . htmlspecialchars($row['rated_at']) . '</td>
                    <td>' . htmlspecialchars($row['booking_date']) . '</td>';
                break;
            case 'ratings_summary':
                $html .= '
                    <td>' . htmlspecialchars($row['category']) . '</td>
                    <td>' . $row['total_ratings'] . '</td>
                    <td>' . $row['average_rating'] . '</td>
                    <td>' . $row['min_rating'] . '</td>
                    <td>' . $row['max_rating'] . '</td>
                    <td>' . $row['five_stars'] . '</td>
                    <td>' . $row['four_stars'] . '</td>
                    <td>' . $row['three_stars'] . '</td>
                    <td>' . $row['two_stars'] . '</td>
                    <td>' . $row['one_star'] . '</td>
                    <td>' . $row['satisfaction_percentage'] . '%</td>';
                break;
        }
        
        $html .= '</tr>';
    }

    $html .= '
            </tbody>
        </table>

        <div class="footer">
            <p>This report was generated by BMove Express System</p>
            <p>For any questions, please contact support</p>
        </div>
    </body>
    </html>';

    return $html;
}
