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
            <h5 class="card-title">User Management</h5>
            <p>Below is a list of all registered users in the system.</p>
            <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addUserModal">
              Add User
            </button>
            <div class="table-responsive">
              <table class="table datatable">
                <thead>
                  <tr>

                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Contact Number</th>
                    <th>Email Address</th>
                    <th>Account Type</th>
                    <th>Status</th>
                    <th data-type="date" data-format="YYYY/DD/MM">Date Added</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php


                  $sql = "SELECT uid, username, full_name, contact_number, email_address, account_type, account_status, created_at FROM users where account_type != 'customer' and is_deleted = 0 ORDER BY created_at DESC";
                  $result = $conn->query($sql);

                  if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                      // Badge color based on account type
                      $badgeClass = 'bg-secondary';
                      if ($row['account_type'] === 'admin')
                        $badgeClass = 'bg-primary';
                      elseif ($row['account_type'] === 'driver')
                        $badgeClass = 'bg-warning text-dark';
                      elseif ($row['account_type'] === 'customer')
                        $badgeClass = 'bg-success';

                      // Badge color based on account status
                      $statusClass = 'bg-secondary';
                      $statusText = $row['account_status'] ?? 'Active';
                      if ($statusText === 'Active')
                        $statusClass = 'bg-success';
                      elseif ($statusText === 'Inactive')
                        $statusClass = 'bg-warning text-dark';
                      elseif ($statusText === 'Archived')
                        $statusClass = 'bg-danger';

                      echo '<tr>';

                      echo '<td>' . htmlspecialchars($row['username']) . '</td>';
                      echo '<td>' . htmlspecialchars($row['full_name']) . '</td>';
                      echo '<td>' . htmlspecialchars($row['contact_number']) . '</td>';
                      echo '<td>' . htmlspecialchars($row['email_address']) . '</td>';
                      echo '<td><span class="badge ' . $badgeClass . '">' . ucfirst(htmlspecialchars($row['account_type'])) . '</span></td>';
                      echo '<td><span class="badge ' . $statusClass . '">' . htmlspecialchars($statusText) . '</span></td>';
                      echo '<td>' . date('Y/m/d', strtotime($row['created_at'])) . '</td>';
                      echo '<td class="d-flex gap-1 justify-content-center">
                                <button class="btn btn-sm btn-primary" title="Edit" onclick="editUser(\'' . $row['uid'] . '\')" data-bs-toggle="modal" data-bs-target="#updateUserModal">
                                  <i class="bi bi-pencil"></i>
                                </button>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Change Status">
                                        <i class="bi bi-gear"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        <li><h6 class="dropdown-header">Set Status</h6></li>
                                        <li><a class="dropdown-item" href="#" onclick="changeUserStatus(\'' . $row['uid'] . '\', \'Active\')"><i class="bi bi-check-circle text-success me-2"></i>Active</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="changeUserStatus(\'' . $row['uid'] . '\', \'Inactive\')"><i class="bi bi-pause-circle text-warning me-2"></i>Inactive</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="changeUserStatus(\'' . $row['uid'] . '\', \'Archived\')"><i class="bi bi-archive text-danger me-2"></i>Archive</a></li>
                                    </ul>
                                </div>
                              </td>';
                      echo '</tr>';
                    }
                  } else {
                    echo '<tr><td colspan="8" class="text-center">No users found.</td></tr>';
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

  <!-- Add User Modal -->
  <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post" onsubmit="return validateForm()" class="needs-validation" novalidate id="register-form"
          action="controller/user/add-user.php">
          <div class="modal-header">
            <h5 class="modal-title" id="addUserModalLabel">Add User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="username" class="form-label">User Name:</label>
              <input type="text" class="form-control" id="username" name="username" placeholder="User Name" required>
            </div>
            <div class="mb-3">
              <label for="full_name" class="form-label">Full Name:</label>
              <input type="text" class="form-control" id="full_name" name="full_name"
                placeholder="First Name MI. Last Name" required>
            </div>
            <div class="mb-3">
              <label for="contact_number" class="form-label">Contact Number:</label>
              <input type="text" class="form-control" id="contact_number" name="contact_number"
                placeholder="09123456789" required inputmode="numeric" maxlength="11" pattern="\d{11}">
            </div>
            <div class="mb-3">
              <label for="emailInput" class="form-label">Email Address:</label>
              <input type="text" class="form-control" id="emailInput" name="email_address"
                placeholder="Samplemail@gmail.com" required>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Password:</label>
              <input type="password" class="form-control" id="password" name="password" placeholder="Password" required
                oninput="checkPasswordStrength()">
              <div class="form-text" id="password-strength"></div>
            </div>
            <div class="mb-3">
              <label for="confirm_password" class="form-label">Confirm Password:</label>
              <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                placeholder="Confirm Password" required oninput="checkPasswordMatch()">
              <div class="form-text" id="password-match"></div>
            </div>
            <div class="mb-3">
              <label for="account_type" class="form-label">Account Type:</label>
              <select class="form-select" id="account_type" name="account_type" required>
                <option value="" disabled selected>Select Account Type</option>

                <option value="driver">Driver</option>
              </select>
            </div>
          </div>



          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary" id="submit-btn">Submit</button>
          </div>
        </form>
      </div>
    </div>
  </div>


  <div class="modal fade" id="updateUserModal" tabindex="-1" aria-labelledby="updateUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form action="controller/user/update-user.php" method="POST" id="update-user">
          <input type="hidden" name="uid" id="update_uid">
          <div class="modal-header">
            <h5 class="modal-title" id="updateUserModalLabel">Update User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="update_username" class="form-label">User Name:</label>
              <input type="text" class="form-control" id="update_username" name="username" placeholder="User Name"
                required>
            </div>
            <div class="mb-3">
              <label for="update_full_name" class="form-label">Full Name:</label>
              <input type="text" class="form-control" id="update_full_name" name="full_name"
                placeholder="First Name MI. Last Name" required>
            </div>
            <div class="mb-3">
              <label for="update_contact_number" class="form-label">Contact Number:</label>
              <input type="text" class="form-control" id="update_contact_number" name="contact_number"
                placeholder="09123456789" required inputmode="numeric" maxlength="11" pattern="\d{11}">
            </div>
            <div class="mb-3">
              <label for="update_emailInput" class="form-label">Email Address:</label>
              <input type="text" class="form-control" id="update_emailInput" name="email_address"
                placeholder="Samplemail@gmail.com" required>
            </div>
            <div class="mb-3">
              <label for="update_password" class="form-label">Password:</label>
              <input type="password" class="form-control" id="update_password" name="password" placeholder="Password">
              <div class="form-text" id="update-password-strength"></div>
            </div>
            <div class="mb-3">
              <label for="update_confirm_password" class="form-label">Confirm Password:</label>
              <input type="password" class="form-control" id="update_confirm_password" name="confirm_password"
                placeholder="Confirm Password">
              <div class="form-text" id="update-password-match"></div>
            </div>
            <div class="mb-3">
              <label for="update_account_type" class="form-label">Account Type:</label>
              <select class="form-select" id="update_account_type" name="account_type" required>
                <option value="" disabled selected>Select Account Type</option>

                <option value="driver">Driver</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary" id="update-btn">Submit</button>
          </div>
        </form>
      </div>
    </div>
  </div>

</main>

<script>
  const createRequest = new CreateRequest({
    formSelector: "#register-form",
    submitButtonSelector: "#submit-btn",
    callback: (err, res) => err ? console.error("Form submission error:", err) : console.log(
      "Form submitted successfully:", res)
  });

  const updateRequest = new UpdateRequest({
    formSelector: '#update-user',
    updateUrl: 'controller/user/update-user.php',
    redirectUrl: 'manage-user-account',
    updateData: null,
    callback: function (error, data) {

    },
    promptMessage: 'Are you sure you want to update this user?'
  });

  window.editUser = uid => {
    new GetRequest({
      getUrl: "controller/user/get-user.php",
      params: {
        uid
      },
      promptMessage: "Do you want to load this user for editing?",
      callback: (err, data) => {
        if (err) return console.error("Error fetching user data:", err);
        console.log("User data retrieved:", data);
        const $form = $("#update-user");
        $.each(data, (key, value) => {
          const $field = $form.find("[name='" + key + "']");
          if ($field.length) $field.val(typeof value === 'boolean' ? (
            value ? 1 : 0) : value);
        });
        $form.find("[name='uid']").val(uid);
      }
    }).send();
  };


  $('#update-user').on('submit', function (e) {
    e.preventDefault();
    updateRequest.send();
  });


  window.changeUserStatus = (uid, status) => {
    Swal.fire({
      title: "Change Account Status?",
      text: `Are you sure you want to change this user's status to ${status}?`,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: status === \'Archived\' ? "#dc3545" : "#0d6efd",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Yes, change it!"
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: 'controller/user/update-status.php',
          type: 'POST',
          contentType: 'application/json',
          data: JSON.stringify({ uid: uid, status: status }),
          success: function (response) {
            if (response.status === 'success') {
              Swal.fire({
                title: "Updated!",
                text: response.message,
                icon: "success"
              }).then(() => {
                location.reload();
              });
            } else {
              Swal.fire("Error!", response.message, "error");
            }
          },
          error: function (xhr, status, error) {
            console.error("Error updating status:", error);
            Swal.fire("Error!", "An error occurred while updating the status.", "error");
          }
        });
      }
    });
  };
</script>