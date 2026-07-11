<?php
$current_page = basename($_SERVER['PHP_SELF']);
$modal_currency_symbol = "₹";
if (isset($_SESSION['currency'])) {
    if ($_SESSION['currency'] === 'USD') $modal_currency_symbol = "$";
    elseif ($_SESSION['currency'] === 'EUR') $modal_currency_symbol = "€";
    elseif ($_SESSION['currency'] === 'GBP') $modal_currency_symbol = "£";
}
?>
<div class="sidebar">

    <!-- Logo -->
    <div class="logo">
        <h2 style="font-weight: 700; letter-spacing: 1px; display: flex; align-items: center; justify-content: center; gap: 8px;">
            <span style="font-size: 28px;">💰</span> SpendWise
        </h2>
    </div>

    <!-- Features -->
    <h4 class="menu-title">MAIN MENU</h4>

    <ul class="menu">

        <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <a href="dashboard.php">
                <i class="fa-solid fa-house"></i>
                <span>DASHBOARD</span>
            </a>
        </li>

        <li class="<?php echo ($current_page == 'transactions.php') ? 'active' : ''; ?>">
            <a href="transactions.php">
                <i class="fa-solid fa-receipt"></i>
                <span>TRANSACTIONS</span>
            </a>
        </li>

        <li class="<?php echo ($current_page == 'analytics.php') ? 'active' : ''; ?>">
            <a href="analytics.php">
                <i class="fa-solid fa-chart-pie"></i>
                <span>ANALYTICS</span>
            </a>
        </li>

        <li class="<?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">
            <a href="reports.php">
                <i class="fa-solid fa-file-lines"></i>
                <span>REPORTS</span>
            </a>
        </li>

        <li class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
            <a href="profile.php">
                <i class="fa-solid fa-user-tie"></i>
                <span>PROFILE</span>
            </a>
        </li>

        <li class="<?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
            <a href="settings.php">
                <i class="fa-solid fa-gear"></i>
                <span>SETTINGS</span>
            </a>
        </li>

    </ul>

    <!-- Account -->
    <h4 class="menu-title account-title">ACCOUNT</h4>

    <div class="logout" style="margin-top: auto; padding: 20px;">
        <a href="logout.php">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>LOGOUT</span>
        </a>
    </div>

</div>

<!-- Global Floating Action Button (FAB) -->
<button class="fab" id="fabBtn" title="Add Transaction" type="button">
    <i class="fa-solid fa-plus"></i>
</button>

<!-- Global Add Transaction Modal -->
<div class="modal" id="transactionModal" style="display: none;">
    <div class="modal-content">
        <span class="close-btn" id="closeModalBtn">&times;</span>
        <h2 class="modal-title"><i class="fa-solid fa-plus-minus text-success"></i> Add Transaction</h2>
        
        <form action="save_transaction.php" method="POST" id="globalTransactionForm">
            <div class="form-group">
                <label for="modal_type">Transaction Type</label>
                <select name="type" id="modal_type" class="form-control" required>
                    <option value="expense">Expense</option>
                    <option value="income">Income</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="modal_title">Title / Description</label>
                <input type="text" name="title" id="modal_title" class="form-control" placeholder="e.g. Weekly Groceries, Monthly Salary" required>
            </div>
            
            <div class="form-group">
                <label for="modal_amount">Amount (<?php echo $modal_currency_symbol; ?>)</label>
                <input type="number" step="0.01" name="amount" id="modal_amount" class="form-control" placeholder="0.00" required>
            </div>
            
            <div class="form-group">
                <label for="modal_category">Category</label>
                <select name="category" id="modal_category" class="form-control" required>
                    <option value="Food">Food & Dining</option>
                    <option value="Travel">Travel & Fuel</option>
                    <option value="Shopping">Shopping</option>
                    <option value="Entertainment">Entertainment</option>
                    <option value="Utilities">Utilities & Bills</option>
                    <option value="Salary">Salary & Income</option>
                    <option value="Investment">Investments</option>
                    <option value="Others">Others</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="modal_transaction_date">Date</label>
                <input type="date" name="transaction_date" id="modal_transaction_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <button type="submit" class="btn-primary">Add Transaction</button>
        </form>
    </div>
</div>

<script>
    // Global Modal & FAB Interaction Logic
    document.addEventListener("DOMContentLoaded", function() {
        const modal = document.getElementById("transactionModal");
        const fabBtn = document.getElementById("fabBtn");
        const closeModalBtn = document.getElementById("closeModalBtn");
        
        // Listeners for page-specific trigger buttons (if they exist)
        const pageTriggerBtns = document.querySelectorAll("#openModalBtn, .openModalBtn");

        const showModal = function() {
            modal.style.display = "flex";
            setTimeout(() => {
                modal.classList.add("show");
            }, 10);
        };

        const hideModal = function() {
            modal.classList.remove("show");
            setTimeout(() => {
                modal.style.display = "none";
            }, 300);
        };

        if (fabBtn) {
            fabBtn.addEventListener("click", function(e) {
                e.preventDefault();
                showModal();
            });
        }

        if (closeModalBtn) {
            closeModalBtn.addEventListener("click", function(e) {
                e.preventDefault();
                hideModal();
            });
        }

        pageTriggerBtns.forEach(btn => {
            btn.addEventListener("click", function(e) {
                e.preventDefault();
                showModal();
            });
        });

        // Close on clicking backdrop
        window.addEventListener("click", function(event) {
            if (event.target === modal) {
                hideModal();
            }
        });
    });
</script>