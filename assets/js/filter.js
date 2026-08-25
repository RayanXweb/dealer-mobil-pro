// AutoDealer AJAX Filter

$(document).ready(function() {
    // Product filter with AJAX
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        applyFilters();
    });
    
    // Filter on change for select inputs
    $('#filterForm select').on('change', function() {
        applyFilters();
    });
    
    // Price range with debounce
    var debounceTimer;
    $('#filterForm input[type="range"], #filterForm .price-input').on('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            applyFilters();
        }, 300);
    });
    
    // Reset filter
    $('#resetFilter').on('click', function() {
        $('#filterForm')[0].reset();
        applyFilters();
    });
});

function applyFilters() {
    var form = $('#filterForm');
    var data = form.serialize();
    var container = $('#productGrid');
    var pagination = $('#paginationContainer');
    var loading = $('#loadingIndicator');
    
    // Show loading
    if (loading.length) {
        loading.show();
    } else {
        container.html('<div class="col-12 text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Memuat...</p></div>');
    }
    
    // Update URL without reload
    var url = new URL(window.location.href);
    var params = new URLSearchParams(data);
    url.search = params.toString();
    window.history.pushState({}, '', url);
    
    $.ajax({
        url: window.location.pathname + '?ajax=1&' + data,
        method: 'GET',
        success: function(response) {
            if (response.html) {
                container.html(response.html);
            } else if (response.products) {
                // Handle JSON response
                renderProducts(response.products);
                if (response.pagination) {
                    renderPagination(response.pagination);
                }
            }
            
            if (loading.length) {
                loading.hide();
            }
        },
        error: function() {
            container.html('<div class="col-12 text-center py-5 text-danger"><i class="fas fa-exclamation-circle fa-3x mb-3"></i><p>Gagal memuat data</p></div>');
            if (loading.length) {
                loading.hide();
            }
        }
    });
}

function renderProducts(products) {
    var container = $('#productGrid');
    if (!products || products.length === 0) {
        container.html('<div class="col-12 text-center py-5"><i class="fas fa-car fa-4x text-muted mb-3"></i><h4>Tidak ada mobil ditemukan</h4><p class="text-muted">Coba ubah filter pencarian Anda</p></div>');
        return;
    }
    
    var html = '';
    products.forEach(function(product) {
        html += `
            <div class="col-md-4 col-lg-3">
                <div class="product-card card h-100 shadow-sm border-0 overflow-hidden">
                    <div class="position-relative">
                        <img src="${product.primary_image || '/assets/images/no-image.jpg'}" 
                             alt="${product.model}" 
                             class="card-img-top product-image" style="height: 200px; object-fit: cover;">
                        ${product.is_promo ? '<span class="badge bg-danger position-absolute top-0 end-0 m-2 px-3 py-2"><i class="fas fa-tag me-1"></i> PROMO</span>' : ''}
                        ${product.is_featured ? '<span class="badge bg-primary position-absolute top-0 start-0 m-2 px-3 py-2"><i class="fas fa-star me-1"></i> UNGGULAN</span>' : ''}
                    </div>
                    <div class="card-body">
                        <h6 class="text-muted small mb-1">${product.brand_name}</h6>
                        <h5 class="card-title mb-2">${product.model}</h5>
                        ${product.variant ? '<small class="text-muted">' + product.variant + '</small>' : ''}
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div>
                                ${product.discount > 0 ? 
                                    `<span class="text-muted text-decoration-line-through small">${formatCurrency(product.original_price)}</span><br>
                                     <span class="text-danger fw-bold fs-5">${formatCurrency(product.final_price)}</span>
                                     <span class="badge bg-success ms-2">-${product.discount_percent}%</span>` :
                                    `<span class="fw-bold fs-5">${formatCurrency(product.final_price)}</span>`
                                }
                            </div>
                            <span class="badge bg-${getStatusBadge(product.status)}">${getStatusLabel(product.status)}</span>
                        </div>
                        <div class="d-flex gap-3 mt-3 text-muted small">
                            <span><i class="fas fa-calendar me-1"></i> ${product.year}</span>
                            <span><i class="fas fa-cog me-1"></i> ${product.transmission}</span>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 pt-0 pb-3">
                        <a href="/product-detail.php?id=${product.id}" class="btn btn-outline-primary w-100">
                            <i class="fas fa-eye me-1"></i> Detail
                        </a>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.html(html);
}

function renderPagination(pagination) {
    var container = $('#paginationContainer');
    if (!pagination || pagination.total_pages <= 1) {
        container.empty();
        return;
    }
    
    var html = '<nav><ul class="pagination justify-content-center">';
    
    if (pagination.current_page > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page - 1}"><i class="fas fa-chevron-left"></i></a></li>`;
    }
    
    for (var i = 1; i <= pagination.total_pages; i++) {
        html += `<li class="page-item ${i == pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                 </li>`;
    }
    
    if (pagination.current_page < pagination.total_pages) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page + 1}"><i class="fas fa-chevron-right"></i></a></li>`;
    }
    
    html += '</ul></nav>';
    container.html(html);
    
    // Handle pagination click
    container.find('.page-link').on('click', function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        if (page) {
            $('#pageInput').val(page);
            applyFilters();
        }
    });
}

// Helper functions for frontend
function formatCurrency(amount) {
    return 'Rp ' + parseInt(amount).toLocaleString('id-ID');
}

function getStatusBadge(status) {
    var badges = {
        'available': 'success',
        'sold': 'danger',
        'reserved': 'warning',
        'inactive': 'secondary'
    };
    return badges[status] || 'secondary';
}

function getStatusLabel(status) {
    var labels = {
        'available': 'Tersedia',
        'sold': 'Terjual',
        'reserved': 'Dipesan',
        'inactive': 'Tidak Aktif'
    };
    return labels[status] || status;
}

// Infinite scroll
$(window).on('scroll', function() {
    if ($(window).scrollTop() + $(window).height() >= $(document).height() - 100) {
        if ($('#loadMoreBtn').length && !$('#loadMoreBtn').hasClass('loading')) {
            $('#loadMoreBtn').click();
        }
    }
});
