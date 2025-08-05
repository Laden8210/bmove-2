CREATE DATABASE IF NOT EXISTS bmovexpress_db;

USE bmovexpress_db;

CREATE TABLE users (
    uid CHAR(36) PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    contact_number VARCHAR(11) NOT NULL,
    email_address VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    account_type ENUM('admin', 'customer','driver') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


create table vehicles (
    vehicleid CHAR(36) PRIMARY KEY,
    name varchar(100),
    platenumber varchar(20) unique,
    totalcapacitykg int,
    status enum('available', 'in use', 'under maintenance', 'unavailable') default 'available',
    baseprice decimal(10, 2),
    rateperkm decimal(10, 2),
    type varchar(50),
    model varchar(50),
    year year,

    date_added date default current_date(),
    driver_uid char(36) NULL

);


CREATE TABLE bookings (
    booking_id CHAR(36) PRIMARY KEY,
    user_id CHAR(36),
    vehicle_id CHAR(36),
    pickup_location VARCHAR(255),
    pickup_lat DECIMAL(10, 7),
    pickup_lng DECIMAL(10, 7),
    dropoff_location VARCHAR(255),
    dropoff_lat DECIMAL(10, 7),
    dropoff_lng DECIMAL(10, 7),
    date DATE,
    time TIME,
    total_distance DECIMAL(10, 2),
    total_price DECIMAL(10, 2),
    total_weight INT,
    items_count INT,
    dropoff_time TIME NULL,
    pickup_time TIME NULL,
    status ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    payment_method ENUM('cash', 'gcash', 'maya') DEFAULT 'cash',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicleid) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE payments (
    payment_id CHAR(36) PRIMARY KEY,
    booking_id CHAR(36) NOT NULL,
    user_id CHAR(36) NOT NULL,

    amount_due DECIMAL(10, 2) NOT NULL,
    amount_received DECIMAL(10, 2) DEFAULT 0.00,
    change_amount DECIMAL(10, 2) GENERATED ALWAYS AS (
        CASE 
            WHEN amount_received > amount_due THEN amount_received - amount_due 
            ELSE 0.00 
        END
    ) STORED,

    payment_method ENUM('cash', 'gcash', 'maya', 'bank_transfer') NOT NULL,
    payment_status ENUM('pending', 'paid', 'partial', 'failed', 'refunded', 'cancelled') DEFAULT 'pending',

    gateway_reference VARCHAR(100),      
    gateway_url VARCHAR(255),          

    receipt_number VARCHAR(100),
    paid_at TIMESTAMP NULL,             
    notes TEXT,                        

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    created_by CHAR(36),
    updated_by CHAR(36),

    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(uid) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(uid) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(uid) ON DELETE SET NULL ON UPDATE CASCADE
);


create table comments (
    comment_id char(36) primary key,
    booking_id char(36) not null,
    user_id char(36) not null,
    comment text not null,
    comment_rating int check (comment_rating >= 1 and comment_rating <= 5),
    created_at timestamp default current_timestamp,

    foreign key (booking_id) references bookings(booking_id) on delete cascade on update cascade,
    foreign key (user_id) references users(uid) on delete cascade on update cascade
);