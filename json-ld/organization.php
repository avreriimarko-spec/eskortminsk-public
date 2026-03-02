<?php
// organization.php — JSON-LD разметка организации (Минск, Беларусь)

defined('ABSPATH') || exit;

// Основные данные сайта
$org_name         = get_bloginfo('name');
$org_url          = home_url('/');
$org_description  = get_bloginfo('description');

// Логотип: Site Icon -> логотип темы
$org_logo = function_exists('get_site_icon_url') && get_site_icon_url(512)
    ? get_site_icon_url(512)
    : get_stylesheet_directory_uri() . '/assets/icons/logo.png';

// Контакты из кастомайзера

$phone_mod    = trim((string) get_theme_mod('contact_phone'));
$whats_mod    = trim((string) get_theme_mod('contact_whatsapp')); 
$email_mod    = trim((string) get_theme_mod('contact_email'));
$admin_email  = get_option('admin_email');

// Нормализация телефона: актуально для Беларуси (+375)
$normalize_phone = static function ($raw) {
    $digits = preg_replace('~\D+~', '', (string) $raw);
    if ($digits === '') return '';
    
    // Если начинается с 80 (внутрибелорусский формат) и длина 11 — преобразуем в +375
    if (str_starts_with($digits, '80') && strlen($digits) === 11) {
        $digits = '375' . substr($digits, 2);
    }
    
    return '+' . ltrim($digits, '+');
};

$org_phone = '+375298467440';
$org_phone = $normalize_phone($org_phone);

// Email: кастомайзер -> admin_email -> info@домен
if (!empty($email_mod)) {
    $org_email = $email_mod;
} elseif (!empty($admin_email)) {
    $org_email = $admin_email;
} else {
    $host      = preg_replace('~^www\.~i', '', (string) wp_parse_url($org_url, PHP_URL_HOST));
    $org_email = 'info@' . $host;
}

// sameAs (соцсети и мессенджеры)
$same_as = [];
$tg = trim((string) get_theme_mod('contact_telegram'));
if ($tg !== '') {
    $same_as[] = 'https://t.me/' . ltrim($tg, '@');
}
if ($whats_mod !== '') {
    $same_as[] = 'https://wa.me/' . preg_replace('~\D+~', '', $whats_mod);
}

// География: Беларусь / Минск
$area_served = [
    [
        "@type" => "Country",
        "name"  => "Беларусь",
    ],
    [
        "@type" => "City",
        "name"  => "Минск",
    ],
];

// Почтовый адрес (добавим нейтральный индекс и город)
$postal_address = [
    "@type"           => "PostalAddress",
    "addressLocality" => "Минск",
    "addressCountry"  => "BY",
    "postalCode"      => "220051", // Любой корректный индекс Минска
    "streetAddress"   => "Минск, Беларусь" // Общее указание без точного дома
];

$organization = [
    "@context"     => "https://schema.org",
    "@type"        => "Organization",
    "@id"          => trailingslashit($org_url) . "#organization",
    "name"         => $org_name,
    "url"          => $org_url,
    "logo"         => $org_logo,
    "description"  => $org_description,
    "address"      => $postal_address,
    "areaServed"   => $area_served,
    "contactPoint" => [
            "@type"       => "ContactPoint",
            "contactType" => "customer support",
            "telephone"   => $org_phone, // Теперь здесь всегда будет +375298467440
            "email"       => $org_email,
        ],
];

if ($org_phone !== '' && $org_phone !== '+') {
    $organization['contactPoint']['telephone'] = $org_phone;
}
if (!empty($org_email)) {
    $organization['contactPoint']['email'] = $org_email;
}
if (!empty($same_as)) {
    $organization['sameAs'] = $same_as;
}

echo '<script type="application/ld+json">' .
    wp_json_encode($organization, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) .
    '</script>';