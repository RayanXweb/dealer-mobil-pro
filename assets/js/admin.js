// AutoDealer Admin JavaScript

$(document).ready(function() {
    // Auto-hide alerts
    setTimeout(function() {
        $('.alert-dismissible').alert('close');
    }, 5000);
    
    // Confirm delete
    window.confirmDelete = function(message, callback) {
        if (confirm(message || 'Apakah Anda yakin ingin menghapus data ini?')) {
            if (typeof callback === 'function') {
                callback();
            }
            return true;
        }
        return false;
    };
    
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Initialize popovers
    $('[data-bs-toggle="popover"]').popover();
    
    // Sidebar toggle for mobile
    $('#sidebarToggle').on('click', function(e) {
        e.preventDefault();
        $('.admin-sidebar').toggleClass('show');
        $('.admin-content').toggleClass('sidebar-open');
    });
    
    // DataTable initialization
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            responsive: true,
            language: {
                search: 'Cari:',
                lengthMenu: 'Tampilkan _MENU_ data',
                info: 'Menampilkan _START_ hingga _END_ dari _TOTAL_ data',
                paginate: {
                    first: 'Pertama',
                    last: 'Terakhir',
                    next: 'Selanjutnya',
                    previous: 'Sebelumnya'
                }
            }
        });
    }
    
    // Date range picker
    if ($.fn.daterangepicker) {
        $('.date-range').daterangepicker({
            locale: {
                format: 'YYYY-MM-DD',
                applyLabel: 'Terapkan',
                cancelLabel: 'Batal',
                daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
            }
        });
    }
    
    // AJAX form submission
    $('.ajax-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitBtn = form.find('[type="submit"]');
        var originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Memproses...');
        
        $.ajax({
            url: form.attr('action'),
            method: form.attr('method') || 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showToast('success', response.message || 'Berhasil');
                    if (form.data('redirect')) {
                        setTimeout(function() {
                            window.location.href = form.data('redirect');
                        }, 1000);
                    }
                } else {
                    showToast('danger', response.message || 'Gagal');
                }
            },
            error: function() {
                showToast('danger', 'Terjadi kesalahan server');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Image preview
    $('.image-preview-input').on('change', function() {
        var input = this;
        var preview = $(this).closest('.image-preview-wrapper').find('.image-preview');
        
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                preview.attr('src', e.target.result).show();
            };
            reader.readAsDataURL(input.files[0]);
        }
    });
    
    // Toggle status
    $('.toggle-status').on('click', function(e) {
        e.preventDefault();
        var url = $(this).data('url');
        var id = $(this).data('id');
        var status = $(this).data('status');
        
        if (confirm('Ubah status?')) {
            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    id: id,
                    status: status,
                    csrf_token: CSRF_TOKEN || ''
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        showToast('danger', response.message || 'Gagal mengubah status');
                    }
                }
            });
        }
    });
    
    // Bulk actions
    $('.bulk-action').on('change', function() {
        var action = $(this).val();
        if (!action) return;
        
        var selected = $('.bulk-checkbox:checked');
        if (selected.length === 0) {
            showToast('warning', 'Pilih minimal satu data');
            return;
        }
        
        if (confirm('Lakukan aksi ' + action + ' pada ' + selected.length + ' data?')) {
            var ids = selected.map(function() {
                return $(this).val();
            }).get();
            
            $.ajax({
                url: $(this).data('url'),
                method: 'POST',
                data: {
                    action: action,
                    ids: ids,
                    csrf_token: CSRF_TOKEN || ''
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        showToast('danger', response.message || 'Gagal');
                    }
                }
            });
        }
    });
});

// Toast notification for admin
function showToast(type, message) {
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
}

// Export function for reports
function exportReport(type) {
    var filters = window.location.search;
    window.location.href = BASE_URL + 'admin/reports/export-' + type + '.php' + filters;
}

// Chart.js helper for admin charts
function createChart(ctx, type, data, options) {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js not loaded');
        return;
    }
    
    return new Chart(ctx, {
        type: type,
        data: data,
        options: options || {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}
