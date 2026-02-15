<?php
require_once 'onboarding/config.php';
if (isLoggedIn()) {
    header('Location: menu.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Word Weavers - Learn English Through Play</title>
    <meta name="description" content="Word Weavers is an interactive educational platform that helps Junior High School students improve English skills through immersive language arts web games.">
    <link rel="icon" type="image/webp" href="assets/menu/ww_logo_main.webp">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="includes/loader.css">
    <link rel="stylesheet" href="includes/mobile-sidebar.css">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #009bd9;
            --primary-rgb: 0, 155, 217;
            --accent-1: #3b82f6;
            --accent-2: #22d3ee;
            --accent-3: #facc15;
            --bg: #050505;
            --card-bg: #101926;
            --card-border: rgba(255,255,255,0.08);
            --card-hover-border: rgba(255,255,255,0.18);
            --text: #fff;
            --text-muted: rgba(255,255,255,0.55);
            --text-dim: rgba(255,255,255,0.35);
            --section-gap: 120px;
            --container: 1100px;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Outfit', system-ui, sans-serif;
            color: var(--text);
            background: var(--bg);
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        /* ── Ambient Background ──────────────────────── */
        @keyframes aurora {
            0% { transform: scale(1) rotate(0deg); opacity: 0.4; }
            50% { transform: scale(1.2) rotate(180deg); opacity: 0.7; }
            100% { transform: scale(1) rotate(360deg); opacity: 0.4; }
        }

        .bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            background: url('assets/menu/menubg.jpg') center/cover no-repeat;
            overflow: hidden;
        }
        .aurora {
            position: absolute;
            width: 300%;
            height: 300%;
            top: -100%;
            left: -100%;
            z-index: 1;
            background: 
                linear-gradient(45deg, #1a1a1a 0%, #003366 100%),
                repeating-linear-gradient(
                    45deg,
                    rgba(0, 255, 255, 0.1) 0px,
                    rgba(0, 255, 255, 0.1) 20px,
                    rgba(0, 255, 0, 0.1) 20px,
                    rgba(0, 255, 0, 0.1) 40px
                ),
                radial-gradient(
                    circle at 50% 50%,
                    rgba(32, 196, 232, 0.3) 0%,
                    rgba(76, 201, 240, 0.1) 100%
                );
            background-blend-mode: normal, overlay, overlay;
            animation: aurora 15s linear infinite;
            mix-blend-mode: overlay;
            pointer-events: none;
        }
        .bg::before, .bg::after {
            content: '';
            position: absolute;
            inset: 0;
            transition: opacity 1.2s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 2;
        }
        .bg::before {
            background: linear-gradient(to bottom, rgba(0,0,0,0) 0%, rgba(0,0,0,0.85) 100%);
            opacity: 1;
        }
        .bg::after {
            background: linear-gradient(to top, rgba(0,0,0,0) 0%, rgba(0,0,0,0.85) 100%);
            opacity: 0;
        }
        .bg.inverted::before { opacity: 0; }
        .bg.inverted::after { opacity: 1; }

        /* ── Utility ─────────────────────────────────── */
        .container {
            max-width: var(--container);
            margin: 0 auto;
            padding: 0 32px;
        }
        .text-blue { color: #009bd9; }
        .text-yellow { color: #fcd200; }

        /* ── Scroll Animation ────────────────────────── */
        .reveal {
            opacity: 0;
            transform: translateY(32px);
            transition: opacity 0.7s cubic-bezier(.22,1,.36,1), transform 0.7s cubic-bezier(.22,1,.36,1);
        }
        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .reveal-d1 { transition-delay: .1s; }
        .reveal-d2 { transition-delay: .2s; }
        .reveal-d3 { transition-delay: .3s; }
        .reveal-d4 { transition-delay: .4s; }

        /* ── Sticky Nav ──────────────────────────────── */
        .top-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 40px;
            transition: all .35s ease;
        }
        .top-bar.scrolled {
            padding: 14px 40px;
            background: rgba(5,5,5,0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--card-border);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .brand img { width: 30px; height: 30px; object-fit: contain; }
        .brand span { font-family: 'Press Start 2P', cursive; font-size: .7rem; font-weight: 400; letter-spacing: 0; line-height: 1.5; padding-top: 4px; }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 32px;
        }
        .nav-links a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: .85rem;
            font-weight: 400;
            color: var(--text-muted);
            text-decoration: none;
            transition: color .2s;
        }
        .nav-links a:hover { color: var(--text); }
        .top-bar .nav-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: inherit;
            font-size: .8rem;
            font-weight: 600;
            color: #ffffff !important;
            background-color: #009bd9 !important;
            border: none;
            border-radius: 10px;
            padding: 10px 24px;
            text-decoration: none;
            cursor: pointer;
            transition: all .2s ease;
            visibility: visible;
            opacity: 1;
        }
        .top-bar .nav-cta:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(0, 155, 217, 0.4);
            background-color: #0089c4 !important;
        }


        /* ── Dropdown ────────────────────────────────── */
        .dropdown {
            position: relative;
            display: inline-flex;
            align-items: center;
            height: 100%;
        }
        .dropdown-btn {
            background: none;
            border: none;
            font-size: .85rem;
            font-weight: 400;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0;
            font-family: inherit;
            transition: color .2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .dropdown-btn svg { width: 14px; height: 14px; opacity: 0.6; transition: transform 0.25s ease; }
        .dropdown:hover .dropdown-btn { color: var(--text); }
        .dropdown:hover .dropdown-btn svg { opacity: 1; transform: rotate(180deg); }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%) translateY(12px);
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 6px;
            min-width: 170px;
            box-shadow: 0 16px 32px -8px rgba(0,0,0,0.4);
            opacity: 0;
            visibility: hidden;
            transition: all 0.25s cubic-bezier(0.2, 0.8, 0.2, 1);
            z-index: 1000;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            pointer-events: none;
        }
        .dropdown-menu::before {
            content: '';
            position: absolute;
            top: -20px;
            left: 0;
            right: 0;
            height: 20px;
        }
        .dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
            pointer-events: auto;
        }
        .dropdown-item {
            display: block;
            padding: 9px 16px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: .85rem;
            border-radius: 8px;
            transition: all 0.15s;
            text-align: left;
        }
        .dropdown-item:hover {
            color: var(--text);
            background: rgba(255,255,255,0.06);
        }

        /* ── Hero ─────────────────────────────────────── */
        .hero {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 120px 0 80px;
        }
        .hero-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 80px;
            width: 100%;
        }
        .hero-text {
            flex: 1;
            text-align: left;
        }
        .hero h1 {
            font-size: 4rem;
            font-weight: 800;
            letter-spacing: -.04em;
            line-height: 1.08;
            margin-bottom: 24px;
            max-width: 640px;
        }
        .hero .subtitle {
            font-size: 1.2rem;
            font-weight: 300;
            line-height: 1.6;
            color: var(--text-muted);
            max-width: 500px;
        }
        .hero-actions {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .hero-cta {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 20px;
        }
        .btn-solid {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 36px;
            font-size: .95rem;
            font-weight: 600;
            font-family: inherit;
            color: var(--bg);
            background: var(--text);
            border: none;
            border-radius: 14px;
            text-decoration: none;
            cursor: pointer;
            transition: all .25s ease;
            box-shadow: 0 4px 24px rgba(0,0,0,.3);
        }
        .btn-solid:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 32px rgba(255,255,255,.15);
        }
        .btn-solid svg { width: 18px; height: 18px; }
        .btn-outline {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 16px 28px;
            font-size: .95rem;
            font-weight: 500;
            font-family: inherit;
            color: rgba(255,255,255,.8);
            background: var(--card-bg);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 14px;
            text-decoration: none;
            cursor: pointer;
            transition: all .25s ease;
            backdrop-filter: blur(8px);
        }
        .btn-outline svg { width: 18px; height: 18px; }
        .btn-outline:hover {
            color: var(--text);
            border-color: rgba(255,255,255,.35);
            background: rgba(255,255,255,.08);
            transform: translateY(-3px);
        }

        /* Hero floating badges */
        .hero-badges {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            font-size: .78rem;
            font-weight: 500;
            color: rgba(255,255,255,.75);
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 100px;
            backdrop-filter: blur(8px);
        }
        .hero-badge svg { width: 16px; height: 16px; color: var(--primary); }

        @keyframes logoFloat {
            from { transform: translateY(0); }
            to   { transform: translateY(-10px); }
        }

        /* ── Section Base ────────────────────────────── */
        section {
            position: relative;
            z-index: 2;
            padding: var(--section-gap) 0;
        }
        .section-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #fff;
            margin-bottom: 16px;
        }
        .section-label .bar {
            display: inline-block;
            width: 24px;
            height: 2px;
            background: #fff;
            border-radius: 2px;
        }
        .section-heading {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -.03em;
            line-height: 1.15;
            margin-bottom: 20px;
        }
        .section-desc {
            font-size: 1.05rem;
            font-weight: 300;
            color: var(--text-muted);
            line-height: 1.7;
            max-width: 560px;
            margin-bottom: 56px;
        }

        /* ── Why Section ─────────────────────────────── */
        .why-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }
        .why-card {
            padding: 40px 32px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            transition: all .3s ease;
        }
        .why-card:hover {
            border-color: var(--card-hover-border);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -12px rgba(0,0,0,.4);
        }
        .why-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 16px;
            margin-bottom: 24px;
            color: var(--primary);
            background: none;
            border: none;
        }
        .why-icon svg { width: 26px; height: 26px; }
        .why-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .why-card h3 {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .why-card p {
            font-size: .9rem;
            color: var(--text-muted);
            line-height: 1.65;
        }

        /* ── Features Section ────────────────────────── */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
        .feature-card {
            padding: 40px 36px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            transition: all .3s ease;
            display: flex;
            flex-direction: column;
        }
        .feature-card:hover {
            border-color: var(--card-hover-border);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -12px rgba(0,0,0,.4);
        }
        .feature-card.featured {
            grid-column: span 2;
            flex-direction: row;
            align-items: center;
            gap: 48px;
            background: linear-gradient(rgba(16, 25, 38, 0.82), rgba(16, 25, 38, 0.82)), url('assets/banner/new_characters.webp') 25% center/cover no-repeat;
            border-color: var(--card-border);
            position: relative;
            overflow: hidden;
        }
        .feature-card.featured .feature-text { flex: 1; }
        .feature-card.featured .feature-visual {
            flex: 0 0 220px;
            height: 180px;
            background: rgba(var(--primary-rgb),0.06);
            border: 1px solid rgba(var(--primary-rgb),0.12);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .feature-card.featured .feature-visual svg { width: 64px; height: 64px; color: var(--primary); opacity: .5; }
        .feature-emoji {
            font-size: 2rem;
            margin-bottom: 20px;
            display: block;
        }
        .feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: fit-content;
            height: 48px;
            margin-bottom: 20px;
            color: var(--primary);
        }
        .feature-card:not(.featured) .feature-icon {
            width: 48px;
            background: none;
            border: none;
            border-radius: 14px;
        }
        .feature-icon svg { width: 22px; height: 22px; }
        .feature-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .feature-card h3 {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .feature-card p {
            font-size: .9rem;
            color: var(--text-muted);
            line-height: 1.65;
        }
        .feature-tag {
            display: inline-block;
            margin-top: 16px;
            padding: 5px 12px;
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .03em;
            color: #ffffff;
            background: #10b981;
            border-radius: 6px;
        }

        /* ── How It Works ────────────────────────────── */
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            counter-reset: step;
        }
        .step-card {
            counter-increment: step;
            padding: 36px 28px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            text-align: center;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            transition: all .3s ease;
            position: relative;
        }
        .step-card:hover {
            border-color: var(--card-hover-border);
            transform: translateY(-4px);
        }
        .step-num {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 20px;
            background: linear-gradient(180deg, rgba(var(--primary-rgb),0.6) 0%, rgba(var(--primary-rgb),0.15) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .step-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            border-radius: 16px;
            margin-bottom: 20px;
            background: rgba(var(--primary-rgb), 0.08);
            border: 1px solid rgba(var(--primary-rgb), 0.12);
            color: var(--primary);
        }
        .step-icon svg { width: 24px; height: 24px; }
        .step-card h3 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .step-card p {
            font-size: .82rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* ── CTA Banner ──────────────────────────────── */
        .cta-banner {
            position: relative;
            z-index: 2;
            padding: 100px 0;
        }
        .cta-inner {
            text-align: center;
            padding: 80px 48px;
            background: linear-gradient(135deg, rgba(var(--primary-rgb),0.12) 0%, rgba(var(--primary-rgb),0.04) 100%);
            border: 1px solid rgba(var(--primary-rgb),0.15);
            border-radius: 28px;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        .cta-inner h2 {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -.03em;
            margin-bottom: 16px;
        }
        .cta-inner p {
            font-size: 1.05rem;
            color: var(--text-muted);
            margin-bottom: 40px;
            max-width: 460px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.7;
        }
        .btn-glow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 18px 42px;
            font-size: 1rem;
            font-weight: 700;
            font-family: inherit;
            color: #fff;
            background: var(--primary);
            border: none;
            border-radius: 14px;
            text-decoration: none;
            cursor: pointer;
            transition: all .25s ease;
            box-shadow: 0 4px 30px rgba(var(--primary-rgb), 0.4);
        }
        .btn-glow:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 40px rgba(var(--primary-rgb), 0.5);
        }
        .btn-glow svg { width: 18px; height: 18px; }

        /* ── Footer ──────────────────────────────────── */
        footer {
            position: relative;
            z-index: 10;
            background: rgba(5,5,5,0.95);
            border-top: 1px solid var(--card-border);
            padding-top: 64px;
        }
        .footer-content {
            max-width: var(--container);
            margin: 0 auto;
            padding: 0 32px;
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 1fr;
            gap: 40px;
            padding-bottom: 60px;
        }
        .footer-brand .f-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .footer-brand .f-logo img { width: 28px; height: 28px; }
        .footer-brand .f-logo span { font-weight: 700; font-size: 1rem; }
        .footer-brand p {
            font-size: .88rem;
            color: var(--text-dim);
            line-height: 1.7;
            margin-bottom: 24px;
        }
        .footer-col h4 {
            font-size: .85rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 20px;
        }
        .footer-col ul { list-style: none; }
        .footer-col ul li { margin-bottom: 12px; }
        .footer-col ul li a {
            color: var(--text-dim);
            text-decoration: none;
            font-size: .88rem;
            transition: color .2s;
        }
        .footer-col ul li a:hover { color: var(--text); }
        .footer-bottom {
            border-top: 1px solid var(--card-border);
            padding: 24px 32px;
            text-align: center;
            background: rgba(0,0,0,.3);
        }
        .footer-bottom p {
            font-size: .78rem;
            color: var(--text-dim);
        }
        .footer-bottom a {
            color: var(--text-muted);
            text-decoration: none;
        }
        .footer-bottom a:hover { color: var(--text); text-decoration: underline; }

        /* ── Divider ─────────────────────────────────── */
        .section-divider {
            width: 100%;
            max-width: var(--container);
            margin: 0 auto;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--card-border), transparent);
        }

        /* ── Animations ──────────────────────────────── */
        @keyframes heroFadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes heroFadeInCenter {
            from { opacity: 0; transform: translateX(-50%) translateY(30px); }
            to   { opacity: 1; transform: translateX(-50%) translateY(0); }
        }
        .hero-anim { animation: heroFadeIn .9s cubic-bezier(.22,1,.36,1) both; }
        .hero-anim-center { animation: heroFadeInCenter .9s cubic-bezier(.22,1,.36,1) both; }
        .hero-anim-d1 { animation-delay: .15s; }
        .hero-anim-d2 { animation-delay: .3s; }
        .hero-anim-d3 { animation-delay: .45s; }
        .hero-anim-d4 { animation-delay: .6s; }
        .hero-anim-d5 { animation-delay: .75s; }

        /* ── Scroll Indicator ────────────────────────── */
        .scroll-indicator {
            position: absolute;
            bottom: 40px;
            left: 50%;
            color: var(--text-muted);
            text-decoration: none;
            transition: color .3s ease;
            z-index: 5;
        }
        .scroll-indicator:hover { color: var(--text); }
        .mouse {
            width: 24px;
            height: 38px;
            border: 2px solid currentColor;
            border-radius: 14px;
            position: relative;
        }
        .wheel {
            width: 3px;
            height: 6px;
            background: currentColor;
            border-radius: 2px;
            position: absolute;
            top: 6px;
            left: 50%;
            transform: translateX(-50%);
            animation: scrollWheel 2s ease-in-out infinite alternate;
        }
        @keyframes scrollWheel {
            0%   { transform: translate(-50%, 0); opacity: 1; }
            100% { transform: translate(-50%, 8px); opacity: 0.3; }
        }

        /* ── Responsive ──────────────────────────────── */
        @media (max-width: 900px) {
            :root { --section-gap: 80px; }
            .hero-inner { flex-direction: column; text-align: center; gap: 48px; }
            .hero-text { text-align: center; }
            .hero-text { text-align: center; }
            .hero h1 { font-size: 2.8rem; margin: 0 auto 24px; }
            .hero .subtitle { margin: 0 auto; }
            .hero-cta { flex-direction: row; justify-content: center; }
            .section-heading { font-size: 2rem; }
            .why-grid { grid-template-columns: 1fr; }
            .features-grid { grid-template-columns: 1fr; }
            .feature-card.featured { grid-column: span 1; flex-direction: column; }
            .feature-card.featured .feature-visual { flex: none; width: 100%; height: 140px; }
            .steps-grid { grid-template-columns: repeat(2, 1fr); }
            .footer-content { grid-template-columns: 1fr 1fr; gap: 40px 20px; }
            .footer-brand { grid-column: span 2; }
            .nav-links { display: none; }
        }

        @media (max-width: 600px) {
            :root { --section-gap: 64px; }
            .top-bar { padding: 16px 20px; }
            .hero { padding: 100px 20px 60px; }
            .hero h1 { font-size: 2.1rem; }
            .hero .subtitle { font-size: .95rem; }
            .hero-cta { flex-direction: column; width: 100%; }
            .btn-solid, .btn-outline { 
                width: 100%; 
                justify-content: center;
                padding: 12px 20px;
                font-size: .9rem;
            }
            .hero-badges { flex-direction: column; align-items: center; }
            .section-heading { font-size: 1.7rem; }
            .steps-grid { grid-template-columns: 1fr; }
            .cta-inner { padding: 48px 24px; }
            .cta-inner h2 { font-size: 1.8rem; }
            .footer-content { display: flex; flex-direction: column; gap: 32px; }
            .footer-brand { grid-column: auto; }
            .container { padding: 0 20px; }
        }
    </style>
</head>
<body>

    <!-- Page Load Overlay -->
    <div class="page-loader-overlay" id="pageLoader">
        <div class="loader"></div>

    </div>

    <!-- Ambient Background -->
    <div class="bg">
        <div class="aurora"></div>
    </div>

    <!-- ─── Sticky Navigation ─── -->
    <nav class="top-bar" id="topBar">
        <div class="brand">
            <img src="assets/menu/ww_logo_main.webp" alt="Word Weavers">
            <span>Word Weavers</span>
        </div>
        <div class="nav-links">
            <a href="#">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                Home
            </a>
            <a href="#features">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                Features
            </a>
            
            <div class="dropdown">
                <button class="dropdown-btn">
                    Resources
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div class="dropdown-menu">
                    <a href="docs/documentation.php" class="dropdown-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        Documentation
                    </a>
                    <a href="docs/changelog.php" class="dropdown-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        Changelog
                    </a>
                    <a href="docs/support.php" class="dropdown-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        Support
                    </a>
                </div>
            </div>

            <a href="main.php" class="nav-cta">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                Login / Register
            </a>
        </div>
        
        <button class="hamburger-btn" id="hamburgerBtn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </nav>

    <!-- ─── Hero Section ─── -->
    <section class="hero">
        <div class="container hero-inner">
            <div class="hero-text hero-anim">
                <h1 class="hero-anim-d1">
                    Master English<br>Through <span class="text-blue">Interactive</span> <span class="text-yellow">Play</span>
                </h1>
                <p class="subtitle hero-anim-d2">
                    Empowering the next generation of learners through gamified education.
                </p>
            </div>
            
            <div class="hero-actions hero-anim-d3">
                <div class="hero-cta">
                    <a href="main.php" class="btn-solid">
                        Login / Register
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                        </svg>
                    </a>
                    <a href="#features" class="btn-outline">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A11.952 11.952 0 0 1 12 15c-2.998 0-5.74-1.1-7.843-2.918m0 0c-.185-.72-.284-1.475-.284-2.253 0-.778.099-1.533.284-2.253" />
                        </svg>
                        Explore Features
                    </a>
                </div>
            </div>
        </div>

        <a href="#why" class="scroll-indicator hero-anim-center hero-anim-d5">
            <div class="mouse">
                <div class="wheel"></div>
            </div>
        </a>
    </section>

    <!-- ─── Why Section ─── -->
    <div class="section-divider"></div>
    <section id="why">
        <div class="container">
            <div class="reveal">
                <span class="section-label"><span class="bar"></span> Why Word Weavers?</span>
                <h2 class="section-heading">Why Learn with<br><span class="text-blue">Word</span> <span class="text-yellow">Weavers?</span></h2>
                <p class="section-desc">Our platform transforms the way students learn English by combining proven educational frameworks with the thrill of gaming.</p>
            </div>

            <div class="why-grid">
                <div class="why-card reveal reveal-d1">
                    <div class="why-icon">
                        <img src="../MainGame/vocabworld/assets/menu/playsys.png" alt="Gamified Learning">
                    </div>
                    <h3>Gamified Learning</h3>
                    <p>Explore various games that teaches new vocabulary, grammar rules, and reading comprehension skills.</p>
                </div>

                <div class="why-card reveal reveal-d2">
                    <div class="why-icon">
                        <img src="../MainGame/vocabworld/assets/menu/vocabsys.png" alt="Classroom-based Learning">
                    </div>
                    <h3>Classroom-based Learning</h3>
                    <p>Bridge the gap between digital play and traditional education with curriculum-aligned lessons designed for the modern classroom.</p>
                </div>

                <div class="why-card reveal reveal-d3">
                    <div class="why-icon">
                        <img src="../assets/pixels/trophy.png" alt="Social & Fun">
                    </div>
                    <h3>Social & Fun</h3>
                    <p>Compete on global leaderboards, add friends, give cresents, and learn together in a vibrant community of learners.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── Features Section ─── -->
    <div class="section-divider"></div>
    <section id="features">
        <div class="container">
            <div class="reveal">
                <span class="section-label"><span class="bar"></span> Features</span>
                <h2 class="section-heading">Unique Features,<br><span class="text-blue">Built for</span> <span class="text-yellow">Learning</span></h2>
                <p class="section-desc">Everything you need to master English, all in one.</p>
            </div>

            <div class="features-grid">
                <!-- Featured Card -->
                <div class="feature-card featured reveal">
                    <div class="feature-text">
                        <div class="feature-icon">
                            <img src="../MainGame/vocabworld/assets/menu/vocab_new.png" alt="Vocabworld">
                        </div>
                        <h3>Vocabworld</h3>
                        <p>Explore a top-down educational RPG featuring character customization, dual currency systems with Essence & Shards, level-based progression, and multiple game worlds.</p>
                        <span class="feature-tag">NEW GAME</span>
                    </div>
                    <div class="feature-visual">
                        <video autoplay loop muted playsinline style="width: 100%; height: 100%; object-fit: cover; border-radius: 16px;">
                            <source src="../assets/menu/vocabworldloop.mp4" type="video/mp4">
                        </video>
                    </div>
                </div>

                <!-- Regular Cards -->
                <div class="feature-card reveal reveal-d1">
                    <div class="feature-icon">
                        <img src="../MainGame/vocabworld/assets/menu/vocabsys.png" alt="Curriculum Aligned">
                    </div>
                    <h3>Learn and Play</h3>
                    <p>Study curriculum-aligned lessons curated by teachers to build foundational knowledge before engaging in immersive educational gameplay.</p>
                </div>

                <div class="feature-card reveal reveal-d2">
                    <div class="feature-icon">
                        <img src="../MainGame/vocabworld/assets/menu/instructionicon.png" alt="Teacher Console">
                    </div>
                    <h3>Teacher Console</h3>
                    <p>Manage lessons and vocabulary wordbanks, monitor individual student GWA, and access real-time performance analytics from a dedicated dashboard.</p>
                </div>

                <div class="feature-card reveal reveal-d1">
                    <div class="feature-icon">
                        <img src="../assets/pixels/save.png" alt="Smart Analytics">
                    </div>
                    <h3>Smart Analytics</h3>
                    <p>Automatic GWA calculations, global leaderboard rankings, and detailed progress reports all in one place.</p>
                </div>

                <div class="feature-card reveal reveal-d2">
                    <div class="feature-icon">
                        <img src="../assets/pixels/sheild.png" alt="Secure Platform">
                    </div>
                    <h3>Secure Platform</h3>
                    <p>Industry-standard security with bcrypt password hashing, prepared SQL statements, OTP email verification, and secure session management.</p>
                </div>
            </div>
        </div>
    </section>


    <!-- ─── Footer ─── -->
    <footer>
        <div class="footer-content">
            <div class="footer-col footer-brand">
                <div class="f-logo">
                    <img src="../assets/menu/ww_logo_main.webp" alt="Word Weavers">
                    <span>Word Weavers</span>
                </div>
                <p>
                    Empowering the next generation of learners through gamified education. Developed by Group 3 Computer Science Seniors at Holy Cross College of Carigara Inc.
                </p>
            </div>

            <div class="footer-col">
                <h4>Platform</h4>
                <ul>
                    <li><a href="#features">Gamified Learning</a></li>
                    <li><a href="#features">Curriculum Alignment</a></li>
                    <li><a href="#features">Progress Tracking</a></li>
                    <li><a href="#features">For Teachers</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Resources</h4>
                <ul>
                    <li><a href="#">Student Guide</a></li>
                    <li><a href="#">Documentation</a></li>
                    <li><a href="#">System Requirements</a></li>
                    <li><a href="#">Support</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Legal</h4>
                <ul>
                    <li><a href="../LICENSE.md">License</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">Credits</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>
                &copy; <?php echo date('Y'); ?> Word Weavers. All rights reserved.
                <span style="margin: 0 10px; opacity: .3">|</span>
                Developed by <a href="https://github.com/frostjade71" target="_blank">The Group 3 Thesis Project from the College Seniors of HCCCI</a>
            </p>
        </div>
    </footer>

    <!-- ─── Scripts ─── -->
    <script>
        // Page Loader
        window.addEventListener('load', () => {
            const loader = document.getElementById('pageLoader');
            if (loader) {
                setTimeout(() => {
                    loader.classList.add('hidden');
                }, 500);
            }
        });

        // Sticky nav and background inversion on scroll
        const nav = document.getElementById('topBar');
        const whySection = document.getElementById('why');
        const bgLayer = document.querySelector('.bg');

        window.addEventListener('scroll', () => {
            const scrollY = window.scrollY;
            nav.classList.toggle('scrolled', scrollY > 60);
            
            if (whySection && bgLayer) {
                // Invert background once we reach the "Why" section
                const whyTop = whySection.offsetTop - 300; // Trigger slightly early for smoothness
                bgLayer.classList.toggle('inverted', scrollY > whyTop);
            }
        });

        // Scroll reveal with IntersectionObserver
        const revealEls = document.querySelectorAll('.reveal');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

        revealEls.forEach(el => observer.observe(el));
    </script>

    <!-- Mobile Sidebar -->
    <div class="mobile-nav-overlay" id="mobileNavOverlay"></div>
    <aside class="mobile-sidebar" id="mobileSidebar">
        <div class="sidebar-header">
            <img src="assets/menu/ww_logo_main.webp" alt="Word Weavers" style="height: 40px; width: auto; object-fit: contain;">
            <button class="close-btn" id="closeSidebar">&times;</button>
        </div>
        <nav class="mobile-nav-links">
            <a href="#">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                Home
            </a>
            <a href="#features">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                Features
            </a>
            <a href="docs/documentation.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Documentation
            </a>
            <a href="docs/changelog.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                Changelog
            </a>
            <a href="docs/support.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                Support
            </a>
            <a href="main.php" class="nav-cta">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                Login / Register
            </a>
        </nav>
    </aside>

    <script src="includes/mobile-sidebar.js"></script>
</body>
</html>
