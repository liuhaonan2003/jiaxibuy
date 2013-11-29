<?php
class TagLibYP extends TagLib
{
	protected $tags   =  array(
        // 标签定义：
        //attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次
        'list'=>array('attr'=>'name,field,limit,order,catid,thumb,posid,where,sql,key,page,mod,id,status','level'=>3),
		'subcat'=>array('attr'=>'catid,type,self,key,id','level'=>3),
		'catpos' => array('attr'=>'catid,space','close'=>0),
		'nav' => array('attr'=>'catid,bcid,level,id,class,home,enname','close'=>0),
		'link' => array('attr'=>'typeid,linktype,field,limit,order,field','level'=>3),
		'kefu' => array('attr'=>'left,top,level,id,class,type','close'=>0),
    );

	public function _nav($attr,$content) {
		$tag        = $this->parseXmlAttr($attr,'nav');
		$level = !empty($tag['level'])? intval($tag['level']) : '1';
		$catid = !empty($tag['catid'])? intval($tag['catid']) : '0';
		$bcid = !empty($tag['bcid'])? intval($tag['bcid']) : '0';
		$class = !empty($tag['class'])? $tag['class'] : '';
		$id = !empty($tag['id'])? $tag['id'] : 'nav';
		$homefont = !empty($tag['home'])? $tag['home'] : '';
		$get = !empty($tag['get'])? $tag['get'] : 'get_nav';
		$enname = !empty($tag['enname'])? intval($tag['enname']) : '0';
		$category_arr = $this->tpl->get('Categorys');
        $site_url = $this->tpl->get('site_url');
		$parsestr ='';
		if(!is_numeric($catid)) $catid =  $this->tpl->get($catid);
		if(is_array($category_arr)){
			foreach($category_arr as $r) {
				if(empty($r['ismenu']))  continue;
				$r['name'] = $r['catname'];
				$r['level']=count(explode(',',$r['arrparentid']));
				$array[] = $r;
			}
			import ( '@.ORG.Tree' );
			$tree = new Tree ($array);
			$parsestr = $tree->$get($catid,$level,$id,$class,$homefont,FALSE,'',$enname,$site_url);
		}
		return  $parsestr;
	}

	public function _subcat($attr,$content) {
		$tag        = $this->parseXmlAttr($attr,'subcat');
		$id = !empty($tag['id'])?$tag['id']:'r'; //定义数据查询的结果存放变量 result
		$type = !empty($tag['type'])?$tag['type']:'';
		$self = !empty($tag['self'])?'1':'';
		$key    = !empty($tag['key'])?$tag['key']:'n';
		if($type) $condition = ' &&  $'.$id.'["type"] == "'.$type.'"';

		if(substr($tag['catid'],0,1)=='$') {
           $catid   = $tag['catid'];
        }elseif(is_numeric($tag['catid'])){
			 $catid   = $tag['catid'];
		}else{
            $catid   = $this->tpl->get($tag['catid']);
        }

		if($self){ $ifleft = '('; $selfcondition = ') or (  $'.$id.'[\'ismenu\']==1 && intval('.$catid.')==$'.$id.'["id"])';}
		$parsestr ='';
		$parsestr .= '<?php $'.$key.'=0;';
		$parsestr .= 'foreach($Categorys as $key=>$'.$id.'):';
		$parsestr .= 'if('.$ifleft.' $'.$id.'[\'ismenu\']==1 && intval('.$catid.')==$'.$id.'["parentid"] '.$condition.$selfcondition.' ) :';
		$parsestr .= '++$'.$key.';?>';
		$parsestr .= $content;
		$parsestr .= '<?php endif;?>';
		$parsestr .= '<?php endforeach;?>';
		return  $parsestr;
	}

	public function _catpos($attr) {
		$tag        = $this->parseXmlAttr($attr,'catpos');
		$space		= $tag['space'];

 		if(substr($tag['catid'],0,1)=='$') {
            $catid   = $this->tpl->get(substr($tag['catid'],1));
        }elseif(is_numeric($tag['catid'])){
            $catid   = intval($tag['catid']);
        }else{
			$catid   = $this->tpl->get($tag['catid']);
		}

		$category_arr = $this->tpl->get('Categorys');
		if(!isset($category_arr[$catid])) return '';
		$arrparentid = array_filter(explode(',', $category_arr[$catid]['arrparentid'].','.$catid));
		foreach($arrparentid as $cid) {
			$parsestr .= '<a href="'.$category_arr[$cid]['url'].'">'.$category_arr[$cid]['catname'].'</a>'.$space;
		}
		return  $parsestr;
	}
	public function _link($attr,$content) {
		$tag    = $this->parseXmlAttr($attr,'link');
		$id = !empty($tag['id'])?$tag['id']:'r';  //定义数据查询的结果存放变量
		$key    = !empty($tag['key'])?$tag['key']:'i';
		$mod    = isset($tag['mod'])?$tag['mod']:'2';

		//$typeid    = isset($tag['$typeid'])?$tag['$typeid']:'';
		$linktype    = isset($tag['linktype'])?$tag['linktype']:'';
		$order  = isset($tag['order'])?$tag['order']:'id desc';
		$limit  = isset($tag['limit'])?$tag['limit']: '10';
		$field  = isset($tag['field'])?$tag['field']:'*';

		if(substr($tag['typeid'],0,1)=='$') {
			$where = 'typeid=$tag["typeid"]';
        }elseif(is_numeric($tag['typeid'])){
			$where = "typeid=".intval($tag['typeid']);
		}else{
            $typeid   = $this->tpl->get($tag['typeid']);
			if($typeid){
			$where = "typeid=".intval($typeid);
			}
        }
		$and = empty($where)? '' : ' and ';
		if($linktype){
			$where .= $and . "linktype=".$linktype;
		}
		$sql  = "M(\"Link\")->field(\"{$field}\")->where(\"{$where}\")->order(\"{$order}\")->limit(\"{$limit}\")->select();";

		//下面拼接输出语句
		$parsestr  = '';
		$parsestr .= '<?php  $_result='.$sql.'; if ($_result): $'.$key.'=0;';
		$parsestr .= 'foreach($_result as $key=>$'.$id.'):';
		$parsestr .= '++$'.$key.';$mod = ($'.$key.' % '.$mod.' );?>';
		$parsestr .= $content;//解析在article标签中的内容
		$parsestr .= '<?php endforeach; endif;?>';
		return  $parsestr;
	}
	public function _kefu($attr,$content) {
		$tag    = $this->parseXmlAttr($attr,'kefu');
		$id = !empty($tag['id'])?$tag['id']:'r';  //定义数据查询的结果存放变量
		$type   = !empty($tag['type'])?$tag['type']: '';
		$order  = isset($tag['order'])?$tag['order']:'listorder desc ,id desc';
		$left =  isset($tag['left'])?$tag['left']: '0';
		$top =  isset($tag['top'])?$tag['top']: '100';
		$where ="status=1";
		if($type)$where .=" and type in($type)";

		$data  = M("Kefu")->field("*")->where($where)->order($order)->select();
		$site_name = $this->tpl->get('site_name');
		foreach($data as $key =>$r){
				if($r['name']) $datas.='<li class="tit">'.$r['name'].':</li>';
			if($r['type']==1){
				//http://wpa.qq.com/msgrd?v=3&uin=426507856&site=qq&menu=yes
				$skin =	str_replace('q','',$r['skin']);
				$datas.='<li><a href="http://wpa.qq.com/msgrd?v=3&uin='.$r['code'].'&site=qq&menu=yes"><img border="0" SRC=http://wpa.qq.com/pa?p=1:'.$r['code'].':'.$skin.' alt="'.$r['name'].'"></a>';
			}elseif($r['type']==2){
				$skin =	str_replace('m','',$r['skin']);
				$datas.='<li><a href="msnim:chat?contact='.$r['code'].'"><img src="'.__ROOT__.'/Public/Images/kefu/msn'.$skin.'.gif"></a></li>';
			}elseif($r['type']==3){
				$skin =	str_replace('w','',$r['skin']);
				$datas.='<a target="_blank" href="http://www.taobao.com/webww/ww.php?ver=3&touid='.$r['code'].'&siteid=cntaobao&status='.$skin.'&charset=utf-8"><img border="0" src="http://amos.alicdn.com/online.aw?v=2&uid='.$r['code'].'&site=cntaobao&s='.$skin.'&charset=utf-8" alt="'.$r['name'].'" /></a>';
			}elseif($r['type']==4){
				$datas.='<li>'.$r['code'].'</li>';
			}elseif($r['type']==5){
				$datas.='<li>'.$r['code'].'</li>';
			}else{
				$datas.='<li>'.$r['code'].'</li>';
			}
		}
		$parsestr='';
		$parsestr .='<div class="kefu" id="'.$id.'"><div class="kftop"></div><div class="kfbox"><ul>';
		$parsestr .=$datas;
		$parsestr .='</ul></div><div class="kfbottom"></div></div>';
		$parsestr .='<script> var '.$id.' = new Floaters(); '.$id.'.addItem("'.$id.'",'.$left.','.$top.',"");'.$id.'.play("'.$id.'");</script>';

		return  $parsestr;
	}

	public function _list($attr,$content) {

			$tag    = $this->parseXmlAttr($attr,'list');
			$id = !empty($tag['id'])?$tag['id']:'r';  //定义数据查询的结果存放变量
			$key    = !empty($tag['key'])?$tag['key']:'i';
			$page   = !empty($tag['page'])? '1' : '0';
			$mod    = isset($tag['mod'])?$tag['mod']:'2';

			if ($tag['name'] || $tag['catid']){   //根据用户输入的值拼接查询条件
				$sql='';
				$module = $tag['name'];
				$order  = isset($tag['order'])?$tag['order']:'id desc';
				$field  = isset($tag['field'])?$tag['field']:'id,catid,url,title,title_style,keywords,description,thumb,createtime,about';
				$where  = isset($tag['where'])?$tag['where']:'';
				$limit  = isset($tag['limit'])?$tag['limit']: '10';
				$status = isset($tag['status'])? $tag['status'] : '1';

				$where= " status=$status ";

				if($tag['catid']){
					$onezm  = substr($tag['catid'],0,1);
					if($onezm=='$'){
						$catid = $this->tpl->get(substr($tag['catid'],1));
					}elseif(!is_numeric($onezm)) {
						$catid = $this->tpl->get($tag['catid']);
					}else{
						$catid = $tag['catid'];
					}
					if(is_numeric($catid)){
						$category_arr = $this->tpl->get('Categorys');
						$module = $category_arr[$catid]['module'];
						if($category_arr[$catid]['child']){
							$where .= " AND catid in(".$category_arr[$catid]['arrchildid'].")";
						}else{
							$where .=  " AND catid=".$catid;
						}
					}elseif($onezm=='$') {
						$where .=  ' AND catid in('.$tag['catid'].')';
					}else{
							$where .=  ' AND catid in('.strip_tags($tag['catid']).')';
					}
				}


				if($tag['posid']){
					if(is_numeric($tag['posid'])){
						$where .=  '  AND posid ='.$tag['posid'];
					}else{
						$where .=  ' AND posid in('.strip_tags($tag['posid']).')';
					}
				}
				if($tag['thumb']){
					$where .=  ' AND  thumb !=\'\' ';
				}
				$sql  = "M(\"{$module}\")->field(\"{$field}\")->where(\"{$where}\")->order(\"{$order}\")->limit(\"{$limit}\")->select();";
			}else{
				if (!$tag['sql']) return ''; //排除没有指定model名称，也没有指定sql语句的情况
				$sql = "M()->query('{$tag['sql']}')";
			}

			//下面拼接输出语句
			$parsestr  = '';
			$parsestr .= '<?php  $_result='.$sql.'; if ($_result): $'.$key.'=0;';
			$parsestr .= 'foreach($_result as $key=>$'.$id.'):';
			$parsestr .= '++$'.$key.';$mod = ($'.$key.' % '.$mod.' );?>';
			$parsestr .= $content;//解析在article标签中的内容
			$parsestr .= '<?php endforeach; endif;?>';
			return  $parsestr;
	}

	public function _getData($attr, $content) {
		static $_iterateParseCache = array ();
		$cacheIterateId = md5 ( $attr . $content );
		if (isset ( $_iterateParseCache [$cacheIterateId] ))
			return $_iterateParseCache [$cacheIterateId];
		$tag = $this->parseXmlAttr ( $attr, 'getData' );
		$name = $tag ['name'];
		$model = $tag ['model'];
		$condition = empty ( $tag ['where'] ) ? '\"\"' : '"' . $tag ['where']. '"';
		$order = empty ( $tag ['order'] ) ? '\'\'' : '\'' . $tag ['order'] . '\'';
		$sql = empty ( $tag ['sql'] ) ? '' : addslashes ( $tag ['sql'] );
		$limit = empty ( $tag ['limit'] ) ? 10 : '\'' . $tag ['limit'] . '\'';
		$id = $tag ['id'];
		$empty = isset ( $tag ['empty'] ) ? $tag ['empty'] : '';
		$key = ! empty ( $tag ['key'] ) ? $tag ['key'] : 'i';
		$mod = isset ( $tag ['mod'] ) ? $tag ['mod'] : '2';
		$name = $this->autoBuildVar ( $name );
		if (! empty ( $model )) {
			$parseStr .= '<?php';
			$parseStr .= ' $condition = ' . $condition . ';';
			$parseStr .= ' $order = ' . $order . ';';
			$parseStr .= ' $limit = ' . $limit . ';';
			$parseStr .= ' if(!isset($' . $model . ')) : $' . $model . 'Dao = M(\'' . $model . '\'); endif;';
			if (empty ( $sql )) {
				$parseStr .= ' if(!isset(' . $name . ')) :' . $name . ' = $' . $model . 'Dao->Where($condition)->Order($order)->Limit($limit)->findAll();endif;';
			} else {
				$parseStr .= ' if(!isset(' . $name . ')) :' . $name . ' = $' . $model . 'Dao->query(\'' . $sql . '\');endif;';
			}
			$parseStr .= ' if(is_array(' . $name . ')): $' . $key . ' = 0;';
			if (isset ( $tag ['length'] ) && '' != $tag ['length']) {
				$parseStr .= ' $__LIST__ = array_slice(' . $name . ',' . $tag ['offset'] . ',' . $tag ['length'] . ');';
			} elseif (isset ( $tag ['offset'] ) && '' != $tag ['offset']) {
				$parseStr .= ' $__LIST__ = array_slice(' . $name . ',' . $tag ['offset'] . ');';
			} else {
				$parseStr .= ' $__LIST__ = ' . $name . ';';
			}
			$parseStr .= 'if( count($__LIST__)==0 ) : echo "' . $empty . '" ;';
			$parseStr .= 'else: ';
			$parseStr .= 'foreach($__LIST__ as $key=>$' . $id . '): ';
			$parseStr .= '++$' . $key . ';';
			$parseStr .= '$mod = ($' . $key . ' % ' . $mod . ' )?>';
			$parseStr .= $this->tpl->parse ( $content );
			$parseStr .= '<?php endforeach; endif; else: echo "' . $empty . '" ;endif; ?>';
			$_iterateParseCache [$cacheIterateId] = $parseStr;
			if (! empty ( $parseStr )) {
				return $parseStr;
			}
		}
		return;
	}
	public function _getItem($attr, $content) {
		static $_iterateParseCache = array ();
		$cacheIterateId = md5 ( $attr . $content );
		if (isset ( $_iterateParseCache [$cacheIterateId] ))
			return $_iterateParseCache [$cacheIterateId];
		$tag = $this->parseXmlAttr ( $attr, 'getItem' );
		$name = $tag ['name'];
		$model = $tag ['model'];
		$condition = empty ( $tag ['where'] ) ? '\'\'' : '\'' . addslashes ( $tag ['where'] ) . '\'';
		$sql = addslashes ( $tag ['sql'] );
		$empty = isset ( $tag ['empty'] ) ? $tag ['empty'] : '';
		$name = $this->autoBuildVar ( $name );
		if (! empty ( $model )) {
			$parseStr .= '<?php';
			$parseStr .= ' $condition = ' . $condition . ';';
			$parseStr .= ' if(!isset($' . $model . ')) : $' . $model . ' = M(\'' . $model . '\'); endif;';
			if (empty ( $sql )) {
				$parseStr .= ' if(!isset(' . $name . ')) :' . $name . ' = $' . $model . '->Where($condition)->find();endif;';
			} else {
				$parseStr .= ' if(!isset(' . $name . ')) :' . $name . ' = $' . $model . '->query(\'' . $sql . '\');' . $name . ' = ' . $name . '[0]; endif; ';
			}
			$parseStr .= '  ?>';
			$parseStr .= $this->tpl->parse ( $content );
			$_iterateParseCache [$cacheIterateId] = $parseStr;
			if (! empty ( $parseStr )) {
				return $parseStr;
			}
		}
		return;
	}
}
?>