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
$_lang['mse2_prop_showDeleted'] = 'Show deleted goods.';
$_lang['mse2_prop_showHidden'] = 'Show goods, that hidden in menu.';
$_lang['mse2_prop_showZeroPrice'] = 'Show goods with zero price.';