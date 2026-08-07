  </main>
</div> <!-- .main-wrapper -->
</div> <!-- .app-layout -->

<!-- JavaScript Files -->
<script src="<?php echo BASE_URL; ?>assets/js/app.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/chart.js"></script>

<?php if (isset($extraScripts) && is_array($extraScripts)): ?>
  <?php foreach ($extraScripts as $script): ?>
    <script src="<?php echo BASE_URL . $script; ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
