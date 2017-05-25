<?php
class Form{

	/**
	 * 功能：产生编辑器 参数：($textareaid,模块名称,栏目id,编辑器设置)
	 */
	public static function editor($textareaid='content',$model='',$catid='',$settings=array()){
		$default=array('toolbar' => 'basic','color' => '','allowupload' => 0,'allowbrowser' => 1,'alowuploadexts' => '','height' => 300,'width' => 700,'show_status' => array('upload'),'allowuploadnum' => '10'); // 默认参数配置
		                                                                                                                                                                                                           
		// 语言设置
		$lang=load::cfg('system', 'lang');
		if(!strpos(SYS_PLUGIN_URL, '://') && !is_file(substr(ROOT_PATH, 0, -1) . SYS_PLUGIN_URL . 'editor/lang/' . $lang . '.js')){
			$lang='en';
		}
		
		$setting=array_merge($default, $settings);
		unset($settings, $default);
		$setting['width']=intval($setting['width']) + 39; // 内置宽度差异校正
		$str='';
		if(!defined('EDITOR_INIT')){
			$str.='<script type="text/javascript" src="' . SYS_PLUGIN_URL . 'editor/ckeditor.js"></script>';
			define('EDITOR_INIT', 1);
		}
		switch($setting['toolbar']){
			case 'desc': // 简易型
				$toolbar="['Bold', 'Italic','Underline','Strike','-','NumberedList','BulletedList'],'-',['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','Link','Unlink'],['Maximize'],\r\n";
				break;
			case 'basic': // 基本型
				$toolbar=defined('ADMIN_INI') ? "['Source']," : '';
				$toolbar.="['Bold', 'Italic','Underline','Strike','-','NumberedList','BulletedList','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','Link','Unlink','Table','Smiley','Image','Flash','SpecialChar'],['Maximize'],
  			            '/',
  			            ['Styles','Format','Font','FontSize'],
			              ['TextColor','BGColor'],\r\n";
				break;
			case 'full': // 完整型
				$toolbar=defined('ADMIN_INI') ? "['Source'," : '[';
				$toolbar.="'-','Templates'],
			    ['Cut','Copy','Paste','PasteText','PasteFromWord','-','Print'],
			    ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],['ShowBlocks'],['Image','Flash'],['Maximize'],
			    '/',
			    ['Bold','Italic','Underline','Strike','-'],
			    ['Subscript','Superscript','-'],
			    ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
			    ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
			    ['Link','Unlink','Anchor'],
			    ['Table','HorizontalRule','Smiley','SpecialChar','PageBreak'],
			    '/',
			    ['Styles','Format','Font','FontSize'],
			    ['TextColor','BGColor'],
			    ['attachment'],\r\n";
				break;
			default:
				$toolbar='';
				break;
		}
		
		$str.="<script type=\"text/javascript\">\r\n";
		
		$str.="CKEDITOR.replace( '$textareaid',{";
		$str.='language:\'' . $lang . '\',resize_dir:\'vertical\',height:' . $setting['height'] . ',' . (!empty($setting['width']) ? 'width:' . $setting['width'] . ',resize_maxWidth:' . $setting['width'] . ',resize_minWidth:' . $setting['width'] . ',' : '');
		$str.='bodyId:\'' . $textareaid . '_iframe\',removePlugins:\'elementspath,scayt\',';
		if(!empty($setting['contentsCss'])){
			$str.='contentsCss:CKEDITOR.getUrl("' . $setting['contentsCss'] . '"),';
		}
		if(!empty($setting['css'])){
			$bodyCss='body_css' . rand(10, 99);
			$str.='bodyClass:\'' . (strpos($setting['css'], ':') === false ? $setting['css'] : $bodyCss) . '\',';
		}
		
		if(empty($toolbar)){
			$str.='toolbarCanCollapse:false,';
		}
		
		$show_page='false';
		$str.="textareaid:'" . $textareaid . "',model:'" . $model . "',catid:'" . $catid . "',\r\n";
		
		if($setting['color']){
			$str.="extraPlugins : 'uicolor',uiColor: '{$setting['color']}',";
		}
		$str.="toolbar :\r\n";
		$str.="[\r\n";
		$str.=$toolbar;
		$str.="]\r\n";
		
		$str.="});\r\n";
		
		foreach($setting['show_status'] as $k=>$v){
			if($v == 1 || (!$setting['allowupload'] && $v == 'upload') || (($v == 'cut2desc' || $v == 'cut2image') && !$setting[$v])){
				unset($setting['show_status'][$k]);
			}else{
				
				$setting['show_status'][$k]='"' . $v . '"';
			}
		}
		
		$upload_para='25,gif|jpg|png,1,1024';
		if(in_array('"upload"', $setting['show_status'])){
			$setting_attachment=getcache('setting', 'setting', 'array', 'attachment');
			$upload_para=intval($setting_attachment['upload_num']) . ',' . str_replace(',', '', $setting_attachment['type']) . ',1,' . intval($setting_attachment['size']) . ',' . intval($setting_attachment['upload_maxwidth']) . ',' . intval($setting_attachment['upload_maxheight']);
		}
		
		$str.='CKEDITOR.instances["' . $textareaid . '"].on( 
			"instanceReady", 
			function(e){' . ((!empty($setting['css']) && strpos($setting['css'], ':') !== false) ? '
			  var cssTxt="body p{text-align:left;text-indent:2em;margin:0;padding:0px;}";
            cssTxt+="*{white-space: pre-wrap; white-space: -moz-pre-wrap; white-space: -pre-wrap; white-space: -o-pre-wrap; 	word-wrap: break-word;}";				
			      cssTxt+=".' . $bodyCss . ',.' . $bodyCss . ' p{font-size:12px;' . $setting['css'] . '}";
			  this.document.appendStyleText(cssTxt);' : '') . '
			  CKEDITOR_SET_STATUS(this.name,"' . upload_key($upload_para) . '",[' . implode(',', $setting['show_status']) . '],"' . $upload_para . '","' . $model . '","' . $catid . '");
			} 
		);';
		
		$str.='</script>';
		if(in_array('"title"', $setting['show_status'])){
			$str.='
			 <div class="editor_bottom">
				 <div id="page_title_div">
						<table cellpadding="0" cellspacing="1" border="0">
							<tr>
								<td class="title">子标题<span id="msg_page_title_value"></span></td>
								<td><a class="close" href="javascript:;" onclick="$(\'#page_title_div\').hide();"><span>×</span></a></td>
							</tr>
							<tr><td colspan="2"><input name="page_title_value" id="page_title_value" class="input-text" value="" size="30">&nbsp;<input type="button" class="button" value="提交" onclick="insert_page_title(\'' . $textareaid . '\',1)"></td></tr>
						</table>
					</div>
				</div>';
		}
		if(in_array('"cut2desc"', $setting['show_status']) || in_array('"cut2image"', $setting['show_status'])){
			$str.='<div class="content_attr" style="width:' . (intval($setting['width']) - 22) . 'px">';
			if(in_array('"cut2desc"', $setting['show_status'])){
				$str.='<label><input name="add_introduce" type="checkbox" value="1" checked>是否截取内容</label>
				<input type="text" name="introcude_length" class="input-text" value="200" size="3">字符至内容摘要&nbsp;&nbsp;&nbsp;';
			}
			if(in_array('"cut2image"', $setting['show_status'])){
				$str.='<label><input type="checkbox" name="auto_thumb" value="1" checked>是否获取内容第</label>
				<input type="text" name="auto_thumb_no" class="input-text" value="1" size="2">张图片作为标题图片';
			}
			$str.='</div>';
		}
		return $str;
	}

	/**
	 * 功能：生成多图上传 参数：($name 表单名称,$id 表单id,$value 表单默认值,$moudle 模块名称,$catid 栏目id,$size 表单大小, $class 表单风格,$ext 表单扩展属性 如果 js事件等,$alowexts 允许图片格式, $thumb_setting 缩略图设置,$watermark_setting 0或1)
	 */
	public static function images($name,$id='',$value='',$model='',$catid='',$size=50,$class='',$ext='',$alowexts='',$thumb_setting=array(),$watermark_setting=0){
		if(!$id)
			$id=$name;
		if(!$size)
			$size=50;
		
		$setting_attachment=getcache('setting', 'setting', 'array', 'attachment');
		$upload_num=intval($setting_attachment['upload_num']);
		if(!empty($thumb_setting) && count($thumb_setting)){
			$thumb_ext=$thumb_setting[0] . ',' . $thumb_setting[1];
		}else{
			$thumb_ext=$setting_attachment['upload_maxwidth'] . ',' . $setting_attachment['upload_maxheight'];
		}
		$maxfilesize=intval($setting_attachment['size']);
		
		if(!$alowexts){
			$alowexts='jpg|jpeg|gif|bmp|png';
		}
		$para_setting=$upload_num . ',' . $alowexts . ',1,' . $maxfilesize . ',' . $thumb_ext . ',' . $watermark_setting;
		$authkey=upload_key($para_setting);
		return $str . '<input type="text" name="' . $name . '" id="' . $id . '" value="' . $value . '" size="' . $size . '" class="' . $class . '" ' . $ext . '/>
		 <input type="button" class="button" onclick="flashupload(\'' . $id . '_images\',\'附件上传\',\'' . $id . '\',submit_images,\'' . $para_setting . '\',\'' . $model . '\',\'' . $catid . '\',\'' . $authkey . '\',1,' . (defined('ADMIN_INI') ? 1 : 0) . ')" value="图片上传" />';
	}

	/**
	 * 功能：文件上传 参数：( string $name 表单名称, int $id 表单id, string $value 表单默认值, string $moudle 模块名称, int $catid 栏目id, int $size 表单大小, string $class 表单风格, string $ext 表单扩展属性 如果 js事件等, string $alowexts 允许图片格式, array $file_setting )
	 */
	public static function upfiles($name,$id='',$value='',$model='',$catid='',$size=50,$class='',$ext='',$alowexts='',$thumb_setting=array()){
		if(!$id)
			$id=$name;
		if(!$size)
			$size=50;
		$setting_attachment=getcache('setting', 'setting', 'array', 'attachment');
		$upload_num=intval($setting_attachment['upload_num']);
		if(!empty($thumb_setting) && count($thumb_setting)){
			$thumb_ext=$thumb_setting[0] . ',' . $thumb_setting[1];
		}else{
			$thumb_ext=$setting_attachment['upload_maxwidth'] . ',' . $setting_attachment['upload_maxheight'];
		}
		$maxfilesize=intval($setting_attachment['size']);
		
		if(!$alowexts){
			$alowexts='zip|rar';
		}
		$para_setting=$upload_num . ',' . $alowexts . ',1,' . $maxfilesize . ',' . $thumb_ext . ',' . $watermark_setting;
		$authkey=upload_key($para_setting);
		return $str . '<input type="text" name="' . $name . '" id="' . $id . '" value="' . $value . '" size="' . $size . '" class="' . $class . '" ' . $ext . '/>  
		<input type="button" class="button" onclick="flashupload(\'' . $id . '_files\', \'文件上传\',\'' . $id . '\',submit_attachment,\'1,' . $alowexts . ',1,' . $file_ext . '\',\'' . $model . '\',\'' . $catid . '\',\'' . $authkey . '\',0,' . (defined('ADMIN_INI') ? 1 : 0) . ')" value="文件上传">';
	}

	/**
	 * 功能：生成日期时间控件 参数：( $name 控件name，id, $value 默认为空,选中值, $isdatetime 默认为0 是否显示时间, $isForm 默认为1 是否生成表单, $showweek 默认为1 是否显示周 $timesystem 时间系统:1->24小时制,0->12小时制，默认为1 )
	 */
	public static function date($name,$value='',$isdatetime=0,$isForm=1,$showweek=1,$timesystem=1,$onSelectJs=''){
		if($value == '0000-00-00 00:00:00')
			$value='';
		$id=preg_match("/\[(.*)\]/", $name, $m) ? $m[1] : $name;
		if($isdatetime){
			$size=21;
			$format='%Y-%m-%d %H:%M:%S';
			$showsTime=$timesystem ? 'true' : '12';
		}else{
			$size=10;
			$format='%Y-%m-%d';
			$showsTime='false';
		}
		$str='';
		
		// 语言设置
		$lang=load::cfg('system', 'lang');
		if(!strpos(SYS_PLUGIN_URL, '://') && !is_file(substr(ROOT_PATH, 0, -1) . SYS_PLUGIN_URL . 'calendar/lang/' . $lang . '.js')){
			$lang='en';
		}
		
		if(!defined('CALENDAR_FORM_FILES')){
			define('CALENDAR_FORM_FILES', 1);
			$str.='<link rel="stylesheet" type="text/css" href="' . SYS_PLUGIN_URL . 'calendar/jscal2.css"/>
			<link rel="stylesheet" type="text/css" href="' . SYS_PLUGIN_URL . 'calendar/border-radius.css"/>
			<link rel="stylesheet" type="text/css" href="' . SYS_PLUGIN_URL . 'calendar/win2k.css"/>
			<script type="text/javascript" src="' . SYS_PLUGIN_URL . 'calendar/calendar.js"></script>
			<script type="text/javascript" src="' . SYS_PLUGIN_URL . 'calendar/lang/' . $lang . '.js"></script>';
		}
		if($isForm){
			$str.='<input type="text" name="' . $name . '" id="' . $id . '" value="' . $value . '" size="' . $size . '" class="date" readonly="readonly">&nbsp;';
		}
		$str.='<script type="text/javascript">Calendar.setup({weekNumbers:' . ($showweek ? 'true' : 'false') . ',inputField:"' . $id . '", trigger:"' . $id . '",dateFormat:"' . $format . '",showTime: ' . $showsTime . ',minuteStep: 1,onSelect   : function() {this.hide();' . $onSelectJs . '}});</script>';
		return $str;
	}

	/**
	 * 功能：生成栏目选择 参数：( string $file 栏目缓存文件名, intval/array $catid 别选中的ID，多选是可以是数组, string $str 属性, string $default_option 默认选项, intval $modelid 按所属模型筛选, intval $type 栏目类型, intval $onlysub 只可选择子栏目, intval $siteid 如果设置了siteid 那么则按照siteid取 )
	 */
	public static function select_category($file='',$catid=0,$str='',$default_option='',$modelid=0,$type=-1,$onlysub=0,$siteid=0,$is_push=0){
		$tree=pc_base::load_sys_class('tree');
		if(!$siteid)
			$siteid=cookie('siteid');
		if(!$file){
			$file='category_content_' . $siteid;
		}
		$result=getcache($file, 'commons');
		$string='<select ' . $str . '>';
		if($default_option)
			$string.="<option value='0'>$default_option</option>";
			// 加载权限表模型 ,获取会员组ID值,以备下面投入判断用
		if($is_push == '1'){
			$priv=pc_base::load_model('category_priv_model');
			$user_groupid=cookie('_groupid') ? cookie('_groupid') : 8;
		}
		if(is_array($result)){
			foreach($result as $r){
				// 检查当前会员组，在该栏目处是否允许投稿？
				if($is_push == '1' and $r['child'] == '0'){
					$sql=array('catid' => $r['catid'],'roleid' => $user_groupid,'action' => 'add');
					$array=$priv->get_one($sql);
					if(!$array){
						continue;
					}
				}
				if($siteid != $r['siteid'] || ($type >= 0 && $r['type'] != $type))
					continue;
				$r['selected']='';
				if(is_array($catid)){
					$r['selected']=in_array($r['catid'], $catid) ? 'selected' : '';
				}elseif(is_numeric($catid)){
					$r['selected']=$catid == $r['catid'] ? 'selected' : '';
				}
				$r['html_disabled']="0";
				if(!empty($onlysub) && $r['child'] != 0){
					$r['html_disabled']="1";
				}
				$categorys[$r['catid']]=$r;
				if($modelid && $r['modelid'] != $modelid)
					unset($categorys[$r['catid']]);
			}
		}
		$str="<option value='\$catid' \$selected>\$spacer \$catname</option>;";
		$str2="<optgroup label='\$spacer \$catname'></optgroup>";
		
		$tree->init($categorys);
		$string.=$tree->get_tree_category(0, $str, $str2);
		
		$string.='</select>';
		return $string;
	}

	/**
	 * 功能：生成下拉选择框 参数：(选项数组,选中id,控件属性字符串,默认选项字符串)
	 */
	public static function select($array=array(),$id=0,$str='',$default_option=''){
		$string='<select ' . $str . '>';
		$default_selected=(empty($id) && $default_option) ? 'selected' : '';
		if($default_option)
			$string.="<option value='' $default_selected>$default_option</option>";
		if(!is_array($array) || count($array) == 0)
			return false;
		$ids=array();
		if(isset($id))
			$ids=explode(',', $id);
		foreach($array as $key=>$value){
			$selected=in_array($key, $ids) ? 'selected' : '';
			$string.='<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
		}
		$string.='</select>';
		return $string;
	}

	/**
	 * 功能：生成复选框 参数：( $array 选项 二维数组, $id 默认选中值，多个用 '逗号'分割, $str 属性, $defaultvalue 是否增加默认值 默认值为 -99, $width 宽度 )
	 */
	public static function checkbox($array=array(),$id='',$str='',$defaultvalue='',$width=0,$field=''){
		$string='';
		$id=trim($id);
		if($id != '')
			$id=strpos($id, ',') ? explode(',', $id) : array($id);
		if($defaultvalue)
			$string.='<input type="hidden" ' . $str . ' value="-99">';
		$i=1;
		foreach($array as $key=>$value){
			$key=trim($key);
			$checked=($id && in_array($key, $id)) ? 'checked' : '';
			if($width)
				$string.='<label class="ib" style="width:' . $width . 'px">';
			$string.='<input type="checkbox" ' . $str . ' id="' . $field . '_' . $i . '" ' . $checked . ' value="' . htmlspecialchars($key) . '"> ' . htmlspecialchars($value);
			if($width)
				$string.='</label>';
			$i++;
		}
		return $string;
	}

	/**
	 * 功能：生成单选框 参数：( $array 选项 二维数组, $id 默认选中值, $str 属性 )
	 */
	public static function radio($array=array(),$id=0,$str='',$width=0,$field=''){
		$string='';
		foreach($array as $key=>$value){
			$checked=trim($id) == trim($key) ? 'checked' : '';
			if($width)
				$string.='<label class="ib" style="display:inline-block;margin-right:10px;">';
			$string.='<input type="radio" ' . $str . ' id="' . $field . '_' . htmlspecialchars($key) . '" ' . $checked . ' value="' . $key . '"> ' . $value;
			if($width)
				$string.='</label>';
		}
		return $string;
	}
}

?>