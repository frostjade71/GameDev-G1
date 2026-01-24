<?php
/**
 * Vocabworld Loader Component
 * This component provides a smooth page transition loader using the dot-spinner design.
 */

// Determine the base path for Vocabworld
$vocabPath = "";
if (file_exists("loaders/loader.css")) {
    $vocabPath = "loaders/";
} elseif (file_exists("../loaders/loader.css")) {
    $vocabPath = "../loaders/";
} elseif (file_exists("../../loaders/loader.css")) {
    $vocabPath = "../../loaders/";
} elseif (file_exists("../../../loaders/loader.css")) {
    $vocabPath = "../../../loaders/";
}
?>

<link rel="stylesheet" href="<?php echo $vocabPath; ?>loader.css?v=<?php echo time(); ?>">

<div class="page-loader-overlay" id="vocabPageLoader">
    <div class="dot-spinner">
        <div class="dot-spinner__dot"></div>
        <div class="dot-spinner__dot"></div>
        <div class="dot-spinner__dot"></div>
        <div class="dot-spinner__dot"></div>
        <div class="dot-spinner__dot"></div>
        <div class="dot-spinner__dot"></div>
        <div class="dot-spinner__dot"></div>
        <div class="dot-spinner__dot"></div>
    </div>
</div>

<script>
    (function() {
        'use strict';

        const loader = document.getElementById('vocabPageLoader');
        const body = document.body;

        // Add fade-in class to body content if not already present
        document.addEventListener('DOMContentLoaded', function() {
            body.classList.add('fade-in-content');
        });

        // Hide loader when page is fully loaded
        window.addEventListener('load', function() {
            if (loader) {
                setTimeout(() => {
                    loader.classList.add('hidden');
                }, 400); // Slight delay for smooth transition
            }
        });

        // Show loader when navigating to another internal page
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a[href]');
            if (!link) return;

            const href = link.getAttribute('href');
            
            // Skip if it's an external link, anchor link, etc.
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
            } catch(err) {}

            if (loader) {
                loader.classList.remove('hidden');
            }
        });

        // Handle form submissions
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (form.hasAttribute('data-no-loader') || form.hasAttribute('data-ajax')) {
                return;
            }

            if (loader) {
                loader.classList.remove('hidden');
            }
        });

        // Handle browser back/forward buttons
        window.addEventListener('pageshow', function(event) {
            if (loader && event.persisted) {
                loader.classList.add('hidden');
            }
        });

        // Fallback: Hide loader if it's still visible after 6 seconds
        setTimeout(() => {
            if (loader && !loader.classList.contains('hidden')) {
                loader.classList.add('hidden');
            }
        }, 6000);
    })();
</script>
