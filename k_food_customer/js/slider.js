// Slider navigation
document.addEventListener("DOMContentLoaded", function () {
  const slides = document.querySelectorAll(".slide");
  const prevButton = document.querySelector(".prev-button");
  const nextButton = document.querySelector(".next-button");
  const dotsContainer = document.querySelector(".dots-container");
  let currentSlide = 0;

  // Create dots
  slides.forEach((_, index) => {
    const dot = document.createElement("span");
    dot.classList.add("dot");
    dot.addEventListener("click", () => goToSlide(index));
    dotsContainer.appendChild(dot);
  });

  const dots = document.querySelectorAll(".dot");

  function updateSlides() {
    slides.forEach((slide, index) => {
      if (index === currentSlide) {
        slide.style.display = "block";
        slide.classList.add("active");
        dots[index].classList.add("active");
      } else {
        slide.style.display = "none";
        slide.classList.remove("active");
        dots[index].classList.remove("active");
      }
    });
    updateOrderButton();
  }

  function goToSlide(n) {
    currentSlide = (n + slides.length) % slides.length;
    updateSlides();
  }

  function nextSlide() {
    goToSlide(currentSlide + 1);
  }

  function prevSlide() {
    goToSlide(currentSlide - 1);
  }

  // Event listeners for navigation
  prevButton.addEventListener("click", prevSlide);
  nextButton.addEventListener("click", nextSlide);

  // Functions to get current product details
  window.getCurrentProduct = function () {
    const activeSlide = document.querySelector(".slide.active");
    return activeSlide.querySelector("h3").textContent;
  };

  window.getCurrentPrice = function () {
    const activeSlide = document.querySelector(".slide.active");
    const priceText = activeSlide.querySelector(".price").textContent;
    return parseFloat(priceText.replace("₱", ""));
  };

  // Update main order button text based on current product
  function updateOrderButton() {
    const product = getCurrentProduct();
    const price = getCurrentPrice();
    const mainOrderBtn = document.getElementById("mainOrderBtn");
    if (mainOrderBtn) {
      mainOrderBtn.textContent = `Order ${product} - ₱${price}`;
    }
  }

  // Initialize the slider
  updateSlides();

  // Auto slide every 5 seconds
  setInterval(nextSlide, 5000);
});
