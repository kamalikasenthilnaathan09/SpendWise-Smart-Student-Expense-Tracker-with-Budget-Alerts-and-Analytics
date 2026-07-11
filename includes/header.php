<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$user_id = $_SESSION['user_id'] ?? 0;
?>

<!-- Immediate Theme Configuration Script -->
<script>
    if ("<?php echo $_SESSION['theme'] ?? 'light'; ?>" === "dark") {
        document.body.classList.add("dark-theme");
    } else {
        document.body.classList.remove("dark-theme");
    }
</script>

<?php
// Dynamic Greetings & Title Configurations
$greeting_text = "";
$subtitle_text = "";

if ($current_page == 'dashboard.php') {
    $hour = intval(date('H'));
    if ($hour >= 5 && $hour < 12) {
        $greeting_text = "Good Morning, " . htmlspecialchars($_SESSION['username'] ?? 'User') . " 👋";
    } elseif ($hour >= 12 && $hour < 17) {
        $greeting_text = "Good Afternoon, " . htmlspecialchars($_SESSION['username'] ?? 'User') . " 👋";
    } else {
        $greeting_text = "Good Evening, " . htmlspecialchars($_SESSION['username'] ?? 'User') . " 👋";
    }
    $subtitle_text = "Let's manage your finances wisely today.";
} else {
    switch ($current_page) {
        case 'transactions.php':
            $greeting_text = "Transactions Registry";
            $subtitle_text = "Monitor and filter your transaction logs.";
            break;
        case 'analytics.php':
            $greeting_text = "Financial Insights";
            $subtitle_text = "Visualise your income and expenditure patterns.";
            break;
        case 'reports.php':
            $greeting_text = "Reports & Statements";
            $subtitle_text = "Export CSV spreadsheets or print tax statements.";
            break;
        case 'profile.php':
            $greeting_text = "Account Profile";
            $subtitle_text = "Manage your login details and overall statistics.";
            break;
        case 'settings.php':
            $greeting_text = "System Settings";
            $subtitle_text = "Configure currencies, monthly budgets, and targets.";
            break;
        default:
            $greeting_text = "SpendWise";
            $subtitle_text = "Personal Finance Manager";
            break;
    }
}

// Fetch dynamic notifications from database
$notifications = [];
$unread_count = 0;
if ($user_id > 0) {
    include_once("db.php");
    include_once("includes/functions.php");
    
    // Fetch count of unread
    $unread_q = mysqli_query($conn, "SELECT COUNT(*) AS count FROM notifications WHERE user_id = $user_id AND is_read = 0");
    if ($unread_q) {
        $unread_count = mysqli_fetch_assoc($unread_q)['count'] ?? 0;
    }
    
    // Fetch latest 10 notifications
    $notif_res = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id = $user_id ORDER BY id DESC LIMIT 10");
    if ($notif_res) {
        while ($row = mysqli_fetch_assoc($notif_res)) {
            $notifications[] = $row;
        }
    }
}
?>

<!-- Real-time Toast container -->
<div class="toast-container" id="toastContainer"></div>

<div class="top-header">

    <div class="header-left">
        <h2><?php echo $greeting_text; ?></h2>
        <p class="welcome-subtitle"><?php echo $subtitle_text; ?></p>
        <p style="color: #9ca3af; font-size: 13px; margin-top: 5px; font-weight: 500;">
            <?php echo date("l, d F Y"); ?>
        </p>
    </div>

    <div class="header-right">

        <!-- Search Bar -->
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass" style="color: #9ca3af;"></i>
            <input
                type="text"
                id="searchBar"
                placeholder="Search transactions..."
            >
        </div>

        <!-- Theme Toggle Switch -->
        <button class="notification" id="themeToggleBtn" title="Toggle Theme" style="font-size: 16px;">
            <?php if (($_SESSION['theme'] ?? 'light') === 'dark'): ?>
                <i class="fa-solid fa-sun" style="color: #f59e0b;"></i>
            <?php else: ?>
                <i class="fa-solid fa-moon" style="color: #475569;"></i>
            <?php endif; ?>
        </button>

        <!-- Notification Bell & Dropdown Container -->
        <div class="notification-container">
            <button class="notification" id="bellBtn" title="Notifications">
                <i class="fa-regular fa-bell"></i>
                <?php if ($unread_count > 0): ?>
                    <span class="badge-dot"></span>
                <?php endif; ?>
            </button>
            
            <div class="notification-dropdown" id="notificationDropdown">
                <div class="notification-dropdown-header">
                    <span>Notifications (<?php echo $unread_count; ?>)</span>
                    <?php if ($unread_count > 0): ?>
                        <span id="markAllBtn" style="font-size: 11px; color: #2563eb; cursor: pointer; font-weight: 600;">Mark all read</span>
                    <?php endif; ?>
                </div>
                <div class="notification-dropdown-body" id="dropdownNotificationBody">
                    <?php if (count($notifications) > 0): ?>
                        <?php foreach ($notifications as $item): 
                            $type_class = "info-item";
                            $icon_class = "fa-circle-info";
                            
                            if ($item['type'] === 'budget_exceeded') {
                                $type_class = 'alert-item';
                                $icon_class = 'fa-triangle-exclamation';
                            } elseif ($item['type'] === 'budget_warning') {
                                $type_class = 'warning-item';
                                $icon_class = 'fa-circle-exclamation';
                            } elseif ($item['type'] === 'goal_reached') {
                                $type_class = 'success-item';
                                $icon_class = 'fa-circle-check';
                            } elseif ($item['type'] === 'transaction_added') {
                                $type_class = 'success-item';
                                $icon_class = 'fa-circle-check';
                            }
                        ?>
                            <div class="notification-dropdown-item <?php echo $type_class; ?>" 
                                 style="<?php echo ($item['is_read'] == 1) ? 'opacity: 0.65;' : ''; ?>" 
                                 onclick="markAsRead(<?php echo $item['id']; ?>, this)">
                                <i class="fa-solid <?php echo $icon_class; ?>"></i>
                                <div class="notification-dropdown-item-details">
                                    <p><?php echo $item['message']; ?></p>
                                    <span><?php echo get_time_ago($item['created_at']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 30px; text-align: center; color: #9ca3af; font-size: 13px; font-weight: 500;">
                            No notifications logged.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Profile Avatar Box -->
        <div class="profile" onclick="window.location.href='profile.php'" style="cursor: pointer;">
            <div style="width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #2563eb, #9333ea); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; border: 2px solid white; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                <?php 
                $first_letter = strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1));
                echo htmlspecialchars($first_letter);
                ?>
            </div>
            <span style="font-weight: 600; color: #374151;">
                <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
            </span>
        </div>

    </div>

</div>

<!-- Synthetic sound generation and toast trigger JS logic -->
<script>
    // Synthesis Beep Generator
    function playNotificationSound(type) {
        try {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (!AudioContextClass) return;
            const audioCtx = new AudioContextClass();
            
            if (type === 'danger' || type === 'budget_exceeded') {
                // Double alerting warning chimes
                playBeep(audioCtx, 520, 0.08, 0);
                playBeep(audioCtx, 520, 0.12, 0.12);
            } else if (type === 'success' || type === 'goal_reached') {
                // Success Arpeggio
                playBeep(audioCtx, 261.63, 0.06, 0); // C4
                playBeep(audioCtx, 329.63, 0.06, 0.06); // E4
                playBeep(audioCtx, 392.00, 0.06, 0.12); // G4
                playBeep(audioCtx, 523.25, 0.15, 0.18); // C5
            } else {
                // Soft double info chime
                playBeep(audioCtx, 440, 0.08, 0); // A4
                playBeep(audioCtx, 554.37, 0.12, 0.08); // C#5
            }
        } catch (e) {
            console.warn("AudioContext block: ", e);
        }
    }

    function playBeep(ctx, frequency, duration, startTime) {
        const osc = ctx.createOscillator();
        const gainNode = ctx.createGain();
        
        osc.connect(gainNode);
        gainNode.connect(ctx.destination);
        
        osc.frequency.value = frequency;
        osc.type = 'sine';
        
        gainNode.gain.setValueAtTime(0, ctx.currentTime + startTime);
        gainNode.gain.linearRampToValueAtTime(0.12, ctx.currentTime + startTime + 0.02);
        gainNode.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + startTime + duration);
        
        osc.start(ctx.currentTime + startTime);
        osc.stop(ctx.currentTime + startTime + duration);
    }

    // Toast Generator
    function showToast(message, type = 'info') {
        const container = document.getElementById("toastContainer");
        if (!container) return;
        
        const toast = document.createElement("div");
        toast.className = `toast ${type}`;
        
        let icon = "fa-circle-info";
        if (type === 'success') icon = "fa-circle-check";
        else if (type === 'danger') icon = "fa-triangle-exclamation";
        else if (type === 'warning') icon = "fa-circle-exclamation";
        
        toast.innerHTML = `
            <i class="fa-solid ${icon} toast-icon"></i>
            <span>${message}</span>
        `;
        
        container.appendChild(toast);
        
        // entrance trigger
        toast.offsetHeight; 
        toast.classList.add("show");
        
        // play notification sound matching type
        playNotificationSound(type);
        
        // auto-dismiss
        setTimeout(() => {
            toast.classList.remove("show");
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 4500);
    }

    // Ajax marking actions
    function markAsRead(notifId, element) {
        fetch('mark_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: notifId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                element.style.opacity = '0.65';
                
                // Decrement count if positive
                const headerTitle = document.querySelector(".notification-dropdown-header span");
                if (headerTitle) {
                    const match = headerTitle.textContent.match(/\d+/);
                    if (match) {
                        let currentCount = parseInt(match[0]);
                        if (currentCount > 0) {
                            currentCount--;
                            headerTitle.textContent = `Notifications (${currentCount})`;
                            if (currentCount === 0) {
                                const badge = document.querySelector(".badge-dot");
                                if (badge) badge.remove();
                                const markAll = document.getElementById("markAllBtn");
                                if (markAll) markAll.remove();
                            }
                        }
                    }
                }
            }
        })
        .catch(err => console.error("Error marking read: ", err));
    }

    document.addEventListener("DOMContentLoaded", function() {
        // Toggle theme preference click handler
        const themeBtn = document.getElementById("themeToggleBtn");
        if (themeBtn) {
            themeBtn.onclick = function() {
                const isDark = document.body.classList.contains("dark-theme");
                const nextTheme = isDark ? "light" : "dark";
                
                if (nextTheme === "dark") {
                    document.body.classList.add("dark-theme");
                    themeBtn.innerHTML = '<i class="fa-solid fa-sun" style="color: #f59e0b;"></i>';
                } else {
                    document.body.classList.remove("dark-theme");
                    themeBtn.innerHTML = '<i class="fa-solid fa-moon" style="color: #475569;"></i>';
                }

                fetch('update_preference.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ theme: nextTheme })
                })
                .then(res => res.json())
                .then(data => {
                    if (typeof Chart !== 'undefined' && Chart.instances) {
                        Object.keys(Chart.instances).forEach(key => {
                            const chartInstance = Chart.instances[key];
                            if (chartInstance) {
                                const gridColor = nextTheme === 'dark' ? 'rgba(255, 255, 255, 0.08)' : '#f3f4f6';
                                const textColor = nextTheme === 'dark' ? '#cbd5e1' : '#6b7280';
                                
                                if (chartInstance.options.scales) {
                                    if (chartInstance.options.scales.x && chartInstance.options.scales.x.ticks) {
                                        chartInstance.options.scales.x.ticks.color = textColor;
                                    }
                                    if (chartInstance.options.scales.y) {
                                        if (chartInstance.options.scales.y.grid) chartInstance.options.scales.y.grid.color = gridColor;
                                        if (chartInstance.options.scales.y.ticks) chartInstance.options.scales.y.ticks.color = textColor;
                                    }
                                }
                                if (chartInstance.options.plugins && chartInstance.options.plugins.legend && chartInstance.options.plugins.legend.labels) {
                                    chartInstance.options.plugins.legend.labels.color = textColor;
                                }
                                chartInstance.update();
                            }
                        });
                    }
                })
                .catch(err => console.error("Theme toggle error: ", err));
            }
        }

        // Toggle dropdown click handlers
        const bellBtn = document.getElementById("bellBtn");
        const notifDropdown = document.getElementById("notificationDropdown");

        if (bellBtn && notifDropdown) {
            bellBtn.onclick = function(e) {
                e.stopPropagation();
                notifDropdown.classList.toggle("show");
            }

            document.onclick = function(e) {
                if (!bellBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
                    notifDropdown.classList.remove("show");
                }
            }
        }

        // Mark all as read button click handler
        const markAllBtn = document.getElementById("markAllBtn");
        if (markAllBtn) {
            markAllBtn.onclick = function(e) {
                e.stopPropagation();
                fetch('mark_read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ mark_all: true })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.querySelectorAll(".notification-dropdown-item").forEach(item => {
                            item.style.opacity = '0.65';
                        });
                        const badge = document.querySelector(".badge-dot");
                        if (badge) badge.remove();
                        markAllBtn.remove();
                        const headerTitle = document.querySelector(".notification-dropdown-header span");
                        if (headerTitle) headerTitle.textContent = "Notifications (0)";
                    }
                })
                .catch(err => console.error("Error marking all read: ", err));
            }
        }
    });
</script>

<!-- Flash notification rendering trigger -->
<?php if (isset($_SESSION['flash_notification'])): 
    $flash = $_SESSION['flash_notification'];
    $flash_class = 'info';
    if ($flash['type'] === 'budget_exceeded' || $flash['type'] === 'budget_warning') $flash_class = 'danger';
    elseif ($flash['type'] === 'goal_reached' || $flash['type'] === 'transaction_added') $flash_class = 'success';
?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        showToast("<?php echo htmlspecialchars($flash['message']); ?>", "<?php echo $flash_class; ?>");
    });
</script>
<?php 
    unset($_SESSION['flash_notification']);
endif; ?>