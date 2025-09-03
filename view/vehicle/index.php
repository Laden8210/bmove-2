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
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div>
                <h5 class="card-title mb-0">Vehicle Management</h5>
                <p class="mb-0">Below is a list of all vehicles in the system.</p>
              </div>
              <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTruckModal">
                <i class="bi bi-plus"></i> Add Truck
              </button>
            </div>
            <div class="table-responsive">
              <table class="table datatable">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Image</th>
                    <th>Plate Number</th>
                    <th>Total Capacity (kg)</th>
                    <th>Status</th>
                    <th>Base Price</th>
                    <th>Rate per km</th>
                    <th>Type</th>
                    <th>Model</th>
                    <th>Year</th>
                    <th>Driver</th>
                    <th data-type="date" data-format="YYYY/DD/MM">Date Added</th>
                    <th>Action</th>
                  </tr>
                </thead>

                <tbody>
                  <?php
                  $sql = "SELECT v.*, u.full_name AS driver_name FROM vehicles v LEFT JOIN users u ON v.driver_uid = u.uid ORDER BY v.date_added DESC";
                  $result = $conn->query($sql);

                  if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                      echo "<tr>";
                      echo "<td>";
                      if (!empty($row['image_path'])) {
                        echo "<img src='uploads/vehicles/" . htmlspecialchars($row['image_path']) . "' alt='" . htmlspecialchars($row['name']) . "' class='img-thumbnail' style='max-width: 100px;'>";
                      } else {
                        echo "<span class='badge bg-secondary'>No Image</span>";
                      }
                      echo "</td>";
                      echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['platenumber']) . "</td>";
                      echo "<td>" . number_format($row['totalcapacitykg']) . "</td>";
                      $status = ucwords($row['status']);
                      $badgeClass = 'secondary';
                      switch (strtolower($row['status'])) {
                        case 'available':
                          $badgeClass = 'success';
                          break;
                        case 'in use':
                          $badgeClass = 'primary';
                          break;
                        case 'under maintenance':
                          $badgeClass = 'warning';
                          break;
                        case 'unavailable':
                          $badgeClass = 'danger';
                          break;
                      }
                      echo "<td><span class='badge bg-$badgeClass'>$status</span></td>";
                      echo "<td>" . number_format($row['baseprice'], 2) . "</td>";
                      echo "<td>" . number_format($row['rateperkm'], 2) . "</td>";
                      echo "<td>" . htmlspecialchars($row['type']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['model']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['year']) . "</td>";
                      $driverName = $row['driver_name'] ?? 'Unassigned';
                      if ($driverName === 'Unassigned' || empty($driverName)) {
                        echo "<td><span class='badge bg-secondary'>Unassigned</span></td>";
                      } else {
                        echo "<td><span class='badge bg-info text-dark'>" . htmlspecialchars($driverName) . "</span></td>";
                      }
                      $dateAdded = date('Y-m-d', strtotime($row['date_added']));
                      echo "<td>" . htmlspecialchars($dateAdded) . "</td>";
                      echo '<td class="d-flex gap-1 justify-content-center">
                      <button type="button" class="btn btn-sm btn-primary" title="Edit" onclick="editTruck(\'' . htmlspecialchars($row['vehicleid'], ENT_QUOTES) . '\')" data-bs-toggle="modal" data-bs-target="#updateTruckModal">
                        <i class="bi bi-pencil"></i>
                      </button>
                      
                      <!-- Update status button -->
                      <button type="button" class="btn btn-sm btn-secondary" title="Update Status" onclick="updateStatus(\'' . htmlspecialchars($row['vehicleid'], ENT_QUOTES) . '\')" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                        <i class="bi bi-arrow-repeat"></i>
                      </button>


                      <button type="button" class="btn btn-sm btn-danger" title="Delete" onclick="deleteQuestion(\'' . htmlspecialchars($row['vehicleid'], ENT_QUOTES) . '\') ">
                        <i class="bi bi-trash"></i>
                      </button>

                      <button type="button" class="btn btn-sm btn-info" title="View Comments" onclick="viewComments(\'' . htmlspecialchars($row['vehicleid'], ENT_QUOTES) . '\')" data-bs-toggle="modal" data-bs-target="#viewCommentsModal">
                        <i class="bi bi-chat-dots"></i>
                      </button>
                    </td>';
                      echo "</tr>";
                    }
                  } else {
                    echo '<tr><td colspan="12" class="text-center">No trucks found.</td></tr>';
                  }
                  ?>
                </tbody>
              </table>
            </div>

          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- View Comments Modal -->
  <div class="modal fade" id="viewCommentsModal" tabindex="-1" aria-labelledby="viewCommentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewCommentsModalLabel">Comments</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="commentsList" class="list-group">
            <!-- Comments will be dynamically loaded here -->
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- Add Truck Modal -->
  <div class="modal fade" id="addTruckModal" tabindex="-1" aria-labelledby="addTruckModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <form method="post" onsubmit="return validateForm()" class="needs-validation" novalidate id="add-form" action="controller/truck/create-truck.php">

        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addTruckModalLabel">Add New Truck</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">

              <div class="col-md-6">
                <label for="truckName" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
              </div>
              <div class="col-md-6">
                <label for="plateNumber" class="form-label">Plate Number</label>
                <input type="text" class="form-control" id="platenumber" name="platenumber" required>
              </div>
              <div class="col-md-6">
                <label for="capacity" class="form-label">Total Capacity (kg)</label>
                <input type="number" class="form-control" id="totalcapacitykg" name="totalcapacitykg" required>
              </div>
              <div class="col-md-6">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" required>
                  <option value="available">Available</option>
                  <option value="in use">In Use</option>
                  <option value="under maintenance">Under Maintenance</option>
                  <option value="unavailable">Unavailable</option>
                </select>
              </div>
              <div class="col-md-6">
                <label for="basePrice" class="form-label">Base Price</label>
                <input type="number" class="form-control" id="baseprice" name="baseprice" required>
              </div>
              <div class="col-md-6">
                <label for="ratePerKm" class="form-label">Rate per km</label>
                <input type="number" class="form-control" id="rateperkm" name="rateperkm" required>
              </div>
              <div class="col-md-6">
                <label for="type" class="form-label">Type</label>
                <select class="form-select" id="type" name="type" required>
                  <option value="">Select Type</option>
                  <option value="Flatbed">Flatbed</option>
                  <option value="Box Truck">Box Truck</option>
                  <option value="Refrigerated">Refrigerated</option>
                  <option value="Tanker">Tanker</option>
                  <option value="Dump Truck">Dump Truck</option>
                  <option value="Car Carrier">Car Carrier</option>
                  <option value="Lowboy">Lowboy</option>
                  <option value="Curtainside">Curtainside</option>
                  <option value="Logging Truck">Logging Truck</option>
                  <option value="Livestock Truck">Livestock Truck</option>
                  <option value="Van">Van</option>
                  <option value="Car">Car</option>
                  <option value="Pickup">Pickup</option>
                  <option value="Mini Truck">Mini Truck</option>
                  <option value="Panel Truck">Panel Truck</option>
                  <option value="Step Van">Step Van</option>
                  <option value="Box Van">Box Van</option>
                  <option value="Chiller Van">Chiller Van</option>
                  <option value="Container Truck">Container Truck</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <div class="col-md-6">
                <label for="model" class="form-label">Model</label>
                <input type="text" class="form-control" id="model" name="model" required>
              </div>
              <div class="col-md-6">
                <label for="year" class="form-label">Year</label>
                <input type="number" class="form-control" id="year" name="year" required>
              </div>

              <div class="col-md-6">
                <label for="vehicle_image" class="form-label">Vehicle Image</label>
                <input type="file" class="form-control" id="vehicle_image" name="vehicle_image" accept="image/*">
              </div>
              <div class="col-md-6">
                <label for="driver" class="form-label">Driver</label>

                <?php
                $sql = "SELECT * FROM users WHERE account_type = 'driver'";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $result = $stmt->get_result();
                $drivers = $result->fetch_all(MYSQLI_ASSOC);

                if ($result->num_rows > 0) {
                  echo '<select class="form-select" id="driver_uid" name="driver_uid" required>';
                  echo '<option value="">Select Driver</option>';
                  foreach ($drivers as $driver) {
                    echo '<option value="' . $driver['uid'] . '">' . $driver['full_name'] . '</option>';
                  }
                  echo '</select>';
                } else {
                  echo '<input type="text" class="form-control" id="driver_uid" name="driver_uid" placeholder="No drivers available" readonly>';
                }
                ?>
              </div>

            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary" id="submit-btn">Submit</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Update Truck Modal -->
  <div class="modal fade" id="updateTruckModal" tabindex="-1" aria-labelledby="updateTruckModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <form method="post" class="needs-validation" novalidate id="update-form" action="controller/truck/update-truck.php">
        <input type="hidden" name="vehicleid" id="update-vehicleid">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="updateTruckModalLabel">Update Truck</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label for="update-name" class="form-label">Name</label>
                <input type="text" class="form-control" id="update-name" name="name" required>
              </div>
              <div class="col-md-6">
                <label for="update-platenumber" class="form-label">Plate Number</label>
                <input type="text" class="form-control" id="update-platenumber" name="platenumber" required>
              </div>
              <div class="col-md-6">
                <label for="update-totalcapacitykg" class="form-label">Total Capacity (kg)</label>
                <input type="number" class="form-control" id="update-totalcapacitykg" name="totalcapacitykg" required>
              </div>
              <div class="col-md-6">
                <label for="update-status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" required>
                  <option value="available">Available</option>
                  <option value="in use">In Use</option>
                  <option value="under maintenance">Under Maintenance</option>
                  <option value="unavailable">Unavailable</option>
                </select>
              </div>
              <div class="col-md-6">
                <label for="update-baseprice" class="form-label">Base Price</label>
                <input type="number" class="form-control" id="update-baseprice" name="baseprice" required>
              </div>
              <div class="col-md-6">
                <label for="update-rateperkm" class="form-label">Rate per km</label>
                <input type="number" class="form-control" id="update-rateperkm" name="rateperkm" required>
              </div>
              <div class="col-md-6">
                <label for="update-type" class="form-label">Type</label>
                <select class="form-select" id="update-type" name="type" required>
                  <option value="">Select Type</option>
                  <option value="Flatbed">Flatbed</option>
                  <option value="Box Truck">Box Truck</option>
                  <option value="Refrigerated">Refrigerated</option>
                  <option value="Tanker">Tanker</option>
                  <option value="Dump Truck">Dump Truck</option>
                  <option value="Car Carrier">Car Carrier</option>
                  <option value="Lowboy">Lowboy</option>
                  <option value="Curtainside">Curtainside</option>
                  <option value="Logging Truck">Logging Truck</option>
                  <option value="Livestock Truck">Livestock Truck</option>
                  <option value="Van">Van</option>
                  <option value="Car">Car</option>
                  <option value="Pickup">Pickup</option>
                  <option value="Mini Truck">Mini Truck</option>
                  <option value="Panel Truck">Panel Truck</option>
                  <option value="Step Van">Step Van</option>
                  <option value="Box Van">Box Van</option>
                  <option value="Chiller Van">Chiller Van</option>
                  <option value="Container Truck">Container Truck</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <div class="col-md-6">
                <label for="update-model" class="form-label">Model</label>
                <input type="text" class="form-control" id="update-model" name="model" required>
              </div>
              <div class="col-md-6">
                <label for="update-year" class="form-label">Year</label>
                <input type="number" class="form-control" id="update-year" name="year" required>
              </div>
              <div class="col-md-6">
                <label for="update-driver_uid" class="form-label">Driver</label>
                <?php
                $sql = "SELECT * FROM users WHERE account_type = 'driver'";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $result = $stmt->get_result();
                $drivers = $result->fetch_all(MYSQLI_ASSOC);

                if ($result->num_rows > 0) {
                  echo '<select class="form-select" id="update-driver_uid" name="driver_uid" required>';
                  echo '<option value="">Select Driver</option>';
                  foreach ($drivers as $driver) {
                    echo '<option value="' . $driver['uid'] . '">' . $driver['full_name'] . '</option>';
                  }
                  echo '</select>';
                } else {
                  echo '<input type="text" class="form-control" id="update-driver_uid" name="driver_uid" placeholder="No drivers available" readonly>';
                }
                ?>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary" id="update-submit-btn">Update</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Update Status Modal -->
  <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form method="post" class="needs-validation" novalidate id="update-status-form" action="controller/truck/update-truck-status.php">
        <input type="hidden" name="vehicleid" id="status-vehicleid">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="updateStatusModalLabel">Update Truck Status</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="status" class="form-label">Select New Status</label>
              <select class="form-select" id="status" name="status" required>
                <option value="available">Available</option>
                <option value="in use">In Use</option>
                <option value="under maintenance">Under Maintenance</option>
                <option value="unavailable">Unavailable</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary" id="update-status-submit-btn">Update Status</button>
          </div>
        </div>
      </form>
    </div>
  </div>
  </div>



</main>


<script>
  document.querySelector("#add-form").addEventListener("submit", function(e) {
    e.preventDefault();

    const submitBtn = document.querySelector("#submit-btn");
    submitBtn.disabled = true;

    const formData = new FormData(this);

    fetch(this.action, {
        method: this.method,
        body: formData
      })
      .then((res) => res.json())
      .then((res) => {
        if (res.status === 'success') {
            Swal.fire({
            icon: "success",
            title: "Success!",
            text: res.message || "Truck added successfully.",
            timer: 2000,
            showConfirmButton: false
            }).then(() => {
            location.reload();
            });

          // Reset form & close modal
          this.reset();
          const modalEl = document.getElementById("addTruckModal");
          const modal = bootstrap.Modal.getInstance(modalEl);
          modal.hide();
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: res.message || "Something went wrong."
          });
        }
      })
      .catch((err) => {
        Swal.fire({
          icon: "error",
          title: "Request Failed",
          text: err.message || "Could not submit the form."
        });
      })
      .finally(() => {
        submitBtn.disabled = false;
      });
  });



  $('#update-form').on('submit', function(e) {
    e.preventDefault();
    updateRequest.send();
  });

  // view comments function
  window.viewComments = vehicleId => {
    const commentContainer = document.getElementById('commentsList');

    commentContainer.innerHTML = '';

    new GetRequest({
      getUrl: "controller/comment/get-comment-by-vehicle.php",
      params: {
        vehicle_id: vehicleId
      },
      callback: (err, data) => {
        if (err) {
          showErrorToast("Failed to load comments");
          console.error("Comment fetch error:", err);
          return;
        }

        // Populate comments
        if (!data.length) {
          commentContainer.innerHTML = '<div class="text-center text-muted py-4">No comments found for this vehicle.</div>';
        } else {
          data.forEach(comment => {
            const listItem = document.createElement('div');
            listItem.className = 'list-group-item py-3';
            listItem.innerHTML = `
              <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="fw-semibold mb-1">
              <i class="bi bi-person-circle me-1"></i>
              ${comment.user_name || 'Anonymous'}
            </div>
            <div class="mb-1">
              ${'<span class="text-warning">' + '★'.repeat(comment.comment_rating) + '</span>'}
              ${'<span class="text-muted">' + '★'.repeat(5 - comment.comment_rating) + '</span>'}
            </div>
            <div class="text-body">${comment.comment}</div>
          </div>
          <div class="text-end">
            <small class="text-muted">${new Date(comment.created_at).toLocaleString()}</small>
          </div>
              </div>
            `;
            commentContainer.appendChild(listItem);
          });
        }

      }
    }).send();
  };

  const updateStatusRequest = new UpdateRequest({
    formSelector: '#update-status-form',
    updateUrl: 'controller/truck/update-truck-status.php',
    redirectUrl: 'vehicle',
    updateData: null,
    callback: function(error, data) {
      if (error) {
        console.error("Error updating status:", error);
      } else {
        console.log("Status updated successfully:", data);
      }
    },
    promptMessage: 'Are you sure you want to update the status of this vehicle?'
  });

  $('#update-status-form').on('submit', function(e) {
    e.preventDefault();
    updateStatusRequest.send();
  });

  window.updateStatus = uid => {
    new GetRequest({
      getUrl: "controller/truck/get-truck.php",
      params: {
        uid
      },
      promptMessage: "Do you want to load this vehicle for status update?",
      callback: (err, data) => {
        if (err) return console.error("Error fetching vehicle data:", err);
        console.log("Vehicle data retrieved:", data);
        const $form = $("#update-status-form");
        $form.find("[name='vehicleid']").val(uid);
        $form.find("[name='status']").val(data.status);
      }
    }).send();
  };


  const updateRequest = new UpdateRequest({
    formSelector: '#update-form',
    updateUrl: 'controller/truck/update-truck.php',
    redirectUrl: 'vehicle',
    updateData: null,
    callback: function(error, data) {

    },
    promptMessage: 'Are you sure you want to update this user?'
  });


  window.editTruck = uid => {
    new GetRequest({
      getUrl: "controller/truck/get-truck.php",
      params: {
        uid
      },
      promptMessage: "Do you want to load this vehicle for editing?",
      callback: (err, data) => {
        if (err) return console.error("Error fetching user data:", err);
        console.log("User data retrieved:", data);
        const $form = $("#update-form");
        $.each(data, (key, value) => {
          const $field = $form.find("[name='" + key + "']");
          if ($field.length) $field.val(typeof value === 'boolean' ? (
            value ? 1 : 0) : value);
        });
        $form.find("[name='uid']").val(uid);
      }
    }).send();
  };

  window.deleteQuestion = uid => {
    new DeleteRequest({
      deleteUrl: "controller/truck/delete-truck.php",
      data: {
        uid: uid.toString()
      },
      promptMessage: "Do you want to delete this user?",
      callback: (err, res) => {
        if (err) return console.error("Deletion error:", err);
        console.log("Item deleted successfully:", res);
      }
    }).send();
  };
</script>