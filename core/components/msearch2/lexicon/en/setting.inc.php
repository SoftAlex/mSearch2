<?php
/**
* Settings English Lexicon Entries for mSearch2
*
* @package msearch2
* @subpackage lexicon
*/

$_lang['area_mse2_main'] = 'Main';
$_lang['area_mse2_search'] = 'Search';
$_lang['area_mse2_index'] = 'Index';

$_lang['setting_mse2_index_fields'] = 'Index fields';
$_lang['setting_mse2_index_fields_desc'] = 'You can specify, which fields need to index. Weight of word in field sets through colon. Template variables must be set with prefix "tv_", for example "pagetitle:3,tv_color:1".';
$_lang['setting_mse2_index_comments'] = 'Index comments';
$_lang['setting_mse2_index_comments_desc'] = 'If true and you using component "Tickets" - comments of resources will be indexed.';
$_lang['setting_mse2_index_comments_weight'] = 'Weight of word in comment';
$_lang['setting_mse2_index_comments_weight_desc'] = 'You can specify weight of word in comment. Default is "1".';
$_lang['setting_mse2_index_min_words_length'] = 'Minimum length of words';
$_lang['setting_mse2_index_min_words_length_desc'] = 'Specify the minimum length of words that will be in index. Default value "3".';

$_lang['setting_mse2_search_exact_match_bonus'] = 'Bonus for an exact match';
$_lang['setting_mse2_search_exact_match_bonus_desc'] = 'Specify the number of points added for an exact match the search phrase and the result. Default is "5".';
$_lang['setting_mse2_search_all_words_bonus'] = 'Bonus for the match whole words';
$_lang['setting_mse2_search_all_words_bonus_desc'] = 'If a search request consists of several words, and all of them were found in the resource - will assigned extra points. Default is "5".';
$_lang['setting_mse2_search_split_words'] = 'Breakdown query on the words';
$_lang['setting_mse2_search_split_words_desc'] = 'Regular expression for php function preg_split(), which breaks the user`s request to separate words for the search. By default words breaks by spaces.';

$_lang['setting_mse2_filters_handler_class'] = 'Filters handler class';
$_lang['setting_mse2_filters_handler_class_desc'] = 'The name of the class that implements the logic of a filters. Default is "mse2FiltersHandler".';

$_lang['setting_mse2_frontend_css'] = 'Frontend styles';
$_lang['setting_mse2_frontend_css_desc'] = 'Path to file with styles of the shop. If you want to use your own styles - specify them here, or clean this parameter and load them in site template.';
$_lang['setting_mse2_frontend_js'] = 'Frontend scripts';
$_lang['setting_mse2_frontend_js_desc'] = 'Path to file with scripts of the shop. If you want to use your own sscripts - specify them here, or clean this parameter and load them in site template.';