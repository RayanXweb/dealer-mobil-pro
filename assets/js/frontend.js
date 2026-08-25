// AutoDealer Frontend JavaScript

$(document).ready(function() {
    // Initialize AOS animations
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            offset: 50
        });
    }
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert-dismissible').alert('close');
    }, 5000);
    
    // Smooth scroll for anchor links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        var target = $(this.hash);
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 80
            }, 800);
        }
    });
    
    // Product image zoom on hover (for detail page)
    $('.product-image-zoom').on('mousemove', function(e) {
        var pos = $(this).offset();
        var x = (e.pageX - pos.left) / $(this).width() * 100;
        var y = (e.pageY - pos.top) / $(this).height() * 100;
        $(this).find('img').css({
            'transform-origin': x + '% ' + y + '%',
            'transform': 'scale(1.5)'
        });
    }).on('mouseleave', function() {
        $(this).find('img').css({
            'transform': 'scale(1)'
        });
    });
    
    // Currency formatting for input fields
    $('.currency-input').on('input', function() {
        var value = $(this).val().replace(/[^0-9]/g, '');
        if (value) {
            $(this).val(parseInt(value).toLocaleString('id-ID'));
        }
    });
    
    // Numeric only input
    $('.numeric-input').on('input', function() {
        $(this).val($(this).val().replace(/[^0-9]/g, ''));
    });
    
    // Toggle password visibility
    $('.toggle-password').on('click', function() {
        var input = $(this).closest('.input-group').find('input');
        var icon = $(this).find('i');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Confirm delete
    window.confirmDelete = function(message) {
        return confirm(message || 'Apakah Anda yakin?');
    };
    
    // Toast notification
    window.showToast = function(type, message) {
        var toast = $('<div class="toast align-items-center text-white bg-' + type + ' border-0" role="alert">' +
            '<div class="d-flex">' +
            '<div class="toast-body">' + message + '</div>' +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
            '</div>' +
            '</div>');
        
        $('.toast-container').append(toast);
        var bsToast = new bootstrap.Toast(toast, { autohide: true, delay: 3000 });
        bsToast.show();
        
        setTimeout(function() {
            toast.remove();
        }, 3500);
    };
    
    // Add toast container if not exists
    if ($('.toast-container').length === 0) {
        $('body').append('<div class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
    }
});

// Add to cart function (global)
function addToCart(productId, quantity) {
    quantity = quantity || 1;
    
    $.ajax({
        url: BASE_URL + 'ajax/cart.php',
        method: 'POST',
        data: {
            action: 'add',
            product_id: productId,
            quantity: quantity,
            csrf_token: CSRF_TOKEN || ''
        },
        success: function(response) {
            if (response.success) {
                showToast('success', 'Berhasil ditambahkan ke keranjang');
                updateCartBadge();
            } else {
                showToast('danger', response.message || 'Gagal menambahkan');
            }
        },
        error: function() {
            showToast('danger', 'Terjadi kesalahan');
        }
    });
}

// Update cart badge
function updateCartBadge() {
    $.ajax({
        url: BASE_URL + 'ajax/cart-count.php',
        method: 'GET',
        success: function(response) {
            $('.cart-badge').text(response.count || 0);
        }
    });
}

// Search autocomplete
function initSearchAutocomplete() {
    $('.search-input').on('keyup', function() {
        var query = $(this).val();
        var container = $(this).closest('.search-wrapper').find('.search-results');
        
        if (query.length < 2) {
            container.empty().hide();
            return;
        }
        
        $.ajax({
            url: BASE_URL + 'ajax/search.php',
            method: 'POST',
            data: { q: query },
            success: function(response) {
                container.html(response).show();
            }
        });
    });
}

// Lazy load images
function lazyLoadImages() {
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var img = entry.target;
                    var src = img.getAttribute('data-src');
                    if (src) {
                        img.src = src;
                        img.removeAttribute('data-src');
                    }
                    observer.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(function(img) {
            observer.observe(img);
        });
    }
}

// Initialize when DOM ready
document.addEventListener('DOMContentLoaded', function() {
    initSearchAutocomplete();
    lazyLoadImages();
});
