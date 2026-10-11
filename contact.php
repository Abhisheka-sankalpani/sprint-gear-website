<?php
// contact.php - Contact Us Page
$pageTitle = "Contact Us";
require_once __DIR__ . '/includes/functions.php';

$errors = [];
$successMessage = '';
$formValues = [
    'name' => '',
    'email' => '',
    'subject' => '',
    'message' => ''
];

// Handle Contact Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_contact') {
    $formValues['name'] = trim($_POST['name'] ?? '');
    $formValues['email'] = trim($_POST['email'] ?? '');
    $formValues['subject'] = trim($_POST['subject'] ?? '');
    $formValues['message'] = trim($_POST['message'] ?? '');

    // Server-side Validation
    if (empty($formValues['name'])) {
        $errors['name'] = 'Please enter your name.';
    }

    if (empty($formValues['email'])) {
        $errors['email'] = 'Please enter your email address.';
    } elseif (!filter_var($formValues['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if (empty($formValues['message'])) {
        $errors['message'] = 'Please enter your message.';
    }

    // Insert into DB if no validation errors
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message, status) VALUES (:name, :email, :subject, :message, 'New')");
            $stmt->execute([
                ':name' => $formValues['name'],
                ':email' => $formValues['email'],
                ':subject' => !empty($formValues['subject']) ? $formValues['subject'] : 'General Inquiry',
                ':message' => $formValues['message']
            ]);

            $successMessage = 'Your message has been sent successfully! We will get back to you as soon as possible. Thank you for contacting us!';
            // Reset form values on successful submission
            $formValues = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
        } catch (PDOException $e) {
            $errors['general'] = 'An error occurred while sending your message. Please try again later.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- BREADCRUMB HEADER -->
<div class="contact-page-header">
    <div class="container">
        <h1 class="page-title">CONTACT SPRINT GEAR</h1>
        <ul class="breadcrumb">
            <li><a href="index.php">Home</a></li>
            <li class="active">Contact Us</li>
        </ul>
    </div>
</div>

<!-- MAIN CONTENT SECTION -->
<div class="contact-page-wrapper">
    <div class="container">
        <div class="contact-grid">
            
            <!-- LEFT COLUMN: GET IN TOUCH CARD -->
            <div class="contact-card info-card">
                <div class="card-header-box">
                    <h2 class="card-heading"><i class="fas fa-headset text-red-500"></i> Get In Touch</h2>
                    <p class="card-subtext">Have questions about sizing, product availability, or order delivery? We're here to assist you.</p>
                </div>

                <div class="contact-details-list">
                    <div class="contact-detail-item">
                        <div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="detail-body">
                            <strong>Store Address</strong>
                            <p>No. 45 Galle Road, Kollupitiya, Colombo 03, Sri Lanka</p>
                        </div>
                    </div>

                    <div class="contact-detail-item">
                        <div class="contact-icon"><i class="fas fa-phone-alt"></i></div>
                        <div class="detail-body">
                            <strong>Customer Support Hotline</strong>
                            <p><a href="tel:+94112345678">+94 11 234 5678</a> &nbsp;/&nbsp; <a href="tel:+94771234567">+94 77 123 4567</a></p>
                        </div>
                    </div>

                    <div class="contact-detail-item">
                        <div class="contact-icon"><i class="fas fa-envelope"></i></div>
                        <div class="detail-body">
                            <strong>Email Support</strong>
                            <p><a href="mailto:info@sprintgear.lk">info@sprintgear.lk</a> &nbsp;/&nbsp; <a href="mailto:support@sprintgear.lk">support@sprintgear.lk</a></p>
                        </div>
                    </div>

                    <div class="contact-detail-item">
                        <div class="contact-icon"><i class="fas fa-clock"></i></div>
                        <div class="detail-body">
                            <strong>Operating Hours</strong>
                            <p>Monday - Saturday: 9:00 AM - 7:00 PM<br>Sunday: 10:00 AM - 4:00 PM</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: SEND US A MESSAGE FORM CARD -->
            <div class="contact-card form-card">
                <div class="card-header-box">
                    <h2 class="card-heading"><i class="fas fa-paper-plane text-red-500"></i> Send Us A Message</h2>
                </div>

                <?php if (!empty($errors['general'])): ?>
                    <div class="contact-alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errors['general']); ?>
                    </div>
                <?php endif; ?>

                <form action="contact.php" method="POST" class="vertical-contact-form" novalidate>
                    <input type="hidden" name="action" value="send_contact">

                    <!-- 1. YOUR NAME -->
                    <div class="form-group-contact">
                        <label for="contact_name" class="form-label-contact">Your Name <span class="required-star">*</span></label>
                        <div class="input-with-icon">
                            <i class="fas fa-user field-icon"></i>
                            <input type="text" 
                                   id="contact_name" 
                                   name="name" 
                                   class="contact-form-control <?php echo isset($errors['name']) ? 'has-error' : ''; ?>" 
                                   placeholder="John Doe" 
                                   value="<?php echo htmlspecialchars($formValues['name']); ?>" 
                                   required>
                        </div>
                        <?php if (isset($errors['name'])): ?>
                            <span class="field-error-text"><?php echo htmlspecialchars($errors['name']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- 2. YOUR EMAIL ADDRESS -->
                    <div class="form-group-contact">
                        <label for="contact_email" class="form-label-contact">Your Email Address <span class="required-star">*</span></label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope field-icon"></i>
                            <input type="email" 
                                   id="contact_email" 
                                   name="email" 
                                   class="contact-form-control <?php echo isset($errors['email']) ? 'has-error' : ''; ?>" 
                                   placeholder="john@example.com" 
                                   value="<?php echo htmlspecialchars($formValues['email']); ?>" 
                                   required>
                        </div>
                        <?php if (isset($errors['email'])): ?>
                            <span class="field-error-text"><?php echo htmlspecialchars($errors['email']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- 3. SUBJECT -->
                    <div class="form-group-contact">
                        <label for="contact_subject" class="form-label-contact">Subject</label>
                        <div class="input-with-icon select-wrapper">
                            <i class="fas fa-tag field-icon"></i>
                            <select id="contact_subject" name="subject" class="contact-form-control contact-select">
                                <option value="" <?php echo empty($formValues['subject']) ? 'selected' : ''; ?>>Select a subject</option>
                                <option value="Size Inquiry" <?php echo $formValues['subject'] === 'Size Inquiry' ? 'selected' : ''; ?>>Size Inquiry</option>
                                <option value="Product Availability" <?php echo $formValues['subject'] === 'Product Availability' ? 'selected' : ''; ?>>Product Availability</option>
                                <option value="Order Status" <?php echo $formValues['subject'] === 'Order Status' ? 'selected' : ''; ?>>Order Status</option>
                                <option value="Shipping & Delivery" <?php echo $formValues['subject'] === 'Shipping & Delivery' ? 'selected' : ''; ?>>Shipping & Delivery</option>
                                <option value="Returns & Exchanges" <?php echo $formValues['subject'] === 'Returns & Exchanges' ? 'selected' : ''; ?>>Returns & Exchanges</option>
                                <option value="Payment" <?php echo $formValues['subject'] === 'Payment' ? 'selected' : ''; ?>>Payment</option>
                                <option value="Other" <?php echo $formValues['subject'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- 4. MESSAGE -->
                    <div class="form-group-contact">
                        <label for="contact_message" class="form-label-contact">Message <span class="required-star">*</span></label>
                        <div class="input-with-icon textarea-wrapper">
                            <i class="fas fa-comment-alt field-icon"></i>
                            <textarea id="contact_message" 
                                      name="message" 
                                      rows="5" 
                                      class="contact-form-control contact-textarea <?php echo isset($errors['message']) ? 'has-error' : ''; ?>" 
                                      placeholder="How can we help you?" 
                                      required><?php echo htmlspecialchars($formValues['message']); ?></textarea>
                        </div>
                        <?php if (isset($errors['message'])): ?>
                            <span class="field-error-text"><?php echo htmlspecialchars($errors['message']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- 5. SEND MESSAGE BUTTON -->
                    <button type="submit" class="btn-send-message">
                        SEND MESSAGE <i class="fas fa-paper-plane"></i>
                    </button>
                </form>

                <!-- 9. SUCCESS MESSAGE DISPLAY -->
                <?php if (!empty($successMessage)): ?>
                    <div class="contact-alert alert-success">
                        <div class="alert-icon"><i class="fas fa-check-circle"></i></div>
                        <div>
                            <strong>Your message has been sent successfully!</strong>
                            <p>We will get back to you as soon as possible. Thank you for contacting us!</p>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<style>
/* CONTACT US PAGE CUSTOM STYLES */
.contact-page-header {
    padding: 2.5rem 0;
    margin-bottom: 0;
    background: #FFFFFF;
    border-bottom: 1px solid var(--border-color, #E2E8F0);
}

.contact-page-wrapper {
    background-color: #F6F8FB;
    padding-top: 3rem;
    padding-bottom: 5rem;
}

.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2.5rem;
    align-items: stretch;
}

.contact-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 2.5rem;
    box-shadow: 0 4px 15px rgba(15, 23, 42, 0.04);
    display: flex;
    flex-direction: column;
}

.card-header-box {
    margin-bottom: 20px;
}

.card-heading {
    font-family: var(--font-heading, 'Outfit', sans-serif);
    font-size: 1.5rem;
    font-weight: 800;
    color: #0F172A;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin: 0 0 0.5rem 0;
}

.card-subtext {
    color: #64748B;
    font-size: 0.95rem;
    line-height: 1.5;
    margin: 0;
}

/* CONTACT DETAILS LIST */
.contact-details-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    margin-top: 0.5rem;
}

.contact-detail-item {
    display: flex;
    align-items: flex-start;
    gap: 1.2rem;
}

.contact-icon {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    background: #FEF2F2;
    color: #EF4444;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}

.detail-body strong {
    display: block;
    font-size: 0.95rem;
    color: #0F172A;
    margin-bottom: 0.25rem;
}

.detail-body p {
    margin: 0;
    font-size: 0.9rem;
    color: #475569;
    line-height: 1.5;
}

.detail-body a {
    color: #475569;
    text-decoration: none;
    transition: color 0.2s ease;
}

.detail-body a:hover {
    color: #EF4444;
}

/* FORM ALIGNMENT & CONTROLS */
.vertical-contact-form {
    display: flex;
    flex-direction: column;
    width: 100%;
}

.form-group-contact {
    margin-bottom: 18px;
    width: 100%;
}

.form-label-contact {
    display: block;
    font-weight: 600;
    font-size: 0.9rem;
    color: #1E293B;
    margin-bottom: 7px;
}

.required-star {
    color: #EF4444;
    font-weight: bold;
}

.input-with-icon {
    position: relative;
    width: 100%;
}

.input-with-icon .field-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94A3B8;
    font-size: 0.95rem;
    pointer-events: none;
    transition: color 0.2s ease;
}

.input-with-icon.textarea-wrapper .field-icon {
    top: 16px;
    transform: none;
}

.contact-form-control {
    width: 100%;
    box-sizing: border-box;
    padding: 12px 14px 12px 42px;
    border: 1px solid #D9E0EA;
    border-radius: 8px;
    font-size: 0.95rem;
    font-family: inherit;
    color: #1E293B;
    background-color: #FFFFFF;
    transition: all 0.2s ease-in-out;
    outline: none;
}

.contact-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%3C94A3B8' d='M6 8.825L1.175 4 2.238 2.938 6 6.7 9.763 2.938 10.825 4z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    cursor: pointer;
}

.contact-textarea {
    min-height: 130px;
    resize: vertical;
    line-height: 1.5;
}

.contact-form-control:focus {
    border-color: #EF4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
}

.input-with-icon .contact-form-control:focus ~ .field-icon,
.input-with-icon .contact-form-control:focus + .field-icon {
    color: #EF4444;
}

.contact-form-control.has-error {
    border-color: #EF4444;
    background-color: #FEF2F2;
}

.field-error-text {
    display: block;
    color: #EF4444;
    font-size: 0.8rem;
    margin-top: 4px;
    font-weight: 500;
}

/* BUTTON */
.btn-send-message {
    width: 100%;
    background: #EF4444;
    color: #FFFFFF;
    font-family: var(--font-heading, 'Outfit', sans-serif);
    font-weight: 700;
    font-size: 1rem;
    letter-spacing: 0.5px;
    padding: 14px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    transition: all 0.2s ease-in-out;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
    margin-top: 4px;
}

.btn-send-message:hover {
    background: #DC2626;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(239, 68, 68, 0.35);
}

/* SUCCESS & ERROR ALERTS */
.contact-alert {
    padding: 1rem 1.25rem;
    border-radius: 8px;
    margin-top: 1.25rem;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    font-size: 0.9rem;
    line-height: 1.5;
}

.alert-success {
    background-color: #ECFDF5;
    border: 1px solid #A7F3D0;
    color: #065F46;
}

.alert-success .alert-icon {
    font-size: 1.25rem;
    color: #10B981;
    margin-top: 2px;
}

.alert-success strong {
    display: block;
    font-size: 0.95rem;
    color: #065F46;
}

.alert-success p {
    margin: 0.25rem 0 0 0;
    color: #047857;
}

.alert-error {
    background-color: #FEF2F2;
    border: 1px solid #FCA5A5;
    color: #991B1B;
}

/* RESPONSIVE DESIGN */
@media (max-width: 900px) {
    .contact-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .contact-card {
        padding: 1.75rem;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
