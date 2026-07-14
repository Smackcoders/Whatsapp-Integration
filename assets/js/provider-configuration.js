
document.addEventListener("DOMContentLoaded", function () {
    function showProvider(selectedProvider) {
        let providers = ["twilio", "infobip", "gupshup"];

        providers.forEach(provider => {
            let settingsDiv = document.getElementById(provider + "_settings");
            let helpDiv = document.getElementById(provider + "-help");

            if (provider === selectedProvider) {
                settingsDiv.classList.remove("hidden");
                helpDiv.classList.remove("hidden");
            } else {
                settingsDiv.classList.add("hidden");
                helpDiv.classList.add("hidden");
            }
        });
    }

    // Automatically show the selected provider's settings on page load
    let providerDropdown = document.getElementById("providerDropdown");
    showProvider(providerDropdown.value);

    // Update settings display on dropdown change
    providerDropdown.addEventListener("change", function () {
        showProvider(this.value);
    });
});


document.addEventListener("DOMContentLoaded", function () {
    const helpCard = document.getElementById("help-card");
    const closeHelpBtn = document.getElementById("close-help");

    // Function to show help card
    function showHelpCard(provider) {
        helpCard.classList.remove("hidden");

        // Hide all help sections
        document.querySelectorAll("#help-card > div").forEach(div => {
            div.classList.add("hidden");
        });

        // Show selected provider's help section
        const selectedHelp = document.getElementById(provider + "-help");
        if (selectedHelp) {
            selectedHelp.classList.remove("hidden");
        }
    }

    // Function to close help card
    closeHelpBtn.addEventListener("click", function () {
        helpCard.classList.add("hidden");
    });

    // Add click event listeners to buttons that trigger help
    document.querySelectorAll(".help-btn").forEach(button => {
        button.addEventListener("click", function () {
            const provider = this.getAttribute("data-provider");
            showHelpCard(provider);
        });
    });
});
document.addEventListener("DOMContentLoaded", function () {
    const helpCard = document.getElementById("help-card");
    const helpButton = document.getElementById("help-button");
    const closeHelpBtn = document.getElementById("close-help");

    // Show the help card when clicking the Help button
    helpButton.addEventListener("click", function (event) {
        event.preventDefault(); // Prevents any unintended reload
        helpCard.classList.remove("hidden"); // Show the help card
    });

    // Close the help card when clicking the close button
    closeHelpBtn.addEventListener("click", function (event) {
        event.preventDefault(); // Prevents any unintended reload
        helpCard.classList.add("hidden"); // Hide the help card
    });


    
});
document.addEventListener("DOMContentLoaded", function () {
    const errorBox = document.querySelector(".notice.notice-error");
    if (errorBox) {
        setTimeout(() => {
            errorBox.style.opacity = "0";
            setTimeout(() => {
                errorBox.style.display = "none";
            }, 500); // Wait for fade to finish
        }, 3000); // Show for 3 seconds
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('whatsapp-settings-form');

    form.addEventListener('submit', function (e) {
        e.preventDefault(); // Stop the page from reloading

        const formData = new FormData(form);

        fetch('', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(data => {
            // Replace current messages
            const parser = new DOMParser();
            const htmlDoc = parser.parseFromString(data, 'text/html');

            const newNotice = htmlDoc.querySelector('.notice');
            const oldNotice = document.querySelector('.notice');

            if (oldNotice) {
                oldNotice.remove();
            }

            if (newNotice) {
                form.insertAdjacentElement('beforebegin', newNotice);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });
});

// document.addEventListener("DOMContentLoaded", function () {
//     const notices = document.querySelectorAll(".notice");
// console.log("itss work",notices);

//     notices.forEach(function (notice) {
//         setTimeout(function () {
//             notice.style.opacity = "0";
//             notice.style.transform = "translateY(-20px)";
//             // Trigger layout recalculation
//             void notice.offsetWidth;
        
//             setTimeout(() => {
//                 notice.style.display = "none";
//             }, 500);
//         }, 2000);
//          // 2 seconds delay before hiding
//     });
// });
document.addEventListener("DOMContentLoaded", function () {
    function handleNotice(notice) {
        if (!notice.classList.contains('handled')) {
            notice.classList.add('handled'); // prevent double fade
            setTimeout(function () {
                notice.style.transition = 'opacity 0.5s ease';
                notice.style.opacity = '0';
                setTimeout(() => {
                    notice.style.display = 'none';
                    // console.log("Notice hidden");
                }, 500);
            }, 1500);
        }
    }

    // Initial notices
    document.querySelectorAll(".notice").forEach(handleNotice);

    // Watch for new notices added to DOM (like from PHP echo or WordPress AJAX)
    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(function (node) {
                if (node.classList && node.classList.contains("notice")) {
                    handleNotice(node);
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});
