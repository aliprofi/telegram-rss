<?php
/*
Plugin Name: Telegram RSS Embed (Custom Layout)
Description: Выводит последние посты из публичного Telegram канала через RSS с минимальным оформлением.
Version: 1.1
Author: Gemini Helper
*/

// --- КОНФИГУРАЦИЯ TELEGRAM RSS ---
// ⚠️ ОБЯЗАТЕЛЬНО: Проверьте и замените на актуальный URL вашего фида
define('TELEGRAM_RSS_FEED_URL', 'https://fetchrss.com/feed/1vUlzp7Xn8Pa1vUlzG7r31Zq.rss'); 
define('MAX_POSTS_TO_SHOW', 4); // Количество анонсов для вывода

/**
 * Основная функция для получения и отображения анонсов Telegram.
 * Используется и для шорткода, и для прямого вызова в шаблоне.
 */
function display_telegram_announcements() {
    // Подключаем класс для работы с RSS-лентой
    if (!function_exists('fetch_feed')) {
        include_once(ABSPATH . WPINC . '/feed.php');
    }

    $rss = fetch_feed(TELEGRAM_RSS_FEED_URL);
    $output = '';

    // Обработка ошибок
    if (is_wp_error($rss)) {
        if (current_user_can('manage_options')) {
            $output .= '<p>Ошибка получения RSS-ленты Telegram: ' . $rss->get_error_message() . '</p>';
        }
        return $output;
    }

    $max_items = $rss->get_item_quantity(MAX_POSTS_TO_SHOW);
    $rss_items = $rss->get_items(0, $max_items);
    
    if ($max_items == 0) {
        $output .= '<p>В Telegram-канале пока нет записей.</p>';
        return $output;
    }

    // Открытие блока
    $output .= '<div class="telegram-rss-block">';
    
    // Вывод каждого элемента
    foreach ( $rss_items as $item ) {
        $title = esc_html($item->get_title());
        $link = esc_url($item->get_permalink());
        $content = $item->get_content(); 

        // 1. ИСПРАВЛЕНИЕ СЛИТНОСТИ ТЕКСТА: Заменяем переносы строки и множественные пробелы на один пробел
        $content = preg_replace('/\s+/', ' ', $content); 

        // 2. Извлечение первого изображения из контента
        $image_url = '';
        if (preg_match('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $content, $matches)) {
            $image_url = $matches[1];
        }

        $output .= '<div class="telegram-post-announcement">';
        
        // Блок с картинкой (если она есть)
        if ($image_url) {
            $output .= '<div class="tg-image-wrapper">';
            $output .= '<a href="' . $link . '" target="_blank" rel="noopener noreferrer">';
            $output .= '<img src="' . $image_url . '" alt="' . $title . '" class="tg-announcement-image">';
            $output .= '</a>';
            $output .= '</div>';
        }

        // Блок с заголовком
        $output .= '<div class="tg-text-wrapper">';
        $output .= '<h3><a href="' . $link . '" target="_blank" rel="noopener noreferrer">' . $title . '</a></h3>';
        $output .= '</div>';
        
        $output .= '</div>';
    }
    
    $output .= '</div>';

    return $output;
}

/**
 * Регистрация шорткода [telegram_rss] для использования в редакторе.
 * Важно: функция display_telegram_announcements должна возвращать (return), а не выводить (echo) контент.
 */
function register_telegram_shortcode() {
    add_shortcode('telegram_rss', 'display_telegram_announcements');
}
add_action('init', 'register_telegram_shortcode'); 


// Добавление кастомных стилей
function telegram_rss_styles() {
    // Включаем стили только если мы не в админке
    if (is_admin()) return; 
    
    ?>
    <style>
        .telegram-rss-block {
            border: 1px solid #e0e0e0;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9em;
        }
        .telegram-post-announcement {
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eeeeee;
            padding: 10px 0;
        }
        .telegram-post-announcement:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .tg-image-wrapper {
            flex-shrink: 0;
            margin-right: 10px;
            width: 80px;
            height: 80px;
            overflow: hidden;
            border-radius: 4px;
        }
        .tg-announcement-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .tg-text-wrapper {
            flex-grow: 1;
        }
        .telegram-post-announcement h3 {
            font-size: 1.1em;
            margin: 0;
            line-height: 1.3;
        }
        .telegram-post-announcement h3 a {
            color: #333;
            text-decoration: none;
        }
        .telegram-post-announcement h3 a:hover {
            text-decoration: underline;
        }
    </style>
    <?php
}
add_action('wp_head', 'telegram_rss_styles');