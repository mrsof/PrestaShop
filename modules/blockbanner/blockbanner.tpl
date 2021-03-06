{*
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
*}
<!-- Block stores module -->
<div id="banner_block_top">
	<div class="banner-top">
	<div class="container">
	  <a href="{$banner_link}" title="{$banner_desc}">
		{if isset($banner_img)}
			<img class="img-responsive" src="{$module_dir}{$banner_img}" alt="{$banner_desc}" title="{$banner_desc}"/>
		{else}
			{$banner_desc}
		{/if}
	  </a>
	</div>
  </div>
</div>
<!-- /Block stores module -->