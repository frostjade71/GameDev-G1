<?php
// Function to determine relative path to includes directory
function getRelativePathToIncludes($currentScriptPath) {
    // This is a basic estimation. 
    // If the script is in root (e.g. menu.php), includes is "includes/"
    // If in navigation/friends/friends.php, includes is "../../includes/"
    
    // Check if we are in the root directory
    if (file_exists('includes/loader.css')) {
        return 'includes/';
    }
    
    // Check one level up
    if (file_exists('../includes/loader.css')) {
        return '../includes/';
    }
    
    // Check two levels up
    if (file_exists('../../includes/loader.css')) {
        return '../../includes/';
    }
    
    // Check three levels up
    if (file_exists('../../../includes/loader.css')) {
        return '../../../includes/';
    }

    // Fallback to absolute path if we can't find it relatively (though relative is better for portability)
    return '/GameDev-G1/includes/';
}

$includesPath = getRelativePathToIncludes(__FILE__); // This might not work as intended because __FILE__ is this file. 
// Actually, when included, the working directory is usually the script execution directory.
// Let's rely on checking file existence from current working directory.

$cssPath = "includes/loader.css";
if (file_exists("includes/loader.css")) {
    $cssPath = "includes/loader.css";
} elseif (file_exists("../includes/loader.css")) {
    $cssPath = "../includes/loader.css";
} elseif (file_exists("../../includes/loader.css")) {
    $cssPath = "../../includes/loader.css";
} elseif (file_exists("../../../includes/loader.css")) {
    $cssPath = "../../../includes/loader.css";
}

?>
<link rel="stylesheet" href="<?php echo $cssPath; ?>?v=<?php echo time(); ?>">

<div class="page-loader-overlay" id="pageLoader">
    <div class="loader"></div>

</div>

<script>
    (function() {
        'use strict';

        const loader = document.getElementById('pageLoader');

        // Hide loader when page is fully loaded
        window.addEventListener('load', function() {
            if (loader) {
                // minimal delay to ensure smooth transition
                setTimeout(() => {
                    loader.classList.add('hidden');
                }, 300);
            }
        });

        // Show loader when navigating to another page
        document.addEventListener('DOMContentLoaded', function() {
            // Get all internal links
            const links = document.querySelectorAll('a[href]');
            
            links.forEach(function(link) {
                // Skip if it's an external link, anchor link, etc.
                const href = link.getAttribute('href');
                
                if (!href || 
                    href.startsWith('#') || 
                    href.startsWith('javascript:') || 
                    href.startsWith('mailto:') || 
                    href.startsWith('tel:') ||
                    link.hasAttribute('data-no-loader') ||
                    link.target === '_blank' ||
                    (href.startsWith('http') && !href.includes(window.location.hostname))) {
                    return;
                }

                // Add click event to show loader
                link.addEventListener('click', function(e) {
                    // Don't show loader if ctrl/cmd key is pressed (new tab)
                    if (e.ctrlKey || e.metaKey || e.button === 1) {
                        return;
                    }

                    // Check if this is a same-page hash navigation
                    try {
                        const linkUrl = new URL(href, window.location.origin);
                        const currentPath = window.location.pathname;
                        const linkPath = linkUrl.pathname;
                        
                        if (linkPath === currentPath && linkUrl.hash) {
                            return;
                        }
                    } catch(e) {}

                    if (loader) {
                        loader.classList.remove('hidden');
                    }
                });
            });

            // Handle form submissions
            const forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                if (form.hasAttribute('data-no-loader') || form.hasAttribute('data-ajax')) {
                    return;
                }

                form.addEventListener('submit', function() {
                    if (loader) {
                        loader.classList.remove('hidden');
                    }
                });
            });

            // Handle browser back/forward buttons (pageshow event)
            window.addEventListener('pageshow', function(event) {
                if (loader && event.persisted) {
                    // Page was loaded from cache (back/forward button)
                    loader.classList.add('hidden');
                }
            });
        });

        // Fallback: Hide loader if it's still visible after 8 seconds
        setTimeout(() => {
            if (loader && !loader.classList.contains('hidden')) {
                loader.classList.add('hidden');
            }
        }, 8000);
    })();
</script>
