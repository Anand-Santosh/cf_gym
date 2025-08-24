<?php
ob_start();
$pageTitle = "Contact Us";
require_once 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <style>
        :root {
            --primary: #FF5A1F; /* Vibrant orange */
            --primary-dark: #E04A14;
            --dark: #121212;
            --darker: #0A0A0A;
            --light: #F8F9FA;
            --text-dark: #E0E0E0;
            --text-light: #FFFFFF;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--dark);
            color: var(--text-light);
            line-height: 1.6;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text-light);
        }

        .contact-section {
            padding: 80px 0;
            background-color: var(--darker);
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-header h2 {
            font-size: 2.8rem;
            text-transform: uppercase;
            position: relative;
            display: inline-block;
        }

        .section-header h2:after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -15px;
            width: 80px;
            height: 4px;
            background: var(--primary);
            transform: translateX(-50%);
        }

        .contact-card {
            background-color: var(--dark);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.1);
            padding: 30px;
            height: 100%;
        }

        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(255, 90, 31, 0.2);
        }

        .contact-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 90, 31, 0.4);
        }

        .form-control {
            background-color: var(--darker);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text-light);
            padding: 12px 15px;
        }

        .form-control:focus {
            background-color: var(--darker);
            color: var(--text-light);
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(255, 90, 31, 0.25);
        }

        .form-label {
            color: var(--text-light);
            margin-bottom: 8px;
            display: block;
            font-weight: 600;
        }

        textarea.form-control {
            min-height: 150px;
        }

        @media (max-width: 768px) {
            .section-header h2 {
                font-size: 2.2rem;
            }
            
            .contact-card {
                margin-bottom: 30px;
            }
        }
    </style>
</head>
<body>
    <section class="contact-section">
        <div class="container">
            <div class="section-header">
                <h2>Contact Us</h2>
                <p>Get in touch with our team for any questions or inquiries</p>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="contact-card">
                        <h3 class="mb-4">Send Us a Message</h3>
                        <form id="contactForm">
                            <div class="mb-4">
                                <label for="name" class="form-label">Your Name</label>
                                <input type="text" class="form-control" id="name" required>
                            </div>
                            <div class="mb-4">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" required>
                            </div>
                            <div class="mb-4">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" required>
                            </div>
                            <div class="mb-4">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" required></textarea>
                            </div>
                            <button type="submit" class="btn">Send Message</button>
                        </form>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="contact-card">
                        <div class="text-center">
                            <div class="contact-icon">
                                <i class="bi bi-envelope"></i>
                            </div>
                            <h3>Email Us</h3>
                            <p>info@crossfitgym.com</p>
                        </div>
                        
                        <div class="text-center mt-5">
                            <div class="contact-icon">
                                <i class="bi bi-clock"></i>
                            </div>
                            <h3>Opening Hours</h3>
                            <p>
                                Mon-Fri: 6:00 AM - 10:00 PM<br>
                                Sat-Sun: 8:00 AM - 8:00 PM
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Form submission handling
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // Here you would typically add your form submission logic
            alert('Thank you for your message! We will get back to you soon.');
            this.reset();
        });
    </script>

    <?php 
    require_once 'includes/footer.php';
    ob_end_flush();
    ?>
</body>
</html>