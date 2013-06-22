<?php
/**
 * Properties English Lexicon Entries for mSearch2
 *
 * @package msearch2
 * @subpackage lexicon
 */

$_lang['mse2_prop_tpl'] = 'The chunk tpl to use for each row.';
$_lang['mse2_prop_limit'] = 'The number of results to limit.';
$_lang['mse2_prop_offset'] = 'An offset of resources returned by the criteria to skip.';
$_lang['mse2_prop_outputSeparator'] = 'An optional string to separate each tpl instance.';
$_lang['mse2_prop_toPlaceholder'] = 'If not empty, the snippet will save output to placeholder with that name, instead of return it to screen.';

$_lang['mse2_prop_returnIds'] = 'Return comma-separated list of ids of matched resources.';
$_lang['mse2_prop_showLog'] = 'Display additional information about snippet work. Only for authenticated in context "mgr".';
$_lang['mse2_prop_fastMode'] = 'If enabled, then in chunk will be only received values ​​from the database. All raw tags of MODX, such as filters, snippets calls will be cut.';

$_lang['mse2_prop_parents'] = 'Container list, separated by commas, to search results. By default, the query is limited to the current parent. If set to 0, query not limited.';
$_lang['mse2_prop_depth'] = 'Integer value indicating depth to search for resources from each parent.';

$_lang['mse2_prop_includeTVs'] = 'An optional comma-delimited list of TemplateVar names to include in selection. For example "action,time" give you placeholders [[+action]] and [[+time]].';
$_lang['mse2_prop_tvPrefix'] = 'The prefix for TemplateVar properties, "tv." for example. By default it is empty.';

$_lang['mse2_prop_where'] = 'A JSON-style expression of criteria to build any additional where clauses from.';
$_lang['mse2_prop_showUnpublished'] = 'Show unpublished resources.';
$_lang['mse2_prop_showDeleted'] = 'Show deleted resources.';
$_lang['mse2_prop_showHidden'] = 'Show resources, that hidden in menu.';

$_lang['mse2_prop_introCutBefore'] = 'Specify the number of characters to be output in placeholder [[+intro]] before the first match in the text. The default value of "50".';
$_lang['mse2_prop_introCutAfter'] = 'Specify the number of characters to be output in placeholder [[+intro]] after the first match in the text. Default - "250".';

$_lang['mse2_prop_htagOpen'] = 'The opening tag for the highlight of found results in [[+intro]].';
$_lang['mse2_prop_htagClose'] = 'Closing tag for the highlight of found results in [[+intro]].';

$_lang['mse2_prop_minQuery'] = 'The minimum length of a search query.';
$_lang['mse2_prop_parentsVar'] = 'The name of the variable to additional filter by parents. Default is "parents", can be send with $_REQUEST.';
$_lang['mse2_prop_queryVar'] = 'The name of the variable of search query to get it from $_REQUEST. Default is "query"';

$_lang['mse2_prop_paginator'] = 'Snippet for pagination, default is "getPage".';
$_lang['mse2_prop_element'] = 'Snippet, which will be called пагинатором to output the results of work. Default is "mSearch2".';
$_lang['mse2_prop_resources'] = 'List of resources for output, separated by commas. This list can be filtered by other parameters such as "parents", "showDeleted", "showHidden" and "showUnpublished".';
$_lang['mse2_prop_showEmptyFilters'] = 'Show filters when it has the only one item.';
$_lang['mse2_prop_sort'] = 'Comma separated list for sorting resources. It must be set in the form "table|field:direction". Default is "resource:publisedon:desc".';
$_lang['mse2_prop_filters'] = 'Comma separated list of filters. It must be set in the form "table|field:method". Default is "resource|parent:parents".';
$_lang['mse2_prop_disableSuggestions'] = 'This option disables the estimated number of results, which is displayed next to each filter. Activate if you are unhappy with filtration rate.';

$_lang['mse2_prop_tplOuter'] = 'Chunk for the whole block of filters and the results.';
$_lang['mse2_prop_tplFilter.outer.default'] = 'Standard chunk of one filters group.';
$_lang['mse2_prop_tplFilter.row.default'] = 'Standard chunk of a filter in the group. By default it look as checkbox.';