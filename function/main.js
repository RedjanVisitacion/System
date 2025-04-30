document.addEventListener("DOMContentLoaded", function () {
    let header = document.querySelector('.header');
    let btnMenu = document.querySelector('.burger'); // Burger button
    let navLinks = document.querySelector('.nav-links'); // Navigation menu
    let navItems = document.querySelectorAll('.nav-links a'); // Navigation links

    if (!btnMenu || !navLinks) return; // Exit if elements are missing

    // Function to show/hide the menu
    function toggleMenu() {
        navLinks.classList.toggle("active");
        btnMenu.classList.toggle("active");
    }

    // Click event on burger button to toggle menu
    btnMenu.addEventListener("click", function (e) {
        e.stopPropagation(); // Prevents the menu from closing immediately
        toggleMenu();
    });

    // Close menu when clicking outside
    document.addEventListener("click", function (e) {
        if (!navLinks.contains(e.target) && !btnMenu.contains(e.target)) {
            navLinks.classList.remove("active");
            btnMenu.classList.remove("active");
        }
    });

    // Close menu when clicking a navigation link
    navItems.forEach(link => {
        link.addEventListener("click", function (e) {
            navLinks.classList.remove("active");
            btnMenu.classList.remove("active");

            // Smooth scroll to section
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

    // Close menu when scrolling
    window.addEventListener("scroll", function () {
        if (navLinks.classList.contains("active")) {
            navLinks.classList.remove("active");
            btnMenu.classList.remove("active");
        }

        // Change header style when scrolling
        if (window.scrollY >= 100) {
            header.classList.add('active');
        } else {
            header.classList.remove('active');
        }
    });
});


document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("#vote form");
    const resultsSection = document.querySelector("#results p");
    const voterNameInput = document.querySelector("#voter-name");
    const candidateSelect = document.querySelector("#candidate");

    // Retrieve votes from localStorage or initialize
    let votes = JSON.parse(localStorage.getItem("votes")) || {
        candidate1: 0,
        candidate2: 0
    };

    // Function to update results display
    function updateResults() {
        resultsSection.innerHTML = `
            <strong>Current Votes:</strong><br>
            🥇 Silver Mae S. Heyrana: ${votes.candidate1} votes<br>
            🥇 Redjan Phil S. Visitacion: ${votes.candidate2} votes
        `;
    }

    // Handle voting submission
    form.addEventListener("submit", function (e) {
        e.preventDefault();

        const voterName = voterNameInput.value.trim();
        const selectedCandidate = candidateSelect.value;

        if (!voterName) {
            alert("Please enter your name.");
            return;
        }

        if (selectedCandidate === "candidate0") {
            alert("Please select a candidate.");
            return;
        }

        // Prevent duplicate votes (basic check)
        if (localStorage.getItem(`voted_${voterName}`)) {
            alert("You have already voted!");
            return;
        }

        // Register the vote
        if (selectedCandidate === "candidate1") {
            votes.candidate1++;
        } else if (selectedCandidate === "candidate2") {
            votes.candidate2++;
        }

        // Save votes and mark voter as voted
        localStorage.setItem("votes", JSON.stringify(votes));
        localStorage.setItem(`voted_${voterName}`, true);

        alert("Thank you for voting!");
        voterNameInput.value = ""; // Reset input field
        candidateSelect.value = "candidate0"; // Reset selection

        updateResults(); // Refresh results
    });

    updateResults(); // Load results on page load
});
