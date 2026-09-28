/* assets/js/main.js - Sprint Gear Main Client Application Script */

document.addEventListener('DOMContentLoaded', () => {
    initMobileNav();
});

// Mobile Drawer Navigation Toggle
function initMobileNav() {
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const navMenu = document.getElementById('navMenu');

    if (mobileBtn && navMenu) {
        mobileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            navMenu.classList.toggle('open');
            const icon = mobileBtn.querySelector('i');
            if (navMenu.classList.contains('open')) {
                icon.className = 'fas fa-times';
            } else {
                icon.className = 'fas fa-bars';
            }
        });

        document.addEventListener('click', (e) => {
            if (navMenu.classList.contains('open') && !navMenu.contains(e.target) && !mobileBtn.contains(e.target)) {
                navMenu.classList.remove('open');
                mobileBtn.querySelector('i').className = 'fas fa-bars';
            }
        });
    }
}

// Toast Popup Notification Handler
function showToast(message, type = 'info') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    let iconClass = 'fas fa-info-circle text-blue-400';
    if (type === 'success') iconClass = 'fas fa-check-circle text-green-400';
    if (type === 'error') iconClass = 'fas fa-exclamation-triangle text-red-400';

    toast.innerHTML = `<i class="${iconClass}"></i> <span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// Wishlist Action Toggle handler via fetch AJAX
function toggleWishlistAction(productId, buttonElement) {
    const formData = new FormData();
    formData.append('action', 'toggle_wishlist');
    formData.append('product_id', productId);

    fetch('wishlist.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.require_login) {
            showToast(data.message, 'error');
            setTimeout(() => { window.location.href = 'login.php'; }, 1200);
            return;
        }

        if (data.success) {
            const badge = document.getElementById('headerWishlistBadge');
            if (badge) badge.textContent = data.count;

            if (buttonElement) {
                const icon = buttonElement.querySelector('i');
                if (data.action === 'added') {
                    buttonElement.classList.add('active');
                    icon.className = 'fas fa-heart';
                    buttonElement.title = 'Remove from Wishlist';
                } else {
                    buttonElement.classList.remove('active');
                    icon.className = 'far fa-heart';
                    buttonElement.title = 'Add to Wishlist';
                }
            }
            showToast(data.message, data.action === 'added' ? 'success' : 'info');
        } else {
            showToast(data.message || 'Error updating wishlist.', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Server error while toggling wishlist.', 'error');
    });
}

// Size Guide Modal Handlers
function openSizeGuideModal(categoryType = 'Shoes') {
    const modal = document.getElementById('sizeGuideModal');
    if (modal) {
        modal.style.display = 'flex';
        let tabName = 'shoes';
        if (categoryType.toLowerCase().includes('top') || categoryType.toLowerCase().includes('shirt') || categoryType.toLowerCase().includes('jersey')) tabName = 'tops';
        if (categoryType.toLowerCase().includes('short') || categoryType.toLowerCase().includes('pant')) tabName = 'bottoms';
        
        const targetBtn = document.querySelector(`.modal-tabs .tab-btn[onclick*="${tabName}"]`);
        if (targetBtn) switchSizeGuideTab(tabName, targetBtn);
    }
}

function closeSizeGuideModal(event) {
    if (!event || event.target.id === 'sizeGuideModal' || event.target.classList.contains('modal-close-btn')) {
        const modal = document.getElementById('sizeGuideModal');
        if (modal) modal.style.display = 'none';
    }
}

function switchSizeGuideTab(tabId, btnElement) {
    document.querySelectorAll('.size-guide-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.modal-tabs .tab-btn').forEach(btn => btn.classList.remove('active'));

    const targetContent = document.getElementById(`sg-${tabId}`);
    if (targetContent) targetContent.style.display = 'block';
    if (btnElement) btnElement.classList.add('active');
}
