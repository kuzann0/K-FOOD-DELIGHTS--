// script.js
document.addEventListener("DOMContentLoaded", () => {
  const nav = document.querySelector(".navdiv");
  const heroContainer = document.querySelector(".hero-container");
  const aboutusSection = document.querySelector(".aboutus-section");
  const sections = document.querySelectorAll(
    ".header-p, .hero-container, .aboutus-section, .product-section, .faqs-section, .contact-section, .footer-bottom"
  );

  let prevScrollY = 0;

  // Scroll handler
  window.addEventListener("scroll", () => {
    // Navbar appearance
    nav.classList.toggle(
      "nav-appear",
      window.scrollY <= 0 || window.scrollY < prevScrollY
    );
    prevScrollY = window.scrollY;

    // Hero section parallax effect
    const overlap = aboutusSection.offsetTop - window.innerHeight;
    heroContainer.style.height = `${Math.max(
      0,
      100 - (overlap / window.innerHeight) * 100
    )}vh`;
    aboutusSection.style.transform = `translateY(${parseFloat(
      heroContainer.style.height
    )}vh)`;

    // Adjust section heights
    const remainingHeight = 100 - parseFloat(heroContainer.style.height);
    aboutusSection.style.minHeight = `${remainingHeight}vh`;

    // Z-index management
    if (overlap <= 0) {
      aboutusSection.style.zIndex = 1;
      heroContainer.style.zIndex = 0;
    } else {
      aboutusSection.style.zIndex = 0;
      heroContainer.style.zIndex = 1;
    }

    // Scroll to top button visibility
    const scrollToTopButton = document.getElementById("scroll-to-top");
    if (window.pageYOffset > 100) {
      scrollToTopButton.classList.add("show");
    } else {
      scrollToTopButton.classList.remove("show");
    }
  });

  // Intersection Observer for sections
  sections.forEach((element) => {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            element.classList.add("appear");
          } else {
            element.classList.remove("appear");
          }
        });
      },
      { threshold: 0.1 }
    );
    observer.observe(element);
  });

  // Initial hero container appearance
  heroContainer.classList.add("appear");

  // Scroll to top functionality
  const scrollToTopButton = document.getElementById("scroll-to-top");
  scrollToTopButton.addEventListener("click", () => {
    window.scrollTo({
      top: 0,
      behavior: "smooth",
    });
  });

  // FAQ items interaction
  const faqItems = document.querySelectorAll(".faq-item");
  faqItems.forEach((item) => {
    item.addEventListener("click", () => {
      item.classList.toggle("active");
    });
  });
});

document.addEventListener("DOMContentLoaded", () => {
  const sliderContainer = document.querySelector(".slider-container");
  const slides = document.querySelectorAll(".slide");
  const prevButton = document.querySelector(".prev-button");
  const nextButton = document.querySelector(".next-button");
  const dotsContainer = document.querySelector(".dots-container");

  let currentSlide = 0;
  let autoSlideInterval;

  // Create dots
  slides.forEach((_, index) => {
    const dot = document.createElement("div");
    dot.classList.add("dot");
    if (index === 0) dot.classList.add("active");
    dot.addEventListener("click", () => goToSlide(index));
    dotsContainer.appendChild(dot);
  });

  const dots = document.querySelectorAll(".dot");

  function updateDots() {
    dots.forEach((dot, index) => {
      dot.classList.toggle("active", index === currentSlide);
    });
  }

  function showSlide(n) {
    sliderContainer.style.transform = `translateX(-${n * 100}%)`;
    updateDots();
  }

  function nextSlide() {
    currentSlide = (currentSlide + 1) % slides.length;
    showSlide(currentSlide);
  }

  function prevSlide() {
    currentSlide = (currentSlide - 1 + slides.length) % slides.length;
    showSlide(currentSlide);
  }

  function goToSlide(n) {
    currentSlide = n;
    showSlide(currentSlide);
    resetAutoSlide();
  }

  function resetAutoSlide() {
    clearInterval(autoSlideInterval);
    startAutoSlide();
  }

  function startAutoSlide() {
    autoSlideInterval = setInterval(nextSlide, 5000);
  }

  // Event Listeners
  nextButton.addEventListener("click", () => {
    nextSlide();
    resetAutoSlide();
  });

  prevButton.addEventListener("click", () => {
    prevSlide();
    resetAutoSlide();
  });

  // Start auto-sliding
  startAutoSlide();
});
