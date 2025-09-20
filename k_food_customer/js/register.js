document.addEventListener("DOMContentLoaded", function () {
  const registerForm = document.getElementById("registerForm");
  const passwordInput = document.getElementById("password");
  const confirmPasswordInput = document.getElementById("confirmPassword");
  const strengthMeter = document.querySelector(".strength-bars");
  const strengthText = document.querySelector(".strength-text");

  if (registerForm) {
    // Form validation
    registerForm.addEventListener("submit", function (e) {
      let isValid = true;
      clearErrors();

      // First Name validation
      const firstName = document.getElementById("firstName").value.trim();
      if (firstName === "") {
        showError("firstName", "First name is required");
        isValid = false;
      }

      // Last Name validation
      const lastName = document.getElementById("lastName").value.trim();
      if (lastName === "") {
        showError("lastName", "Last name is required");
        isValid = false;
      }

      // Username validation
      const username = document.getElementById("username").value.trim();
      if (username === "") {
        showError("username", "Username is required");
        isValid = false;
      } else if (username.length < 3) {
        showError("username", "Username must be at least 3 characters");
        isValid = false;
      }

      // Email validation
      const email = document.getElementById("email").value.trim();
      if (email === "") {
        showError("email", "Email is required");
        isValid = false;
      } else if (!isValidEmail(email)) {
        showError("email", "Please enter a valid email address");
        isValid = false;
      }

      // Password validation
      const password = passwordInput.value;
      const confirmPassword = confirmPasswordInput.value;

      if (password === "") {
        showError("password", "Password is required");
        isValid = false;
      } else if (!isValidPassword(password)) {
        showError(
          "password",
          "Password must be at least 8 characters with numbers and letters"
        );
        isValid = false;
      }

      if (confirmPassword === "") {
        showError("confirmPassword", "Please confirm your password");
        isValid = false;
      } else if (password !== confirmPassword) {
        showError("confirmPassword", "Passwords do not match");
        isValid = false;
      }

      // Terms validation
      const terms = document.getElementById("terms");
      if (!terms.checked) {
        showError(
          "terms",
          "You must agree to the Terms of Service and Privacy Policy"
        );
        isValid = false;
      }

      if (!isValid) {
        e.preventDefault();
      }
    });
  }

  // Real-time password strength meter
  if (passwordInput) {
    passwordInput.addEventListener("input", function () {
      updatePasswordStrength(this.value);
    });
  }

  function updatePasswordStrength(password) {
    const strength = getPasswordStrength(password);
    const strengthClasses = [
      "strength-weak",
      "strength-fair",
      "strength-good",
      "strength-strong",
    ];
    const strengthTexts = ["Weak", "Fair", "Good", "Strong"];

    // Remove all strength classes
    strengthMeter.classList.remove(...strengthClasses);

    if (password.length > 0) {
      const strengthClass = strengthClasses[strength - 1];
      const strengthMessage = strengthTexts[strength - 1];

      strengthMeter.classList.add(strengthClass);
      strengthText.textContent = strengthMessage;
    } else {
      strengthText.textContent = "";
    }
  }

  // Username availability check
  const usernameInput = document.getElementById("username");
  if (usernameInput) {
    let timeout = null;
    usernameInput.addEventListener("input", function () {
      clearTimeout(timeout);
      const username = this.value.trim();

      if (username.length >= 3) {
        timeout = setTimeout(() => {
          fetch("api/check_username.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded",
            },
            body: "username=" + encodeURIComponent(username),
          })
            .then((response) => response.json())
            .then((data) => {
              if (data.available) {
                showSuccess("username", "Username is available");
              } else {
                showError("username", "Username is already taken");
              }
            })
            .catch((error) => {
              console.error("Error:", error);
            });
        }, 500);
      }
    });
  }
});

// Show success message under an input field
function showSuccess(inputId, message) {
  const input = document.getElementById(inputId);
  const successDiv = document.createElement("div");
  successDiv.className = "success-text";
  successDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
  input.parentNode.appendChild(successDiv);
  input.classList.add("success");
}
