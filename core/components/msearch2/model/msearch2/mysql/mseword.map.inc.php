<?php
$xpdo_meta_map['mseWord']= array (
  'package' => 'msearch2',
  'version' => '1.1',
  'table' => 'mse2_words',
  'extends' => 'xPDOObject',
  'fields' => 
  array (
    'word' => NULL,
    'resource' => NULL,
    'weight' => NULL,
  ),
  'fieldMeta' => 
  array (
    'word' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '32',
      'phptype' => 'string',
      'null' => false,
    ),
    'resource' => 
    array (
      'dbtype' => 'integer',
      'precision' => '10',
      'phptype' => 'integer',
      'attributes' => 'unsigned',
      'null' => false,
    ),
    'weight' => 
    array (
      'dbtype' => 'integer',
      'precision' => '10',
      'phptype' => 'integer',
      'attributes' => 'unsigned',
      'null' => false,
    ),
  ),
  'indexes' => 
  array (
    'word' => 
    array (
      'alias' => 'word',
      'primary' => true,
      'unique' => true,
      'type' => 'BTREE',
      'columns' => 
      array (
        'word' => 
        array (
          'length' => '',
          'collation' => 'A',
          'null' => false,
        ),
        'resource' => 
        array (
          'length' => '',
          'collation' => 'A',
          'null' => false,
        ),
      ),
    ),
  ),
  'aggregates' => 
  array (
    'Resource' => 
    array (
      'class' => 'modResource',
      'local' => 'resource',
      'foreign' => 'id',
      'owner' => 'foreign',
      'cardinality' => 'one',
    ),
  ),
);
