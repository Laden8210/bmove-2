<?php


// Fetch bookings
$sql = "
  SELECT 
    b.*, 
    u.full_name AS customer_name, 
    d.full_name AS driver_name,
    v.name AS vehicle_name, 
    v.platenumber, 
    v.type, 
    v.model, 
    v.year 
  FROM bookings b
  LEFT JOIN users u ON b.user_id = u.uid
  LEFT JOIN vehicles v ON b.vehicle_id = v.vehicleid
  LEFT JOIN users d ON v.driver_uid = d.uid
";

$result = $conn->query($sql);
?>
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<style>
  .card-body ul li {
    line-height: 1.5;
  }

  .map-container {
    height: 700px;
    width: 100%;
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  }

  .info-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border-left: 4px solid var(--primary);
    width: 20%;
  }

  .info-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
  }

  .list-unstyled li {
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
  }

  .list-unstyled li:last-child {
    border-bottom: none;
  }

  .list-unstyled li i {
    width: 24px;
    text-align: center;
    margin-right: 10px;
    color: var(--primary);
  }

  .leaflet-right {
    width: 400px !important;
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

        <div class="card overflow-auto">
          <div class="card-body">
            <h5 class="card-title">Bookings</h5>
            <p>Below is a list of all bookings in the system.</p>
            <div class="table-responsive">
              <table class="table datatable">
                <thead>
                  <tr>

                    <th>Booking Date</th>
                    <th>Customer Name</th>
                    <th>Driver Name</th>
                    <th>Total Distance</th>
                    <th>Total Price</th>
                    <th>Status</th>
                    <th data-sortable="false">Payment Method</th>
                    <th>Date Created</th>
                    <th data-sortable="false">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                      <tr>
                        <td>
                          <?php
                          $date = date('M d, Y', strtotime($row['date']));
                          $time = htmlspecialchars($row['time']);
                          echo htmlspecialchars($date) . ' ' . $time;
                          ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['customer_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['driver_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['total_distance']); ?></td>
                        <td>&#8369;<?php echo number_format($row['total_price'], 2); ?></td>
                        <td>
                          <?php
                          $status = htmlspecialchars($row['status']);
                          $badgeClass = 'secondary';
                          switch (strtolower($status)) {
                            case 'pending':
                              $badgeClass = 'warning';
                              break;
                            case 'confirmed':
                              $badgeClass = 'primary';
                              break;
                            case 'completed':
                              $badgeClass = 'success';
                              break;
                            case 'cancelled':
                              $badgeClass = 'danger';
                              break;
                          }
                          ?>
                          <span class="badge bg-<?php echo $badgeClass; ?>"><?php echo $status; ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                        <td>
                          <?php
                          $createdAt = date('M d, Y h:i A', strtotime($row['created_at']));
                          echo htmlspecialchars($createdAt);
                          ?>
                        </td>
                        <td>
                          <!-- View Map Button -->
                          <button class="btn btn-outline-primary btn-sm view-map-btn" data-bs-toggle="modal"
                            data-bs-target="#mapModal"
                            data-pickup="<?php echo htmlspecialchars($row['pickup_location']); ?>"
                            data-dropoff="<?php echo htmlspecialchars($row['dropoff_location']); ?>"
                            data-date="<?php echo htmlspecialchars($row['date']); ?>"
                            data-time="<?php echo htmlspecialchars($row['time']); ?>"
                            data-distance="<?php echo htmlspecialchars($row['total_distance']); ?>"
                            data-price="<?php echo htmlspecialchars($row['total_price']); ?>"
                            data-status="<?php echo htmlspecialchars($row['status']); ?>"
                            data-platenumber="<?php echo htmlspecialchars($row['platenumber']); ?>"
                            data-vehiclename="<?php echo htmlspecialchars($row['vehicle_name']); ?>"
                            data-vehicletype="<?php echo htmlspecialchars($row['type']); ?>"
                            data-vehiclemodel="<?php echo htmlspecialchars($row['model']); ?>"
                            data-vehicleyear="<?php echo htmlspecialchars($row['year']); ?>"
                            data-customer="<?php echo htmlspecialchars($row['customer_name']); ?>"
                            data-pickuplat="<?php echo $row['pickup_lat']; ?>"
                            data-pickuplng="<?php echo $row['pickup_lng']; ?>"
                            data-dropofflat="<?php echo $row['dropoff_lat']; ?>"
                            data-dropofflng="<?php echo $row['dropoff_lng']; ?>">
                            <i class="bi bi-map"></i>
                          </button>

                          <!-- Update Status Button -->
                          <button class="btn btn-sm btn-outline-success update-status-btn" data-bs-toggle="modal"
                            data-bs-target="#statusModal" title="Update Status" data-id="<?php echo $row['booking_id']; ?>"
                            onclick="document.getElementById('statusBookingId').value = this.dataset.id;">
                            <i class="bi bi-pencil-square"></i>
                          </button>

                          <?php if (!in_array(strtolower($row['status']), ['completed', 'cancelled'])): ?>
                          <!-- Reassign Vehicle Button -->
                          <button class="btn btn-sm btn-outline-warning reassign-btn" title="Reassign Vehicle"
                            data-booking-id="<?php echo htmlspecialchars($row['booking_id']); ?>"
                            onclick="openReassignModal(this.dataset.bookingId)">
                            <i class="bi bi-arrow-repeat"></i>
                          </button>
                          <?php endif; ?>
                        </td>

                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="12">No bookings found.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
</main>

<!-- Reassign Vehicle Modal -->
<div class="modal fade" id="reassignModal" tabindex="-1" aria-labelledby="reassignModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title"><i class="bi bi-arrow-repeat me-2"></i>Reassign Vehicle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="reassignLoading" class="text-center py-4">
          <div class="spinner-border text-warning" role="status"></div>
          <p class="mt-2 text-muted">Loading available vehicles...</p>
        </div>
        <div id="reassignContent" style="display:none;">
          <div class="alert alert-info mb-3">
            <i class="bi bi-info-circle me-1"></i>
            Current vehicle: <strong id="reassignCurrentVehicle"></strong>
            (Type: <strong id="reassignVehicleType"></strong>)
          </div>
          <p class="text-muted mb-2">Select a replacement vehicle of the same type:</p>
          <div class="table-responsive">
            <table class="table table-hover" id="reassignTable">
              <thead class="table-light">
                <tr>
                  <th>Vehicle Name</th>
                  <th>Plate</th>
                  <th>Model / Year</th>
                  <th>Driver</th>
                  <th>Base Price</th>
                  <th>Rate/km</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="reassignTableBody"></tbody>
            </table>
          </div>
          <div id="reassignEmpty" class="text-center text-muted py-3" style="display:none;">
            <i class="bi bi-exclamation-circle fs-3 d-block mb-2"></i>
            No available vehicles of the same type found.
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Booking Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="controller/booking/update-booking.php" id="update-booking-form"
        class="needs-validation" novalidate>

        <div class="modal-body">
          <div class="mb-3">
            <label for="statusSelect" class="form-label">Select Status</label>
            <select class="form-select" id="statusSelect" required name="status">
              <option value="" disabled selected>Select status</option>
              <option value="confirmed">Confirmed</option>
              <option value="cancelled">Cancelled</option>
            </select>
            <div class="invalid-feedback">
              Please select a status.
            </div>
          </div>
          <div class="mb-3">
            <label for="statusNote" class="form-label">Note (optional)</label>
            <small class="text-muted">Add a note for the status update (optional).</small>
          </div>
          <textarea class="form-control" id="statusNote" placeholder="Enter a note (optional)"
            name="remarks"></textarea>
          <input type="hidden" id="statusBookingId" name="booking_id" value="">
        </div>
        <div class="modal-footer">
          <button id="submit-btn" class="btn btn-success" id>Submit</button>

          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </form>
    </div>
  </div>
</div>


<div class="modal fade" id="mapModal" tabindex="-1" aria-labelledby="mapModalLabel" aria-hidden="true">
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
                  <li class="mb-2"><i class="bi bi-tag-fill me-1"></i><strong>Name:</strong> <span
                      id="modalVehicleName"></span></li>
                  <li class="mb-2"><i class="bi bi-car-front-fill me-1"></i><strong>Plate:</strong> <span
                      id="modalPlateNumber"></span></li>
                  <li class="mb-2"><i class="bi bi-truck me-1"></i><strong>Type:</strong> <span
                      id="modalVehicleType"></span></li>
                  <li class="mb-2"><i class="bi bi-cpu-fill me-1"></i><strong>Model:</strong> <span
                      id="modalVehicleModel"></span></li>
                  <li><i class="bi bi-calendar-event-fill me-1"></i><strong>Year:</strong> <span
                      id="modalVehicleYear"></span></li>
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
                  <li class="mb-2"><i class="bi bi-geo-fill me-1"></i><strong>Pickup:</strong> <span
                      id="modalPickup"></span></li>
                  <li class="mb-2"><i class="bi bi-geo-alt-fill me-1"></i><strong>Dropoff:</strong> <span
                      id="modalDropoff"></span></li>
                  <li class="mb-2"><i class="bi bi-calendar-fill me-1"></i><strong>Date:</strong> <span
                      id="modalDate"></span></li>
                  <li class="mb-2"><i class="bi bi-clock-fill me-1"></i><strong>Time:</strong> <span
                      id="modalTime"></span></li>
                  <li class="mb-2"><i class="bi bi-rulers me-1"></i><strong>Distance:</strong> <span
                      id="modalDistance"></span> km</li>
                  <li class="mb-2"><i class="bi bi-currency-dollar me-1"></i><strong>Price:</strong> ₱<span
                      id="modalPrice"></span></li>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Leaflet & Routing Libraries -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js"></script>

<script>
  const createRequest = new CreateRequest({
    formSelector: "#update-booking-form",
    submitButtonSelector: "#submit-btn",
    callback: (err, res) => err ? console.error("Form submission error:", err) : console.log(
      "Form submitted successfully:", res)
  });
  let map;
  let routeControl;
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
      createMarker: function (i, wp, n) {
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

  document.addEventListener('DOMContentLoaded', function () {
    // Handle view map button clicks
    document.querySelectorAll('.view-map-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        // Populate modal data
        document.getElementById('modalCustomer').textContent = btn.dataset.customer;
        document.getElementById('modalVehicleName').textContent = btn.dataset.vehiclename;
        document.getElementById('modalPlateNumber').textContent = btn.dataset.platenumber;
        document.getElementById('modalVehicleType').textContent = btn.dataset.vehicletype;
        document.getElementById('modalVehicleModel').textContent = btn.dataset.vehiclemodel;
        document.getElementById('modalVehicleYear').textContent = btn.dataset.vehicleyear;

        document.getElementById('modalPickup').textContent = btn.dataset.pickup;
        document.getElementById('modalDropoff').textContent = btn.dataset.dropoff;
        document.getElementById('modalDate').textContent = btn.dataset.date;
        document.getElementById('modalTime').textContent = btn.dataset.time;
        document.getElementById('modalDistance').textContent = btn.dataset.distance;
        document.getElementById('modalPrice').textContent = btn.dataset.price;
        document.getElementById('modalStatus').textContent = btn.dataset.status;

        // Set badge class based on status
        const statusBadge = document.getElementById('modalStatus');
        statusBadge.className = 'badge';
        if (btn.dataset.status === 'Completed') {
          statusBadge.classList.add('bg-success');
        } else if (btn.dataset.status === 'In Progress') {
          statusBadge.classList.add('bg-warning', 'text-dark');
        } else {
          statusBadge.classList.add('bg-info');
        }

        // Show the modal
        const mapModal = new bootstrap.Modal(document.getElementById('mapModal'));
        mapModal.show();

        // Show the route on map after a short delay to allow modal to render
        setTimeout(() => {
          showRoute(
            parseFloat(btn.dataset.pickuplat),
            parseFloat(btn.dataset.pickuplng),
            parseFloat(btn.dataset.dropofflat),
            parseFloat(btn.dataset.dropofflng)
          );
        }, 300);
      });
    });

    // Initialize the map when the modal is shown
    document.getElementById('mapModal').addEventListener('shown.bs.modal', function () {
      if (map) {
        setTimeout(() => {
          map.invalidateSize();
        }, 100);
      }
    });
  });

  // Reassign Vehicle functionality
  let reassignBookingId = null;

  function openReassignModal(bookingId) {
    reassignBookingId = bookingId;
    const modal = new bootstrap.Modal(document.getElementById('reassignModal'));
    modal.show();

    document.getElementById('reassignLoading').style.display = 'block';
    document.getElementById('reassignContent').style.display = 'none';

    fetch(`controller/booking/reassign-vehicle.php?booking_id=${encodeURIComponent(bookingId)}`)
      .then(res => res.json())
      .then(data => {
        document.getElementById('reassignLoading').style.display = 'none';
        document.getElementById('reassignContent').style.display = 'block';

        if (data.status === 'success') {
          document.getElementById('reassignCurrentVehicle').textContent = data.data.current_vehicle;
          document.getElementById('reassignVehicleType').textContent = data.data.vehicle_type;

          const tbody = document.getElementById('reassignTableBody');
          tbody.innerHTML = '';

          if (data.data.recommendations.length === 0) {
            document.getElementById('reassignTable').style.display = 'none';
            document.getElementById('reassignEmpty').style.display = 'block';
          } else {
            document.getElementById('reassignTable').style.display = '';
            document.getElementById('reassignEmpty').style.display = 'none';

            data.data.recommendations.forEach(v => {
              const row = document.createElement('tr');
              row.innerHTML = `
                <td>${escapeHtml(v.name)}</td>
                <td>${escapeHtml(v.platenumber)}</td>
                <td>${escapeHtml(v.model)} / ${escapeHtml(v.year)}</td>
                <td>${escapeHtml(v.driver_name || 'Unassigned')}</td>
                <td>&#8369;${parseFloat(v.baseprice).toFixed(2)}</td>
                <td>&#8369;${parseFloat(v.rateperkm).toFixed(2)}</td>
                <td>
                  <button class="btn btn-sm btn-warning" onclick="confirmReassign('${escapeHtml(v.vehicleid)}', '${escapeHtml(v.name)}')">
                    <i class="bi bi-check-lg"></i> Select
                  </button>
                </td>
              `;
              tbody.appendChild(row);
            });
          }
        } else {
          Swal.fire('Error', data.message, 'error');
        }
      })
      .catch(() => {
        document.getElementById('reassignLoading').style.display = 'none';
        Swal.fire('Error', 'Failed to load vehicle recommendations', 'error');
      });
  }

  function confirmReassign(vehicleId, vehicleName) {
    Swal.fire({
      title: 'Reassign Vehicle?',
      html: `Are you sure you want to reassign this booking to <strong>${vehicleName}</strong>?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#ffc107',
      confirmButtonText: 'Yes, reassign',
      cancelButtonText: 'Cancel'
    }).then(result => {
      if (result.isConfirmed) {
        fetch('controller/booking/reassign-vehicle.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            booking_id: reassignBookingId,
            new_vehicle_id: vehicleId
          })
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'success') {
            Swal.fire('Success', 'Vehicle reassigned successfully!', 'success')
              .then(() => location.reload());
          } else {
            Swal.fire('Error', data.message, 'error');
          }
        })
        .catch(() => Swal.fire('Error', 'Failed to reassign vehicle', 'error'));
      }
    });
  }

  function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  // Auto-sync: poll for booking status changes every 15 seconds
  let knownStatuses = {};
  document.querySelectorAll('tr[data-booking-id]').forEach(r => {
    knownStatuses[r.dataset.bookingId] = r.dataset.bookingStatus;
  });

  // Store booking IDs and statuses from server-rendered table
  (function initBookingSync() {
    document.querySelectorAll('.update-status-btn').forEach(btn => {
      const row = btn.closest('tr');
      const badgeEl = row?.querySelector('.badge');
      if (btn.dataset.id && badgeEl) {
        knownStatuses[btn.dataset.id] = badgeEl.textContent.trim().toLowerCase();
      }
    });
  })();

  setInterval(() => {
    fetch('controller/booking/get-dashboard-data.php')
      .then(r => r.json())
      .then(res => {
        if (res.status !== 'success' || !res.data.recentBookings) return;
        let changed = false;
        res.data.recentBookings.forEach(b => {
          const prev = knownStatuses[b.booking_id];
          if (prev && prev !== b.status) {
            changed = true;
          }
        });
        if (changed) {
          // Show a non-intrusive banner to refresh
          if (!document.getElementById('sync-banner')) {
            const banner = document.createElement('div');
            banner.id = 'sync-banner';
            banner.className = 'alert alert-info alert-dismissible fade show position-fixed bottom-0 start-50 translate-middle-x mb-3 shadow';
            banner.style.zIndex = '1090';
            banner.innerHTML = `
              <i class="bi bi-arrow-repeat me-1"></i>
              Booking statuses have been updated by a driver.
              <button class="btn btn-sm btn-primary ms-2" onclick="location.reload()">Refresh Now</button>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
            document.body.appendChild(banner);
          }
        }
      })
      .catch(() => {});
  }, 15000);
</script>