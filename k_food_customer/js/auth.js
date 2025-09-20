document.addEventListener("DOMContentLoaded", function () {
  // Password visibility toggle
  const togglePassword = document.querySelector(".toggle-password");
  if (togglePassword) {
    togglePassword.addEventListener("click", function () {
      const passwordInput = this.previousElementSibling;
      const type =
        passwordInput.getAttribute("type") === "password" ? "text" : "password";
      passwordInput.setAttribute("type", type);

      // Toggle icon
      const icon = this.querySelector("i");
      icon.classList.toggle("fa-eye");
      icon.classList.toggle("fa-eye-slash");
    });
  }

  // Form validation
  const loginForm = document.getElementById("loginForm");
  if (loginForm) {
    loginForm.addEventListener("submit", function (e) {
      const username = document.getElementById("username").value.trim();
      const password = document.getElementById("password").value;
      let isValid = true;

      // Reset previous error states
      clearErrors();

      // Username validation
      if (username === "") {
        showError("username", "Username is required");
        isValid = false;
      }

      // Password validation
      if (password === "") {
        showError("password", "Password is required");
        isValid = false;
      }

      if (!isValid) {
        e.preventDefault();
      }
    });
  }
});

// Show error message under an input field
function showError(inputId, message) {
  const input = document.getElementById(inputId);
  const errorDiv = document.createElement("div");
  errorDiv.className = "error-text";
  errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
  input.parentNode.appendChild(errorDiv);
  input.classList.add("error");
}

// Clear all error messages
function clearErrors() {
  document.querySelectorAll(".error-text").forEach((error) => error.remove());
  document
    .querySelectorAll(".error")
    .forEach((input) => input.classList.remove("error"));
}

// Show toast message
function showToast(message, type = "success") {
  const toast = document.getElementById("toast");
  toast.textContent = message;
  toast.className = `toast ${type} show`;

  setTimeout(() => {
    toast.className = toast.className.replace("show", "");
  }, 3000);
}

// Handle "Remember me" functionality
const rememberCheckbox = document.getElementById("remember");
if (rememberCheckbox) {
  // Check if there's a saved username
  const savedUsername = localStorage.getItem("rememberedUsername");
  if (savedUsername) {
    document.getElementById("username").value = savedUsername;
    rememberCheckbox.checked = true;
  }

  // Save username when "Remember me" is checked
  rememberCheckbox.addEventListener("change", function () {
    const username = document.getElementById("username").value;
    if (this.checked) {
      localStorage.setItem("rememberedUsername", username);
    } else {
      localStorage.removeItem("rememberedUsername");
    }
  });
}
