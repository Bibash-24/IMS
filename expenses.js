
document.addEventListener("DOMContentLoaded", () => {
    // AUTHENTICATION
    const USER_KEY = "ims_single_user";
    const SESSION_KEY = "ims_current_user";
    const savedUser = localStorage.getItem(USER_KEY);
    const session = localStorage.getItem(SESSION_KEY);

    if (!savedUser || !session) {
        window.location.replace("index.html");
        return;
    }

    let user;
    try {
        user = JSON.parse(savedUser);
    } catch (error) {
        console.error("Invalid user data:", error);
        window.location.replace("index.html");
        return;
    }

    if (!user || !user.name) {
        window.location.replace("index.html");
        return;
    }

    const userId = String(user.id || user.name);
    const userName = user.name;
    const userAvatar = document.getElementById("userAvatar");

    if (userAvatar) {
        userAvatar.textContent = userName.charAt(0).toUpperCase();
    }

    // STORAGE
    const STORAGE_KEY = "ims_expenses";

    function getExpenses() {
        try {
            const expenses = JSON.parse(localStorage.getItem(STORAGE_KEY) || "[]");
            if (!Array.isArray(expenses)) return [];
            return expenses.filter(expense => String(expense.userId) === userId);
        } catch (error) {
            console.error("Expense loading error:", error);
            return [];
        }
    }

    function saveExpenses(expenses) {
        let allExpenses = [];
        try {
            allExpenses = JSON.parse(localStorage.getItem(STORAGE_KEY) || "[]");
        } catch (error) {
            console.error("Expense storage error:", error);
        }

        const otherUsers = Array.isArray(allExpenses)
            ? allExpenses.filter(expense => String(expense.userId) !== userId)
            : [];

        const currentUserExpenses = expenses.map(expense => ({
            ...expense,
            userId
        }));

        localStorage.setItem(STORAGE_KEY, JSON.stringify([
            ...otherUsers,
            ...currentUserExpenses
        ]));
    }

    // ELEMENTS
    const expenseForm = document.getElementById("expenseForm");
    const expenseId = document.getElementById("expenseId");
    const expenseTitle = document.getElementById("expenseTitle");
    const expenseCategory = document.getElementById("expenseCategory");
    const expenseAmount = document.getElementById("expenseAmount");
    const expenseDate = document.getElementById("expenseDate");
    const paymentMethod = document.getElementById("paymentMethod");
    const expenseReference = document.getElementById("expenseReference");
    const expenseDescription = document.getElementById("expenseDescription");
    const expenseTableBody = document.getElementById("expenseTableBody");
    const searchExpense = document.getElementById("searchExpense");
    const filterCategory = document.getElementById("filterCategory");
    const filterStartDate = document.getElementById("filterStartDate");
    const filterEndDate = document.getElementById("filterEndDate");
    const submitBtn = document.getElementById("submitBtn");

    if (!expenseForm || !expenseTableBody || !expenseDate) {
        console.error("Expenses page HTML elements are missing.");
        return;
    }

    let expenses = getExpenses();

    // HELPERS
    function escapeHTML(value) {
        return String(value ?? "").replace(/[&<>"']/g, char => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;"
        })[char]);
    }

    function formatMoney(amount) {
        return "Rs. " + Number(amount || 0).toLocaleString("en-IN", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function todayDate() {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, "0");
        const day = String(today.getDate()).padStart(2, "0");
        return `${year}-${month}-${day}`;
    }

    function generateId() {
        return "EXP-" + Date.now() + "-" + Math.random().toString(36).slice(2, 7);
    }

    // CURRENT DATE
    const currentDateElement = document.getElementById("currentDate");
    if (currentDateElement) {
        currentDateElement.textContent = new Date().toLocaleDateString("en-US", {
            weekday: "short",
            year: "numeric",
            month: "short",
            day: "numeric"
        });
    }

    expenseDate.max = todayDate();
    expenseDate.value = todayDate();

    // SUMMARY CARDS
    function updateSummary() {
        const total = expenses.reduce((sum, expense) => sum + Number(expense.amount || 0), 0);
        const currentMonth = new Date().getMonth();
        const currentYear = new Date().getFullYear();

        const monthly = expenses.filter(expense => {
            const date = new Date(expense.date + "T00:00:00");
            return date.getMonth() === currentMonth && date.getFullYear() === currentYear;
        }).reduce((sum, expense) => sum + Number(expense.amount || 0), 0);

        const average = expenses.length ? total / expenses.length : 0;

        const totalElement = document.getElementById("totalExpenses");
        const monthlyElement = document.getElementById("monthlyExpenses");
        const transactionsElement = document.getElementById("totalTransactions");
        const averageElement = document.getElementById("averageExpense");

        if (totalElement) totalElement.textContent = formatMoney(total);
        if (monthlyElement) monthlyElement.textContent = formatMoney(monthly);
        if (transactionsElement) transactionsElement.textContent = expenses.length;
        if (averageElement) averageElement.textContent = formatMoney(average);
    }

    // DISPLAY EXPENSES
    function renderExpenses() {
        const search = searchExpense ? searchExpense.value.toLowerCase().trim() : "";
        const category = filterCategory ? filterCategory.value : "";
        const startDate = filterStartDate ? filterStartDate.value : "";
        const endDate = filterEndDate ? filterEndDate.value : "";

        const filteredExpenses = expenses.filter(expense => {
            const matchesSearch = [
                expense.id,
                expense.title,
                expense.category,
                expense.payment,
                expense.reference,
                expense.description
            ].some(value => String(value || "").toLowerCase().includes(search));

            const matchesCategory = !category || expense.category === category;
            const matchesStart = !startDate || expense.date >= startDate;
            const matchesEnd = !endDate || expense.date <= endDate;

            return matchesSearch && matchesCategory && matchesStart && matchesEnd;
        });

        filteredExpenses.sort((a, b) =>
            b.date.localeCompare(a.date) || (b.createdAt || 0) - (a.createdAt || 0)
        );

        if (filteredExpenses.length === 0) {
            expenseTableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="empty-state">No matching expenses found.</td>
                </tr>
            `;
        } else {
            expenseTableBody.innerHTML = filteredExpenses.map(expense => `
                <tr>
                    <td>${escapeHTML(String(expense.id || "").slice(0, 12))}</td>
                    <td><strong>${escapeHTML(expense.title)}</strong></td>
                    <td>${escapeHTML(expense.category)}</td>
                    <td><strong>${formatMoney(expense.amount)}</strong></td>
                    <td>${escapeHTML(expense.payment)}</td>
                    <td>${escapeHTML(expense.date)}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="edit-btn" data-id="${escapeHTML(expense.id)}" type="button">Edit</button>
                            <button class="delete-btn" data-id="${escapeHTML(expense.id)}" type="button">Delete</button>
                        </div>
                    </td>
                </tr>
            `).join("");
        }

        const recordCount = document.getElementById("recordCount");
        if (recordCount) {
            recordCount.textContent = `Showing ${filteredExpenses.length} of ${expenses.length} expenses`;
        }
    }

    // ADD / UPDATE EXPENSE
    expenseForm.addEventListener("submit", event => {
        event.preventDefault();

        const title = expenseTitle.value.trim();
        const category = expenseCategory.value;
        const amount = Number(expenseAmount.value);
        const date = expenseDate.value;
        const payment = paymentMethod.value;
        const reference = expenseReference.value.trim();
        const description = expenseDescription.value.trim();

        if (!title || !category || !date || !payment) {
            alert("Please complete all required fields.");
            return;
        }

        if (!Number.isFinite(amount) || amount <= 0) {
            alert("Please enter a valid expense amount.");
            return;
        }

        if (date > todayDate()) {
            alert("Future expense dates are not allowed.");
            return;
        }

        const existingId = expenseId.value;

        if (existingId) {
            const index = expenses.findIndex(expense => expense.id === existingId);

            if (index === -1) {
                alert("Expense record not found.");
                return;
            }

            expenses[index] = {
                ...expenses[index],
                title,
                category,
                amount,
                date,
                payment,
                reference,
                description,
                updatedAt: Date.now()
            };

            alert("Expense updated successfully!");
        } else {
            expenses.push({
                id: generateId(),
                userId,
                title,
                category,
                amount,
                date,
                payment,
                reference,
                description,
                createdAt: Date.now()
            });

            alert("Expense added successfully!");
        }

        saveExpenses(expenses);
        expenseForm.reset();
        expenseId.value = "";
        expenseDate.value = todayDate();
        submitBtn.textContent = "+ Add Expense";
        updateSummary();
        renderExpenses();
    });

    // EDIT / DELETE
    expenseTableBody.addEventListener("click", event => {
        const editButton = event.target.closest(".edit-btn");
        const deleteButton = event.target.closest(".delete-btn");

        if (editButton) {
            const expense = expenses.find(item => item.id === editButton.dataset.id);
            if (!expense) return;

            expenseId.value = expense.id;
            expenseTitle.value = expense.title;
            expenseCategory.value = expense.category;
            expenseAmount.value = expense.amount;
            expenseDate.value = expense.date;
            paymentMethod.value = expense.payment;
            expenseReference.value = expense.reference || "";
            expenseDescription.value = expense.description || "";
            submitBtn.textContent = "Update Expense";

            expenseForm.scrollIntoView({
                behavior: "smooth",
                block: "start"
            });
        }

        if (deleteButton) {
            const id = deleteButton.dataset.id;
            const expense = expenses.find(item => item.id === id);
            if (!expense) return;

            if (!confirm(`Are you sure you want to delete "${expense.title}"?`)) return;

            expenses = expenses.filter(item => item.id !== id);
            saveExpenses(expenses);

            if (expenseId.value === id) {
                expenseForm.reset();
                expenseId.value = "";
                expenseDate.value = todayDate();
                submitBtn.textContent = "+ Add Expense";
            }

            updateSummary();
            renderExpenses();
            alert("Expense deleted successfully!");
        }
    });

    // SEARCH & FILTER
    if (searchExpense) searchExpense.addEventListener("input", renderExpenses);
    if (filterCategory) filterCategory.addEventListener("change", renderExpenses);
    if (filterStartDate) filterStartDate.addEventListener("change", renderExpenses);

    if (filterEndDate) {
        filterEndDate.addEventListener("change", () => {
            if (filterStartDate && filterStartDate.value && filterEndDate.value &&
                filterEndDate.value < filterStartDate.value) {
                alert("End date cannot be earlier than start date.");
                filterEndDate.value = "";
            }
            renderExpenses();
        });
    }

    const clearFilters = document.getElementById("clearFilters");
    if (clearFilters) {
        clearFilters.addEventListener("click", () => {
            if (searchExpense) searchExpense.value = "";
            if (filterCategory) filterCategory.value = "";
            if (filterStartDate) filterStartDate.value = "";
            if (filterEndDate) filterEndDate.value = "";
            renderExpenses();
        });
    }

    // RESET FORM
    const resetBtn = document.getElementById("resetBtn");
    if (resetBtn) {
        resetBtn.addEventListener("click", () => {
            setTimeout(() => {
                expenseId.value = "";
                expenseDate.value = todayDate();
                submitBtn.textContent = "+ Add Expense";
            }, 0);
        });
    }

    // EXPORT CSV
    const exportBtn = document.getElementById("exportBtn");
    if (exportBtn) {
        exportBtn.addEventListener("click", () => {
            if (expenses.length === 0) {
                alert("No expenses available to export.");
                return;
            }

            const headers = [
                "Expense ID",
                "Title",
                "Category",
                "Amount",
                "Payment Method",
                "Date",
                "Reference",
                "Description"
            ];

            function csvEscape(value) {
                return '"' + String(value ?? "").replace(/"/g, '""') + '"';
            }

            const rows = expenses.map(expense => [
                expense.id,
                expense.title,
                expense.category,
                expense.amount,
                expense.payment,
                expense.date,
                expense.reference,
                expense.description
            ]);

            const csvContent = [headers, ...rows]
                .map(row => row.map(csvEscape).join(","))
                .join("\r\n");

            const blob = new Blob(["\uFEFF" + csvContent], {
                type: "text/csv;charset=utf-8;"
            });

            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.href = url;
            link.download = "IMS_Expenses_" + todayDate() + ".csv";
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        });
    }

    // LOGOUT
    const logoutBtn = document.getElementById("logoutBtn");
    if (logoutBtn) {
        logoutBtn.addEventListener("click", () => {
            if (confirm("Are you sure you want to logout?")) {
                localStorage.removeItem(SESSION_KEY);
                window.location.replace("index.html");
            }
        });
    }

    // INITIALIZE
    updateSummary();
    renderExpenses();
});
