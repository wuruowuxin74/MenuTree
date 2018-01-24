<?php
/**
 * 根据内容标题关系自动生成目录树
 * 
 * @package Menu Tree
 * @author 原作者(Melon) 修改(BeaconFire)
 * @version 原版本(0.1.0) 修改(0.1.1)
 * @link https://github.com/wuruowuxin74/MenuTree
 */

 // 使用方法：
 // 在文章某处地方加上<!-- index-menu -->，程序会把这个注释替换成目录树

 // 样式：
 // .index-menu			整个目录
 // .index-menu-list	列表 ul
 // .index-menu-item	每个目录项 li
 // .index-menu-link	目录项连接 a

class MenuTree_Plugin implements Typecho_Plugin_Interface {
	
	/**
	 * 索引ID
	 */
	public static $id = 1;
	
	public static $pattern = '/(&lt;|<)!--\s*index-menu\s*--(&gt;|>)/i';

	/**
	 * 目录树
	 */
	public static $tree = array();

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('MenuTree_Plugin', 'contentEx');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('MenuTree_Plugin', 'excerptEx');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form) {}
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 列表页忽略目录生成标记
     * 
     * @access public
     * @return string
     */
    public static function excerptEx( $html, $widget, $lastResult){
        return preg_replace(self::$pattern,'',$html);
    }

    /**
     * 内容页构造索引目录
     * 
     * @access public
     * @return string
     */
    public static function contentEx( $html, $widget, $lastResult ) {
        $html = empty( $lastResult ) ? $html : $lastResult;
		$html = preg_replace_callback( '/<h([1-6])[^>]*>.*?<\/h\1>/s', array( 'MenuTree_Plugin', 'parseCallback' ), $html );
		$html = preg_replace( self::$pattern, '<div class="index-menu">' . self::buildMenuHtml( self::$tree ) . '</div>', $html );
		self::$id = 1;
		self::$tree = array();
        return $html;
    }

    /**
     * 解析
     * 
     * @access public
     * @param array $matches 解析值
     * @return string
     */
    public static function parseCallback( $match ) {
		$parent = &self::$tree;

		$html = $match[0];
		$n = $match[1];
		$menu = array(
			'num' => $n,
			'title' => trim( strip_tags( $html ) ),
			'id' => 'menu_index_' . self::$id,
			'sub' => array()
		);
		$current = array();
		if( $parent ) {
			$current = &$parent[ count( $parent ) - 1 ];
		}
		// 根
		if( ! $parent || ( isset( $current['num'] ) && $n <= $current['num'] ) ) {
			$parent[] = $menu;
		} else {
			while( is_array( $current[ 'sub' ] ) ) {
				// 父子关系
				if( $current['num'] == $n - 1 ) {
					$current[ 'sub' ][] = $menu;
					break;
				}
				// 后代关系，并存在子菜单
				elseif( $current['num'] < $n && $current[ 'sub' ] ) {
					$current = &$current['sub'][ count( $current['sub'] ) - 1 ];
				}
				// 后代关系，不存在子菜单
				else {
					for( $i = 0; $i < $n - $current['num']; $i++ ) {
						$current['sub'][] = array(
							'num' => $current['num'] + 1,
							'sub' => array()
						);
						$current = &$current['sub'][0];
					}
					$current['sub'][] = $menu;
					break;
				}
			}
		}
		self::$id++;
		return "<span id=\"{$menu['id']}\" name=\"{$menu['id']}\"></span>" . $html;
	}
	/**
     * 构建目录树，生成索引
     * 
     * @access public
     * @return string
     */
	public static function buildMenuHtml( $tree, $include = true ) {
		$menuHtml = '';
		foreach( $tree as $menu ) {
			if( ! isset( $menu['id'] ) && $menu['sub'] ) {
				$menuHtml .= self::buildMenuHtml( $menu['sub'], false );
			} elseif( $menu['sub'] ) {
				$menuHtml .= "<li class=\"index-menu-item\"><a data-scroll class=\"index-menu-link\" href=\"#{$menu['id']}\" title=\"{$menu['title']}\">{$menu['title']}</a>" . self::buildMenuHtml( $menu['sub'] ) . "</li>";
			} else {
				$menuHtml .= "<li class=\"index-menu-item\"><a data-scroll class=\"index-menu-link\" href=\"#{$menu['id']}\" title=\"{$menu['title']}\">{$menu['title']}</a></li>";
			}
		}
		if( $include ) {
			$menuHtml = '<ul class="index-menu-list">' . $menuHtml . '</ul>';
		}
		return $menuHtml;
	}
}
