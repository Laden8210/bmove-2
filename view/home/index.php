<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
            <img src="public/images/c580uoa9yo.jpg" alt="Family Home" class="img-fluid mb-4">
            <h1 class="display-5 fw-bold mb-3">We are Here to Help You Move</h1>
            <p class="lead">WE MAKE MOVING EASY, FAST, AND HASSLE FREE.</p>
            <a href="#" class="btn btn-primary btn-lg my-3">BOOK NOW</a>
        </div>
        <div class="row mt-5">
            <?php

            $query = "SELECT vehicleid, name, baseprice, type, model, year, platenumber, totalcapacitykg, status, rateperkm, date_added FROM vehicles WHERE status = 'available' ORDER BY date_added DESC LIMIT 3";
            $result = $conn->query($query);

            if ($result && $result->num_rows > 0):
                while ($car = $result->fetch_assoc()): 
            ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($car['name']) ?></h5>
                                <ul class="list-unstyled mb-2">
                                    <li><strong>Plate Number:</strong> <?= htmlspecialchars($car['platenumber']) ?></li>
                                    <li><strong>Type:</strong> <?= htmlspecialchars($car['type']) ?></li>
                                    <li><strong>Model:</strong> <?= htmlspecialchars($car['model']) ?></li>
                                    <li><strong>Year:</strong> <?= htmlspecialchars($car['year']) ?></li>
                                    <li><strong>Total Capacity:</strong> <?= number_format($car['totalcapacitykg']) ?> kg</li>
                                    <li>
                                        <strong>Status:</strong>
                                        <?php if ($car['status'] === 'available'): ?>
                                            <span class="badge bg-success"><?= htmlspecialchars($car['status']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($car['status']) ?></span>
                                        <?php endif; ?>
                                    </li>
                                    <li><strong>Rate per KM:</strong> &#8369;<?= number_format($car['rateperkm'], 2) ?></li>
                                    <li><strong>Date Added:</strong> <?= htmlspecialchars($car['date_added']) ?></li>
                                </ul>
                            </div>
                            <div class="card-footer">
                                <strong>Base Price: &#8369;<?= number_format($car['baseprice'], 2) ?></strong>
                                <a href="book?car=<?= urlencode($car['vehicleid']) ?>" class="btn btn-success btn-sm float-end ms-2">Book Now</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile;
            else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">No vehicles available at the moment.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>