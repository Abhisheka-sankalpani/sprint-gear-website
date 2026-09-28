/* assets/js/product-details.js - Dynamic Variant Engine & Gallery Handlers */

document.addEventListener('DOMContentLoaded', () => {
    initVariantEngine();
});

function initVariantEngine() {
    const colorSwatches = document.querySelectorAll('.color-swatch-picker input[name="selected_color"]');
    const mainImg = document.getElementById('mainProductImage');
    const selectedVariantInput = document.getElementById('selectedVariantId');
    const stockBadge = document.getElementById('variantStockBadge');
    const addToCartBtn = document.getElementById('addToCartBtn');
    const buyNowBtn = document.getElementById('buyNowBtn');

    if (!window.productVariantsData || window.productVariantsData.length === 0) return;

    // Helper to get variants by color ID
    function getVariantsForColor(colorId) {
        return window.productVariantsData.filter(v => parseInt(v.color_id) === parseInt(colorId));
    }

    // Update size buttons state based on selected color
    function updateSizeAvailability(colorId) {
        const colorVariants = getVariantsForColor(colorId);
        const sizeButtons = document.querySelectorAll('.size-picker input[name="selected_size"]');

        let firstAvailableSizeInput = null;

        sizeButtons.forEach(btn => {
            const sizeId = parseInt(btn.value);
            const variant = colorVariants.find(v => parseInt(v.size_id) === sizeId);
            const parentLabel = btn.closest('.size-option');

            if (variant && parseInt(variant.stock) > 0) {
                btn.disabled = false;
                parentLabel.classList.remove('disabled');
                if (!firstAvailableSizeInput && btn.checked) {
                    firstAvailableSizeInput = btn;
                }
            } else {
                btn.disabled = true;
                btn.checked = false;
                parentLabel.classList.add('disabled');
            }
        });

        // Auto select first available size if currently checked size is disabled
        const currentChecked = document.querySelector('.size-picker input[name="selected_size"]:checked');
        if (!currentChecked) {
            const firstEnabled = document.querySelector('.size-picker input[name="selected_size"]:not(:disabled)');
            if (firstEnabled) {
                firstEnabled.checked = true;
                updateSelectedVariant();
            } else {
                // Out of stock in all sizes for this color
                if (selectedVariantInput) selectedVariantInput.value = '';
                if (stockBadge) {
                    stockBadge.textContent = 'OUT OF STOCK';
                    stockBadge.className = 'stock-badge badge-out';
                }
                if (addToCartBtn) addToCartBtn.disabled = true;
                if (buyNowBtn) buyNowBtn.disabled = true;
            }
        } else {
            updateSelectedVariant();
        }
    }

    // Update main image and stock info when variant combination selected
    function updateSelectedVariant() {
        const selectedColor = document.querySelector('.color-swatch-picker input[name="selected_color"]:checked');
        const selectedSize = document.querySelector('.size-picker input[name="selected_size"]:checked');

        if (!selectedColor || !selectedSize) return;

        const colorId = parseInt(selectedColor.value);
        const sizeId = parseInt(selectedSize.value);

        const matchingVariant = window.productVariantsData.find(
            v => parseInt(v.color_id) === colorId && parseInt(v.size_id) === sizeId
        );

        if (matchingVariant) {
            if (selectedVariantInput) selectedVariantInput.value = matchingVariant.id;

            // DYNAMIC COLOR SELECTION IMAGE SWITCH:
            // Update main product image if variant has a custom image!
            if (matchingVariant.variant_image && mainImg) {
                mainImg.src = matchingVariant.variant_image;
            }

            const stock = parseInt(matchingVariant.stock);
            if (stock > 0) {
                if (stockBadge) {
                    stockBadge.textContent = `IN STOCK (${stock} available)`;
                    stockBadge.className = 'stock-badge badge-in';
                }
                if (addToCartBtn) addToCartBtn.disabled = false;
                if (buyNowBtn) buyNowBtn.disabled = false;
            } else {
                if (stockBadge) {
                    stockBadge.textContent = 'OUT OF STOCK';
                    stockBadge.className = 'stock-badge badge-out';
                }
                if (addToCartBtn) addToCartBtn.disabled = true;
                if (buyNowBtn) buyNowBtn.disabled = true;
            }
        }
    }

    // Attach listeners to color swatches
    colorSwatches.forEach(swatch => {
        swatch.addEventListener('change', (e) => {
            updateSizeAvailability(e.target.value);
        });
    });

    // Attach listeners to size buttons
    document.querySelectorAll('.size-picker input[name="selected_size"]').forEach(sizeInput => {
        sizeInput.addEventListener('change', () => {
            updateSelectedVariant();
        });
    });

    // Initialize with default selected color
    const initialColor = document.querySelector('.color-swatch-picker input[name="selected_color"]:checked');
    if (initialColor) {
        updateSizeAvailability(initialColor.value);
    }
}

// Quantity Counter Handlers (+ / -)
function adjustQuantity(delta) {
    const input = document.getElementById('productQuantity');
    if (!input) return;
    let val = parseInt(input.value) || 1;
    val = Math.max(1, val + delta);
    input.value = val;
}

// Switch Detail Tabs (Description, Additional Info, Reviews)
function switchTab(tabId, btnElement) {
    document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
    document.querySelectorAll('.product-detail-tabs .tab-link').forEach(b => b.classList.remove('active'));

    const content = document.getElementById(`tab-${tabId}`);
    if (content) content.style.display = 'block';
    if (btnElement) btnElement.classList.add('active');
}

// Change Main Gallery Thumbnail Image
function changeGalleryImage(src, thumbElement) {
    const mainImg = document.getElementById('mainProductImage');
    if (mainImg) mainImg.src = src;
    document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
    if (thumbElement) thumbElement.classList.add('active');
}
