document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("adminRegistrationForm");
  const messageDiv = document.getElementById("message");

  // Function to display messages
  function showMessage(message, isError = false) {
    messageDiv.textContent = message;
    messageDiv.className = isError ? "error-message" : "success-message";
    messageDiv.style.display = "block";

    // Auto-hide after 5 seconds
    setTimeout(() => {
      messageDiv.style.display = "none";
    }, 5000);
  }

  // Function to validate password strength
  function validatePassword(password) {
    const minLength = 8;
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    const hasSpecialChar = /[^A-Za-z0-9]/.test(password);

    if (password.length < minLength) {
      return "Password must be at least 8 characters long";
    }
    if (!hasUpperCase) {
      return "Password must contain at least one uppercase letter";
    }
    if (!hasLowerCase) {
      return "Password must contain at least one lowercase letter";
    }
    if (!hasNumbers) {
      return "Password must contain at least one number";
    }
    if (!hasSpecialChar) {
      return "Password must contain at least one special character";
    }
    return "";
  }

  // Function to validate email format
  function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  // Handle form submission
  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    // Get form data
    const formData = {
      fullName: form.fullName.value.trim(),
      email: form.email.value.trim(),
      username: form.username.value.trim(),
      password: form.password.value,
      confirmPassword: form.confirmPassword.value,
      role: parseInt(form.role.value),
    };

    // Basic validation
    if (
      !formData.fullName ||
      !formData.email ||
      !formData.username ||
      !formData.password ||
      !formData.confirmPassword
    ) {
      showMessage("All fields are required", true);
      return;
    }

    // Email validation
    if (!validateEmail(formData.email)) {
      showMessage("Please enter a valid email address", true);
      return;
    }

    // Password validation
    const passwordError = validatePassword(formData.password);
    if (passwordError) {
      showMessage(passwordError, true);
      return;
    }

    // Password match validation
    if (formData.password !== formData.confirmPassword) {
      showMessage("Passwords do not match", true);
      return;
    }

    try {
      const response = await fetch("api/register_admin.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(formData),
      });

      const data = await response.json();

      if (response.ok) {
        showMessage(data.message);
        form.reset(); // Clear the form on success
      } else {
        showMessage(data.error, true);
      }
    } catch (error) {
      showMessage("An error occurred while processing your request", true);
      console.error("Error:", error);
    }
  });

  // Real-time password strength validation
  const passwordInput = form.password;
  const strengthIndicator = document.getElementById("passwordStrength");

  passwordInput.addEventListener("input", function () {
    const password = this.value;
    const error = validatePassword(password);

    if (error) {
      strengthIndicator.textContent = error;
      strengthIndicator.className = "password-weak";
    } else {
      strengthIndicator.textContent = "Strong password";
      strengthIndicator.className = "password-strong";
    }
  });

  // Real-time password match validation
  const confirmPasswordInput = form.confirmPassword;
  const matchIndicator = document.getElementById("passwordMatch");

  function checkPasswordMatch() {
    const password = passwordInput.value;
    const confirmPassword = confirmPasswordInput.value;

    if (confirmPassword) {
      if (password === confirmPassword) {
        matchIndicator.textContent = "Passwords match";
        matchIndicator.className = "password-match";
      } else {
        matchIndicator.textContent = "Passwords do not match";
        matchIndicator.className = "password-mismatch";
      }
    } else {
      matchIndicator.textContent = "";
    }
  }

  passwordInput.addEventListener("input", checkPasswordMatch);
  confirmPasswordInput.addEventListener("input", checkPasswordMatch);
});
