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
            $query = "SELECT 
                v.vehicleid, 
                v.name, 
                v.baseprice, 
                v.type, 
                v.model, 
                v.year, 
                v.platenumber, 
                v.totalcapacitykg, 
                v.status, 
                v.rateperkm, 
                v.date_added,
                v.image_path,  
                COALESCE(AVG(c.comment_rating), 0) as average_rating,
                COUNT(c.comment_id) as total_ratings
            FROM vehicles v
            LEFT JOIN bookings b ON v.vehicleid = b.vehicle_id
            LEFT JOIN comments c ON b.booking_id = c.booking_id
            WHERE v.status = 'available' 
            GROUP BY v.vehicleid
            ORDER BY v.date_added DESC 
            ";
            
            $result = $conn->query($query);

            if ($result && $result->num_rows > 0):
                while ($car = $result->fetch_assoc()): 
                    $average_rating = round($car['average_rating'], 1);
                    $total_ratings = $car['total_ratings'];
                    $image_path =  "upload/vehicles/".$car['image_path'] ?: 'uploads/default-vehicle.jpg'; 
            ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <!-- Vehicle Image -->
                            <img src="<?= htmlspecialchars($image_path) ?>" 
                                 alt="<?= htmlspecialchars($car['name']) ?>" 
                                 class="card-img-top" 
                                 style="height: 200px; object-fit: cover;"
                                 onerror="this.src='uploads/default-vehicle.jpg'">
                            
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($car['name']) ?></h5>
                                
                                <!-- Rating Display -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-center mb-1">
                                        <div class="star-rating me-2">
                                            <?php
                                            $full_stars = floor($average_rating);
                                            $has_half_star = ($average_rating - $full_stars) >= 0.5;
                                            
                                            for ($i = 1; $i <= 5; $i++):
                                                if ($i <= $full_stars):
                                                    echo '<i class="bi bi-star-fill text-warning"></i>';
                                                elseif ($has_half_star && $i == $full_stars + 1):
                                                    echo '<i class="bi bi-star-half text-warning"></i>';
                                                else:
                                                    echo '<i class="bi bi-star text-warning"></i>';
                                                endif;
                                            endfor;
                                            ?>
                                        </div>
                                        <span class="text-muted small">
                                            <?= number_format($average_rating, 1) ?> (<?= $total_ratings ?> reviews)
                                        </span>
                                    </div>
                                </div>

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
</div>

<style>
.star-rating {
    font-size: 1rem;
}
.star-rating i {
    margin-right: 1px;
}
.card-img-top {
    transition: transform 0.3s ease;
}
.card:hover .card-img-top {
    transform: scale(1.05);
}
</style>