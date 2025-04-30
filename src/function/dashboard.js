const listItems = document.querySelectorAll("ul li");

// Add click event to each list item
listItems.forEach(item => {
  item.addEventListener("click", (e) => {
    e.stopPropagation(); // Prevent click from bubbling to document

    const isLargeScreen = window.innerWidth >= 1024;

    if (!item.classList.contains("active")) {
      listItems.forEach(li => {
        li.classList.remove("active");
        const arrow = li.querySelector(".arrow");
        if (arrow) arrow.style.display = "none";
      });

      item.classList.add("active");

      // Only show arrow on large screens
      if (isLargeScreen) {
        const arrow = item.querySelector(".arrow");
        if (arrow) arrow.style.display = "inline";
      }
    }
  });
});

// If user clicks anywhere else on the page, remove active from all
document.addEventListener("click", () => {
  listItems.forEach(li => {
    li.classList.remove("active");
    const arrow = li.querySelector(".arrow");
    if (arrow) arrow.style.display = "none";
  });
});




function toggleMenu() {
    const sidebar = document.getElementById("sidebar");
    sidebar.classList.toggle("show");
  }

  function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    const toggleIcon = document.querySelector(".toggle-icon i");

    sidebar.classList.toggle("mini");

    if (sidebar.classList.contains("mini")) {
      toggleIcon.classList.remove("fa-angle-left");
      toggleIcon.classList.add("fa-angle-right");
    } else {
      toggleIcon.classList.remove("fa-angle-right");
      toggleIcon.classList.add("fa-angle-left");
    }
  }

  // Auto-close sidebar on outside click (mobile only)
  document.addEventListener("click", function (event) {
    const sidebar = document.getElementById("sidebar");
    const menuIcon = document.querySelector(".menu-icon");

    if (
      !sidebar.contains(event.target) &&
      !menuIcon.contains(event.target) &&
      window.innerWidth <= 768
    ) {
      sidebar.classList.remove("show");
    }
  });

  const profileDropdown = document.querySelector('.profile-dropdown');
const dropdownMenu = document.querySelector('.dropdown-menu');

// Function to toggle the dropdown
function toggleDropdown(event) {
    event.preventDefault(); // Prevent default anchor behavior (if needed)
    dropdownMenu.classList.toggle('show');
}


function closeDropdown(event) {
    if (!profileDropdown.contains(event.target)) {
        dropdownMenu.classList.remove('show');
    }
}

profileDropdown.addEventListener('click', toggleDropdown);
document.addEventListener('click', closeDropdown);

// Check if user is logged in
function checkAuth() {
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    if (!isLoggedIn) {
        window.location.href = 'login.html';
    }
}

// Logout function
function logout() {
    localStorage.removeItem('isLoggedIn');
    localStorage.removeItem('currentUser');
    window.location.href = 'login.html';
}

// Update profile section with current user
function updateProfile() {
    const currentUser = localStorage.getItem('currentUser');
    const namesElement = document.querySelector('.names');
    if (namesElement && currentUser) {
        namesElement.textContent = currentUser.toUpperCase();
    }
}

// Initialize dashboard
document.addEventListener("DOMContentLoaded", function () {
    // Check authentication
    checkAuth();
    
    // Update profile
    updateProfile();
    
    // Add logout event listener
    const logoutLink = document.querySelector('.dropdown-menu a[href="Elecom.html"]');
    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            logout();
        });
    }

    let header = document.querySelector('.header');
    let btnMenu = document.querySelector('.burger');
    let navLinks = document.querySelector('.nav-links');
    let navItems = document.querySelectorAll('.nav-links a');

    if (!btnMenu || !navLinks) return;

    function toggleMenu() {
        navLinks.classList.toggle("active");
        btnMenu.classList.toggle("active");
    }

    btnMenu.addEventListener("click", function (e) {
        e.stopPropagation();
        toggleMenu();
    });

    document.addEventListener("click", function (e) {
        if (!navLinks.contains(e.target) && !btnMenu.contains(e.target)) {
            navLinks.classList.remove("active");
            btnMenu.classList.remove("active");
        }
    });

    navItems.forEach(link => {
        link.addEventListener("click", function (e) {
            navLinks.classList.remove("active");
            btnMenu.classList.remove("active");

            let targetId = this.getAttribute("href");
            if (targetId.startsWith("#")) {
                e.preventDefault();
                let targetSection = document.querySelector(targetId);
                if (targetSection) {
                    window.scrollTo({
                        top: targetSection.offsetTop - 50,
                        behavior: "smooth"
                    });
                }
            }
        });
    });

    window.addEventListener("scroll", function () {
        if (navLinks.classList.contains("active")) {
            navLinks.classList.remove("active");
            btnMenu.classList.remove("active");
        }

        if (window.scrollY >= 100) {
            header.classList.add('active');
        } else {
            header.classList.remove('active');
        }
    });
});
