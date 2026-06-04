<?php if (!empty($extra_js)): foreach ($extra_js as $js): ?>
<script src="<?= $base_path ?? '' ?><?= $js ?>"></script>
<?php endforeach; endif; ?>

<script src="<?= $base_path ?? '' ?>estilos/js/app.js"></script>
</body>
</html>
