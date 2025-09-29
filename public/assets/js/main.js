/**
 * Main JavaScript - Apple Clone E-commerce
 * Modern interactive features and animations
 */

class AppleStore {
    constructor() {
        this.cart = this.getCart();
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.updateCartCount();
        this.initAnimations();
        this.initModals();
        this.initUserSidebar();
    }

    setupEventListeners() {
        // Mobile menu toggle
        const mobileToggle = document.querySelector('.mobile-menu-toggle');
        if (mobileToggle) {
            mobileToggle.addEventListener('click', this.toggleMobileMenu.bind(this));
        }

        // Add to cart buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.add-to-cart, .add-to-cart *')) {
                e.preventDefault();
                const button = e.target.closest('.add-to-cart');
                this.addToCart(button);
            }
        });

        // Cart quantity updates
        document.addEventListener('change', (e) => {
            if (e.target.matches('.cart-quantity')) {
                this.updateCartQuantity(e.target);
            }
        });

        // Remove from cart
        document.addEventListener('click', (e) => {
            if (e.target.matches('.remove-from-cart, .remove-from-cart *')) {
                e.preventDefault();
                const button = e.target.closest('.remove-from-cart');
                this.removeFromCart(button);
            }
        });

        // Search functionality
        const searchForm = document.querySelector('.search-form');
        if (searchForm) {
            searchForm.addEventListener('submit', this.handleSearch.bind(this));
        }

        // Filter functionality
        const filterForm = document.querySelector('.filter-form');
        if (filterForm) {
            filterForm.addEventListener('change', this.handleFilter.bind(this));
        }
    }

    // Cart Management
    getCart() {
        const cart = localStorage.getItem('apple_cart');
        return cart ? JSON.parse(cart) : [];
    }

    saveCart() {
        localStorage.setItem('apple_cart', JSON.stringify(this.cart));
        this.updateCartCount();
    }

    addToCart(button) {
        const productId = button.dataset.productId;
        const variantId = button.dataset.variantId || null;
        const quantity = parseInt(button.dataset.quantity || 1);
        const price = parseFloat(button.dataset.price);
        const name = button.dataset.name;
        const image = button.dataset.image;

        // Check if item already exists in cart
        const existingItem = this.cart.find(item => 
            item.productId === productId && item.variantId === variantId
        );

        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            this.cart.push({
                productId,
                variantId,
                quantity,
                price,
                name,
                image
            });
        }

        this.saveCart();
        this.showAddToCartAnimation(button);
        this.showNotification('Product added to cart!', 'success');
    }

    updateCartQuantity(input) {
        const index = parseInt(input.dataset.index);
        const quantity = parseInt(input.value);

        if (quantity <= 0) {
            this.cart.splice(index, 1);
        } else {
            this.cart[index].quantity = quantity;
        }

        this.saveCart();
        this.updateCartDisplay();
    }

    removeFromCart(button) {
        const index = parseInt(button.dataset.index);
        const itemName = this.cart[index].name;
        
        this.cart.splice(index, 1);
        this.saveCart();
        this.updateCartDisplay();
        this.showNotification(`${itemName} removed from cart`, 'info');
    }

    updateCartCount() {
        const count = this.cart.reduce((total, item) => total + item.quantity, 0);
        const badges = document.querySelectorAll('.cart-badge .badge');
        
        badges.forEach(badge => {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        });
    }

    updateCartDisplay() {
        // Update cart page if we're on it
        const cartContainer = document.querySelector('.cart-items');
        if (cartContainer) {
            this.renderCartItems(cartContainer);
        }
    }

    showAddToCartAnimation(button) {
        // Create flying cart animation
        const rect = button.getBoundingClientRect();
        const cartIcon = document.querySelector('.navbar-actions .fa-shopping-cart');
        
        if (cartIcon) {
            const flying = document.createElement('div');
            flying.innerHTML = '<i class="fas fa-plus"></i>';
            flying.style.cssText = `
                position: fixed;
                left: ${rect.left + rect.width/2}px;
                top: ${rect.top + rect.height/2}px;
                color: var(--primary);
                font-size: 1.5rem;
                z-index: 9999;
                pointer-events: none;
                transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            `;
            
            document.body.appendChild(flying);
            
            setTimeout(() => {
                const cartRect = cartIcon.getBoundingClientRect();
                flying.style.left = cartRect.left + 'px';
                flying.style.top = cartRect.top + 'px';
                flying.style.opacity = '0';
                flying.style.transform = 'scale(0.5)';
            }, 100);
            
            setTimeout(() => {
                document.body.removeChild(flying);
            }, 900);
        }
    }

    // UI Interactions
    toggleMobileMenu() {
        const nav = document.querySelector('.navbar-nav');
        nav.classList.toggle('show');
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span>${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 1rem 1.5rem;
            z-index: 9999;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
        `;

        if (type === 'success') {
            notification.style.borderLeftColor = 'var(--success)';
        } else if (type === 'error') {
            notification.style.borderLeftColor = 'var(--danger)';
        }

        document.body.appendChild(notification);

        // Show notification
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 100);

        // Auto hide
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 3000);

        // Close button
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 300);
        });
    }

    // Search and Filter
    handleSearch(e) {
        e.preventDefault();
        const query = e.target.querySelector('input[name="q"]').value.trim();
        if (query) {
            window.location.href = `search.php?q=${encodeURIComponent(query)}`;
        }
    }

    handleFilter() {
        const form = document.querySelector('.filter-form');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        window.location.href = `${window.location.pathname}?${params.toString()}`;
    }

    // Animations
    initAnimations() {
        // Scroll reveal animation
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                }
            });
        }, observerOptions);

        // Observe elements for scroll animations
        document.querySelectorAll('.scroll-reveal').forEach(el => {
            observer.observe(el);
        });

        // Parallax effect for hero sections
        this.initParallax();
    }

    initParallax() {
        const parallaxElements = document.querySelectorAll('.parallax-bg');
        
        if (parallaxElements.length > 0) {
            window.addEventListener('scroll', () => {
                const scrolled = window.pageYOffset;
                
                parallaxElements.forEach(element => {
                    const speed = element.dataset.speed || 0.5;
                    const yPos = -(scrolled * speed);
                    element.style.transform = `translate3d(0, ${yPos}px, 0)`;
                });
            });
        }
    }

    // Modal Management
    initModals() {
        // Modal triggers
        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-modal]');
            if (trigger) {
                e.preventDefault();
                this.openModal(trigger.dataset.modal);
            }

            // Modal close
            if (e.target.matches('.modal-close, .modal-backdrop')) {
                this.closeModal();
            }
        });

        // ESC key to close modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        });
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    closeModal() {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => {
            modal.classList.remove('show');
        });
        document.body.style.overflow = '';
    }

    // User Sidebar Management
    initUserSidebar() {
        const userMenuToggle = document.getElementById('userMenuToggle');
        const userSidebar = document.getElementById('userSidebar');
        const sidebarOverlay = document.querySelector('.sidebar-overlay');
        const sidebarClose = document.querySelector('.sidebar-close');
        
        if (!userMenuToggle || !userSidebar) return;
        
        // Open sidebar
        userMenuToggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.openSidebar();
        });
        
        // Close sidebar when clicking overlay
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', (e) => {
                if (e.target === sidebarOverlay) {
                    this.closeSidebar();
                }
            });
        }
        
        // Close sidebar when clicking close button
        if (sidebarClose) {
            sidebarClose.addEventListener('click', (e) => {
                e.preventDefault();
                this.closeSidebar();
            });
        }
        
        // Close sidebar when pressing ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && userSidebar.classList.contains('active')) {
                this.closeSidebar();
            }
        });
        
        // Prevent sidebar content clicks from closing sidebar
        const sidebarContent = document.querySelector('.sidebar-content');
        if (sidebarContent) {
            sidebarContent.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }
    }
    
    openSidebar() {
        const userSidebar = document.getElementById('userSidebar');
        const userMenuToggle = document.getElementById('userMenuToggle');
        
        if (userSidebar && userMenuToggle) {
            userSidebar.classList.add('active');
            userMenuToggle.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent body scroll
            
            // Focus trap
            const focusableElements = userSidebar.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            if (focusableElements.length > 0) {
                setTimeout(() => focusableElements[0].focus(), 100);
            }
        }
    }
    
    closeSidebar() {
        const userSidebar = document.getElementById('userSidebar');
        const userMenuToggle = document.getElementById('userMenuToggle');
        
        if (userSidebar && userMenuToggle) {
            userSidebar.classList.remove('active');
            userMenuToggle.classList.remove('active');
            document.body.style.overflow = ''; // Restore body scroll
            
            // Return focus to toggle button
            userMenuToggle.focus();
        }
    }

    // Utility Functions
    formatPrice(price, currency = 'USD') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(price);
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Initialize the app
document.addEventListener('DOMContentLoaded', () => {
    window.appleStore = new AppleStore();
});

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AppleStore;
}