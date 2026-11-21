<?php
/**
 * AVIF copies of the header logo files that slice.py made (PHP GD).
 *
 *     php avif.php <theme img/brand folder>
 *
 * Checks that transparency survived by reading a corner pixel back.
 */
$dir = rtrim($argv[1] ?? '', '/\\');
if (!$dir || !is_dir($dir) || !function_exists('imageavif')) {
	fwrite(STDERR, "Usage: php avif.php <folder> (needs GD with AVIF)\n");
	exit(1);
}
foreach (glob($dir . '/sfs-logo-*.webp') as $webp) {
	$image = imagecreatefromwebp($webp);
	imagealphablending($image, false);
	imagesavealpha($image, true);
	$avif = substr($webp, 0, -5) . '.avif';
	imageavif($image, $avif, 62, 4);
	$back = imagecreatefromavif($avif);
	$corner = imagecolorsforindex($back, imagecolorat($back, 0, 0));
	printf("%s %d bytes (webp %d), corner alpha %d of 127\n", basename($avif), filesize($avif), filesize($webp), $corner['alpha']);
}
