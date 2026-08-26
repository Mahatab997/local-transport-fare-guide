<?php
/**
 * footer.php
 * Closes the HTML document and loads JS.
 */
?>
  <footer>
    <div class="container footer-inner">
      <div class="footer-card">
        <h4><i class="fa-solid fa-route"></i> Transport &amp; Fare Guide</h4>
        <p>
          Real-time, admin-verified local transport fares — updated the moment
          the authority changes them, so you always pay the right price.
        </p>
        <span class="footer-pill">Verified fares</span>
      </div>
      <div class="footer-card">
        <h4>Explore</h4>
        <a href="<?= APP_URL ?>/user/search.php">Search Routes</a>
        <a href="<?= APP_URL ?>/user/fare_history.php">Fare History</a>
        <a href="<?= APP_URL ?>/user/review.php">Reviews</a>
      </div>
      <div class="footer-card">
        <h4>Account</h4>
        <a href="<?= APP_URL ?>/auth/login.php">Login</a>
        <a href="<?= APP_URL ?>/auth/register.php">Register</a>
        <a href="<?= APP_URL ?>/index.php">Home</a>
      </div>
    </div>
    <div class="footer-bottom">
      &copy; <?= date('Y') ?> Local Transport &amp; Fare Guide. Built with PHP &amp; MySQL.
    </div>
  </footer>
<script src="<?= APP_URL ?>/assets/js/script.js"></script>
</body>
</html>
