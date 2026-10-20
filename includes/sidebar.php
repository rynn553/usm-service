<?php
$navigation = [
'home'=>['Papan pemuka','/home.php','▦'],
'create'=>['Rekod perkhidmatan','/services/create.php','✎'],
'active'=>['Permintaan aktif','/services/index.php','☷'],
'payment'=>['Penyelesaian & Bayaran','/payments/index.php','◇'],
'history'=>['Sejarah perkhidmatan','/history.php','◷'],
'reports'=>['Laporan','/reports.php','▤'],
];
?>
<a class="skip-link" href="#main-content">Langkau ke kandungan</a>
<aside class="sidebar"><a class="brand sidebar-brand" href="<?= APP_BASE ?>/home.php"><span class="brand-mark">USM</span><div><strong>Perkhidmatan USM</strong><span>Operasi staf</span></div></a><button class="menu-toggle outline" type="button" aria-expanded="true" aria-controls="sidebar-menu" hidden>Menu utama</button><div id="sidebar-menu" class="sidebar-menu"><nav class="sidebar-nav" aria-label="Menu utama"><?php foreach($navigation as $key=>[$label,$path,$icon]): ?><a href="<?= APP_BASE . $path ?>"<?= ($activePage ?? '') === $key ? ' aria-current="page"' : '' ?>><span class="nav-icon" aria-hidden="true"><?= $icon ?></span><?= e($label) ?></a><?php endforeach; ?></nav><div class="sidebar-footer">Universiti Sains Malaysia</div></div></aside>
