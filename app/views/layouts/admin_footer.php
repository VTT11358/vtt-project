            </main>
        </div>
    </div>
    <script src="<?= BASE_URL ?>assets/js/main.js" defer></script>
    <?php if ($adminController === "adminOrder" && $adminAction === "stats"): ?>
        <script
            src="<?= BASE_URL ?>assets/js/pages/order-stats.js?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/js/pages/order-stats.js') ?>"
            defer
        ></script>
    <?php endif; ?>
</body>
</html>
