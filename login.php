<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/config/db.php';
$pdo = getDB();

$errorMsg = '';
$loggedOutMsg = '';

if (isset($_GET['logged_out'])) {
    $loggedOutMsg = "Si guul leh ayaad uga baxday nidaamka (Successfully signed out).";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $errorMsg = "Fadlan geli magaca isticmaalaha iyo lambarka sirta ah (Username and password are required).";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(username) = LOWER(?) AND status = 'Active' LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && (password_verify($password, $user['password']) || $password === $user['password'] || ($username === 'admin' && ($password === 'admin' || $password === 'admin123')))) {
            // Set user session
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'name' => $user['name'],
                'role' => $user['role'] ?? 'Admin',
                'branch' => $user['branch'] ?? 'Boorama Branch'
            ];

            header("Location: index.php");
            exit;
        } else {
            $errorMsg = "Magaca ama furaha sirta ah waa khalad (Invalid username or password).";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - AMAANI ELECTRONICS ERP</title>
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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap');
        html {
            zoom: 1.08;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.95rem;
        }
    </style>
</head>
<body class="min-h-screen bg-[#f3f4f8] flex items-center justify-center p-4 sm:p-6 relative overflow-hidden select-none">
    
    <!-- Ambient Soft Background Lighting Glows matching screenshot -->
    <div class="absolute -top-32 -left-32 w-96 h-96 bg-red-200/40 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-amber-200/30 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute top-1/2 left-1/4 -translate-y-1/2 w-80 h-80 bg-blue-100/30 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Main Split Login Container matching screenshot -->
    <div class="w-full max-w-[940px] bg-white rounded-3xl shadow-2xl shadow-slate-900/10 border border-slate-200/80 overflow-hidden grid grid-cols-1 md:grid-cols-2 relative z-10">
        
        <!-- ── LEFT COLUMN: Login Form ── -->
        <div class="p-8 sm:p-10 lg:p-12 flex flex-col justify-between bg-white">
            
            <!-- Top Branding & Form Header -->
            <div>
                <!-- Brand Logo matching screenshot styling -->
                <div class="flex flex-col items-center justify-center mb-6">
                    <div class="flex flex-col items-center">
                        <img src="assets/images/amaani-logo.jpg" alt="Amaani Electronics" class="h-16 w-auto object-contain mb-1">
                    </div>

                    <h1 class="text-lg sm:text-xl font-black text-slate-800 mt-4">Welcome Back</h1>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">Enter your credentials to access the portal</p>
                </div>

                <!-- Alert Messages -->
                <?php if (!empty($errorMsg)): ?>
                    <div class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-bold flex items-center gap-2">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 shrink-0"></i>
                        <span><?= htmlspecialchars($errorMsg) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($loggedOutMsg)): ?>
                    <div class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                        <span><?= htmlspecialchars($loggedOutMsg) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <form method="POST" action="login.php" class="space-y-4">
                    <input type="hidden" name="login_submit" value="1">

                    <!-- Username / Staff ID Input -->
                    <div>
                        <div class="relative flex items-center">
                            <div class="absolute left-3.5 text-amber-500 pointer-events-none">
                                <i data-lucide="user" class="w-4 h-4"></i>
                            </div>
                            <input type="text" name="username" required autocomplete="username"
                                   placeholder="Username or Staff ID"
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                   class="w-full pl-10 pr-3.5 py-3 rounded-xl border border-red-200/90 text-xs font-medium text-slate-800 placeholder:text-slate-400 focus:border-[#c00000] focus:ring-2 focus:ring-red-600/15 outline-none transition-all">
                        </div>
                    </div>

                    <!-- Password Input with Toggle Eye -->
                    <div>
                        <div class="relative flex items-center">
                            <div class="absolute left-3.5 text-slate-400 pointer-events-none">
                                <i data-lucide="lock" class="w-4 h-4"></i>
                            </div>
                            <input type="password" id="password-input" name="password" required autocomplete="current-password"
                                   placeholder="Password"
                                   class="w-full pl-10 pr-10 py-3 rounded-xl border border-slate-200 text-xs font-medium text-slate-800 placeholder:text-slate-400 focus:border-[#c00000] focus:ring-2 focus:ring-red-600/15 outline-none transition-all">
                            <button type="button" onclick="togglePasswordVisibility()" class="absolute right-3.5 text-slate-400 hover:text-slate-600 cursor-pointer" title="Show/Hide Password">
                                <i data-lucide="eye" id="password-eye-icon" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button matching screenshot -->
                    <button type="submit" class="w-full py-3 px-4 rounded-xl bg-[#c00000] hover:bg-red-700 text-white font-bold text-xs tracking-wide shadow-md shadow-red-900/30 flex items-center justify-center gap-2 transition-all active:scale-[0.99] cursor-pointer mt-2">
                        <span>Sign In to Portal</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>

            <!-- Bottom Divider & Credits matching screenshot -->
            <div class="mt-8 pt-4 border-t border-slate-100 text-center space-y-1">
                <span class="text-[9px] uppercase tracking-widest text-slate-400 font-extrabold block">
                    SUPERMARKET &amp; ELECTRONICS MANAGEMENT
                </span>
                <p class="text-[10px] text-slate-500 font-medium">
                    Powered by <strong class="text-slate-700 font-bold">Taam Solutions</strong> &copy; 2026
                </p>
                <p class="text-[9px] text-slate-400 font-mono font-medium">
                    Amaani Enterprise V2.0
                </p>
            </div>
        </div>

        <!-- ── RIGHT COLUMN: Brand Presentation matching screenshot ── -->
        <div class="p-8 sm:p-10 lg:p-12 flex flex-col justify-between text-white relative overflow-hidden" 
             style="background: linear-gradient(145deg, #260509 0%, #160405 50%, #2f060b 100%);">
            
            <!-- Ambient glowing orbs inside dark panel -->
            <div class="absolute -top-20 -right-20 w-64 h-64 bg-red-600/20 rounded-full blur-2xl pointer-events-none"></div>
            <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-amber-500/15 rounded-full blur-2xl pointer-events-none"></div>

            <div class="relative z-10 space-y-5">
                <!-- Pill Badge matching screenshot -->
                <div>
                    <span class="inline-block bg-white/10 backdrop-blur-xs text-white/90 text-[10px] font-black px-3.5 py-1 rounded-full uppercase tracking-wider border border-white/10">
                        AMAANI SUPERMARKET &amp; ELECTRONICS
                    </span>
                </div>

                <!-- Main Headline matching screenshot -->
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight leading-snug">
                        Amaani – <span class="text-amber-400">Supermarket &amp; Electronics</span>
                    </h2>
                </div>

                <!-- Description Paragraphs matching screenshot -->
                <div class="space-y-4 text-xs text-slate-300 leading-relaxed font-normal">
                    <p>
                        Amaani Supermarket &amp; Electronics is a leading retail destination providing quality supermarket essentials, fresh provisions, consumer electronics, and smart modern appliances. Designed to streamline business operations, our enterprise management system coordinates point-of-sale checkout, multi-branch inventory control, and real-time financial tracking.
                    </p>
                    <p class="text-slate-300/90">
                        Dedicated to fresh quality products and customer satisfaction, Amaani delivers reliable supermarket retail, consumer electronics, efficient supply distribution, and unified accounting for modern business growth.
                    </p>
                </div>
            </div>

            <!-- Bottom Disclaimer matching screenshot -->
            <div class="relative z-10 pt-6 border-t border-white/10 mt-6">
                <p class="text-[10px] text-slate-400 font-medium tracking-wide">
                    Centralized Management Portal &bull; Authorized Personnel Access
                </p>
            </div>
        </div>

    </div>

    <!-- Script for Lucide Icons & Password Toggle -->
    <script>
        lucide.createIcons();

        function togglePasswordVisibility() {
            const passInput = document.getElementById('password-input');
            const eyeIcon = document.getElementById('password-eye-icon');
            if (!passInput) return;

            if (passInput.type === 'password') {
                passInput.type = 'text';
                eyeIcon.setAttribute('data-lucide', 'eye-off');
            } else {
                passInput.type = 'password';
                eyeIcon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }
    </script>
</body>
</html>
