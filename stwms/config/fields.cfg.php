﻿<?php
/**
	字段类型说明：
	  基本字段-》
	  		此值由系统设定，添加时系统可以自动设定的值包括'catid','isimages','status','userid',
	  		'addtime','edittime','hits','allimages'，编辑时包括'catid','isimages','edittime','allimages'；	
	  系统字段-》
	  		由系统自动创建，创建之后不能修改字段名称、类型，不能删除或禁用；
	  栏目必须字段-》
	  		当应用到栏目时必须填写的字段，此字段不能修改、删除和禁用。
*/
  return array(
  array(
    'field'=>'catid',
    'formtype'=>'catid',
    'name'=>'栏目',
    'issystem'=>'1',
    'isbase'=>'1',
    'listorder'=>'12',
    'dsetting'=> array ('isindex' => '1'),
    'iscatneed'=>'1',
    'groupids'=>'0|-1'
  ),
  array(
    'field'=>'typeid',
    'formtype'=>'classid',
    'name'=>'类别',
    'issystem'=>'0',
    'isbase'=>'0',
    'listorder'=>'11',
    'iscatneed'=>'0'
  ),
  array(
    'field'=>'title',
    'formtype'=>'title',
    'name'=>'标题',
    'issystem'=>'1',
    'isbase'=>'0',
    'listorder'=>'10',
    'msetting'=> array('istolist'=>'1','issearch'=>'1','align' => 'left'),
    'iscatneed'=>'0'
   ),
  array(
    'field'=>'keywords',
    'formtype'=>'keyword',
    'name'=>'关键字',
    'issystem'=>'0',
    'isbase'=>'0',
    'listorder'=>'9',
    'iscatneed'=>'0'
   ),
  array(
    'field'=>'description',
    'formtype'=>'textarea',
    'name'=>'摘要',
    'issystem'=>'0',
    'isbase'=>'0',
    'listorder'=>'8',
    'iscatneed'=>'0'
   ),
  array(
    'field'=>'status',
    'formtype'=>'smallint',
    'name'=>'状态',
    'issystem'=>'1',
    'isbase'=>'1',
    'listorder'=>'7',
    'dsetting'=> array ('isindex' => '1'),
    'iscatneed'=>'1',
    'groupids'=>'0|-1'
   ),
  array(
    'field'=>'listorder',
    'formtype'=>'integer',
    'name'=>'排序',
    'issystem'=>'1',
    'isbase'=>'1',
    'listorder'=>'6',
    'dsetting'=> array ('isindex' => '1'),
    'iscatneed'=>'0',
    'groupids'=>'0|-1'
   ),
  array(
    'field'=>'isimages',
    'formtype'=>'smallint',
    'name'=>'是否有图片',
    'issystem'=>'1',
    'isbase'=>'1',
    'listorder'=>'5',
    'dsetting'=> array ('isindex' => '1'),
    'iscatneed'=>'1',
    'groupids'=>'0|-1'
   ),
  array(
    'field'=>'userid',
    'formtype'=>'integer',
    'name'=>'用户ID',
    'issystem'=>'1',
    'isbase'=>'1',
    'listorder'=>'4',
    'iscatneed'=>'0',
    'groupids'=>'0|-1'
   ),
  array(
    'field'=>'addtime',
    'formtype'=>'datetime',
    'name'=>'添加时间',
    'issystem'=>'1',
    'isbase'=>'1',
    'listorder'=>'3',
    'msetting'=> array('istolist'=>'1','isorder'=>'1','width' => '120','align' => 'center'),
    'iscatneed'=>'0',
    'groupids'=>'0|-1'
   ),
  array(
    'field'=>'edittime',
    'formtype'=>'datetime',
    'name'=>'编辑时间',
    'issystem'=>'1',
    'isbase'=>'1',
    'listorder'=>'2',
    'iscatneed'=>'0',
    'groupids'=>'0|-1'
   ),
  array(
    'field'=>'hits',
    'formtype'=>'integeru',
    'name'=>'点击量',
    'issystem'=>'1',
    'isbase'=>'1',
    'listorder'=>'1',
    'msetting'=> array('istolist'=>'1','isorder'=>'1','width' => '70','align' => 'center'),
    'iscatneed'=>'0',
    'groupids'=>'0|-1'
   ),
 );
?>
