// SprintGear.lk JavaScript Frontend Interactivity

document.addEventListener('DOMContentLoaded', () => {
    
    // 1. Color Swatch Switching
    const swatchItems = document.querySelectorAll('.swatch-item');
    swatchItems.forEach(swatch => {
        swatch.addEventListener('click', (e) => {
            e.stopPropagation();
            const parentCard = swatch.closest('.product-card') || swatch.closest('.product-detail-container');
            if (!parentCard) return;

            // Remove active class from sibling swatches
            const siblingSwatches = parentCard.querySelectorAll('.swatch-item');
            siblingSwatches.forEach(s => s.classList.remove('active'));

            // Add active class
            swatch.classList.add('active');

            // Change image if swatch has data-img
            const targetImgUrl = swatch.getAttribute('data-img');
            if (targetImgUrl) {
                const targetImg = parentCard.querySelector('.product-main-img img') || parentCard.querySelector('.product-image-wrapper img');
                if (targetImg) {
                    targetImg.style.opacity = '0.4';
                    setTimeout(() => {
                        targetImg.src = targetImgUrl;
                        targetImg.style.opacity = '1';
                    }, 150);
                }
            }
        });
    });

    // 2. Add to Cart AJAX
    const cartButtons = document.querySelectorAll('.btn-add-cart, .btn-add-to-cart-detail');
    cartButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const productId = btn.getAttribute('data-product-id');
            const qtyInput = document.querySelector('#product-qty');
            const quantity = qtyInput ? qtyInput.value : 1;

            if (!productId) return;

            // Visual loading state
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add&product_id=${productId}&quantity=${quantity}`
            })
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = originalHTML;
                btn.disabled = false;

                if (data.success) {
                    // Update header cart badge
                    const cartBadge = document.querySelector('#cart-count-badge');
                    if (cartBadge) {
                        cartBadge.textContent = data.cart_count;
                        cartBadge.style.transform = 'scale(1.3)';
                        setTimeout(() => cartBadge.style.transform = 'scale(1)', 200);
                    }

                    showToast('Product added to cart!');
                } else {
                    showToast('Failed to add product', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                showToast('Added to cart!', 'success');
            });
        });
    });

    // 3. Wishlist Heart Toggle
    const wishlistBtns = document.querySelectorAll('.product-wishlist-btn');
    wishlistBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const productId = btn.getAttribute('data-product-id');
            
            btn.classList.toggle('active');
            const icon = btn.querySelector('i');
            if (icon) {
                if (btn.classList.contains('active')) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    icon.style.color = '#E74C3C';
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    icon.style.color = '#666';
                }
            }

            fetch('api/wishlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${productId}`
            })
            .then(res => res.json())
            .then(data => {
                const wishlistBadge = document.querySelector('#wishlist-count-badge');
                if (wishlistBadge && data.wishlist_count !== undefined) {
                    wishlistBadge.textContent = data.wishlist_count;
                }
            })
            .catch(() => {});
        });
    });

    // 4. Newsletter Toast Helper
    const subscribeForm = document.querySelector('.subscribe-form');
    if (subscribeForm) {
        subscribeForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const input = subscribeForm.querySelector('.subscribe-input');
            if (input && input.value.trim() !== '') {
                showToast('Thank you for subscribing to Sprint Gear!');
                input.value = '';
            }
        });
    }

    // Helper Toast Notification
    function showToast(message, type = 'success') {
        let toast = document.createElement('div');
        toast.className = `sprint-toast ${type}`;
        toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${message}`;
        
        Object.assign(toast.style, {
            position: 'fixed',
            bottom: '30px',
            right: '30px',
            backgroundColor: type === 'success' ? '#0D0D12' : '#E74C3C',
            color: '#FFF',
            padding: '14px 24px',
            borderRadius: '8px',
            boxShadow: '0 10px 30px rgba(0,0,0,0.3)',
            zIndex: '9999',
            fontFamily: 'Montserrat, sans-serif',
            fontWeight: '600',
            fontSize: '0.9rem',
            display: 'flex',
            alignItems: 'center',
            gap: '10px',
            borderLeft: `4px solid ${type === 'success' ? '#FF4500' : '#FFF'}`,
            transition: 'all 0.3s ease'
        });

        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
});
