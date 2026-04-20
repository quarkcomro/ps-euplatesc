{*
* 2007-2016 PrestaShop
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
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<div class="container" style="margin-top:50px;">
	<div class="col-lg-3 col-md-2"></div>
	<div class="well col-lg-6 col-md-8">
		<center>
			<form action="{$link->getModuleLink('euplatesc', 'validation', [], true)|escape:'html'}" method="post">
				<div class="box cheque-box">

					<h3 class="page-subheading">{l s='Online cu card bancar prin EuPlatesc.ro' mod='euplatesc'}</h3>

				
					<input type="hidden" name="confirm" value="1" />
					<p>
						<img src="{$this_path_cod}euplatesc.jpg" alt="{l s='Online cu card bancar prin EuPlatesc.ro' mod='euplatesc'}" style="margin: 0px 10px 5px 0px;" />
						{l s='Ai ales sa platesti cu metoda Online cu card bancar prin EuPlatesc.ro' mod='euplatesc'}
						<br/>
						{l s='The total amount of your order is' mod='euplatesc'}
						<span id="amount" class="price">{$total} {$curr}</span>
					</p>
					{$rate nofilter}
					<br><br>
					<p style="color:red">{$err}</p>
					<br><br>
					<p><b>{l s='Please confirm your order by clicking \'I confirm my order\'.' mod='euplatesc'}</b></p>
					
					
				</div>
				
				<p class="cart_navigation" id="cart_navigation">
					<a class="button_large button-exclusive btn btn-default" href="{$link->getPageLink('order', true)}?step=3"><i class="icon-chevron-left"></i>{l s='Other payment methods' mod='euplatesc'}</a>
					<button class="exclusive_large button btn btn-default button-medium" type="submit"><span>{l s='I confirm my order' mod='euplatesc'}<i class="icon-chevron-right right"></i></span></button>
				</p>
				
			</form>
		</center>
	</div>
</div>