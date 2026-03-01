<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service — Word Weavers</title>
    <link rel="icon" type="image/webp" href="../assets/menu/ww_logo_main.webp">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../includes/loader.css">
    <link rel="stylesheet" href="../includes/mobile-sidebar.css">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #009bd9;
            --bg: #050505;
            --card-bg: #101926;
            --card-border: rgba(255,255,255,0.08);
            --text: #fff;
            --text-muted: rgba(255,255,255,0.85);
        }

        @keyframes aurora {
            0% { transform: scale(1) rotate(0deg); opacity: 0.4; }
            50% { transform: scale(1.2) rotate(180deg); opacity: 0.7; }
            100% { transform: scale(1) rotate(360deg); opacity: 0.4; }
        }

        .bg {
            position: fixed; inset: 0; z-index: 0;
            background: url('../assets/menu/menubg.jpg') center/cover no-repeat;
            overflow: hidden;
        }
        .aurora {
            position: absolute; width: 300%; height: 300%; top: -100%; left: -100%; z-index: 1;
            background: linear-gradient(45deg, #1a1a1a 0%, #003366 100%),
                repeating-linear-gradient(45deg, rgba(0,255,255,0.1) 0px, rgba(0,255,255,0.1) 20px, rgba(0,255,0,0.1) 20px, rgba(0,255,0,0.1) 40px),
                radial-gradient(circle at 50% 50%, rgba(32,196,232,0.3) 0%, rgba(76,201,240,0.1) 100%);
            background-blend-mode: normal, overlay, overlay;
            animation: aurora 15s linear infinite; mix-blend-mode: overlay; pointer-events: none;
        }
        .bg::before { content: ''; position: absolute; inset: 0; z-index: 2;
            background: linear-gradient(to bottom, rgba(0,0,0,0) 0%, rgba(0,0,0,0.85) 100%); opacity: 1;
        }

        body { font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

        .top-bar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 40px; background: rgba(5,5,5,0.75);
            backdrop-filter: blur(20px); border-bottom: 1px solid var(--card-border);
        }
        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: #fff; }
        .brand img { width: 30px; height: 30px; }
        .brand span { font-family: 'Press Start 2P', cursive; font-size: .7rem; font-weight: 400; padding-top: 4px; }
        .nav-links { display: flex; align-items: center; gap: 32px; }
        .nav-links a:not(.nav-cta):not(.dropdown-item) {
            display: inline-flex; align-items: center; gap: 6px;
            color: var(--text-muted); text-decoration: none; font-size: .85rem; transition: color .2s;
        }
        .nav-links a:not(.nav-cta):not(.dropdown-item):hover { color: var(--text); }
        .nav-cta {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 24px; font-size: .8rem; font-weight: 600;
            color: #fff !important; background: var(--primary);
            border-radius: 10px; text-decoration: none; transition: all .2s;
        }
        .nav-cta:hover { background: #0089c4; transform: translateY(-1px); }

        .dropdown { position: relative; display: inline-flex; height: 100%; align-items: center; }
        .dropdown-btn { background: none; border: none; font-size: .85rem; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; gap: 6px; font-family: inherit; }
        .dropdown-btn svg { width: 14px; height: 14px; opacity: 0.6; transition: transform .2s; }
        .dropdown:hover .dropdown-btn { color: var(--text); }
        .dropdown:hover .dropdown-btn svg { opacity: 1; transform: rotate(180deg); }
        .dropdown-menu { position: absolute; top: 100%; left: 50%; transform: translateX(-50%) translateY(10px); background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 12px; padding: 6px; min-width: 170px; opacity: 0; visibility: hidden; transition: all .2s; pointer-events: none; }
        .dropdown:hover .dropdown-menu { opacity: 1; visibility: visible; transform: translateX(-50%) translateY(0); pointer-events: auto; }
        .dropdown-menu::before { content: ''; position: absolute; top: -20px; left: 0; right: 0; height: 20px; }
        .dropdown-item { display: flex; align-items: center; gap: 8px; padding: 9px 16px; color: var(--text-muted); text-decoration: none; font-size: .85rem; border-radius: 8px; transition: .15s; }
        .dropdown-item:hover { color: var(--text); background: rgba(255,255,255,0.06); }

        .page-content { padding: 120px 40px 60px; max-width: 900px; margin: 0 auto; position: relative; z-index: 10; }
        h1 { display: flex; align-items: center; gap: 16px; font-size: 2.5rem; font-weight: 800; margin-bottom: 24px; letter-spacing: -.02em; }
        h1 svg { width: 1em; height: 1em; color: var(--text); }
        p { color: var(--text-muted); line-height: 1.6; margin-bottom: 16px; }

        .glass-panel {
            background: rgba(16, 25, 38, 0.75); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border); border-radius: 24px; padding: 40px; margin-bottom: 40px;
        }
        .glass-panel h2 { font-size: 1.3rem; font-weight: 700; margin-bottom: 16px; color: var(--text); }
        .glass-panel ul { margin-left: 20px; color: var(--text-muted); line-height: 1.8; margin-bottom: 16px; }

        @media (max-width: 600px) {
            .top-bar { padding: 16px 20px; }
            .nav-links { display: none; }
            .page-content { padding: 100px 20px 40px !important; }
            h1 { font-size: 2rem !important; margin-bottom: 30px !important; }
            .glass-panel { padding: 24px !important; margin-bottom: 24px !important; }
        }
    </style>
</head>
<body>
    <div class="page-loader-overlay" id="pageLoader"><div class="loader"></div></div>
    <div class="bg"><div class="aurora"></div></div>
    <nav class="top-bar">
        <a href="../index.php" class="brand">
            <img src="../assets/menu/ww_logo_main.webp" alt="Word Weavers">
            <span>Word Weavers</span>
        </a>
        <div class="nav-links">
            <a href="../index.php">Home</a>
            <a href="../index.php#features">Features</a>
            <div class="dropdown">
                <button class="dropdown-btn">
                    Resources
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                </button>
                <div class="dropdown-menu">
                    <a href="documentation.php" class="dropdown-item">Documentation</a>
                    <a href="changelog.php" class="dropdown-item">Changelog</a>
                    <a href="support.php" class="dropdown-item">Support</a>
                </div>
            </div>
            <a href="../main.php" class="nav-cta">Login / Register</a>
        </div>
        <button class="hamburger-btn" id="hamburgerBtn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>
    </nav>

    <main class="page-content">
        <h1>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
            Terms of Service
        </h1>

        <section class="glass-panel">
            <p style="font-size: 0.85rem; opacity: 0.6; margin-bottom: 20px;">Last updated: March 1, 2026</p>
            <p>
                Welcome to Word Weavers. By accessing or using this platform, you agree to the following terms and conditions. Word Weavers is a thesis project system developed for academic purposes at Holy Cross College of Carigara Inc. (HCCCI).
            </p>
        </section>

        <section class="glass-panel">
            <h2>Use of the Platform</h2>
            <ul>
                <li>Word Weavers is an educational platform designed for students and teachers</li>
                <li>You must provide accurate information during registration</li>
                <li>You are responsible for maintaining the security of your account credentials</li>
                <li>The platform is intended for educational purposes only</li>
            </ul>
        </section>

        <section class="glass-panel">
            <h2>User Accounts</h2>
            <ul>
                <li>One account per user; sharing accounts is not permitted</li>
                <li>Users must verify their email address via OTP during registration</li>
                <li>Administrators reserve the right to suspend or terminate accounts that violate these terms</li>
            </ul>
        </section>

        <section class="glass-panel">
            <h2>Acceptable Use</h2>
            <p>You agree not to:</p>
            <ul>
                <li>Use the platform for any purpose other than educational learning</li>
                <li>Attempt to exploit, hack, or interfere with the platform's functionality</li>
                <li>Share inappropriate content through any platform feature</li>
                <li>Impersonate other users or use misleading account information</li>
            </ul>
        </section>

        <section class="glass-panel">
            <h2>Intellectual Property</h2>
            <p>
                All content, designs, game assets, and code within Word Weavers are the intellectual property of the development team. This project is developed as part of an academic thesis and is protected under the project's license terms.
            </p>
        </section>

        <section class="glass-panel">
            <h2>Disclaimer</h2>
            <p>
                Word Weavers is provided "as is" as a thesis project. While we strive for reliability, we do not guarantee uninterrupted service or the absence of errors. The platform is primarily intended for academic demonstration and educational use.
            </p>
        </section>

        <section class="glass-panel">
            <h2>Contact</h2>
            <p>For questions regarding these terms, contact us at <a href="mailto:wordweavershccci@gmail.com" style="color: var(--primary); text-decoration: none;">wordweavershccci@gmail.com</a></p>
        </section>
    </main>

    <script>
        window.addEventListener('load', () => {
            const loader = document.getElementById('pageLoader');
            if (loader) setTimeout(() => loader.classList.add('hidden'), 500);
        });
    </script>
    <div class="mobile-nav-overlay" id="mobileNavOverlay"></div>
    <aside class="mobile-sidebar" id="mobileSidebar">
        <div class="sidebar-header">
            <img src="../assets/menu/ww_logo_main.webp" alt="Word Weavers" style="height: 40px; width: auto; object-fit: contain;">
            <button class="close-btn" id="closeSidebar">&times;</button>
        </div>
        <nav class="mobile-nav-links">
            <a href="../index.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                Home
            </a>
            <a href="../index.php#features">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                Features
            </a>
            <a href="documentation.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Documentation
            </a>
            <a href="changelog.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                Changelog
            </a>
            <a href="support.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                Support
            </a>
            <a href="../main.php" class="nav-cta">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                Login / Register
            </a>
        </nav>
    </aside>
    <script src="../includes/mobile-sidebar.js"></script>
</body>
</html>
