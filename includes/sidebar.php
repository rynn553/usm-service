<?php
$navigation = [
'home'=>['Papan pemuka','/home.php','▦'],
'create'=>['Rekod perkhidmatan','/rekod.php','✎'],
'active'=>['Permintaan aktif','/aktif.php','☷'],
'payment'=>['Penyelesaian & Bayaran','/payments/index.php','◇'],
'history'=>['Sejarah perkhidmatan','/history.php','◷'],
'reports'=>['Laporan','/reports.php','▤'],
];
?>
<a class="skip-link" href="#main-content">Langkau ke kandungan</a>
<aside class="sidebar"><a class="brand sidebar-brand" href="<?= APP_BASE ?>/home.php"><span class="brand-mark amdi-sidebar-logo"><img src="<?= APP_BASE ?>/usm-logo.png" alt="Logo Universiti Sains Malaysia"></span><div><strong>e-Servis Digital@AMDI</strong><span>Pengurusan Perkhidmatan IT</span></div></a><button class="menu-toggle outline" type="button" aria-expanded="true" aria-controls="sidebar-menu" hidden>Menu utama</button><div id="sidebar-menu" class="sidebar-menu"><nav class="sidebar-nav" aria-label="Menu utama"><?php foreach($navigation as $key=>[$label,$path,$icon]): ?><a href="<?= APP_BASE . $path ?>"<?= ($activePage ?? '') === $key ? ' aria-current="page"' : '' ?>><span class="nav-icon" aria-hidden="true"><?= $icon ?></span><?= e($label) ?></a><?php endforeach; ?></nav><div class="sidebar-footer">Universiti Sains Malaysia</div></div></aside>

<style>
/* Branding sidebar sahaja; menu dan fungsi asal dikekalkan. */
.sidebar-brand .amdi-sidebar-logo { display:flex; align-items:center; justify-content:center; flex:0 0 64px; width:64px; height:64px; padding:6px; overflow:hidden; border:0; border-radius:10px; background:#fff; box-sizing:border-box; }
.sidebar-brand .amdi-sidebar-logo img { display:block; width:100%; height:100%; object-fit:contain; }
.sidebar-brand div { min-width:0; }
.sidebar-brand strong { font-size:15px; line-height:1.35; }
.sidebar-brand div > span { font-size:11px; line-height:1.5; }
@media(max-width:800px) { .sidebar-brand .amdi-sidebar-logo { flex-basis:54px; width:54px; height:54px; } }
</style>

<style> .sidebar-brand strong{overflow-wrap:anywhere} </style>
