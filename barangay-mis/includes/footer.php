<?php
/**
 * includes/footer.php
 * Closes the layout opened by header.php and loads JS.
 * Optional variable a page can set before requiring this file:
 *   $extraScript (string) raw JS injected in a page-specific <script> tag
 */
?>
        </div><!-- /.content-wrap -->
    </div><!-- /.main-area -->
</div><!-- /.app-shell -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if (!empty($useCharts)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<?php endif; ?>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<?php if (!empty($extraScript)): ?>
<script><?= $extraScript ?></script>
<?php endif; ?>
</body>
</html>
