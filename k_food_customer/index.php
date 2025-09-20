<?php
session_start();
include 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta
      name="description"
      content="K-Food Delight - Experience authentic Korean fusion cuisine"
    />
    <meta
      name="keywords"
      content="Korean food, fusion cuisine, restaurant, food delivery"
    />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet" href="css/logout.css" />
    <link
      href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@400;600&display=swap"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    />
    <link rel="stylesheet" href="css/style.css"/>
    <link rel="stylesheet" href="css/sigin.css"/>
    <link rel="stylesheet" href="css/cart.css"/>
    <link rel="shortcut icon" href="../logo-tab-icon.ico" type="image/x-icon" />
    <title>K-Food Delight</title>
    <script src="js/logout.js" defer></script>
  </head>
  <body>
    
    <div class="navbar">
      <div class="navdiv">
        <div class="logo">
          <a href="#"> K-Food Delights</a>
        </div>
        <nav class="nav-links">
          <ul>
            <li><a href="#aboutus-section">About Us</a></li>
            <li><a href="#product-section">Product</a></li>
            <!-- <li><a href="#faqs-section">FAQs</a></li> -->
            <li><a href="#contact-section">Contact</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
              <li class="profile-link"><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
              <li class="cart-icon" id="cart-icon">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count" id="cart-count">0</span>
              </li>
              <li class="logout-link"><a href="#" onclick="showLogoutModal()"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            <?php else: ?>
              <li class="profile-link"><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
            <?php endif; ?>
          </ul>
        </nav>
      </div>
    </div>
    

    <div class="hero-container">
      <div class="wrapper">
        <img
          src="../resources/images/sushi.png"
          alt="sushi"
          class="img-sushi"
        />
        <img src="../resources/images/logo.png" alt="logo" class="img-logo" />
        <img
          src="../resources/images/lasagna.png"
          alt="lasagna"
          class="img-lasagna"
        />
        <div class="heading-k"><h1>K-Food</h1></div>
        <div class="header-back-text">
          <h1 class="header-text-scroll">
            K-FOOD DELIGHT K-FOOD DELIGHT K-FOOD DELIGHT K-FOOD DELIGHT K-FOOD DELIGHT K-FOOD DELIGHT K-FOOD DELIGHT 
          </h1>
          <h1 class="header-text-scroll">
            K-FOOD DELIGHT K-FOOD DELIGHT K-FOOD DELIGHT K-FOOD DELIGHT K-FOOD DELIGHT K-FOOD DELIGHT K-FOOD DELIGHT
          </h1>

        </div>
      </div>
    </div>

    <div class="main-content">
      <section class="aboutus-section" id="aboutus-section">
        <div class="panel">
          <div class="header-p">
            <h1><i class="fas fa-store"></i> About Us</h1>
          </div>
        </div>
        <div class="section-content">
          <p>
            K-Food Delight, in Philam, Quezon City, offers delicious homemade
            Filipino-fusion cuisine. Owners Michelle J. Javillo and Krystal E.
            Selisana create unique dishes, from lasagna and sushi to their
            signature spicy chicken pastil. We cater to students, families,
            professionals, and anyone craving exciting flavors. We're passionate
            about providing high-quality food and memorable dining experiences.
          </p>
        </div>
      </section>

      <!-- Replace the existing product-section with this code -->
      <section class="product-section" id="product-section">
        <div class="panel">
          <div class="header-p">
            <h1><i class="fas fa-bowl-food"></i> Our Menu</h1>
          </div>
        </div>
        
        <div class="product-content">
          <div class="image-slider">
            <div class="slider-container">
              <div class="slide fade">
                <img src="../resources/images/pastil.jpg" alt="Pastil" />
                <div class="product-info">
                  <h3>Pastil</h3>
                  <p>Comes with different tastes!</p>
                  <span class="price">₱170.00</span>
                </div>
              </div>
              <div class="slide fade">
                <img src="../resources/images/sushi.jpg" alt="Sushi" />
                <div class="product-info">
                  <h3>Sushi</h3>
                  <p>Fresh and flavorful sushi!</p>
                  <span class="price">₱250.00</span>
                </div>
              </div>
              <div class="slide fade">
                <img src="../resources/images/lasagna.jpg" alt="Lasagna" />
                <div class="product-info">
                  <h3>Lasagna</h3>
                  <p>Filipino-style lasagna!</p>
                  <span class="price">₱300.00</span>
                </div>
              </div>
            </div>

            <button class="slider-btn prev-button" aria-label="Previous slide">
              <i class="fas fa-chevron-left" aria-hidden="true"></i>
            </button>
            <button class="slider-btn next-button" aria-label="Next slide">
              <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </button>

            <div class="dots-container"></div>
          </div>

          <div class="product-actions">
            <div class="action-buttons">
              <div class="product-button-group" data-product-id="1">
                <h4>Pastil</h4>
                <button class="order-btn" data-product-id="1">Order Now</button>
                <button class="add-to-cart-btn" data-product-id="1">Add to Cart</button>
              </div>
              <div class="product-button-group" data-product-id="2">
                <h4>Sushi</h4>
                <button class="order-btn" data-product-id="2">Order Now</button>
                <button class="add-to-cart-btn" data-product-id="2">Add to Cart</button>
              </div>
              <div class="product-button-group" data-product-id="3">
                <h4>Lasagna</h4>
                <button class="order-btn" data-product-id="3">Order Now</button>
                <button class="add-to-cart-btn" data-product-id="3">Add to Cart</button>
              </div>
            </div>
          </div>
        </div>
      </section>

        <section class="aboutus-section" id="aboutus-section">
        <div class="panel">
          <div class="header-p">
            <h1><i class="fas fa-store"></i> About Us</h1>
          </div>
        </div>
        <div class="section-content">
          <p>
            K-Food Delight, in Philam, Quezon City, offers delicious homemade
            Filipino-fusion cuisine. Owners Michelle J. Javillo and Krystal E.
            Selisana create unique dishes, from lasagna and sushi to their
            signature spicy chicken pastil. We cater to students, families,
            professionals, and anyone craving exciting flavors. We're passionate
            about providing high-quality food and memorable dining experiences,
            and we strive to be Quezon City's leading provider of innovative
            Filipino-fusion dishes.
          </p>
        </div>
      </section>

      <!-- <section class="faqs-section" id="faqs-section">
        <div class="panel">
          <div class="header-p">
            <h1><i class="fas fa-question-circle"></i> FAQs</h1>
          </div>
        </div>
        <div class="section-content">
          <div class="faq-item">
            <h3>What are your hours?</h3>
            <p>We are open from 11 AM to 9 PM, Monday through Saturday.</p>
          </div>
          <div class="faq-item">
            <h3>Do you offer catering?</h3>
            <p>Yes, we do! Please contact us for more information.</p>
          </div>
        </div>
      </section> -->

      <section class="contact-section" id="contact-section">
        <div class="panel">
          <div class="header-p">
            <h1><i class="fas fa-envelope"></i> Contact</h1>
          </div>
        </div>
        <div class="section-content">
          <div class="contact-info">
            <p>
              <i class="fas fa-map-marker-alt"></i> 123 Main Street, Anytown, CA
              91234
            </p>
            <p><i class="fas fa-phone"></i> (555) 555-5555</p>
            <p><i class="fas fa-envelope"></i> info@kfooddelights.com</p>
          </div>
        </div>
      </section>
    </div>

    <button id="scroll-to-top" title="Go to top">
      <i class="fas fa-arrow-up"></i>
    </button>

    <footer class="footer-section">
      <div class="footer-content">
        <div class="footer-bottom">
          <p>&copy; 2025 K-Food Delights. All rights reserved.</p>
        </div>
      </div>
    </footer>
<!-- Login Modal -->
<!-- <div id="loginModal" class="modal">
  <div class="modal-content login-box">
    <span class="close" onclick="closeModal()">&times;</span>
    <h2>Sign In</h2>

    <form action="login.php" method="POST">
      <label>Username</label>
      <input type="text" name="username" placeholder="Username" required />

      <label>Password</label>
      <input type="password" name="password" placeholder="Password" required />

      <button type="submit" class="login-btn">Sign In</button>

      <div class="form-options">
        <a href="#" class="no-account" onclick="switchToSignup()">Don't have an account?</a>
        <a href="#" class="forgot">Forgot Password</a>
      </div>
    </form>
  </div>
</div> -->

<!-- Sign Up Modal -->
<!-- <div id="signupModal" class="modal">
  <div class="modal-content-signup">
    <span class="close" onclick="closeSignupModal()">&times;</span>
    <h2>Sign Up</h2>

<form id="signupForm" method="POST">
  <input type="text" name="firstname" placeholder="First Name" required />
  <input type="text" name="lastname" placeholder="Last Name" required />
  <input type="email" name="email" placeholder="Email" required />
  <input type="text" name="username" placeholder="Username" required />
  <input type="password" name="password" placeholder="Password" required />

  <button type="submit" class="login-btn" name="submit">Register</button>

  <div class="form-options">
    <a href="#" class="no-account" onclick="switchToLogin()">Already have an account?</a>
  </div>
</form>

  </div>
</div> -->
<script>
// Attach form event listener only if the form exists
document.addEventListener('DOMContentLoaded', function() {
    const signupForm = document.getElementById("signupForm");
    if (signupForm) {
        signupForm.addEventListener("submit", function (e) {
            e.preventDefault(); // prevent form from reloading the page

            const formData = new FormData(this);

            fetch("register.php", {
                method: "POST",
                body: formData,
                headers: {
                    'Accept': 'application/json'  // Request JSON response
                }
            })
            .then(response => {
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.includes("application/json")) {
                    return response.json();
                } else {
                    // If not JSON, get text and try to parse it
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error('Server returned invalid JSON: ' + text);
                        }
                    });
                }
            })
            .then(data => {
                if (data.success) {
                    showNotification('success', 'Registered successfully!');
                    closeSignupModal();   // hide signup modal
                    openLoginModal();     // show login modal
                    this.reset();         // optional: clear the form fields
                } else {
                    showNotification('error', data.message || 'Registration failed');
                    console.error(data); // log full error
                }
            })
            .catch(error => {
                showNotification('error', 'An error occurred. Please try again.');
                console.error("Error:", error);
            });
        });
    }
});
</script>

    <script src="js/signin.js"></script>
    <script src="js/script.js"></script>
    
  <!-- Order Modal -->
  <!-- <div id="orderModal" class="modal" style="display: none;">
    <div class="modal-content">
      <span class="close">&times;</span>
      <h2 style="margin-bottom: 20px; text-align: center; color: #333;">Place Your Order</h2>
      <form id="orderForm">
        Customer Information - Left Column 
        <div class="form-group">
          <label for="customerName">Full Name:</label>
          <input type="text" id="customerName" name="customerName" required placeholder="Enter your full name">
        </div>
        <div class="form-group">
          <label for="email">Email:</label>
          <input type="email" id="email" name="email" required placeholder="Enter your email">
        </div>
        <div class="form-group">
          <label for="phone">Phone:</label>
          <input type="tel" id="phone" name="phone" required placeholder="Enter your phone number">
        </div>
        <div class="form-group">
          <label for="deliveryDate">Preferred Delivery Date:</label>
          <input type="date" id="deliveryDate" name="deliveryDate" required>
        </div>
       
        Order Information - Right Column 
        <div class="form-group">
          <label for="product">Selected Product:</label>
          <input type="text" id="product" name="product" readonly>
        </div>
        <div class="form-group">
          <label for="quantity">Quantity:</label>
          <input type="number" id="quantity" name="quantity" min="1" value="1" required>
        </div>
        <div class="form-group">
          <label for="totalPrice">Total Price:</label>
          <input type="text" id="totalPrice" name="totalPrice" readonly>
        </div>
        
         Full Width Fields 
        <div class="form-group full-width">
          <label for="address">Delivery Address:</label>
          <textarea id="address" name="address" required placeholder="Enter your complete delivery address"></textarea>
        </div>
        
        <button type="submit" class="submit-btn" id="place-order-submit">Place Order</button>
      </form>
    </div>
  </div> -->

  <style>
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
      background-color: #fff;
      margin: 2% auto;
      padding: 25px;
      border-radius: 12px;
      width: 90%;
      max-width: 1000px;
      position: relative;
      max-height: 90vh;
      overflow-y: auto;
    }

    .close {
      position: absolute;
      right: 20px;
      top: 15px;
      font-size: 28px;
      cursor: pointer;
    }

    #orderForm {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
      margin-top: 20px;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group.full-width {
      grid-column: span 2;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: bold;
      color: #333;
    }

    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 14px;
      transition: border-color 0.3s ease;
    }

    .form-group input:focus,
    .form-group textarea:focus {
      border-color: #ff6666;
      outline: none;
      box-shadow: 0 0 0 2px rgba(255, 102, 102, 0.1);
    }

    .form-group textarea {
      resize: vertical;
      min-height: 100px;
    }

    .submit-btn {
      background: linear-gradient(135deg, #ff6666, #ffaf00);
      color: white;
      padding: 12px 25px;
      border: none;
      border-radius: 25px;
      cursor: pointer;
      width: 100%;
      font-size: 16px;
      font-weight: bold;
      transition: all 0.3s ease;
      grid-column: span 2;
      margin-top: 10px;
    }

    .submit-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      #orderForm {
        grid-template-columns: 1fr;
      }
      
      .form-group.full-width {
        grid-column: span 1;
      }
      
      .submit-btn {
        grid-column: span 1;
      }
      
      .modal-content {
        width: 95%;
        margin: 5% auto;
        padding: 15px;
      }
    }
  </style>

    <!-- Cart Modal -->
    <div id="cart-modal" class="modal" role="dialog" aria-labelledby="cart-modal-title">
        <div class="cart-modal-content">
            <span class="close" aria-label="Close cart">&times;</span>
            <h2 id="cart-modal-title">Your Cart</h2>
            <div id="cart-items"></div>
            <div class="cart-footer">
                <div class="cart-total">Total: <span id="cart-total">₱0.00</span></div>
                <button class="checkout-btn" onclick="cart.checkout()">Proceed to Checkout</button>
            </div>
        </div>
    </div>

    <?php include 'includes/logout-modal.php'; ?>

    <script src="js/slider.js"></script>
    <script src="js/cart.js"></script>
    <script src="js/order-system.js"></script>
    <script>
        // Initialize notification system if not exists
        if (typeof window.showNotification !== 'function') {
            window.showNotification = function(type, message) {
                const notificationDiv = document.createElement('div');
                notificationDiv.className = `notification ${type}`;
                notificationDiv.innerHTML = `
                    <div class="notification-content">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                        <span>${message}</span>
                    </div>
                `;
                document.body.appendChild(notificationDiv);
                
                setTimeout(() => {
                    notificationDiv.classList.add('fade-out');
                    setTimeout(() => notificationDiv.remove(), 300);
                }, 3000);
            };
        }
    </script>
    <style>
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            padding: 15px 25px;
            border-radius: 8px;
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease;
            max-width: 400px;
        }
        
        .notification.success {
            background-color: #4CAF50;
            color: white;
        }
        
        .notification.error {
            background-color: #ff6666;
            color: white;
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .notification.fade-out {
            animation: fadeOut 0.3s ease forwards;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes fadeOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    </style>
  </body>
</html>
