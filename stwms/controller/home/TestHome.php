<?php
defined('IN_MYCMS') or exit('No permission resources.');
class TestHome extends HomeController{
	public function a(){
		$this->assign('mb','hello');
		$this->display('/test');
	}
	
	public function b(){
// 		var_dump(parse_url('a/b?id=100#abc?id=10'));
//  		echo U('home/a/b@www.baidu.com');
		$value=array(
				'http://www.baidu.com'=> SITE_URL,
				'af'=>'您好',
				'b'=>array(
						'谢','春平'
				),
		);
		var_dump($_GET);
		
// 		$ret=urldecode(json_encode(array_map_deep($value,'urlencode')));
// 		var_dump($ret);
		//cookie('aa',null);
		//echo cookie('aa');
	}
	
	
	private function parseAttrs($str){
		$isDq=strpos($str,'\"')!==false;
		$isSq=strpos($str,'\\\'')!==false;
		$spat='/(\w+)=\'([^\']+)\'/';
		$dpat='/(\w+)="([^"]+)"/';
		
		if($isDq){
			$rDtag='____DOUBLE_QUOTE____';
			$str=str_replace('\"', $rDtag, $str);
		}
		
		if($isSq){
			$rStag='____SINGLE_QUOTE____';
			$str=str_replace('\\\'', $rStag, $str);
		}
		
		!preg_match_all($spat, $str,$matches)&&preg_match_all($dpat, $str,$matches);
		!empty($matches[0])&&array_shift($matches);
		$isDq&&$this->replace($rDtag,'\"',  $matches);
		$isSq&&$this->replace($rStag,'\\\'',  $matches);
		
		return array_combine($matches[0],$matches[1]);		
	}
	
	private function replace($se,$re,&$str){
		if(is_array($str)){
			foreach($str as $k => $v){
				$str[$k]=$this->replace($se,$re,$v);
			}
		}else if(is_string($str)){
			$str=str_replace($se,$re,$str);
		}
		return $str;
	}
}

?>