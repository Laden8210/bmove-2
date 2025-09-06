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
                        <div class="vehicle-card h-100">
                            <!-- Vehicle Image -->
                            <div class="vehicle-image-container">
                                <img src="<?= htmlspecialchars($image_path) ?>" 
                                     alt="<?= htmlspecialchars($car['name']) ?>" 
                                     class="vehicle-image" 
                                     onerror="this.src='uploads/default-vehicle.jpg'">
                                <div class="vehicle-overlay">
                                    <div class="vehicle-status-badge">
                                        <?php if ($car['status'] === 'available'): ?>
                                            <span class="status-available"><?= htmlspecialchars($car['status']) ?></span>
                                        <?php else: ?>
                                            <span class="status-unavailable"><?= htmlspecialchars($car['status']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="vehicle-content">
                                <div class="vehicle-header">
                                    <h5 class="vehicle-title"><?= htmlspecialchars($car['name']) ?></h5>
                                    <div class="vehicle-price">₱<?= number_format($car['baseprice'], 2) ?></div>
                                </div>
                                
                                <!-- Rating Display -->
                                <div class="rating-section">
                                    <div class="star-rating">
                                        <?php
                                        $full_stars = floor($average_rating);
                                        $has_half_star = ($average_rating - $full_stars) >= 0.5;
                                        
                                        for ($i = 1; $i <= 5; $i++):
                                            if ($i <= $full_stars):
                                                echo '<i class="star-fill">★</i>';
                                            elseif ($has_half_star && $i == $full_stars + 1):
                                                echo '<i class="star-half">★</i>';
                                            else:
                                                echo '<i class="star-empty">☆</i>';
                                            endif;
                                        endfor;
                                        ?>
                                    </div>
                                    <span class="rating-text">
                                        <?= number_format($average_rating, 1) ?> (<?= $total_ratings ?> reviews)
                                    </span>
                                </div>

                                <div class="vehicle-specs">
                                    <div class="spec-grid">
                                        <div class="spec-item">
                                            <span class="spec-label">Plate Number</span>
                                            <span class="spec-value"><?= htmlspecialchars($car['platenumber']) ?></span>
                                        </div>
                                        <div class="spec-item">
                                            <span class="spec-label">Type</span>
                                            <span class="spec-value"><?= htmlspecialchars($car['type']) ?></span>
                                        </div>
                                        <div class="spec-item">
                                            <span class="spec-label">Model</span>
                                            <span class="spec-value"><?= htmlspecialchars($car['model']) ?></span>
                                        </div>
                                        <div class="spec-item">
                                            <span class="spec-label">Year</span>
                                            <span class="spec-value"><?= htmlspecialchars($car['year']) ?></span>
                                        </div>
                                        <div class="spec-item">
                                            <span class="spec-label">Capacity</span>
                                            <span class="spec-value"><?= number_format($car['totalcapacitykg']) ?> kg</span>
                                        </div>
                                        <div class="spec-item">
                                            <span class="spec-label">Rate per KM</span>
                                            <span class="spec-value">₱<?= number_format($car['rateperkm'], 2) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="vehicle-footer">
                                <div class="date-added">Added: <?= date('M d, Y', strtotime($car['date_added'])) ?></div>
                                <a href="book?car=<?= urlencode($car['vehicleid']) ?>" class="book-btn">Book Now</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile;
            else: ?>
                <div class="col-12">
                    <div class="no-vehicles-alert">
                        <div class="alert-icon">🚛</div>
                        <h3>No Vehicles Available</h3>
                        <p>We're currently updating our fleet. Please check back later!</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Main Container Enhancements */
.container {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}


.display-5 {
    color: #1a202c;
    font-weight: 700;
    letter-spacing: -0.02em;
}

.lead {
    color: #4a5568;
    font-weight: 500;
    margin-bottom: 2rem;
}

.btn-primary {
    background-color: #3182ce;
    border-color: #3182ce;
    padding: 15px 40px;
    font-weight: 600;
    letter-spacing: 0.5px;
    border-radius: 50px;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background-color: #2c5aa0;
    border-color: #2c5aa0;
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(49, 130, 206, 0.3);
}

/* Vehicle Card Enhancements */
.vehicle-card {
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    position: relative;
}

.vehicle-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    border-color: #3182ce;
}

/* Vehicle Image Container */
.vehicle-image-container {
    position: relative;
    overflow: hidden;
    height: 240px;
}

.vehicle-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.vehicle-card:hover .vehicle-image {
    transform: scale(1.1);
}

.vehicle-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to bottom, rgba(0,0,0,0.1) 0%, transparent 50%, rgba(0,0,0,0.1) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.vehicle-card:hover .vehicle-overlay {
    opacity: 1;
}

.vehicle-status-badge {
    position: absolute;
    top: 15px;
    right: 15px;
}

.status-available {
    background-color: #48bb78;
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-unavailable {
    background-color: #a0aec0;
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Vehicle Content */
.vehicle-content {
    padding: 25px;
    flex: 1;
}

.vehicle-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.vehicle-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: #1a202c;
    margin: 0;
    flex: 1;
    margin-right: 15px;
}

.vehicle-price {
    font-size: 1.3rem;
    font-weight: 700;
    color: #3182ce;
    white-space: nowrap;
}

/* Rating Section */
.rating-section {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 25px;
    padding: 12px;
    background-color: #f7fafc;
    border-radius: 12px;
}

.star-rating {
    display: flex;
    gap: 2px;
}

.star-fill {
    color: #f6ad55;
    font-size: 1.1rem;
}

.star-half {
    color: #f6ad55;
    font-size: 1.1rem;
    opacity: 0.5;
}

.star-empty {
    color: #cbd5e0;
    font-size: 1.1rem;
}

.rating-text {
    color: #4a5568;
    font-size: 0.9rem;
    font-weight: 500;
}

/* Vehicle Specifications */
.vehicle-specs {
    margin-bottom: 20px;
}

.spec-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.spec-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.spec-label {
    font-size: 0.8rem;
    color: #718096;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.spec-value {
    font-size: 0.95rem;
    color: #2d3748;
    font-weight: 600;
}

/* Vehicle Footer */
.vehicle-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    background-color: #f7fafc;
    border-top: 1px solid #e2e8f0;
}

.date-added {
    font-size: 0.85rem;
    color: #718096;
    font-weight: 500;
}

.book-btn {
    background-color: #48bb78;
    color: white;
    padding: 10px 25px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.book-btn:hover {
    background-color: #38a169;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(72, 187, 120, 0.3);
    color: white;
    text-decoration: none;
}

/* No Vehicles Alert */
.no-vehicles-alert {
    text-align: center;
    padding: 60px 40px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    border: 1px solid #e2e8f0;
}

.alert-icon {
    font-size: 4rem;
    margin-bottom: 20px;
}

.no-vehicles-alert h3 {
    color: #1a202c;
    font-weight: 700;
    margin-bottom: 10px;
}

.no-vehicles-alert p {
    color: #4a5568;
    font-size: 1.1rem;
    margin: 0;
}

/* Responsive Design */
@media (max-width: 768px) {
    .vehicle-header {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
    
    .vehicle-price {
        font-size: 1.5rem;
    }
    
    .spec-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .vehicle-footer {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
        text-align: center;
    }
    
    .book-btn {
        width: 100%;
        padding: 12px 25px;
    }
    
    .rating-section {
        flex-direction: column;
        gap: 8px;
        align-items: flex-start;
    }
}

@media (max-width: 576px) {
    .vehicle-content {
        padding: 20px;
    }
    
    .vehicle-footer {
        padding: 15px 20px;
    }
    
    .no-vehicles-alert {
        padding: 40px 20px;
    }
    
    .display-5 {
        font-size: 2rem;
    }
}

/* Animation for cards appearing */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.vehicle-card {
    animation: fadeInUp 0.6s ease-out;
}

.vehicle-card:nth-child(2) {
    animation-delay: 0.1s;
}

.vehicle-card:nth-child(3) {
    animation-delay: 0.2s;
}

.vehicle-card:nth-child(4) {
    animation-delay: 0.3s;
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

.book-btn:focus,
.btn-primary:focus {
    outline: 3px solid #3182ce;
    outline-offset: 2px;
}
</style>