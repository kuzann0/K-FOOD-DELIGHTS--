// Profile management functionality
document.addEventListener("DOMContentLoaded", function () {
  const profileForm = document.getElementById("profileForm");
  const passwordForm = document.getElementById("passwordForm");
  const profilePictureInput = document.getElementById("profilePicture");

  // Get CSRF token
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

  // Handle profile picture change
  profilePictureInput.addEventListener("change", async function (e) {
    if (!e.target.files || !e.target.files[0]) return;

    const file = e.target.files[0];

    // Validate file type
    const allowedTypes = ["image/jpeg", "image/png", "image/jpg"];
    if (!allowedTypes.includes(file.type)) {
      showToast("Please select a valid image file (JPEG or PNG)", "error");
      return;
    }

    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
      showToast("Image size should be less than 5MB", "error");
      return;
    }

    // Additional validation for file name
    if (/[^a-zA-Z0-9._-]/.test(file.name)) {
      showToast("File name contains invalid characters", "error");
      return;
    }

    const profilePicture = document.querySelector(".profile-picture");
    const imgPreview = document.getElementById("profilePreview");
    const formData = new FormData();
    formData.append("profile_picture", file);

    try {
      // Show loading state
      profilePicture.classList.add("loading");

      // Create preview and optimize image
      const reader = new FileReader();
      reader.onload = async function (e) {
        // First show immediate preview
        imgPreview.src = e.target.result;

        // Then optimize the image
        const optimizedImage = await optimizeImage(file, {
          maxWidth: 800,
          maxHeight: 800,
          quality: 0.8,
        });

        if (optimizedImage) {
          formData.set("profile_picture", optimizedImage);
        }
      };
      reader.readAsDataURL(file);

      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 second timeout

      formData.append("csrf_token", csrfToken);

      const response = await fetch("api/update_profile_picture.php", {
        method: "POST",
        body: formData,
        signal: controller.signal,
        credentials: "same-origin",
      });

      const data = await response.json();

      if (data.success) {
        showToast("Profile picture updated successfully!", "success");
        // Update the profile picture with the new server path
        imgPreview.src =
          "uploads/profile/" + data.filename + "?t=" + new Date().getTime();
      } else {
        throw new Error(data.message || "Error updating profile picture");
      }
    } catch (error) {
      console.error("Error:", error);
      showToast(error.message || "Error updating profile picture", "error");
      // Revert to previous image if there was an error
      imgPreview.src = imgPreview.getAttribute("data-original-src");
    } finally {
      // Remove loading state
      profilePicture.classList.remove("loading");
    }
  });

  // Store original image src for fallback
  const profilePreview = document.getElementById("profilePreview");
  if (profilePreview) {
    profilePreview.setAttribute("data-original-src", profilePreview.src);
  }

  // Handle profile form submission
  profileForm.addEventListener("submit", function (e) {
    e.preventDefault();

    // Validate input
    const email = document.getElementById("email").value;
    const phone = document.getElementById("phone").value;

    if (!isValidEmail(email)) {
      showToast("Please enter a valid email address", "error");
      return;
    }

    if (phone && !isValidPhone(phone)) {
      showToast("Please enter a valid phone number", "error");
      return;
    }

    // Collect form data
    const formData = new FormData(profileForm);

    // Add CSRF token
    formData.append("csrf_token", csrfToken);

    // Create abort controller for timeout
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 30000);

    // Send update request
    fetch("api/update_profile.php", {
      method: "POST",
      body: formData,
      signal: controller.signal,
      credentials: "same-origin",
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          showToast("Profile updated successfully!", "success");
        } else {
          showToast(data.message || "Error updating profile", "error");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        showToast("Error updating profile", "error");
      });
  });

  // Handle password form submission
  passwordForm.addEventListener("submit", function (e) {
    e.preventDefault();

    const newPassword = document.getElementById("newPassword").value;
    const confirmPassword = document.getElementById("confirmPassword").value;

    if (!isValidPassword(newPassword)) {
      showToast(
        "Password must be at least 8 characters with numbers and letters",
        "error"
      );
      return;
    }

    if (newPassword !== confirmPassword) {
      showToast("Passwords do not match", "error");
      return;
    }

    // Collect form data
    const formData = new FormData(passwordForm);

    // Send update request
    fetch("api/update_password.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          showToast("Password updated successfully!", "success");
          passwordForm.reset();
        } else {
          showToast(data.message || "Error updating password", "error");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        showToast("Error updating password", "error");
      });
  });

  // Handle password strength indicator
  const newPasswordInput = document.getElementById("newPassword");
  const strengthIndicator = document.querySelector(".password-strength");

  newPasswordInput.addEventListener("input", function () {
    const strength = getPasswordStrength(this.value);
    updateStrengthIndicator(strength);
  });
});

// Utility functions
function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPhone(phone) {
  return /^[0-9+\-\s()]*$/.test(phone);
}

function isValidPassword(password) {
  return (
    password.length >= 8 &&
    /[A-Z]/.test(password) &&
    /[a-z]/.test(password) &&
    /[0-9]/.test(password)
  );
}

function getPasswordStrength(password) {
  let strength = 0;

  if (password.length >= 8) strength++;
  if (/[A-Z]/.test(password)) strength++;
  if (/[a-z]/.test(password)) strength++;
  if (/[0-9]/.test(password)) strength++;
  if (/[^A-Za-z0-9]/.test(password)) strength++;

  return strength;
}

// Image optimization function
async function optimizeImage(file, options = {}) {
  const {
    maxWidth = 800,
    maxHeight = 800,
    quality = 0.8,
    format = "image/jpeg",
  } = options;

  return new Promise((resolve) => {
    const img = new Image();
    img.onload = () => {
      // Calculate new dimensions
      let width = img.width;
      let height = img.height;

      if (width > maxWidth || height > maxHeight) {
        const ratio = Math.min(maxWidth / width, maxHeight / height);
        width = Math.round(width * ratio);
        height = Math.round(height * ratio);
      }

      // Create canvas for resizing
      const canvas = document.createElement("canvas");
      canvas.width = width;
      canvas.height = height;

      // Draw and optimize image
      const ctx = canvas.getContext("2d");
      ctx.imageSmoothingQuality = "high";
      ctx.drawImage(img, 0, 0, width, height);

      // Convert to blob
      canvas.toBlob(
        (blob) => {
          if (blob) {
            // Create new file with original name but optimized content
            const optimizedFile = new File([blob], file.name, {
              type: format,
              lastModified: new Date().getTime(),
            });
            resolve(optimizedFile);
          } else {
            resolve(file); // Fallback to original if optimization fails
          }
        },
        format,
        quality
      );
    };
    img.onerror = () => resolve(file); // Fallback to original if loading fails
    img.src = URL.createObjectURL(file);
  });
}

function updateStrengthIndicator(strength) {
  const strengthIndicator = document.querySelector(".password-strength");
  const colors = ["#ff4444", "#ffbb33", "#00C851", "#33b5e5", "#2BBBAD"];
  const widths = ["20%", "40%", "60%", "80%", "100%"];

  strengthIndicator.style.width = widths[strength - 1] || "0%";
  strengthIndicator.style.backgroundColor = colors[strength - 1] || "#ddd";
}

function showToast(message, type = "success") {
  const toast = document.getElementById("toast");
  toast.textContent = message;
  toast.className = "toast " + type + " show";

  setTimeout(() => {
    toast.className = toast.className.replace("show", "");
  }, 3000);
}

function confirmDeleteAccount() {
  if (
    confirm(
      "Are you sure you want to delete your account? This action cannot be undone."
    )
  ) {
    fetch("api/delete_account.php", {
      method: "POST",
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          window.location.href = "logout.php";
        } else {
          showToast(data.message || "Error deleting account", "error");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        showToast("Error deleting account", "error");
      });
  }
}

// Support and Preferences Functions
function showTicketForm() {
  const ticketForm = document.getElementById("ticketForm");
  if (ticketForm) {
    ticketForm.style.display = "block";
  }
}

function hideTicketForm() {
  const ticketForm = document.getElementById("ticketForm");
  if (ticketForm) {
    ticketForm.style.display = "none";
  }
}

function initLiveChat() {
  showToast("Live chat feature coming soon!", "info");
}

// Initialize preferences and support functionality
document.addEventListener("DOMContentLoaded", function () {
  // Handle preferences form submission
  const preferencesForm = document.getElementById("preferencesForm");
  if (preferencesForm) {
    preferencesForm.addEventListener("submit", async (e) => {
      e.preventDefault();

      const formData = new FormData(preferencesForm);
      const preferences = {
        language: formData.get("language"),
        notifications: {
          orderUpdates: formData.get("orderUpdates") === "on",
          promotions: formData.get("promotions") === "on",
          newsletter: formData.get("newsletter") === "on",
        },
      };

      try {
        const response = await fetch("api/update_preferences.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(preferences),
        });

        if (response.ok) {
          showToast("Preferences updated successfully!", "success");
        } else {
          throw new Error("Failed to update preferences");
        }
      } catch (error) {
        console.error("Error:", error);
        showToast("Failed to update preferences. Please try again.", "error");
      }
    });
  }

  // Handle support ticket form submission
  const supportTicketForm = document.getElementById("supportTicketForm");
  if (supportTicketForm) {
    supportTicketForm.addEventListener("submit", async (e) => {
      e.preventDefault();

      const formData = new FormData(supportTicketForm);

      try {
        const response = await fetch("api/create_support_ticket.php", {
          method: "POST",
          body: formData,
        });

        if (response.ok) {
          showToast("Support ticket created successfully!", "success");
          supportTicketForm.reset();
          hideTicketForm();
        } else {
          throw new Error("Failed to create support ticket");
        }
      } catch (error) {
        console.error("Error:", error);
        showToast(
          "Failed to create support ticket. Please try again.",
          "error"
        );
      }
    });
  }

  // File upload preview
  const fileInput = document.querySelector('input[name="attachments"]');
  if (fileInput) {
    fileInput.addEventListener("change", function () {
      const fileCount = this.files.length;
      const fileLabel = this.nextElementSibling;
      if (fileLabel) {
        fileLabel.textContent = fileCount
          ? `${fileCount} file(s) selected`
          : "Choose files";
      }
    });
  }

  // Tab switching functionality
  const tabLinks = document.querySelectorAll("[data-tab]");
  const tabContents = document.querySelectorAll('[id$="-tab"]');

  tabLinks.forEach((tabLink) => {
    tabLink.addEventListener("click", (e) => {
      e.preventDefault();

      // Remove active class from all tab links
      tabLinks.forEach((link) => link.classList.remove("active"));

      // Hide all tab contents
      tabContents.forEach((content) => {
        if (content) {
          content.style.display = "none";
        }
      });

      // Add active class to clicked tab
      tabLink.classList.add("active");

      // Show selected tab content
      const targetId = tabLink.getAttribute("data-tab");
      const targetContent = document.getElementById(targetId);
      if (targetContent) {
        targetContent.style.display = "block";
      }
    });
  });
});
