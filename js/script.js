(function () {
  function getHeaderHTML() {
    function getCurrentPage() {
      const path = window.location.pathname;
      const page = path.split("/").pop();
      console.log(`Current page: ${page}`);
      return page;
    }

    function getStyles(isActive) {
      return isActive ? "active-btn" : "";
    }

    const currentPage = getCurrentPage();

    const homePage = "home.html";
    const findPage = "find.html";
    const propertiesPage = "properties.html";
    const galleryPage = "gallery.html";
    const favouritesPage = "favourites.html";

    let isHomePageActive = false;
    let isFindPageActive = false;
    let isPropertiesPageActive = false;
    let isGalleryPageActive = false;
    let isFavouritesPageActive = false;

    switch (currentPage) {
      case homePage:
        isHomePageActive = true;
        break;
      case findPage:
        isFindPageActive = true;
        break;
      case propertiesPage:
        isPropertiesPageActive = true;
        break;
      case galleryPage:
        isGalleryPageActive = true;
        break;
      case favouritesPage:
        isFavouritesPageActive = true;
        break;
    }

    return `
            <div id="header-hero-container">
              <header>
                <div class="flex container">
                  <a href="${homePage}">
                    <img src="img/logo.png" id="logo" alt="logo" />
                  </a>

                  <a id="logo-text" href="${homePage}">Dream Home</a>

                  <nav>
                    <button id="nav-toggle" class="hamburger-menu">
                      <span class="strip"></span>
                      <span class="strip"></span>
                      <span class="strip"></span>
                    </button>

                    <ul id="nav-menu">
                      <li>
                        <a href="${homePage}">Home</a>
                      </li>                     
                      <li>
                        <a href="${findPage}">Find</a>
                      </li>
                      <li>
                        <a href="${propertiesPage}">Properties</a>
                      </li>
                      <li>
                        <a href="${galleryPage}">Gallery</a>
                      </li>
                      <li>
                        <a href="${favouritesPage}">Favourites</a>
                      </li>
                    </ul>
                  </nav>

                </div>
              </header>
            </div>
        `;
  }

  function getFooterHTML() {
    function getCurrentPage() {
      const path = window.location.pathname;
      const page = path.split("/").pop();
      console.log(`Current page: ${page}`);
      return page;
    }
    const currentPage = getCurrentPage();

    const homePage = "home.html";
    const findPage = "find.html";
    const propertiesPage = "properties.html";
    const galleryPage = "gallery.html";
    const favouritesPage = "favourites.html";

    let isHomePageActive = false;
    let isFindPageActive = false;
    let isPropertiesPageActive = false;
    let isGalleryPageActive = false;
    let isFavouritesPageActive = false;

    switch (currentPage) {
      case homePage:
        isHomePageActive = true;
        break;
      case findPage:
        isFindPageActive = true;
        break;
      case propertiesPage:
        isPropertiesPageActive = true;
        break;
      case galleryPage:
        isGalleryPageActive = true;
        break;
      case favouritesPage:
        isFavouritesPageActive = true;
        break;
    }

    return `<footer>
        <hr />
        <div class="site-footer">
            <div class="site-footer-contact">
                <p class="footer-title">Get In Touch</p>
                <div class="footer-contact-icons">
                  <div class="icon">
                    <i class="fa fa-map-marker"></i>
                    <span>Smith Ave, UK</span>
                  </div>
                  <div class="icon">
                    <i class="fa fa-phone"></i>
                    <span>07522670617</span>
                  </div>
                  <div class="icon">
                    <i class="fa fa-envelope"></i>
                    <span>dreamhome@gmail.com</span>
                  </div>
                </div>
                <div class="footer-contact-media">
                  <a href="https://www.facebook.com/" target="_blank">
                    <i class="fab fa-facebook-f"></i>
                  </a>
                  <a href="https://twitter.com/" target="_blank">
                      <i class="fab fa-twitter"></i>
                  </a>
                  <a href="https://www.instagram.com/" target="_blank">
                      <i class="fab fa-instagram"></i>
                  </a>
                  <a href="https://www.youtube.com/" target="_blank">
                      <i class="fab fa-youtube"></i>
                  </a>
                </div>
            </div>
            <div class="site-footer-links">
              <p class="footer-title">Quick Links</p>
              <ul class="footer-links-items">
                <li class="item">
                  <a href="${homePage}">Home</a>
                </li>                     
                <li class="item">
                  <a href="${findPage}">Find</a>
                </li>
                <li class="item">
                  <a href="${propertiesPage}">Properties</a>
                </li>
                <li class="item">
                  <a href="${galleryPage}">Gallery</a>
                </li>
                <li class="item">
                  <a href="${favouritesPage}">Favourites</a>
                </li>
              </ul>               
            </div>
            <div class="site-footer-gallery">
              <p class="footer-title">Photo Gallery</p>
              <div>
              </div>       
            </div>
            <div class="site-footer-newsletter">
              <p class="footer-title">Photo Gallery</p>
              <div></div>
            </div>
        </div>     
    </footer>`;
  }

  try {
    document.getElementById("header").innerHTML = getHeaderHTML();
    document.getElementById("footer").innerHTML = getFooterHTML();
  } catch (error) {}

  [...document.querySelectorAll(".control")].forEach((button) => {
    button.addEventListener("click", function () {
      document.querySelector(".active-btn")?.classList.remove("active-btn");
      this.classList?.add("active-btn");
      document.querySelector(".active")?.classList.remove("active");
      document.getElementById(button.dataset.id)?.classList.add("active");
    });
  });

  document.querySelector(".theme-btn").addEventListener("click", () => {
    document.body?.classList.toggle("light-mode");
    document.getElementById("theme-icon")?.classList.toggle("fa-sun");
    document.getElementById("theme-icon")?.classList.toggle("fa-moon");
  });
})();
