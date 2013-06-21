<?php
/**
 * Settings Russian Lexicon Entries for mSearch2
 *
 * @package msearch2
 * @subpackage lexicon
 */

$_lang['area_mse2_main'] = 'Основные';
$_lang['area_mse2_search'] = 'Поиск';
$_lang['area_mse2_index'] = 'Индексация';

$_lang['setting_mse2_index_fields'] = 'Индексация полей';
$_lang['setting_mse2_index_fields_desc'] = 'Укажите поля ресурса для индексации, через запятую. Каждому полю можно назначить вес слова через двоеточие. ТВ параметры указываются с префиксом "tv_", например "pagetitle:3,tv_color:1".';
$_lang['setting_mse2_index_comments'] = 'Индексировать комментарии';
$_lang['setting_mse2_index_comments_desc'] = 'Если эта настройка включена, и вы используете компонент "Tickets" - будут проиндексированы комментарии ресурсов.';
$_lang['setting_mse2_index_comments_weight'] = 'Вес слова в комментарии';
$_lang['setting_mse2_index_comments_weight_desc'] = 'Укажите вес слова для комментария. По умолчанию - "1".';
$_lang['setting_mse2_index_min_words_length'] = 'Минимальная длина слова';
$_lang['setting_mse2_index_min_words_length_desc'] = 'Укажите минимальную длину слова, которое будет учавствовать в поиске для исключения ложных срабатываний. По умолчанию - "3".';

$_lang['setting_mse2_search_exact_match_bonus'] = 'Балл за точное совпадение';
$_lang['setting_mse2_search_exact_match_bonus_desc'] = 'Укажите, сколько баллов добавлять за точное совпадение поисковой фразы и содержимого страницы. По умолчанию - "5".';
$_lang['setting_mse2_search_all_words_bonus'] = 'Бал за совпадение по всем словам';
$_lang['setting_mse2_search_all_words_bonus_desc'] = 'Если поисковый запрос состоит из нескольких слов, и все они были найдены в ресурсе - ему присваиваются дополнительные очки. По умолчанию - "5".';
$_lang['setting_mse2_search_split_words'] = 'Разбивка запроса на слова';
$_lang['setting_mse2_search_split_words_desc'] = 'Регулярное выражение для php функции preg_split(), которое разбивает запрос пользователя на отдельные слова для поиска. По умолчанию, разбивка идет по пробелам.';

$_lang['setting_mse2_filters_handler_class'] = 'Класс-обработчик фильтров';
$_lang['setting_mse2_filters_handler_class_desc'] = 'Имя класса, который реализует логику работы фильтров. По умолчанию - "mse2FiltersHandler".';

$_lang['setting_mse2_frontend_css'] = 'Стили фронтенда';
$_lang['setting_mse2_frontend_css_desc'] = 'Путь к файлу со стилями магазина. Если вы хотите использовать собственные стили - укажите путь к ним здесь, или очистите параметр и загрузите их вручную через шаблон сайта.';
$_lang['setting_mse2_frontend_js'] = 'Скрипты фронтенда';
$_lang['setting_mse2_frontend_js_desc'] = 'Путь к файлу со скриптами магазина. Если вы хотите использовать собственные скрипты - укажите путь к ним здесь, или очистите параметр и загрузите их вручную через шаблон сайта.';