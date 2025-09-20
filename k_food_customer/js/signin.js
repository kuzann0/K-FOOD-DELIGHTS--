function openLoginModal() {
  document.getElementById("loginModal").style.display = "flex";
}

function closeModal() {
  document.getElementById("loginModal").style.display = "none";
}

function openSignupModal() {
  document.getElementById("signupModal").style.display = "flex";
}

function closeSignupModal() {
  document.getElementById("signupModal").style.display = "none";
}

function switchToSignup() {
  closeModal();
  openSignupModal();
}

function switchToLogin() {
  closeSignupModal();
  openLoginModal();
}
