/**
 * assets/js/main.js
 * Shared behavior: mobile sidebar toggle + confirm-before-delete.
 * Loaded on every page after Bootstrap + SweetAlert2 (CDN).
 */

document.addEventListener('DOMContentLoaded', function () {
    // ---- Mobile sidebar toggle -----------------------------------
    var toggleBtn = document.querySelector('.sidebar-toggle');
    var sidebar   = document.querySelector('.sidebar');
    var backdrop  = document.querySelector('.sidebar-backdrop');

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('open');
        if (backdrop) backdrop.classList.remove('show');
    }

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            if (backdrop) backdrop.classList.toggle('show');
        });
    }
    if (backdrop) {
        backdrop.addEventListener('click', closeSidebar);
    }
    // Close sidebar automatically when a nav link is tapped on mobile
    document.querySelectorAll('.sidebar-nav a').forEach(function (link) {
        link.addEventListener('click', closeSidebar);
    });

    // ---- Confirm-before-delete on any <form data-confirm="..."> --
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var message = form.getAttribute('data-confirm') || 'Are you sure?';
            if (window.Swal) {
                Swal.fire({
                    title: 'Please confirm',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, proceed',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#C1502E',
                    cancelButtonColor: '#5B6660',
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            } else if (window.confirm(message)) {
                form.submit();
            }
        });
    });

    // ---- Auto-dismiss success alerts after 5s ---------------------
    document.querySelectorAll('.alert-success').forEach(function (el) {
        setTimeout(function () {
            if (window.bootstrap) {
                var alert = window.bootstrap.Alert.getOrCreateInstance(el);
                alert.close();
            }
        }, 5000);
    });
});

/** Called from print buttons on certificate / report views. */
function printSection() {
    window.print();
}

/** Lightweight client-side search box for list tables (no jQuery/DataTables needed). */
function quickFilter(input, tableId) {
    var filter = input.value.toLowerCase();
    var rows = document.querySelectorAll('#' + tableId + ' tbody tr');
    var visibleCount = 0;
    rows.forEach(function (row) {
        var match = row.textContent.toLowerCase().indexOf(filter) !== -1;
        row.style.display = match ? '' : 'none';
        if (match) visibleCount++;
    });
    var emptyRow = document.getElementById(tableId + 'NoMatch');
    if (emptyRow) {
        emptyRow.style.display = visibleCount === 0 ? '' : 'none';
    }
}
