<?php
/*
* 2007-2013 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2013 PrestaShop SA

*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_'))
	exit;

include_once _PS_MODULE_DIR_.'blockcmsinfo/infoClass.php';

class Blockcmsinfo extends Module
{
	public function __construct()
	{
		$this->name = 'blockcmsinfo';
		$this->tab = 'front_office_features';
		$this->version = '1.0';
		$this->bootstrap = true;
		$this->need_instance = 0;
		parent::__construct();
		$this->displayName = $this->l('Customer cms information block');
		$this->description = $this->l('Adds an information block customers in your store.');
	}

	public function install()
	{
			return parent::install() &&
			$this->installDB() &&
			Configuration::updateValue('blockcmsinfo_nbblocks', 2) &&
			$this->registerHook('home') && $this->installFixtures();
	}
	
	public function installDB()
	{
		$return = true;
		$return &= Db::getInstance()->execute('
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'info` (
				`id_info` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				`id_shop` int(10) unsigned NOT NULL ,
				PRIMARY KEY (`id_info`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;');
		
		$return &= Db::getInstance()->execute('
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'info_lang` (
				`id_info` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				`id_lang` int(10) unsigned NOT NULL ,
				`text` text NOT NULL,
				PRIMARY KEY (`id_info`, `id_lang`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;');

		return $return;
	}

	public function uninstall()
	{
		// Delete configuration
			return Configuration::deleteByName('blockcmsinfo_nbblocks') &&
			$this->uninstallDB() &&
			parent::uninstall();
	}

	public function uninstallDB()
	{
		return Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'info`') && Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'info_lang`');
	}

	public function addToDB()
	{
		if (isset($_POST['nbblocks']))
		{
			for ($i = 1; $i <= (int)$_POST['nbblocks']; $i++)
			{
				
				Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.'info` (`text`)
											VALUES ("'.((isset($_POST['info'.$i.'_text']) && $_POST['info'.$i.'_text'] != '') ? pSQL($_POST['info'.$i.'_text']) : '').'")');
			}
			return true;
		} else
			return false;
	}

	public function removeFromDB()
	{
		return Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'info`');
	}

	public function getContent()
	{
		$html = '';
		$id_info = (int)Tools::getValue('id_info');

		if (Tools::isSubmit('saveblockcmsinfo'))
		{
			if ($id_info = Tools::getValue('id_info'))
				$info = new infoClass((int)$id_info);
			else
				$info = new infoClass();
			$info->copyFromPost();
			$info->id_shop = $this->context->shop->id;
			
			if ($info->validateFields(false) && $info->validateFieldsLang(false))
			{
				$info->save();
				$this->_clearCache('blockcmsinfo.tpl');
			}
			else
				$html .= '<div class="conf error">'.$this->l('An error occurred while attempting to save.').'</div>';
		}
		
		if (Tools::isSubmit('updateblockcmsinfo') || Tools::isSubmit('addblockcmsinfo'))
		{
			$helper = $this->initForm();
			foreach (Language::getLanguages(false) as $lang)
				if ($id_info)
				{
					$info = new infoClass((int)$id_info);
					$helper->fields_value['text'][(int)$lang['id_lang']] = $info->text[(int)$lang['id_lang']];
				}	
				else
					$helper->fields_value['text'][(int)$lang['id_lang']] = Tools::getValue('text_'.(int)$lang['id_lang'], '');
			if ($id_info = Tools::getValue('id_info'))
			{
				$this->fields_form[0]['form']['input'][] = array('type' => 'hidden', 'name' => 'id_info');
				$helper->fields_value['id_info'] = (int)$id_info;
 			}
				
			return $html.$helper->generateForm($this->fields_form);
		}
		else if (Tools::isSubmit('deleteblockcmsinfo'))
		{
			$info = new infoClass((int)$id_info);
			$info->delete();
			$this->_clearCache('blockcmsinfo.tpl');
			Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'));
		}
		else
		{
			$helper = $this->initList();
			return $html.$helper->generateList($this->getListContent((int)Configuration::get('PS_LANG_DEFAULT')), $this->fields_list);
		}

		if (isset($_POST['submitModule']))
		{
			Configuration::updateValue('blockcmsinfo_nbblocks', ((isset($_POST['nbblocks']) && $_POST['nbblocks'] != '') ? (int)$_POST['nbblocks'] : ''));
			if ($this->removeFromDB() && $this->addToDB())
			{
				$this->_clearCache('blockcmsinfo.tpl');
				$output = '<div class="conf confirm">'.$this->l('The block configuration has been updated.').'</div>';
			}
			else
				$output = '<div class="conf error"><img src="../img/admin/disabled.gif"/>'.$this->l('An error occurred while attempting to save.').'</div>';
		}
	}

	protected function getListContent($id_lang)
	{
		return  Db::getInstance()->executeS('
			SELECT r.`id_info`, r.`id_shop`, rl.`text`
			FROM `'._DB_PREFIX_.'info` r
			LEFT JOIN `'._DB_PREFIX_.'info_lang` rl ON (r.`id_info` = rl.`id_info`)
			WHERE `id_lang` = '.(int)$id_lang.' '.Shop::addSqlRestrictionOnLang());
	}

	protected function initForm()
	{
		$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
		$this->fields_form[0]['form'] = array(
					'tinymce' => true,
					'legend' => array(
					'title' => $this->l('New cms custom block.'),
			),
			'input' => array(
				array(
					'type' => 'textarea',
					'label' => $this->l('Text:'),
					'lang' => true,
					'name' => 'text',
					'cols' => 40,
					'rows' => 10,
					'class' => 'rte',
       				'autoload_rte' => true, 
					
				)
			),
			'submit' => array(
				'title' => $this->l('Save'),
				'class' => 'button'
			)
		);

		$helper = new HelperForm();
		$helper->module = $this;
		$helper->name_controller = 'blockcmsinfo';
		$helper->identifier = $this->identifier;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		foreach (Language::getLanguages(false) as $lang)
			$helper->languages[] = array(
				'id_lang' => $lang['id_lang'],
				'iso_code' => $lang['iso_code'],
				'name' => $lang['name'],
				'is_default' => ($default_lang == $lang['id_lang'] ? 1 : 0)
			);

		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
		$helper->default_form_language = $default_lang;
		$helper->allow_employee_form_lang = $default_lang;
		$helper->toolbar_scroll = true;
		$helper->title = $this->displayName;
		$helper->submit_action = 'saveblockcmsinfo';
		$helper->toolbar_btn =  array(
			'save' =>
			array(
				'desc' => $this->l('Save'),
				'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
			),
			'back' =>
			array(
				'href' => AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
				'desc' => $this->l('Back to list')
			)
		);
		return $helper;
	}

	protected function initList()
	{


		$this->fields_list =  array(
		
			'id_info' => array(
				'title' => $this->l('Custom block #'),
				'width' => 40,
				'type' => 'text',
			),
			
		);

		if (Shop::isFeatureActive())
			$this->fields_list['id_shop'] = array('title' => $this->l('ID Shop'), 'align' => 'center', 'width' => 25, 'type' => 'int');

		$helper = new HelperList();
		$helper->shopLinkType = '';
		$helper->simple_header = true;
		$helper->identifier = 'id_info';
		$helper->actions = array('edit', 'delete');
		$helper->show_toolbar = true;
		$helper->imageType = 'jpg';
		$helper->toolbar_btn['new'] =  array(
			'href' => AdminController::$currentIndex.'&configure='.$this->name.'&add'.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
			'desc' => $this->l('Add new')
		);

		$helper->title = $this->displayName;
		$helper->table = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
		return $helper;
	}

	public function hookHome($params)
	{
		// Check if not a mobile theme
		if ($this->context->getMobileDevice() != false)
			return false;

		$this->context->controller->addCSS($this->_path.'style.css', 'all');
		if (!$this->isCached('blockcmsinfo.tpl', $this->getCacheId()))
		{
			$infos = $this->getListContent($this->context->language->id);
			$this->context->smarty->assign(array('infos' => $infos, 'nbblocks' => count($infos)));
		}
		return $this->display(__FILE__, 'blockcmsinfo.tpl', $this->getCacheId());
	}

	public function installFixtures()
	{
		$return = true;
		$tab_texts = array(
			array('text' => '<ul>
<li><em class="icon-truck"></em>
<div class="type-text">
<h3>Free Shipping</h3>
<p>Lorem ipsum dolor sit amet conse ctetur voluptate velit esse cillum dolore eu</p>
</div>
</li>
<li><em class="icon-phone"></em>
<div class="type-text">
<h3>Call us: (800)2345-6789</h3>
<p>Lorem ipsum dolor sit amet conse ctetur voluptate velit esse cillum dolore eu</p>
</div>
</li>
<li><em class="icon-credit-card"></em>
<div class="type-text">
<h3>Gift cards</h3>
<p>Lorem ipsum dolor sit amet conse ctetur voluptate velit esse cillum dolore eu</p>
</div>
</li>
</ul>'),
			array('text' => '<h3>Custom Block</h3>
<p><strong class="dark">Lorem ipsum dolor sit amet conse ctetu</strong></p>
<p>Sit amet conse ctetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit.</p>'),
		);
		foreach($tab_texts as $tab)
		{
			$info = new infoClass();
			foreach (Language::getLanguages(false) as $lang)
			$info->text[$lang['id_lang']] =  $tab['text'];
			$info->id_shop = $this->context->shop->id;
			$return &= $info->save();
		}
		return $return;
	}
}
