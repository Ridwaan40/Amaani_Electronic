<?php
if (!ob_get_level()) {
    ob_start();
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);

// Authentication Guard: require login for all protected ERP pages
if (!isset($_SESSION['user']) && $currentPage !== 'login.php') {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

// Global active branch filter
$currentBranch = isset($_GET['branch']) ? $_GET['branch'] : 'Boorama Branch';
$selectedYear = isset($_GET['year']) ? $_GET['year'] : 'All Years';
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : 'All Months';

// Overdue debt count
$overdueCountQuery = $pdo->query("SELECT COUNT(*) FROM sales WHERE paid_amount < total_sale AND return_date < CURDATE()");
$overdueCount = $overdueCountQuery->fetchColumn();

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AMAANI ELECTRONICS - ERP Management System</title>
    <!-- Pre-paint Zoom Enforcement -->
    <script>
        (function() {
            try {
                var savedZoom = localStorage.getItem('caalami_erp_zoom');
                if (savedZoom) {
                    document.documentElement.style.zoom = savedZoom;
                } else {
                    document.documentElement.style.zoom = '1.08';
                    localStorage.setItem('caalami_erp_zoom', '1.08');
                }
            } catch(e) {}
        })();
    </script>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontSize: {
                        'xs': ['0.84rem', { lineHeight: '1.25rem' }],
                        'sm': ['0.95rem', { lineHeight: '1.4rem' }],
                        'base': ['1.05rem', { lineHeight: '1.55rem' }],
                        'lg': ['1.18rem', { lineHeight: '1.7rem' }],
                        'xl': ['1.32rem', { lineHeight: '1.85rem' }],
                    },
                    colors: {
                        navy: {
                            DEFAULT: '#0B2A5B',
                            dark: '#071d40',
                            light: '#133a78'
                        },
                        crimson: {
                            DEFAULT: '#c00000',
                            dark: '#a00000',
                            light: '#e00000'
                        },
                        sidebar: {
                            bg: '#170406',
                            hover: '#26090c',
                            active: '#dc2626'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600;700&display=swap');
        html {
            font-size: 16px;
            zoom: 1.08;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.95rem;
            -webkit-font-smoothing: antialiased;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Slightly enhance readability of custom micro-text */
        .text-\[9px\] { font-size: 11px !important; }
        .text-\[10px\] { font-size: 12px !important; }
        .text-\[11px\] { font-size: 13px !important; }

        /* Sidebar Collapsed Mode Styles */
        #main-sidebar {
            transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        #main-sidebar.sidebar-collapsed {
            width: 4.85rem !important; /* ~77px */
        }
        #main-sidebar.sidebar-collapsed .sidebar-brand-text,
        #main-sidebar.sidebar-collapsed nav span:not(.sidebar-icon-holder),
        #main-sidebar.sidebar-collapsed nav i[data-lucide="chevron-down"],
        #main-sidebar.sidebar-collapsed nav i[data-lucide="chevron-right"],
        #main-sidebar.sidebar-collapsed .sidebar-section-title,
        #main-sidebar.sidebar-collapsed [id^="sub-"] {
            display: none !important;
        }
        #main-sidebar.sidebar-collapsed .sidebar-header-box {
            justify-content: center !important;
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }
        #main-sidebar.sidebar-collapsed nav a,
        #main-sidebar.sidebar-collapsed nav button {
            justify-content: center !important;
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }
        #main-sidebar.sidebar-collapsed nav a i,
        #main-sidebar.sidebar-collapsed nav button i:first-child {
            margin: 0 !important;
        }
        /* Custom scrollbars */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.35);
        }

        .sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-scroll::-webkit-scrollbar-track {
            background: #170406;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: #3b1116;
            border-radius: 9999px;
        }

        @media print {
            body * {
                visibility: hidden;
            }
            #printable-receipt, #printable-receipt * {
                visibility: visible;
            }
            #printable-receipt {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                background: white;
                color: black;
            }
        }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 min-h-screen antialiased selection:bg-[#c00000] selection:text-white">
<div class="min-h-screen flex w-full">
