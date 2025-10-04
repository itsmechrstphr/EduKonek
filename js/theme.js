class ThemeManager {
    constructor() {
        this.themeToggle = null;
        this.currentTheme = 'light';
        this.init();
    }

    init() {
        this.detectSystemPreference();
        this.loadUserPreference();
        this.applyTheme();
        this.createToggleButton();
        this.bindEvents();
    }

    detectSystemPreference() {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            this.currentTheme = 'dark';
        }
    }

    loadUserPreference() {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            this.currentTheme = savedTheme;
        }
    }

    saveUserPreference() {
        localStorage.setItem('theme', this.currentTheme);
    }

    applyTheme() {
        // List of pages to disable custom themes on (keep light for these)
        const disabledPages = [
            '/login.php',
            '/signup.php',
            '/admin_dashboard.php',
            '/dashboard.php',
            '/faculty_dashboard.php',
            '/student_dashboard.php'
        ];
        const currentPath = window.location.pathname.toLowerCase();

        // Check if current page is in disabledPages list
        const disableCustomTheme = disabledPages.some(page => currentPath.endsWith(page));

        if (disableCustomTheme) {
            document.body.setAttribute('data-theme', 'light');
        } else {
            document.body.setAttribute('data-theme', this.currentTheme);
        }
    }

    toggleTheme() {
        this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        this.applyTheme();
        this.saveUserPreference();
        this.updateToggleButton();
    }

    createToggleButton() {
        // Create toggle button for settings page
        const toggleContainer = document.createElement('div');
        toggleContainer.className = 'theme-toggle-container';
        toggleContainer.innerHTML = `
            <label class="theme-toggle">
                <input type="checkbox" id="theme-toggle-checkbox">
                <span class="slider"></span>
            </label>
            <span class="toggle-label">Dark Mode</span>
        `;

        // Add styles for the toggle
        const style = document.createElement('style');
        style.textContent = `
            .theme-toggle-container {
                display: flex;
                align-items: center;
                gap: 10px;
                margin: 20px 0;
            }

            .theme-toggle {
                position: relative;
                display: inline-block;
                width: 60px;
                height: 34px;
            }

            .theme-toggle input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: var(--color-border);
                transition: .4s;
                border-radius: 34px;
            }

            .slider:before {
                position: absolute;
                content: "";
                height: 26px;
                width: 26px;
                left: 4px;
                bottom: 4px;
                background-color: var(--color-card);
                transition: .4s;
                border-radius: 50%;
            }

            input:checked + .slider {
                background-color: var(--color-primary);
            }

            input:checked + .slider:before {
                transform: translateX(26px);
            }

            .toggle-label {
                font-weight: 500;
                color: var(--color-text-primary);
            }
        `;
        document.head.appendChild(style);

        this.themeToggle = toggleContainer;
        this.checkbox = toggleContainer.querySelector('#theme-toggle-checkbox');
        this.updateToggleButton();
    }

    updateToggleButton() {
        if (this.checkbox) {
            this.checkbox.checked = this.currentTheme === 'dark';
        }
    }

    bindEvents() {
        // Listen for system theme changes
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (!localStorage.getItem('theme')) {
                    this.currentTheme = e.matches ? 'dark' : 'light';
                    this.applyTheme();
                    this.updateToggleButton();
                }
            });
        }

        // Bind toggle button click
        if (this.checkbox) {
            this.checkbox.addEventListener('change', () => {
                this.toggleTheme();
            });
        }
    }

    getToggleElement() {
        return this.themeToggle;
    }

    setTheme(theme) {
        this.currentTheme = theme;
        this.applyTheme();
        this.saveUserPreference();
        this.updateToggleButton();
    }

    getCurrentTheme() {
        return this.currentTheme;
    }
}

// Initialize theme manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.themeManager = new ThemeManager();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeManager;
}
