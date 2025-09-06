<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - MoveEasy</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.7;
            color: #2d3748;
            background-color: #f7fafc;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .headers {
            text-align: center;
            margin-bottom: 60px;
            background: #ffffff;
            padding: 60px 40px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            position: relative;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background-color: #3182ce;
        }

        .header h1 {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 20px;
            letter-spacing: -0.02em;
        }

        .header p {
            font-size: 1.25rem;
            color: #4a5568;
            max-width: 700px;
            margin: 0 auto;
            font-weight: 400;
        }

        .section {
            background: #ffffff;
            margin: 40px 0;
            padding: 50px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .section:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        .section h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 30px;
            position: relative;
            padding-left: 30px;
        }

        .section h2::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 6px;
            height: 60px;
            background-color: #3182ce;
            border-radius: 3px;
        }

        .section p {
            font-size: 1.1rem;
            color: #4a5568;
            margin-bottom: 20px;
            font-weight: 400;
            line-height: 1.8;
        }

        .capabilities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 40px;
        }

        .capability-item {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            position: relative;
        }

        .capability-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            border-color: #3182ce;
        }

        .capability-item h3 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #1a202c;
        }

        .capability-item p {
            color: #4a5568;
            margin: 0;
            line-height: 1.6;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .team-member {
            background-color: #ffffff;
            padding: 40px 30px 30px;
            border-radius: 16px;
            text-align: center;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            position: relative;
        }

        .team-member:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
            border-color: #3182ce;
        }

        .team-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 20px;
            background-color: #3182ce;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
            color: #ffffff;
            border: 4px solid #e2e8f0;
        }

        .team-member:hover .team-avatar {
            border-color: #3182ce;
        }

        .team-member h3 {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #1a202c;
        }

        .team-member .age,
        .team-member .location {
            font-size: 1rem;
            color: #4a5568;
            margin-bottom: 8px;
        }

        .team-member .course {
            background-color: #3182ce;
            color: #ffffff;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-block;
            margin-top: 10px;
        }

        .mission-vision {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .mission, .vision {
            padding: 40px;
            border-radius: 16px;
            text-align: center;
            transition: transform 0.3s ease;
            border: 2px solid #e2e8f0;
        }

        .mission:hover, .vision:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .mission {
            background-color: #edf2f7;
            border-color: #cbd5e0;
        }

        .vision {
            background-color: #f0fff4;
            border-color: #c6f6d5;
        }

        .mission h3, .vision h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: #1a202c;
        }

        .mission p, .vision p {
            font-size: 1.1rem;
            margin: 0;
            color: #2d3748;
            line-height: 1.7;
        }

        .stats-section {
            background-color: #2d3748;
            color: #ffffff;
            padding: 60px 50px;
            text-align: center;
            border-radius: 16px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-top: 40px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: #3182ce;
            display: block;
        }

        .stat-label {
            font-size: 1.1rem;
            color: #cbd5e0;
            margin-top: 5px;
        }

        /* Enhanced animations */
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

        .section {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Responsive improvements */
        @media (max-width: 1024px) {
            .container {
                padding: 15px;
            }
            
            .section {
                padding: 35px;
            }
            
            .capabilities-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 40px 25px;
                margin-bottom: 40px;
            }
            
            .section {
                padding: 25px;
                margin: 25px 0;
            }
            
            .section h2 {
                font-size: 2rem;
                padding-left: 25px;
            }
            
            .capabilities-grid {
                grid-template-columns: 1fr;
            }
            
            .team-grid {
                grid-template-columns: 1fr;
            }
            
            .mission-vision {
                grid-template-columns: 1fr;
            }

            .stats-section {
                padding: 40px 25px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 30px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 10px;
            }
            
            .header {
                padding: 30px 20px;
                border-radius: 12px;
            }
            
            .section {
                padding: 20px;
                border-radius: 12px;
            }
            
            .section h2 {
                font-size: 1.75rem;
            }
            
            .capability-item {
                padding: 25px;
            }
            
            .team-member {
                padding: 30px 25px 25px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }


        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }


        .capability-item:focus,
        .team-member:focus {
            outline: 3px solid #3182ce;
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="headers">
            <h1>About MoveEasy</h1>
            <p>Your trusted partner for seamless moving and logistics solutions with cutting-edge technology and exceptional service</p>
        </div>

        <div class="section">
            <h2>Company Overview</h2>
            <p>MoveEasy is a comprehensive moving and logistics application designed to revolutionize the way people plan, book, and manage their moving services. Our platform combines modern technology with traditional moving expertise to provide customers with a seamless, transparent, and efficient moving experience.</p>
            
            <p>Built by a dedicated team of BSIT students from Bataan, our application leverages real-time tracking, automated booking systems, and intelligent route optimization to ensure your belongings reach their destination safely and on time.</p>

            <div class="mission-vision">
                <div class="mission">
                    <h3>Our Mission</h3>
                    <p>To simplify the moving process by providing innovative, reliable, and customer-centric logistics solutions that make relocating stress-free and affordable for everyone.</p>
                </div>
                <div class="vision">
                    <h3>Our Vision</h3>
                    <p>To become the leading digital platform for moving services, setting new standards in customer satisfaction, operational efficiency, and technological innovation.</p>
                </div>
            </div>
        </div>



        <div class="section">
            <h2>Platform Capabilities</h2>
            <p>MoveEasy offers a comprehensive suite of features designed to enhance every aspect of your moving experience with professional-grade tools and services.</p>
            
            <div class="capabilities-grid">
                <div class="capability-item">
                    <h3>Customer Registration & Login</h3>
                    <p>Secure user registration and authentication system with comprehensive account management and data protection protocols.</p>
                </div>
                
                <div class="capability-item">
                    <h3>Vehicle Management</h3>
                    <p>Advanced administrative tools for fleet management, vehicle tracking, and assignment optimization for maximum efficiency.</p>
                </div>
                
                <div class="capability-item">
                    <h3>Real-Time Fare Calculator</h3>
                    <p>Precision distance-based pricing system integrated with Google Maps API for accurate cost estimation and transparent billing.</p>
                </div>
                
                <div class="capability-item">
                    <h3>Multiple Payment Options</h3>
                    <p>Flexible payment solutions supporting GCash, Maya, credit cards, and cash on delivery for customer convenience.</p>
                </div>
                
                <div class="capability-item">
                    <h3>Automated Booking System</h3>
                    <p>Streamlined booking process with automated confirmations, scheduling, and comprehensive service activity management.</p>
                </div>
                
                <div class="capability-item">
                    <h3>Status Management</h3>
                    <p>Real-time tracking system with detailed status updates including pending, confirmed, in-transit, delivered, and cancelled states.</p>
                </div>
                
                <div class="capability-item">
                    <h3>User Management</h3>
                    <p>Comprehensive account creation and modification tools for customers, drivers, and administrative personnel.</p>
                </div>
                
                <div class="capability-item">
                    <h3>Rating & Review System</h3>
                    <p>Enhanced service reliability through comprehensive customer feedback system and professional mover rating protocols.</p>
                </div>
                
                <div class="capability-item">
                    <h3>Business Analytics</h3>
                    <p>Advanced reporting dashboard with insights on business performance, booking trends, payment analytics, and operational efficiency metrics.</p>
                </div>
                
                <div class="capability-item">
                    <h3>2D Mapping & Tracking</h3>
                    <p>Professional-grade tracking and location services enabling real-time monitoring of moves with precision mapping technology.</p>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Development Team</h2>
            <p>Our application is developed by a skilled team of BSIT students from Bataan, combining academic excellence with practical innovation to deliver cutting-edge moving solutions.</p>
            
            <div class="team-grid">
                <div class="team-member">
                    <div class="team-avatar">
                        <img src="public/images/eidrenz_reyes.jpg" alt="Eidrenz Klein Reyes" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                    </div>
                    <h3>Eidrenz Klein Reyes</h3>
                    <div class="age">Age: 22</div>
                    <div class="location">📍 Orani, Bataan</div>
                    <div class="course">BSIT NW4E</div>
                </div>
                
                <div class="team-member">
                    <div class="team-avatar">
                        <img src="public/images/iverson_reyes.jpg" alt="Iverson Reyes" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                    </div>
                    <h3>Iverson Reyes</h3>
                    <div class="age">Age: 21</div>
                    <div class="location">📍 Tortugas, Balanga City, Bataan</div>
                    <div class="course">BSIT NW4E</div>
                </div>
                
                <div class="team-member">
                    <div class="team-avatar">
                        <img src="public/images/john_michael_angeles.jpg" alt="John Michael Angeles" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                    </div>
                    <h3>John Michael Angeles</h3>
                    <div class="age">Age: 21</div>
                    <div class="location">📍 Wawa, Abucay, Bataan</div>
                    <div class="course">BSIT NW4E</div>
                </div>
                
                <div class="team-member">
                    <div class="team-avatar">
                        <img src="public/images/james_jovero.png" alt="James A. Jovero" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                    </div>
                    <h3>James A. Jovero</h3>
                    <div class="age">Age: 27</div>
                    <div class="location">📍 Pilar, Bataan</div>
                    <div class="course">BSIT NW4E</div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Why Choose MoveEasy</h2>
            <p>Our commitment to excellence drives continuous innovation and service improvement. We understand that relocating can be challenging, which is why MoveEasy is designed to be intuitive, reliable, and comprehensive. From initial booking through final delivery, we ensure transparency, efficiency, and peace of mind throughout your moving experience.</p>
            
            <p>With deep local expertise in Bataan and surrounding regions, combined with modern technology solutions, we provide personalized service that understands your unique requirements while delivering professional results you can trust. Our platform represents the future of moving services – where technology meets traditional expertise to create exceptional customer experiences.</p>
        </div>
    </div>
</body>
</html>