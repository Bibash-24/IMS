
// =====================================
// INVENTORY MANAGEMENT SYSTEM
// SINGLE USER AUTHENTICATION
// =====================================

const USER_KEY = "ims_single_user";
const SESSION_KEY = "ims_current_user";

// =====================================
// MODAL FUNCTIONS
// =====================================

function openModal(id) {
    const modal = document.getElementById(id);

    if (modal) {
        modal.style.display = "flex";
        if (id === "login") {
            const emailInput = document.getElementById("loginEmail");
            const passInput = document.getElementById("loginPassword");
            if (emailInput && !emailInput.value) emailInput.value = "admin@ims.com";
            if (passInput && !passInput.value) passInput.value = "admin123";
        }
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);

    if (modal) {
        modal.style.display = "none";
    }
}

// Close modal when clicking outside
window.addEventListener("click", function(event) {
    if (event.target.classList.contains("modal")) {
        event.target.style.display = "none";
    }
});

// =====================================
// SHOW / HIDE PASSWORD
// =====================================

function togglePassword(passId, confirmId = null) {
    const password = document.getElementById(passId);

    if (password) {
        password.type =
            password.type === "password" ? "text" : "password";
    }

    if (confirmId) {
        const confirmPassword =
            document.getElementById(confirmId);

        if (confirmPassword) {
            confirmPassword.type =
                confirmPassword.type === "password"
                    ? "text"
                    : "password";
        }
    }
}

// =====================================
// PASSWORD HASHING
// =====================================

async function hashPassword(password) {

    if (!window.crypto || !window.crypto.subtle) {
        throw new Error(
            "Secure hashing unavailable. Open this website using GitHub Pages HTTPS."
        );
    }

    const encodedPassword =
        new TextEncoder().encode(password);

    const hash = await crypto.subtle.digest(
        "SHA-256",
        encodedPassword
    );

    return Array.from(new Uint8Array(hash))
        .map(byte => byte.toString(16).padStart(2, "0"))
        .join("");
}

// =====================================
// DISPLAY MESSAGES
// =====================================

function showMessage(element, message, type) {
    if (!element) return;

    element.textContent = message;
    element.style.display = "block";
}

// =====================================
// SIGNUP FUNCTION
// =====================================

async function handleSignup(event) {

    event.preventDefault();

    const name =
        document.getElementById("signupName").value.trim();

    const email =
        document.getElementById("signupEmail").value.trim().toLowerCase();

    const phone =
        document.getElementById("signupPhone").value.trim();

    const password =
        document.getElementById("signupPassword").value;

    const confirmPassword =
        document.getElementById("signupCPassword").value;

    const error = document.getElementById("signupError");
    const success = document.getElementById("signupSuccess");

    error.textContent = "";
    success.textContent = "";

    if (!name || !email || !password) {
        error.textContent = "Please fill in all required fields.";
        return;
    }

    if (password.length < 6) {
        error.textContent =
            "Password must contain at least 6 characters.";
        return;
    }

    if (password !== confirmPassword) {
        error.textContent = "Passwords do not match!";
        return;
    }

    // Only one account is allowed
    if (localStorage.getItem(USER_KEY)) {
        error.textContent =
            "An account already exists! Please login instead.";
        return;
    }

    try {

        const hashedPassword = await hashPassword(password);

        const user = {
            name: name,
            email: email,
            phone: phone,
            password: hashedPassword,
            createdAt: new Date().toISOString()
        };

        localStorage.setItem(
            USER_KEY,
            JSON.stringify(user)
        );

        document.getElementById("signupForm").reset();

        success.textContent =
            "Account created successfully! Please login.";

    } catch (err) {

        error.textContent = err.message;
        console.error("Signup error:", err);

    }
}

// =====================================
// DEFAULT SYSTEM ACCOUNTS
// =====================================

const DEFAULT_ACCOUNTS = {
    "admin@ims.com": {
        name: "System Administrator",
        passwordPlain: "admin123",
        passwordHash: "240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9",
        role: "admin"
    },
    "staff@ims.com": {
        name: "Store Staff",
        passwordPlain: "staff123",
        passwordHash: "8528bb23ce2ebdd5a5eaec8c9da0fd06c126d4052ca5f653556093406206cbfd",
        role: "staff"
    }
};

// =====================================
// LOGIN FUNCTION
// =====================================

async function handleLogin(event) {

    event.preventDefault();

    const email =
        document.getElementById("loginEmail").value.trim().toLowerCase();

    const password =
        document.getElementById("loginPassword").value;

    const error = document.getElementById("loginError");
    const success = document.getElementById("loginSuccess");

    error.textContent = "";
    success.textContent = "";

    if (!email || !password) {
        error.textContent = "Please enter both email and password.";
        return;
    }

    // 1. Check built-in system accounts
    if (DEFAULT_ACCOUNTS[email]) {
        const acc = DEFAULT_ACCOUNTS[email];
        let match = (password === acc.passwordPlain);

        if (!match) {
            try {
                const hashed = await hashPassword(password);
                if (hashed === acc.passwordHash) match = true;
            } catch (e) {}
        }

        if (match) {
            const session = {
                name: acc.name,
                email: email,
                role: acc.role,
                loginTime: new Date().toISOString()
            };
            const user = {
                name: acc.name,
                email: email,
                phone: "9800000000",
                password: acc.passwordHash,
                createdAt: new Date().toISOString()
            };

            localStorage.setItem(SESSION_KEY, JSON.stringify(session));
            if (!localStorage.getItem(USER_KEY)) {
                localStorage.setItem(USER_KEY, JSON.stringify(user));
            }

            success.textContent = "Login successful! Redirecting to dashboard...";
            document.getElementById("loginForm").reset();

            setTimeout(function() {
                window.location.replace("./dashboard.html");
            }, 300);
            return;
        } else {
            error.textContent = "Incorrect password for " + email;
            return;
        }
    }

    // 2. Check user saved in localStorage
    const savedUser = localStorage.getItem(USER_KEY);
    if (savedUser) {
        try {
            const user = JSON.parse(savedUser);
            if (user && user.email && user.email.toLowerCase() === email) {
                let match = false;
                try {
                    const hashed = await hashPassword(password);
                    match = (hashed === user.password || password === user.password);
                } catch (e) {
                    match = (password === user.password);
                }

                if (match) {
                    const session = {
                        name: user.name || "User",
                        email: user.email,
                        loginTime: new Date().toISOString()
                    };
                    localStorage.setItem(SESSION_KEY, JSON.stringify(session));
                    success.textContent = "Login successful! Redirecting...";
                    document.getElementById("loginForm").reset();

                    setTimeout(function() {
                        window.location.replace("./dashboard.html");
                    }, 300);
                    return;
                } else {
                    error.textContent = "Incorrect password!";
                    return;
                }
            }
        } catch (err) {
            console.error("Parse user error:", err);
        }
    }

    // 3. Fallback: prompt user
    error.textContent = "No account found for this email. Use admin@ims.com or click Sign Up.";
}

// =====================================
// INITIALIZE APPLICATION
// =====================================

document.addEventListener("DOMContentLoaded", function() {

    const signupForm =
        document.getElementById("signupForm");

    const loginForm =
        document.getElementById("loginForm");

    if (!signupForm || !loginForm) {
        console.error("Login or signup form not found.");
        return;
    }

    // Connect signup form
    signupForm.addEventListener("submit", handleSignup);

    // Connect login form
    loginForm.addEventListener("submit", handleLogin);

    // Pre-seed default user if not yet initialized
    if (!localStorage.getItem(USER_KEY)) {
        const defaultAdmin = {
            name: "System Administrator",
            email: "admin@ims.com",
            phone: "9800000000",
            password: "240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9", // SHA-256 for admin123
            createdAt: new Date().toISOString()
        };
        localStorage.setItem(USER_KEY, JSON.stringify(defaultAdmin));
    }

    // Check existing login session
    const currentUser = localStorage.getItem(SESSION_KEY);

    if (currentUser) {
        window.location.replace("./dashboard.html");
    }

    console.log("IMS authentication initialized successfully.");

});
