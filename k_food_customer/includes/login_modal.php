<div id="loginModal" class="modal">
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
</div>

<!-- Sign Up Modal -->
<div id="signupModal" class="modal">
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
</div>
