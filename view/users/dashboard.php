<?php


// Get current year and month
$currentYear = date('Y');
$currentMonth = date('m');
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : $currentYear;
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : $currentMonth;

// Fetch statistics
$revenue = $bookings = $activeRentals = $totalVehicles = 0;
$vehicleStatus = ['available' => 0, 'in use' => 0, 'under maintenance' => 0, 'unavailable' => 0];

$result = $conn->query("SELECT SUM(amount_received) AS total_revenue FROM payments WHERE payment_status = 'paid'");
if ($result && $row = $result->fetch_assoc()) {
    $revenue = $row['total_revenue'] ?? 0;
}

// Calculate admin's gross profit (60% of net profit after expenses)
$totalExpensesAll = 0;
$expResult = $conn->query("SELECT SUM(amount) AS total_expenses FROM vehicle_expenses");
if ($expResult && $expRow = $expResult->fetch_assoc()) {
    $totalExpensesAll = $expRow['total_expenses'] ?? 0;
}
$netProfitAll = $revenue - $totalExpensesAll;
$adminGrossProfit = $netProfitAll * 0.60;

$result = $conn->query("SELECT COUNT(*) AS total_bookings FROM bookings");
if ($result && $row = $result->fetch_assoc()) {
    $bookings = $row['total_bookings'] ?? 0;
}


// Active Rentals
$result = $conn->query("SELECT COUNT(*) AS active_rentals FROM bookings WHERE status = 'in_progress'");
if ($result && $row = $result->fetch_assoc()) {
    $activeRentals = $row['active_rentals'] ?? 0;
}

// Total Vehicles
$result = $conn->query("SELECT COUNT(*) AS total_vehicles FROM vehicles WHERE status != 'Inactive'");
if ($result && $row = $result->fetch_assoc()) {
    $totalVehicles = $row['total_vehicles'] ?? 0;
}

// Vehicle Status
$result = $conn->query("SELECT status, COUNT(*) AS count FROM vehicles GROUP BY status");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $vehicleStatus[$row['status']] = $row['count'];
    }
}

// Recent Bookings with real data
$recentBookings = [];
$result = $conn->query("
    SELECT b.booking_id, u.full_name, v.name AS vehicle_name, b.date, b.time, b.status,
           b.total_distance, b.total_price
    FROM bookings b
    JOIN users u ON b.user_id = u.uid
    LEFT JOIN vehicles v ON b.vehicle_id = v.vehicleid
    ORDER BY b.created_at DESC
    LIMIT 5
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentBookings[] = $row;
    }
}

// Weekly revenue data for the selected year
$weeklyRevenue = array_fill(1, 52, 0);

$result = $conn->prepare("
    SELECT WEEK(paid_at, 1) AS week, SUM(amount_received) AS weekly_revenue
    FROM payments
    WHERE payment_status = 'paid'
    AND YEAR(paid_at) = ?
    GROUP BY week
    ORDER BY week
");
$result->bind_param("i", $selectedYear);
$result->execute();
$result->bind_result($week, $weekly_revenue);

while ($result->fetch()) {
    if ($week == 0)
        $week = 1;
    if ($week > 52)
        continue;
    $weeklyRevenue[$week] = $weekly_revenue;
}
$result->close();

// Weekly bookings data for the selected year
$weeklyBookings = array_fill(1, 52, 0);

$result = $conn->prepare("
    SELECT WEEK(created_at, 1) AS week, COUNT(*) AS weekly_count
    FROM bookings
    WHERE YEAR(created_at) = ?
    GROUP BY week
    ORDER BY week
");
$result->bind_param("i", $selectedYear);
$result->execute();
$result->bind_result($week, $weekly_count);

while ($result->fetch()) {
    if ($week == 0)
        $week = 1;
    if ($week > 52)
        continue;
    $weeklyBookings[$week] = $weekly_count;
}
$result->close();


$years = [];
$result = $conn->query("SELECT DISTINCT YEAR(paid_at) AS year FROM payments WHERE paid_at IS NOT NULL ORDER BY year DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $years[] = $row['year'];
    }
}


$conn->close();


$weekNames = [];
for ($i = 1; $i <= 52; $i++) {
    $weekNames[$i] = "W$i";
}

$chartLabels = array_values($weekNames);
$chartData = array_values($weeklyRevenue);
$bookingData = array_values($weeklyBookings);
$maxRevenue = max($chartData) > 0 ? max($chartData) : 10000; // Avoid division by zero
?>


<style>
    .stat-card {
        border-radius: 10px;
        border: none;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s, box-shadow 0.3s;
        overflow: hidden;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .recent-bookings .card {
        border: none;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }

    .badge-pill {
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: 500;
    }

    .chart-container {
        position: relative;
        height: 300px;
        margin-top: 20px;
    }

    .progress {
        height: 8px;
        border-radius: 4px;
    }

    .navbar {
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .pagetitle {
        margin-bottom: 1.5rem;
    }

    .breadcrumb {
        background-color: transparent;
        padding: 0;
    }

    .customer-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #6c757d;
    }

    .status-badge {
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

    .month-selector {
        background-color: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }

    .revenue-card {
        background: linear-gradient(120deg, #4361ee, #4cc9f0);
        color: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 6px 15px rgba(67, 97, 238, 0.3);
    }

    .month-highlight {
        background-color: rgba(67, 97, 238, 0.1);
        border-left: 4px solid var(--primary-color);
    }

    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .month-bar {
        display: flex;
        justify-content: space-between;
        margin-top: 10px;
        padding: 0 5px;
    }

    .month-item {
        flex: 1;
        text-align: center;
        padding: 8px 5px;
        cursor: pointer;
        border-radius: 6px;
        transition: all 0.3s;
    }

    .month-item:hover {
        background-color: rgba(67, 97, 238, 0.1);
    }

    .month-item.active {
        background-color: #4361ee;
        color: white;
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


        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-4">
                <div class="card stat-card border-top border-4 border-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title text-muted mb-2">Bookings</h5>
                                <h3 class="mb-0" id="stat-bookings"><?= number_format($bookings) ?></h3>
                                <p class="text-success small mb-0 mt-1">
                                    <i class="bi bi-arrow-up"></i> Weekly
                                </p>
                            </div>
                            <div class="stat-icon bg-success bg-opacity-10 text-success">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card stat-card border-top border-4 border-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title text-muted mb-2">Active Rentals</h5>
                                <h3 class="mb-0" id="stat-active-rentals"><?= number_format($activeRentals) ?></h3>
                                <p class="text-muted small mb-0 mt-1">
                                    Currently in use
                                </p>
                            </div>
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                                <i class="bi bi-car-front"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card stat-card border-top border-4 border-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title text-muted mb-2">Vehicles</h5>
                                <h3 class="mb-0" id="stat-vehicles"><?= number_format($totalVehicles) ?></h3>
                                <p class="text-success small mb-0 mt-1">
                                    <i class="bi bi-plus-circle"></i> Total count
                                </p>
                            </div>
                            <div class="stat-icon bg-info bg-opacity-10 text-info">
                                <i class="bi bi-truck"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-4">
                <div class="card stat-card border-top border-4 border-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title text-muted mb-2">Admin Gross Profit</h5>
                                <h3 class="mb-0" id="stat-admin-profit">₱<?= number_format($adminGrossProfit, 0) ?></h3>
                                <p class="text-muted small mb-0 mt-1">
                                    60% of net profit
                                </p>
                            </div>
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-graph-up"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Data Section -->
        <div class="row mb-4">
            <!-- Revenue Chart -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="chart-header">
                            <h5 class="card-title mb-0">Weekly Revenue Overview for <?= $selectedYear ?></h5>
                            <div class="btn-group">
                                <button id="toggleRevenueBtn"
                                    class="btn btn-sm btn-outline-primary active">Revenue</button>
                                <button id="toggleBookingsBtn"
                                    class="btn btn-sm btn-outline-secondary">Bookings</button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vehicle Status -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Vehicle Status Distribution</h5>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Available</span>
                                <span><?= $vehicleStatus['available'] ?>
                                    (<?= $totalVehicles > 0 ? round($vehicleStatus['available'] / $totalVehicles * 100) : 0 ?>%)</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" role="progressbar"
                                    style="width: <?= ($totalVehicles > 0) ? ($vehicleStatus['available'] / $totalVehicles * 100) : 0 ?>%">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span>In Use</span>
                                <span><?= $vehicleStatus['in use'] ?>
                                    (<?= $totalVehicles > 0 ? round($vehicleStatus['in use'] / $totalVehicles * 100) : 0 ?>%)</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-primary" role="progressbar"
                                    style="width: <?= ($totalVehicles > 0) ? ($vehicleStatus['in use'] / $totalVehicles * 100) : 0 ?>%">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Maintenance</span>
                                <span><?= $vehicleStatus['under maintenance'] ?>
                                    (<?= $totalVehicles > 0 ? round($vehicleStatus['under maintenance'] / $totalVehicles * 100) : 0 ?>%)</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-warning" role="progressbar"
                                    style="width: <?= ($totalVehicles > 0) ? ($vehicleStatus['under maintenance'] / $totalVehicles * 100) : 0 ?>%">
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Unavailable</span>
                                <span><?= $vehicleStatus['unavailable'] ?>
                                    (<?= $totalVehicles > 0 ? round($vehicleStatus['unavailable'] / $totalVehicles * 100) : 0 ?>%)</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-danger" role="progressbar"
                                    style="width: <?= ($totalVehicles > 0) ? ($vehicleStatus['unavailable'] / $totalVehicles * 100) : 0 ?>%">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="row">
            <div class="col-12">
                <div class="card recent-bookings">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title">Recent Bookings</h5>
                            <a href="#" class="btn btn-sm btn-outline-primary">View All Bookings</a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Vehicle</th>
                                        <th>Date & Time</th>
                                        <th>Distance</th>
                                        <th>Revenue</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="recent-bookings-body">
                                    <?php foreach ($recentBookings as $booking):
                                        // Get initials for avatar
                                        $names = explode(' ', $booking['full_name']);
                                        $initials = '';
                                        if (count($names)) {
                                            $initials = strtoupper(substr($names[0], 0, 1)) .
                                                (isset($names[1]) ? strtoupper(substr($names[1], 0, 1)) : '');
                                        }

                                        // Map status to classes
                                        $statusClass = '';
                                        switch ($booking['status']) {
                                            case 'pending':
                                                $statusClass = 'pending';
                                                break;
                                            case 'confirmed':
                                                $statusClass = 'confirmed';
                                                break;
                                            case 'in_progress':
                                                $statusClass = 'in_progress';
                                                break;
                                            case 'completed':
                                                $statusClass = 'completed';
                                                break;
                                            case 'cancelled':
                                                $statusClass = 'cancelled';
                                                break;
                                        }

                                        // Use real booking data
                                        $distance = htmlspecialchars($booking['total_distance'] ?? '0') . ' km';
                                        $bookingRevenue = '₱' . number_format($booking['total_price'] ?? 0, 2);
                                        ?>
                                        <tr class="<?= $booking['status'] == 'in_progress' ? 'table-primary' : '' ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="customer-avatar me-2">
                                                        <?= $initials ?>
                                                    </div>
                                                    <div><?= $booking['full_name'] ?></div>
                                                </div>
                                            </td>
                                            <td><?= $booking['vehicle_name'] ?? 'No Vehicle' ?></td>
                                            <td><?= date('M d, Y', strtotime($booking['date'])) . ' ' . $booking['time'] ?>
                                            </td>
                                            <td><?= $distance ?></td>
                                            <td class="fw-bold"><?= $bookingRevenue ?></td>
                                            <td>
                                                <span class="status-badge bg-<?= $statusClass ?>">
                                                    <?= ucwords(str_replace('_', ' ', $booking['status'])) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Revenue Chart
    const ctx = document.getElementById('revenueChart').getContext('2d');

    const revenueData = <?= json_encode($chartData) ?>;
    const bookingData = <?= json_encode($bookingData) ?>;

    const revenueChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Weekly Revenue (₱)',
                data: revenueData,
                backgroundColor: 'rgba(67, 97, 238, 0.5)',
                borderColor: '#4361ee',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return '₱' + context.raw.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false
                    },
                    ticks: {
                        callback: function (value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    document.getElementById('toggleRevenueBtn').addEventListener('click', function () {
        this.classList.add('btn-outline-primary', 'active');
        this.classList.remove('btn-outline-secondary');

        const bookingBtn = document.getElementById('toggleBookingsBtn');
        bookingBtn.classList.add('btn-outline-secondary');
        bookingBtn.classList.remove('btn-outline-primary', 'active');

        revenueChart.data.datasets[0].data = revenueData;
        revenueChart.data.datasets[0].label = 'Weekly Revenue (₱)';
        revenueChart.options.plugins.tooltip.callbacks.label = function (context) {
            return '₱' + context.raw.toLocaleString();
        };
        revenueChart.options.scales.y.ticks.callback = function (value) {
            return '₱' + value.toLocaleString();
        };
        revenueChart.update();
    });

    document.getElementById('toggleBookingsBtn').addEventListener('click', function () {
        this.classList.add('btn-outline-primary', 'active');
        this.classList.remove('btn-outline-secondary');

        const revenueBtn = document.getElementById('toggleRevenueBtn');
        revenueBtn.classList.add('btn-outline-secondary');
        revenueBtn.classList.remove('btn-outline-primary', 'active');

        revenueChart.data.datasets[0].data = bookingData;
        revenueChart.data.datasets[0].label = 'Weekly Bookings';
        revenueChart.options.plugins.tooltip.callbacks.label = function (context) {
            return context.raw.toLocaleString();
        };
        revenueChart.options.scales.y.ticks.callback = function (value) {
            return value.toLocaleString();
        };
        revenueChart.update();
    });

    // Filter functionality
    const yearSelector = document.getElementById('yearSelector');
    if (yearSelector) {
        yearSelector.addEventListener('change', function () {
            const year = this.value;
            const month = <?= $selectedMonth ?>;
            window.location.href = `?year=${year}&month=${month}`;
        });
    }

    document.querySelectorAll('.month-item').forEach(item => {
        item.addEventListener('click', function () {
            const month = this.getAttribute('data-month');
            const year = <?= $selectedYear ?>;
            window.location.href = `?year=${year}&month=${month}`;
        });
    });

    // Real-time dashboard polling every 15 seconds
    let previousBookingStatuses = {};

    function refreshDashboardData() {
        fetch('controller/booking/get-dashboard-data.php')
            .then(res => res.json())
            .then(response => {
                if (response.status !== 'success') return;
                const d = response.data;

                // Update stat cards
                document.getElementById('stat-bookings').textContent = Number(d.bookings).toLocaleString();
                document.getElementById('stat-active-rentals').textContent = Number(d.activeRentals).toLocaleString();
                document.getElementById('stat-vehicles').textContent = Number(d.totalVehicles).toLocaleString();
                document.getElementById('stat-admin-profit').textContent = '₱' + Number(d.adminGrossProfit).toLocaleString(undefined, {maximumFractionDigits: 0});

                // Detect status changes and show toast notifications
                if (d.recentBookings) {
                    d.recentBookings.forEach(b => {
                        const prev = previousBookingStatuses[b.booking_id];
                        if (prev && prev !== b.status) {
                            const statusLabel = b.status.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
                            showSyncToast(`Booking for ${b.full_name} is now <strong>${statusLabel}</strong>`);
                        }
                        previousBookingStatuses[b.booking_id] = b.status;
                    });
                }

                // Update recent bookings table
                const tbody = document.getElementById('recent-bookings-body');
                if (tbody && d.recentBookings) {
                    let html = '';
                    d.recentBookings.forEach(b => {
                        const names = b.full_name.split(' ');
                        const initials = (names[0]?.[0] || '').toUpperCase() + (names[1]?.[0] || '').toUpperCase();
                        const statusClass = b.status || '';
                        const dateStr = new Date(b.date).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'});
                        const distance = (b.total_distance || 0) + ' km';
                        const revenue = '₱' + Number(b.total_price || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        const isActive = b.status === 'in_progress' ? 'table-primary' : '';
                        const statusLabel = b.status.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
                        html += `<tr class="${isActive}">
                            <td><div class="d-flex align-items-center"><div class="customer-avatar me-2">${initials}</div><div>${b.full_name}</div></div></td>
                            <td>${b.vehicle_name || 'No Vehicle'}</td>
                            <td>${dateStr} ${b.time}</td>
                            <td>${distance}</td>
                            <td class="fw-bold">${revenue}</td>
                            <td><span class="status-badge bg-${statusClass}">${statusLabel}</span></td>
                        </tr>`;
                    });
                    tbody.innerHTML = html;
                }
            })
            .catch(err => console.error('Dashboard refresh error:', err));
    }

    function showSyncToast(message) {
        const container = document.getElementById('toast-container') || createToastContainer();
        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-bg-info border-0 show';
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body"><i class="bi bi-arrow-repeat me-1"></i> ${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>`;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    }

    function createToastContainer() {
        const c = document.createElement('div');
        c.id = 'toast-container';
        c.className = 'toast-container position-fixed top-0 end-0 p-3';
        c.style.zIndex = '1090';
        document.body.appendChild(c);
        return c;
    }

    setInterval(refreshDashboardData, 15000);
</script>