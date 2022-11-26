(function () {
  function getNavigationHTML() {
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
    const savedPage = "saved.html";
    const favouritesPage = "favourites.html";

    let isHomePageActive = false;
    let isFindPageActive = false;
    let isPropertiesPageActive = false;
    let isSavedPageActive = false;
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
      case savedPage:
        isSavedPageActive = true;
        break;
      case favouritesPage:
        isFavouritesPageActive = true;
        break;
    }

    return `
            <nav class="controls">
                <div class="controls-wrapper">
                    <a href="${homePage}">
                        <div class="control ${getStyles(isHomePageActive)}">
                            <i class="fas fa-home fa-lg"></i>
                            <span>Home</span>
                        </div>                          
                    </a>                      
                    <a href="${findPage}">
                        <div class="control ${getStyles(isFindPageActive)}">
                            <i class="fas fa-search fa-lg"></i>
                            <span>Find</span>
                        </div>
                    </a>
                    <a href="${propertiesPage}">
                        <div class="control ${getStyles(isPropertiesPageActive)}">
                            <i class="fas fa-list-alt fa-lg"></i>
                            <span>Properties</span>
                        </div>
                    </a>
                    <a href="${savedPage}">
                        <div class="control ${getStyles(isSavedPageActive)}">
                            <i class="fas fa-bookmark fa-lg"></i>
                            <span>Saved</span>
                        </div>
                    </a>
                    <a href="${favouritesPage}">
                        <div class="control ${getStyles(isFavouritesPageActive)}">
                            <i class="fas fa-heart fa-lg"></i>
                            <span>Favourites</span>
                        </div>
                    </a>
                    <div class="sub-content-header">
                        <div class="theme-btn">
                            <i id="theme-icon" class="fas fa-sun fa-lg"></i>
                        </div>
                    </div>
                </div>
            </nav>
        `;
  }

  function getFooterHTML() {
    function getCurrentPage() {
      const path = window.location.pathname;
      const page = path.split("/").pop();
      console.log(`Current page: ${page}`);
      return page;
    }
    
    function getStyles(isActive) {
      return isActive ? "active-btn" : "";
    }

    const homePage = "home.html";
    const findPage = "find.html";
    const propertiesPage = "properties.html";
    const savedPage = "saved.html";
    const favouritesPage = "favourites.html";

    const currentPage = getCurrentPage();

    let isHomePageActive = false;
    let isFindPageActive = false;
    let isPropertiesPageActive = false;
    let isSavedPageActive = false;
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
      case savedPage:
        isSavedPageActive = true;
        break;
      case favouritesPage:
        isFavouritesPageActive = true;
        break;
    }

    return `<footer>
        <hr />
        <div class="site-footer">
            <div class="copyright-text">
                <p>Copyright &copy; <time datetime="2022">2022</time></p>
            </div>
            <div class="footer-controls">
              <ul>
                <li>
                  <a href="${homePage}">
                    <div class="footer-control ${getStyles(isHomePageActive)}">
                      <i class="fas fa-home fa-sm"></i>
                      <span>Home</span>
                    </div>
                  </a>                  
                </li>
                <li>
                  <a href="${findPage}">
                    <div class="footer-control ${getStyles(isFindPageActive)}">
                      <i class="fas fa-search fa-sm"></i>
                      <span>Find</span>
                    </div>
                  </a>
                </li>
                <li>
                  <a href="${propertiesPage}">
                    <div class="footer-control ${getStyles(isPropertiesPageActive)}">
                      <i class="fas fa-list-alt fa-sm"></i>
                      <span>Properties</span>
                    </div>
                  </a>
                </li>
                <li>
                  <a href="${savedPage}">
                    <div class="footer-control ${getStyles(isSavedPageActive)}">
                      <i class="fas fa-bookmark fa-sm"></i>
                      <span>Saved</span>
                    </div>
                  </a>
                </li>
                <li>
                  <a href="${favouritesPage}">
                    <div class="footer-control ${getStyles(isFavouritesPageActive)}">
                      <i class="fas fa-heart fa-sm"></i>
                      <span>Favourites</span>
                    </div>
                  </a>
                </li>
              </ul>                
            </div>
            <div class="contact-icon">
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
            <div class="back-to-top-icon">
                <a href="#top">
                <i class="fa fa-arrow-up"></i>
                </a>
            </div>
        </div>     
    </footer>`;
  }

  try {
    document.getElementById("navigation").innerHTML = getNavigationHTML();
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
