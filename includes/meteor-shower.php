<?php
// Generate a denser, full-screen meteor shower
$total = 30;
$meteors = [];

for ($i = 0; $i < $total; $i++) {
    $meteors[] = [
        // spread across and slightly outside viewport vertically
        'top' => (-20 + mt_rand() / mt_getrandmax() * 140) . '%',

        // spawn across the horizontal axis (allow outside both sides)
        'left' => (-30 + mt_rand() / mt_getrandmax() * 160) . '%',

        'delay' => (mt_rand() / mt_getrandmax() * 12) . 's',
        'duration' => (4 + mt_rand() / mt_getrandmax() * 3) . 's',

        // variable streak length
        'width' => (40 + mt_rand() / mt_getrandmax() * 220),

        'opacity' => (0.25 + mt_rand() / mt_getrandmax() * 0.65),
    ];
}

?>

<div class="meteor-wrapper">
  <?php foreach ($meteors as $m): ?>
    <span
      class="meteor"
      style="
        top: <?= $m['top'] ?>;
        left: <?= $m['left'] ?>;
        width: <?= intval($m['width']) ?>px;
        opacity: <?= $m['opacity'] ?>;
        animation-delay: <?= $m['delay'] ?>;
        animation-duration: <?= $m['duration'] ?>;
      "
    ></span>
  <?php endforeach; ?>
</div>
