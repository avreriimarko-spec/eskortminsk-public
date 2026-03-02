<?php

/**
 * Водяные знаки для фотографий моделей
 * - батчи, превью, бэкапы/откат
 * - центрирование, без искажений
 * - прозрачность 0.5
 * - настраиваемый размер (original | percent), по умолчанию 35%
 * @version 1.4
 */
if (!defined('ABSPATH')) exit;

class ModelsWatermark
{
    /** Путь к файлу водяного знака (png/svg) */
    private string $wm_path;

    /** Включено ли наложение вотермарки */
    private bool $enabled;

    /** Имя пост-тайпа моделей */
    private string $ptype = 'models';

    /** Расширение вотермарки */
    private string $wm_ext;

    /** Режим размера: original | percent */
    private string $wm_mode;

    /** Процент от меньшей стороны (1..200) */
    private int $wm_percent;

    public function __construct()
    {
        // Можно заменить на svg: assets/watermark.svg
        $this->wm_path   = get_theme_file_path('assets/watermark.png');
        $this->wm_ext    = strtolower(pathinfo($this->wm_path, PATHINFO_EXTENSION));
        $this->enabled   = (bool) get_option('models_watermark_enabled', true);

        $mode            = (string) get_option('models_watermark_mode', 'percent');
        $this->wm_mode   = in_array($mode, ['original', 'percent'], true) ? $mode : 'percent';

        // Сделал «чуть побольше» по умолчанию: 35%
        $percent         = (int) get_option('models_watermark_percent', 35);
        $this->wm_percent = max(1, min(200, $percent));

        $this->init_hooks();
    }

    private function init_hooks(): void
    {
        add_filter('wp_handle_upload', [$this, 'handle_upload'], 10, 1);
        add_action('add_attachment',    [$this, 'process_attachment'], 10, 1);

        add_action('wp_ajax_process_models_watermarks_batch',  [$this, 'ajax_process_watermarks_batch']);
        add_action('wp_ajax_preview_models_watermark',         [$this, 'ajax_preview_watermark']);
        add_action('wp_ajax_get_model_images',                 [$this, 'ajax_get_model_images']);
        add_action('wp_ajax_restore_models_watermark',         [$this, 'ajax_restore_watermark']);
        add_action('wp_ajax_process_models_watermarks',        [$this, 'ajax_process_watermarks_all']); // совместимость

        add_action('admin_menu', [$this, 'add_admin_page']);
    }

    /* ===================== ЗАГРУЗКА/ПРИКРЕПЛЕНИЕ ===================== */

    public function handle_upload(array $upload): array
    {
        if (!$this->enabled) return $upload;
        if (empty($upload['type']) || strpos($upload['type'], 'image/') !== 0) return $upload;
        if (!$this->is_models_context()) return $upload;

        $this->ensure_backup_file($upload['file']);
        $this->apply_watermark($upload['file']);
        return $upload;
    }

    private function is_models_context(): bool
    {
        if (isset($_POST['post_id'])) {
            $pid = (int) $_POST['post_id'];
            return get_post_type($pid) === $this->ptype;
        }
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $r = (string) $_SERVER['HTTP_REFERER'];
            if (strpos($r, 'post-new.php') !== false && strpos($r, 'post_type=' . $this->ptype) !== false) return true;
            if (preg_match('~post\.php\?post=(\d+)~', $r, $m)) {
                return get_post_type((int)$m[1]) === $this->ptype;
            }
        }
        return false;
    }

    public function process_attachment(int $attachment_id): void
    {
        if (!$this->enabled) return;

        $post = get_post($attachment_id);
        if (!$post || (int)$post->post_parent === 0) return;
        if (get_post_type((int)$post->post_parent) !== $this->ptype) return;

        $path = get_attached_file($attachment_id);
        if ($path && file_exists($path)) {
            $this->ensure_backup_attachment($attachment_id);
            $this->apply_watermark($path);
            $this->process_image_sizes($attachment_id);
        }
    }

    /* ===================== НАЛОЖЕНИЕ ВОДЯНОГО ЗНАКА ===================== */

    public function apply_watermark(string $image_path): bool
    {
        if (!file_exists($image_path) || !file_exists($this->wm_path)) return false;

        $info = @getimagesize($image_path);
        if (!$info) return false;
        [$w, $h, $type] = $info;

        if ($w < 300 || $h < 300) return false;

        if (extension_loaded('imagick')) {
            return $this->apply_with_imagick_file($image_path, $w, $h);
        }

        $gd = $this->create_gd_from_type($image_path, $type);
        if (!$gd) return false;

        $ok = $this->apply_overlay_via_gd($gd, $w, $h);
        if ($ok) $this->save_gd_by_type($gd, $image_path, $type);
        imagedestroy($gd);
        return $ok;
    }

    private function create_gd_from_type(string $path, int $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            case IMAGETYPE_WEBP:
                return function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : false;
            default:
                return false;
        }
    }

    private function save_gd_by_type($im, string $path, int $type): bool
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return (bool) imagejpeg($im, $path, 90);

            case IMAGETYPE_PNG:
                imagealphablending($im, false);
                imagesavealpha($im, true);
                return (bool) imagepng($im, $path, 8);

            case IMAGETYPE_WEBP:
                if (function_exists('imagewebp')) {
                    return (bool) imagewebp($im, $path, 90);
                }
                return false;

            default:
                return false;
        }
    }

    /**
     * Итоговые размеры вотермарки без искажений сторон.
     * @return array [dst_w, dst_h]
     */
    private function calc_wm_target_dims(int $img_w, int $img_h, int $src_w, int $src_h): array
    {
        if ($this->wm_mode === 'original') {
            // как есть, но если больше кадра — уменьшаем, чтобы влез (сохраняя пропорции)
            $dst_w = $src_w;
            $dst_h = $src_h;
            if ($dst_w > $img_w || $dst_h > $img_h) {
                $scale = min(($img_w * 0.9) / $src_w, ($img_h * 0.9) / $src_h);
                $scale = max(0.01, min(1, $scale));
                $dst_w = (int) round($src_w * $scale);
                $dst_h = (int) round($src_h * $scale);
            }
            return [$dst_w, $dst_h];
        }

        // percent — вписываем в квадрат (процент от меньшей стороны кадра)
        $box   = max(1, (int) round(min($img_w, $img_h) * ($this->wm_percent / 100)));
        $scale = min($box / $src_w, $box / $src_h);
        $scale = max(0.01, $scale);
        $dst_w = (int) round($src_w * $scale);
        $dst_h = (int) round($src_h * $scale);
        return [$dst_w, $dst_h];
    }

    /** Координаты центра */
    private function center_pos(int $img_w, int $img_h, int $wm_w, int $wm_h): array
    {
        return [
            'x' => (int) round(($img_w - $wm_w) / 2),
            'y' => (int) round(($img_h - $wm_h) / 2),
        ];
    }

    private function apply_with_imagick_file(string $image_path, int $w, int $h): bool
    {
        try {
            $im = new Imagick($image_path);
            $im->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);

            $wm = new Imagick();
            $wm->setBackgroundColor(new ImagickPixel('transparent'));

            if (in_array($this->wm_ext, ['svg', 'svgz'], true)) {
                $wm->setImageUnits(Imagick::RESOLUTION_PIXELSPERINCH);
                $wm->setResolution(300, 300);
            }
            $wm->readImage($this->wm_path);

            $src_w = $wm->getImageWidth();
            $src_h = $wm->getImageHeight();

            [$dst_w, $dst_h] = $this->calc_wm_target_dims($w, $h, $src_w, $src_h);
            if ($dst_w !== $src_w || $dst_h !== $src_h) {
                $wm->resizeImage($dst_w, $dst_h, Imagick::FILTER_LANCZOS, 1, true);
            }

            // Прозрачность 0.5
            $wm->evaluateImage(Imagick::EVALUATE_MULTIPLY, 0.5, Imagick::CHANNEL_ALPHA);

            $pos = $this->center_pos($w, $h, $dst_w, $dst_h);
            $im->compositeImage($wm, Imagick::COMPOSITE_OVER, $pos['x'], $pos['y']);

            $ok = $im->writeImage($image_path);
            $wm->destroy();
            $im->destroy();
            return (bool) $ok;
        } catch (\Throwable $e) {
            error_log('[WM] Imagick: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * GD-ветка: сохраняем пропорции, центрируем, выставляем прозрачность 0.5.
     */
    private function apply_overlay_via_gd($gd, int $w, int $h): bool
    {
        // PNG-водяной знак
        if ($this->wm_ext === 'png' && file_exists($this->wm_path)) {
            $wm = @imagecreatefrompng($this->wm_path);
            if ($wm) {
                imagealphablending($gd, true);
                imagesavealpha($wm, true);

                $src_w = imagesx($wm);
                $src_h = imagesy($wm);

                [$dst_w, $dst_h] = $this->calc_wm_target_dims($w, $h, $src_w, $src_h);

                $dst = $wm;
                if ($dst_w !== $src_w || $dst_h !== $src_h) {
                    $dst = imagecreatetruecolor($dst_w, $dst_h);
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                    imagecopyresampled($dst, $wm, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
                    imagedestroy($wm);
                }

                // Применим глобальную прозрачность 0.5, сохраняя внутреннюю альфу пикселей
                $this->gd_apply_global_opacity($dst, 0.5);

                $pos = $this->center_pos($w, $h, $dst_w, $dst_h);
                imagecopy($gd, $dst, $pos['x'], $pos['y'], 0, 0, $dst_w, $dst_h);

                if ($dst !== $wm) imagedestroy($dst);
                return true;
            }
        }

        // Фолбэк: текст по центру
        return $this->apply_text_fallback_centered($gd);
    }

    /**
     * Меняет «общую» непрозрачность PNG-изображения, не убивая внутреннюю альфу.
     * $opacity: 0..1 (0 — полностью прозрачно, 1 — исходная альфа)
     */
    private function gd_apply_global_opacity($im, float $opacity): void
    {
        $opacity = max(0.0, min(1.0, $opacity));
        imagealphablending($im, false);
        imagesavealpha($im, true);

        $w = imagesx($im);
        $h = imagesy($im);

        // Для ускорения кэшируем уже выделенные цвета
        $cache = [];

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgba = imagecolorat($im, $x, $y);
                $c    = imagecolorsforindex($im, $rgba); // ['red','green','blue','alpha']
                $key  = "{$c['red']},{$c['green']},{$c['blue']},{$c['alpha']}";

                if (!isset($cache[$key])) {
                    // GD альфа: 0 — непрозрачно, 127 — полностью прозрачно
                    $aOld = (int) $c['alpha'];
                    $aNew = (int) round($aOld + (127 - $aOld) * (1 - $opacity)); // увеличиваем прозрачность

                    $cache[$key] = imagecolorallocatealpha($im, $c['red'], $c['green'], $c['blue'], $aNew);
                }

                imagesetpixel($im, $x, $y, $cache[$key]);
            }
        }
    }

    private function apply_text_fallback_centered($im): bool
    {
        $text = (string)(wp_parse_url(home_url('/'), PHP_URL_HOST) ?: 'watermark');

        $font = 5;
        $tw   = imagefontwidth($font) * strlen($text);
        $th   = imagefontheight($font);

        $iw = imagesx($im);
        $ih = imagesy($im);

        $x = (int) round(($iw - $tw) / 2);
        $y = (int) round(($ih - $th) / 2);

        $bg  = imagecolorallocatealpha($im, 0, 0, 0, 100);
        $fg  = imagecolorallocatealpha($im, 255, 255, 255, 40);
        $pad = 8;

        imagefilledrectangle($im, $x - $pad, $y - $pad, $x + $tw + $pad, $y + $th + $pad, $bg);
        imagestring($im, $font, $x, $y, $text, $fg);
        return true;
    }

    /* ===================== БЭКАП/ВОССТАНОВЛЕНИЕ ===================== */

    private function ensure_backup_file(string $path): bool
    {
        if (!file_exists($path)) return false;
        $bak = $path . '.bak';
        if (file_exists($bak)) return true;
        if (!@copy($path, $bak)) return false;
        @chmod($bak, fileperms($path) & 0777);
        return true;
    }

    private function ensure_backup_attachment(int $attachment_id): void
    {
        $orig = get_attached_file($attachment_id);
        if ($orig) $this->ensure_backup_file($orig);

        $meta = wp_get_attachment_metadata($attachment_id);
        if (!empty($meta['sizes'])) {
            $base_dir = dirname($orig);
            foreach ($meta['sizes'] as $size) {
                if (empty($size['file'])) continue;
                $p = $base_dir . '/' . $size['file'];
                if (file_exists($p)) $this->ensure_backup_file($p);
            }
        }
    }

    private function restore_file_from_backup(string $path): bool
    {
        $bak = $path . '.bak';
        if (!file_exists($bak) || !file_exists($path)) return false;
        return (bool) @copy($bak, $path);
    }

    private function restore_attachment(int $attachment_id): array
    {
        $restored = 0;
        $miss = 0;

        $orig = get_attached_file($attachment_id);
        if ($orig) {
            $this->restore_file_from_backup($orig) ? $restored++ : $miss++;
        }

        $meta = wp_get_attachment_metadata($attachment_id);
        if (!empty($meta['sizes'])) {
            $base_dir = dirname($orig);
            foreach ($meta['sizes'] as $size) {
                if (empty($size['file'])) continue;
                $p = $base_dir . '/' . $size['file'];
                if (file_exists($p . '.bak')) {
                    $this->restore_file_from_backup($p) ? $restored++ : $miss++;
                }
            }
        }

        return ['restored' => $restored, 'miss' => $miss];
    }

    private function restore_model(int $model_id): array
    {
        $atts = get_attached_media('image', $model_id);
        $sumR = 0;
        $sumM = 0;
        $count = 0;

        foreach ($atts as $att) {
            $res = $this->restore_attachment($att->ID);
            $sumR += $res['restored'];
            $sumM += $res['miss'];
            $count++;
        }
        return ['attachments' => $count, 'restored' => $sumR, 'miss' => $sumM];
    }

    /* ===================== РАЗМЕРЫ КАРТИНОК ===================== */

    private function process_image_sizes(int $attachment_id): void
    {
        $meta = wp_get_attachment_metadata($attachment_id);
        if (empty($meta['sizes'])) return;

        $base_dir = dirname(get_attached_file($attachment_id));
        foreach ($meta['sizes'] as $size) {
            if (empty($size['file'])) continue;
            $p = $base_dir . '/' . $size['file'];
            if (file_exists($p)) {
                $this->ensure_backup_file($p);
                $this->apply_watermark($p);
            }
        }
    }

    /* ===================== МАССОВАЯ ОБРАБОТКА ===================== */

    public function ajax_process_watermarks_all(): void
    {
        if (!current_user_can('manage_options')) wp_die('Access denied');
        wp_send_json_success($this->process_all_models_images());
    }

    public function process_all_models_images(): array
    {
        $models = get_posts([
            'post_type'   => $this->ptype,
            'post_status' => 'any',
            'numberposts' => -1,
            'fields'      => 'ids',
        ]);

        $processed = 0;
        $errors = 0;

        foreach ($models as $mid) {
            $atts = get_attached_media('image', $mid);
            foreach ($atts as $att) {
                $path = get_attached_file($att->ID);
                if ($path && file_exists($path)) {
                    $this->ensure_backup_attachment($att->ID);
                    if ($this->apply_watermark($path)) {
                        $processed++;
                        $this->process_image_sizes($att->ID);
                    } else {
                        $errors++;
                    }
                }
            }
        }

        return [
            'mode'         => 'all',
            'processed'    => $processed,
            'errors'       => $errors,
            'models_count' => count($models),
        ];
    }

    public function ajax_process_watermarks_batch(): void
    {
        if (!current_user_can('manage_options')) wp_die('Access denied');

        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'models_watermark_nonce')) {
            wp_send_json_error(['message' => 'Bad nonce'], 400);
        }

        $page = max(1, (int)($_POST['page'] ?? 1));
        $per  = max(1, min(100, (int)($_POST['per_page'] ?? 10)));

        $q = new WP_Query([
            'post_type'      => $this->ptype,
            'post_status'    => 'any',
            'posts_per_page' => $per,
            'paged'          => $page,
            'fields'         => 'ids',
            'orderby'        => 'ID',
            'order'          => 'ASC',
        ]);

        $processed = 0;
        $errors = 0;

        if ($q->have_posts()) {
            foreach ($q->posts as $mid) {
                $atts = get_attached_media('image', $mid);
                foreach ($atts as $att) {
                    $path = get_attached_file($att->ID);
                    if ($path && file_exists($path)) {
                        $this->ensure_backup_attachment($att->ID);
                        if ($this->apply_watermark($path)) {
                            $processed++;
                            $this->process_image_sizes($att->ID);
                        } else {
                            $errors++;
                        }
                    }
                }
            }
        }

        $has_more = ($page < (int)$q->max_num_pages);
        wp_send_json_success([
            'mode'        => 'batch',
            'processed'   => $processed,
            'errors'      => $errors,
            'page'        => $page,
            'per_page'    => $per,
            'has_more'    => $has_more,
            'next_page'   => $has_more ? $page + 1 : null,
            'total_pages' => (int)$q->max_num_pages,
        ]);
    }

    /* ===================== ПРЕВЬЮ ===================== */

    public function ajax_preview_watermark(): void
    {
        if (!current_user_can('upload_files')) wp_die('Access denied');

        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'models_watermark_nonce')) {
            wp_send_json_error(['message' => 'Bad nonce'], 400);
        }

        $att_id = (int)($_POST['attachment_id'] ?? 0);
        if ($att_id <= 0) wp_send_json_error(['message' => 'attachment_id required'], 400);

        $path = get_attached_file($att_id);
        if (!$path || !file_exists($path)) wp_send_json_error(['message' => 'file not found'], 404);

        $info = @getimagesize($path);
        if (!$info) wp_send_json_error(['message' => 'not an image'], 400);
        [$w, $h, $type] = $info;

        try {
            if (extension_loaded('imagick')) {
                $im = new Imagick($path);
                $orig_w = $im->getImageWidth();
                if ($orig_w > 1200) $im->resizeImage(1200, 0, Imagick::FILTER_LANCZOS, 1);

                $cur_w = $im->getImageWidth();
                $cur_h = $im->getImageHeight();

                $wm = new Imagick();
                $wm->setBackgroundColor(new ImagickPixel('transparent'));
                if (in_array($this->wm_ext, ['svg', 'svgz'], true)) {
                    $wm->setImageUnits(Imagick::RESOLUTION_PIXELSPERINCH);
                    $wm->setResolution(300, 300);
                }
                $wm->readImage($this->wm_path);

                $src_w = $wm->getImageWidth();
                $src_h = $wm->getImageHeight();

                [$dst_w, $dst_h] = $this->calc_wm_target_dims($cur_w, $cur_h, $src_w, $src_h);
                if ($dst_w !== $src_w || $dst_h !== $src_h) {
                    $wm->resizeImage($dst_w, $dst_h, Imagick::FILTER_LANCZOS, 1, true);
                }

                // Прозрачность 0.5
                $wm->evaluateImage(Imagick::EVALUATE_MULTIPLY, 0.5, Imagick::CHANNEL_ALPHA);

                $pos = $this->center_pos($cur_w, $cur_h, $dst_w, $dst_h);
                $im->compositeImage($wm, Imagick::COMPOSITE_OVER, $pos['x'], $pos['y']);

                $im->setImageFormat('jpeg');
                $im->setImageCompressionQuality(85);

                $blob = $im->getImageBlob();
                $data = 'data:image/jpeg;base64,' . base64_encode($blob);
                $wm->destroy();
                $im->destroy();

                wp_send_json_success(['data_url' => $data]);
            } else {
                $gd = $this->create_gd_from_type($path, $type);
                if (!$gd) wp_send_json_error(['message' => 'gd create failed'], 500);

                $cur_w = imagesx($gd);
                $cur_h = imagesy($gd);
                if ($cur_w > 1200) {
                    $new_h = (int) round($cur_h * (1200 / $cur_w));
                    $tmp = imagecreatetruecolor(1200, $new_h);
                    imagecopyresampled($tmp, $gd, 0, 0, 0, 0, 1200, $new_h, $cur_w, $cur_h);
                    imagedestroy($gd);
                    $gd = $tmp;
                    $cur_w = 1200;
                    $cur_h = $new_h;
                }

                $this->apply_overlay_via_gd($gd, $cur_w, $cur_h);

                ob_start();
                imagejpeg($gd, null, 85);
                $blob = ob_get_clean();
                imagedestroy($gd);
                $data = 'data:image/jpeg;base64,' . base64_encode($blob);
                wp_send_json_success(['data_url' => $data]);
            }
        } catch (\Throwable $e) {
            wp_send_json_error(['message' => $e->getMessage()], 500);
        }
    }

    /* ===================== СПИСОК ФОТО ПО МОДЕЛИ (для превью) ===================== */

    public function ajax_get_model_images(): void
    {
        if (!current_user_can('upload_files')) wp_die('Access denied');

        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'models_watermark_nonce')) {
            wp_send_json_error(['message' => 'Bad nonce'], 400);
        }

        $model_id = (int) ($_POST['model_id'] ?? 0);
        if ($model_id <= 0 || get_post_type($model_id) !== $this->ptype) {
            wp_send_json_error(['message' => 'Bad model_id'], 400);
        }

        $atts = get_attached_media('image', $model_id);
        $out  = [];
        foreach ($atts as $att) {
            $thumb = wp_get_attachment_image_src($att->ID, 'thumbnail');
            $url   = wp_get_attachment_url($att->ID);
            $out[] = [
                'id'    => $att->ID,
                'url'   => $url,
                'thumb' => $thumb ? $thumb[0] : $url,
            ];
        }

        wp_send_json_success(['items' => $out]);
    }

    /* ===================== ОТКАТ (вложение или целая модель) ===================== */

    public function ajax_restore_watermark(): void
    {
        if (!current_user_can('manage_options')) wp_die('Access denied');

        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'models_watermark_nonce')) {
            wp_send_json_error(['message' => 'Bad nonce'], 400);
        }

        $attachment_id = (int) ($_POST['attachment_id'] ?? 0);
        $model_id      = (int) ($_POST['model_id']      ?? 0);

        if ($attachment_id > 0) {
            $r = $this->restore_attachment($attachment_id);
            wp_send_json_success(['scope' => 'attachment', 'result' => $r]);
        } elseif ($model_id > 0 && get_post_type($model_id) === $this->ptype) {
            $r = $this->restore_model($model_id);
            wp_send_json_success(['scope' => 'model', 'result' => $r]);
        }

        wp_send_json_error(['message' => 'Pass attachment_id or model_id'], 400);
    }

    /* ===================== АДМИНКА ===================== */

    public function add_admin_page(): void
    {
        add_submenu_page(
            'edit.php?post_type=' . $this->ptype,
            'Водяные знаки',
            'Водяные знаки',
            'manage_options',
            'models-watermark',
            [$this, 'admin_page_content']
        );
    }

    public function admin_page_content(): void
    {
        // Сохранение настроек
        if (!empty($_POST['save_wm_options'])) {
            check_admin_referer('save_wm_options_nonce');

            $this->enabled = !empty($_POST['wm_enabled']);
            update_option('models_watermark_enabled', $this->enabled);

            $mode = isset($_POST['wm_mode']) ? (string) $_POST['wm_mode'] : 'percent';
            $mode = in_array($mode, ['original', 'percent'], true) ? $mode : 'percent';
            $this->wm_mode = $mode;
            update_option('models_watermark_mode', $mode);

            $percent = isset($_POST['wm_percent']) ? (int) $_POST['wm_percent'] : 35;
            $percent = max(1, min(200, $percent));
            $this->wm_percent = $percent;
            update_option('models_watermark_percent', $percent);

            echo '<div class="notice notice-success"><p>Настройки сохранены.</p></div>';
        }

        // Тоггл (совместимость со старой кнопкой)
        if (isset($_POST['toggle_watermark'])) {
            $this->enabled = !$this->enabled;
            update_option('models_watermark_enabled', $this->enabled);
            echo '<div class="notice notice-success"><p>Статус переключён.</p></div>';
        }

        $nonce = wp_create_nonce('models_watermark_nonce');
?>
        <div class="wrap">
            <h1>Водяные знаки для «Моделей»</h1>

            <form method="post" style="margin-bottom:16px;">
                <?php wp_nonce_field('save_wm_options_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Статус</th>
                        <td>
                            <label>
                                <input type="checkbox" name="wm_enabled" value="1" <?php checked($this->enabled, true); ?>>
                                Включить водяные знаки
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Файл водяного знака</th>
                        <td>
                            <code><?php echo esc_html($this->wm_path); ?></code><br>
                            Статус:
                            <?php if (file_exists($this->wm_path)) : ?>
                                <span class="dashicons dashicons-yes-alt" style="color:#198754"></span> Найден
                            <?php else : ?>
                                <span class="dashicons dashicons-dismiss" style="color:#dc3545"></span> Не найден
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Размер водяного знака</th>
                        <td>
                            <fieldset>
                                <label style="display:block;margin:6px 0;">
                                    <input type="radio" name="wm_mode" value="original" <?php checked($this->wm_mode, 'original'); ?>>
                                    Оригинальный размер (как есть). Если больше кадра — уменьшить, чтобы влез.
                                </label>
                                <label style="display:block;margin:6px 0;">
                                    <input type="radio" name="wm_mode" value="percent" <?php checked($this->wm_mode, 'percent'); ?>>
                                    Процент от меньшей стороны кадра:
                                    <input type="number" name="wm_percent" min="1" max="200" value="<?php echo esc_attr($this->wm_percent); ?>" class="small-text"> %
                                </label>
                                <p class="description">По умолчанию сейчас 35% (больше, чем было).</p>
                            </fieldset>
                        </td>
                    </tr>
                </table>

                <p>
                    <button type="submit" name="save_wm_options" class="button button-primary">Сохранить параметры</button>
                </p>
            </form>

            <hr>

            <h2>Массовая обработка (батчами)</h2>
            <p>Запускайте частями, чтобы не перегружать сервер.</p>
            <div style="display:flex;gap:8px;align-items:center;margin:8px 0;">
                <label for="wm-per-page"><strong>Моделей за шаг:</strong></label>
                <input id="wm-per-page" type="number" min="1" max="100" value="10" class="small-text">
                <button id="wm-start" class="button button-primary">Начать</button>
                <button id="wm-next" class="button">Следующий батч</button>
                <button id="wm-auto" class="button">Автопроход</button>
            </div>

            <div id="wm-progress" style="display:none;margin-top:10px;">
                <p><strong>Шаг <span id="wm-page">1</span>/<span id="wm-pages">?</span></strong></p>
                <p>Обработано изображений: <span id="wm-done">0</span>,
                    Ошибок: <span id="wm-err">0</span></p>
                <div id="wm-log"></div>
            </div>

            <hr>

            <h2>Превью водяного знака</h2>
            <p>1) По ID вложения или 2) Выбрать модель и кликнуть миниатюру.</p>

            <div style="display:flex;gap:8px;align-items:center;margin:8px 0;">
                <label for="wm-att-id"><strong>ID вложения:</strong></label>
                <input id="wm-att-id" type="number" class="small-text" placeholder="123">
                <button id="wm-preview" class="button">Показать превью</button>
            </div>

            <div style="display:flex;gap:16px;align-items:flex-start;margin:12px 0;">
                <div>
                    <label for="wm-model-id"><strong>ID модели:</strong></label>
                    <input id="wm-model-id" type="number" class="small-text" placeholder="456">
                    <button id="wm-load-model" class="button">Загрузить фото модели</button>
                    <div id="wm-model-grid" style="display:grid;grid-template-columns:repeat(auto-fill,90px);gap:8px;margin-top:10px;"></div>
                </div>
                <div style="flex:1;">
                    <div id="wm-preview-wrap" style="margin-top:10px;display:none;">
                        <img id="wm-preview-img" src="" alt="Превью водяного знака" style="max-width:100%;height:auto;border:1px solid #ddd;">
                    </div>
                </div>
            </div>

            <hr>

            <h2>Откат</h2>
            <div style="display:flex;gap:12px;align-items:center;margin:8px 0;">
                <label for="wm-rollback-att">ID вложения:</label>
                <input id="wm-rollback-att" type="number" class="small-text" placeholder="123">
                <button id="wm-rollback-att-btn" class="button">Откатить фото</button>
            </div>
            <div style="display:flex;gap:12px;align-items:center;margin:8px 0;">
                <label for="wm-rollback-model">ID модели:</label>
                <input id="wm-rollback-model" type="number" class="small-text" placeholder="456">
                <button id="wm-rollback-model-btn" class="button">Откатить все фото модели</button>
            </div>
        </div>

        <script>
            (function() {
                const ajax = window.ajaxurl;
                const nonce = <?php echo json_encode($nonce); ?>;
                const $ = (s, r = document) => r.querySelector(s);

                /* ===== Батчи ===== */
                let page = 1,
                    per = 10,
                    auto = false,
                    totalPages = null,
                    done = 0,
                    err = 0;
                const ui = {
                    per: $('#wm-per-page'),
                    start: $('#wm-start'),
                    next: $('#wm-next'),
                    auto: $('#wm-auto'),
                    prog: $('#wm-progress'),
                    page: $('#wm-page'),
                    pages: $('#wm-pages'),
                    done: $('#wm-done'),
                    err: $('#wm-err'),
                    log: $('#wm-log'),
                };

                function setProg(v) {
                    ui.prog.style.display = v ? 'block' : 'none';
                }

                async function runBatch() {
                    const body = new URLSearchParams();
                    body.set('action', 'process_models_watermarks_batch');
                    body.set('nonce', nonce);
                    body.set('page', String(page));
                    body.set('per_page', String(per));

                    ui.log.insertAdjacentHTML('beforeend', `<p>Батч #${page}…</p>`);
                    const res = await fetch(ajax, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: body.toString()
                    });
                    const j = await res.json();
                    if (!j || !j.success) throw new Error(j?.data?.message || 'Ошибка ответа');

                    done += (j.data.processed || 0);
                    err += (j.data.errors || 0);
                    if (totalPages === null) totalPages = j.data.total_pages || 1;

                    ui.page.textContent = String(page);
                    ui.pages.textContent = String(totalPages);
                    ui.done.textContent = String(done);
                    ui.err.textContent = String(err);

                    if (j.data.has_more && j.data.next_page) {
                        page = j.data.next_page;
                        if (auto) await runBatch();
                    } else {
                        auto = false;
                        ui.log.insertAdjacentHTML('beforeend', `<p><strong>Готово</strong></p>`);
                    }
                }

                ui.start.addEventListener('click', async (e) => {
                    e.preventDefault();
                    page = 1;
                    per = Math.max(1, Math.min(100, parseInt(ui.per.value || '10', 10)));
                    totalPages = null;
                    done = 0;
                    err = 0;
                    setProg(true);
                    try {
                        await runBatch();
                    } catch (ex) {
                        alert(ex.message || ex);
                    }
                });
                ui.next.addEventListener('click', async (e) => {
                    e.preventDefault();
                    if (!totalPages) {
                        alert('Сначала «Начать».');
                        return;
                    }
                    try {
                        await runBatch();
                    } catch (ex) {
                        alert(ex.message || ex);
                    }
                });
                ui.auto.addEventListener('click', async (e) => {
                    e.preventDefault();
                    if (!totalPages) {
                        page = 1;
                        per = Math.max(1, Math.min(100, parseInt(ui.per.value || '10', 10)));
                        totalPages = null;
                        done = 0;
                        err = 0;
                        setProg(true);
                    }
                    auto = true;
                    try {
                        await runBatch();
                    } catch (ex) {
                        auto = false;
                        alert(ex.message || ex);
                    }
                });

                /* ===== Превью по ID вложения ===== */
                const prevBtn = document.getElementById('wm-preview');
                const attInp = document.getElementById('wm-att-id');
                const prevWrap = document.getElementById('wm-preview-wrap');
                const prevImg = document.getElementById('wm-preview-img');

                prevBtn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const id = parseInt(attInp.value || '0', 10);
                    if (!id) {
                        alert('Укажите ID вложения');
                        return;
                    }
                    const body = new URLSearchParams();
                    body.set('action', 'preview_models_watermark');
                    body.set('nonce', nonce);
                    body.set('attachment_id', String(id));
                    prevBtn.disabled = true;
                    try {
                        const res = await fetch(ajax, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: body.toString()
                        });
                        const j = await res.json();
                        if (!j || !j.success) throw new Error(j?.data?.message || 'Ошибка превью');
                        prevImg.src = j.data.data_url;
                        prevWrap.style.display = 'block';
                    } catch (ex) {
                        alert(ex.message || ex);
                    } finally {
                        prevBtn.disabled = false;
                    }
                });

                /* ===== Превью: выбрать модель, показать сетку её фото, клик по миниатюре ===== */
                const modelInp = document.getElementById('wm-model-id');
                const modelBtn = document.getElementById('wm-load-model');
                const modelGrid = document.getElementById('wm-model-grid');

                modelBtn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const mid = parseInt(modelInp.value || '0', 10);
                    if (!mid) {
                        alert('Укажите ID модели');
                        return;
                    }
                    const body = new URLSearchParams();
                    body.set('action', 'get_model_images');
                    body.set('nonce', nonce);
                    body.set('model_id', String(mid));
                    modelBtn.disabled = true;
                    modelGrid.innerHTML = '';
                    try {
                        const res = await fetch(ajax, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: body.toString()
                        });
                        const j = await res.json();
                        if (!j || !j.success) throw new Error(j?.data?.message || 'Не удалось получить фото модели');
                        const items = j.data.items || [];
                        if (!items.length) {
                            modelGrid.innerHTML = '<em>У модели нет изображений</em>';
                            return;
                        }
                        for (const it of items) {
                            const a = document.createElement('a');
                            a.href = '#';
                            a.dataset.attId = String(it.id);
                            a.innerHTML = `<img src="${it.thumb}" alt="att ${it.id}" style="width:90px;height:90px;object-fit:cover;border:1px solid #ccc;">`;
                            a.addEventListener('click', async (ev) => {
                                ev.preventDefault();
                                const body2 = new URLSearchParams();
                                body2.set('action', 'preview_models_watermark');
                                body2.set('nonce', nonce);
                                body2.set('attachment_id', String(it.id));
                                const r2 = await fetch(ajax, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded'
                                    },
                                    body: body2.toString()
                                });
                                const j2 = await r2.json();
                                if (!j2 || !j2.success) {
                                    alert(j2?.data?.message || 'Ошибка превью');
                                    return;
                                }
                                prevImg.src = j2.data.data_url;
                                prevWrap.style.display = 'block';
                            });
                            modelGrid.appendChild(a);
                        }
                    } catch (ex) {
                        alert(ex.message || ex);
                    } finally {
                        modelBtn.disabled = false;
                    }
                });

                /* ===== Откат ===== */
                const rbAttInp = document.getElementById('wm-rollback-att');
                const rbAttBtn = document.getElementById('wm-rollback-att-btn');
                rbAttBtn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const id = parseInt(rbAttInp.value || '0', 10);
                    if (!id) {
                        alert('ID вложения?');
                        return;
                    }
                    const body = new URLSearchParams();
                    body.set('action', 'restore_models_watermark');
                    body.set('nonce', nonce);
                    body.set('attachment_id', String(id));
                    rbAttBtn.disabled = true;
                    try {
                        const res = await fetch(ajax, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: body.toString()
                        });
                        const j = await res.json();
                        if (!j || !j.success) throw new Error(j?.data?.message || 'Ошибка отката');
                        alert(`Откат: восстановлено файлов ${j.data.result.restored}, не найдено бэкапов ${j.data.result.miss}`);
                    } catch (ex) {
                        alert(ex.message || ex);
                    } finally {
                        rbAttBtn.disabled = false;
                    }
                });

                const rbModelInp = document.getElementById('wm-rollback-model');
                const rbModelBtn = document.getElementById('wm-rollback-model-btn');
                rbModelBtn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const mid = parseInt(rbModelInp.value || '0', 10);
                    if (!mid) {
                        alert('ID модели?');
                        return;
                    }
                    const body = new URLSearchParams();
                    body.set('action', 'restore_models_watermark');
                    body.set('nonce', nonce);
                    body.set('model_id', String(mid));
                    rbModelBtn.disabled = true;
                    try {
                        const res = await fetch(ajax, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: body.toString()
                        });
                        const j = await res.json();
                        if (!j || !j.success) throw new Error(j?.data?.message || 'Ошибка отката модели');
                        alert(`Откат модели: вложений ${j.data.result.attachments}, восстановлено файлов ${j.data.result.restored}, нет бэкапов ${j.data.result.miss}`);
                    } catch (ex) {
                        alert(ex.message || ex);
                    } finally {
                        rbModelBtn.disabled = false;
                    }
                });
            })();
        </script>
<?php
    }
}

new ModelsWatermark();
