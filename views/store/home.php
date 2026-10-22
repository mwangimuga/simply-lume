<?php
ob_start();
?>
<section class="hero">
    <div>
        <p class="kicker" style="color:#f7f3ef">Jewellery atelier</p>
        <h1>Simply Lume</h1>
        <p>Pieces for the body — watches, chains, rings, cuffs and piercings — composed with restraint.</p>
        <a class="btn" href="<?= e(url('shop')) ?>">View the collection</a>
    </div>
</section>
<section class="section wrap">
    <p class="kicker">Classify</p>
    <h2 class="display">By type</h2>
    <div class="cat-grid">
        <?php foreach ($categories as $c): ?>
            <a class="cat-card" href="<?= e(url('shop?category=' . $c['slug'])) ?>">
                <span><?= e($c['name']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<section class="section wrap" style="padding-top:0">
    <p class="kicker">Arrivals</p>
    <h2 class="display">Selected pieces</h2>
    <div class="product-grid">
        <?php foreach ($featured as $p) { include __DIR__ . '/partial-card.php'; } ?>
    </div>
</section>
<?php
$content = ob_get_clean();
$title = 'Simply Lume';
require __DIR__ . '/layout.php';
