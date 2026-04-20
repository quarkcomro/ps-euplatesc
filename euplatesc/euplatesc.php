<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_'))
	exit;

class EuPlatesc extends PaymentModule
{
	public static $EP_SETTINGS;
	
	public function __construct()
	{
		$this->name = 'euplatesc';
		$this->tab = 'payments_gateways';
		$this->version = '1.9';
		$this->author = 'EuPlatesc';
		$this->need_instance = 1;
		$this->controllers = array('validation');
		$this->is_eu_compatible = 1;

		$this->currencies = false;
		
		parent::__construct();

		$this->displayName = $this->l('Plata online cu card bancar prin EuPlatesc.ro');
		$this->description = $this->l('Online payments method');
		
		self::$EP_SETTINGS = Configuration::get('EUPLATESC_SETTINGS') != '' ? unserialize(Tools::htmlentitiesDecodeUTF8(Configuration::get('EUPLATESC_SETTINGS'))) : array();


		/* For 1.4.3 and less compatibility */
		$updateConfig = array('PS_OS_CHEQUE', 'PS_OS_PAYMENT', 'PS_OS_PREPARATION', 'PS_OS_SHIPPING', 'PS_OS_CANCELED', 'PS_OS_REFUND', 'PS_OS_ERROR', 'PS_OS_OUTOFSTOCK', 'PS_OS_BANKWIRE', 'PS_OS_PAYPAL', 'PS_OS_WS_PAYMENT');
		if (!Configuration::get('PS_OS_PAYMENT'))
			foreach ($updateConfig as $u)
				if (!Configuration::get($u) && defined('_'.$u.'_'))
					Configuration::updateValue($u, constant('_'.$u.'_'));
	}

	public function install()
	{
		if (!parent::install() OR ! $this->registerHook('paymentOptions') OR !$this->registerHook('paymentReturn'))
			return false;
		
		Db::getInstance()->execute("CREATE TABLE IF NOT EXISTS " . _DB_PREFIX_ . "ep_trans (	`id` int NOT NULL AUTO_INCREMENT,`ep_id` VARCHAR (50) NOT NULL,`invoice_id` VARCHAR (50) NOT NULL,UNIQUE KEY id (id));");
		
		$tmlp='/src/Adapter/Module/TabModuleListProvider.php';
		if(file_exists(_PS_ROOT_DIR_ . $tmlp)){
			$code=file_get_contents(_PS_ROOT_DIR_ . $tmlp);
			if(strpos($code,'euplatesc')===false){
				$code=str_replace('return $modules[\'default_list\'];','$modules[\'default_list\'][] = "euplatesc";'."\r\n".'        return $modules[\'default_list\'];',$code);
				file_put_contents(_PS_ROOT_DIR_ . $tmlp,$code);
			}
		}
		
		return true;
	}

	public function hookPaymentReturn($params)
	{
		if (!$this->active)
			return ;

		return $this->display(__FILE__, 'confirmation.tpl');
	}
	
	public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        $newOption = new PaymentOption();
        $newOption->setCallToActionText(self::$EP_SETTINGS['title'])
                ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
                ->setAdditionalInformation(self::$EP_SETTINGS['desc']);

        return [$newOption];
    }
	
    public function hookDisplayPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }
        return $this->fetch('module:euplatesc/views/templates/hook/confirmation.tpl');
    }


	
    public function getContent()
    {
        $settings = Configuration::get('EUPLATESC_SETTINGS') != ''
            ? unserialize(Tools::htmlentitiesDecodeUTF8(Configuration::get('EUPLATESC_SETTINGS')))
            : [];
    
        if (Tools::isSubmit('submitEuplatesc')) {
            $new = [];
    
            $new['title']  = (string) Tools::getValue('title', '');
            $new['desc']   = (string) Tools::getValue('desc', '');
            $new['mid']    = (string) Tools::getValue('mid', '');
            $new['key']    = (string) Tools::getValue('key', '');
    
            $new['epidn']  = (int) Tools::getValue('idEPNew');     // New Order Status ID
            $new['epids']  = (int) Tools::getValue('idEPSuccess'); // Payment Success Status ID
            $new['epidf']  = (int) Tools::getValue('idEPFail');    // Payment Failed Status ID
    
            $new['save_mode'] = (int) Tools::getValue('save_mode', 0);
    
            $new['staterate']     = (int) Tools::getValue('ep_cbrate', 0);
            $new['staterateapb']  = (int) Tools::getValue('staterateapb', 0);
            $new['staterategbr']  = (int) Tools::getValue('staterategbr', 0);
            $new['stateratebtrl'] = (int) Tools::getValue('stateratebtrl', 0);
            $new['stateratebrdf'] = (int) Tools::getValue('stateratebrdf', 0);
            $new['stateratepbr']  = (int) Tools::getValue('stateratepbr', 0);
            $new['stateraterzb']  = (int) Tools::getValue('stateraterzb', 0);
    
            $new['rateapb']  = (string) Tools::getValue('rateapb', '');
            $new['rategbr']  = (string) Tools::getValue('rategbr', '');
            $new['ratebtrl'] = (string) Tools::getValue('ratebtrl', '');
            $new['ratebrdf'] = (string) Tools::getValue('ratebrdf', '');
            $new['ratepbr']  = (string) Tools::getValue('ratepbr', '');
            $new['raterzb']  = (string) Tools::getValue('raterzb', '');
    
            $new['ordinerate'] = (string) Tools::getValue('ordinerate', '');
            $new['ratecompact'] = (int) Tools::getValue('ratecompact', 0);
    
            Configuration::updateValue('EUPLATESC_SETTINGS', serialize($new));
    
            $this->context->controller->confirmations[] = $this->trans('Settings updated', [], 'Admin.Notifications.Success');
        }
    
        return $this->renderForm();
    }

        public function renderForm()
    {
        $statusesOptions = $this->getStatusesOptions();
    
        $fields_form_settings = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('EuPlătesc – Settings', [], 'Modules.Euplatesc.Admin'),
                    'icon'  => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->trans('Title', [], 'Modules.Euplatesc.Admin'),
                        'name' => 'title',
                        'required' => true,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->trans('Description', [], 'Modules.Euplatesc.Admin'),
                        'name' => 'desc',
                        'cols' => 40,
                        'rows' => 5,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Merchant ID', [], 'Modules.Euplatesc.Admin'),
                        'name' => 'mid',
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Secure Key', [], 'Modules.Euplatesc.Admin'),
                        'name' => 'key',
                        'required' => true,
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('New Order Status ID', [], 'Modules.Euplatesc.Admin'),
                        'name' => 'idEPNew',
                        'options' => [
                            'query' => $statusesOptions,
                            'id'    => 'id',
                            'name'  => 'name',
                        ],
                        'desc' => $this->trans('Status for newly created orders.', [], 'Modules.Euplatesc.Admin'),
                        'required' => true,
                    ],
    
                    [
                        'type' => 'select',
                        'label' => $this->trans('Payment Success Status ID', [], 'Modules.Euplatesc.Admin'),
                        'name' => 'idEPSuccess',
                        'form_group_class' => 'ep-save-mode-only',
                        'options' => [
                            'query' => $statusesOptions,
                            'id'    => 'id',
                            'name'  => 'name',
                        ],
                        'desc' => $this->trans('Used when "Save order before payment" is enabled.', [], 'Modules.Euplatesc.Admin'),
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Payment Failed Status ID', [], 'Modules.Euplatesc.Admin'),
                        'name' => 'idEPFail',
                        'form_group_class' => 'ep-save-mode-only',
                        'options' => [
                            'query' => $statusesOptions,
                            'id'    => 'id',
                            'name'  => 'name',
                        ],
                        'desc' => $this->trans('Used when "Save order before payment" is enabled.', [], 'Modules.Euplatesc.Admin'),
                    ],
    
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Save order before payment', [], 'Modules.Euplatesc.Admin'),
                        'name' => 'save_mode',
                        'is_bool' => true,
                        'desc' => $this->trans('If enabled, the order is created before redirecting to payment (customer also gets the "new order" email).', [], 'Modules.Euplatesc.Admin'),
                        'values' => [
                            ['id' => 'active_on',  'value' => 1, 'label' => $this->trans('Yes', [], 'Admin.Global')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->trans('No', [], 'Admin.Global')],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];
    
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = (int) (Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?: 0);
        $helper->identifier = $this->identifier;
    
        $helper->submit_action = 'submitEuplatesc';
    
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name
            . '&tab_module=' . $this->tab
            . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
    
        $values = $this->getConfigFieldsValues();
        $helper->tpl_vars = [
            'fields_value' => $values,
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];
    
        $html = $helper->generateForm([$fields_form_settings]);
    
        $initialDisplay = ((int)$values['save_mode'] === 1) ? 'block' : 'none';
        $html .= '<style>.ep-save-mode-only{display: '.$initialDisplay.';}</style>';
    
        $html .= <<<JS
    <script>
    (function(){
      function toggleEpSaveMode(){
        var inputs = document.querySelectorAll('input[name="save_mode"]');
        var val = null;
        for (var i=0;i<inputs.length;i++){ if (inputs[i].checked) { val = inputs[i].value; break; } }
        var show = (val == '1' || val === 1);
        var nodes = document.querySelectorAll('.ep-save-mode-only');
        for (var j=0;j<nodes.length;j++){ nodes[j].style.display = show ? 'block' : 'none'; }
      }
      document.addEventListener('DOMContentLoaded', function(){
        toggleEpSaveMode();
        document.addEventListener('change', function(e){
          if (e.target && e.target.name === 'save_mode') { toggleEpSaveMode(); }
        });
      });
    })();
    </script>
    JS;
    
        return $html;
    }

    
    protected function getConfigFieldsValues()
    {
        $s = Configuration::get('EUPLATESC_SETTINGS') != ''
            ? unserialize(Tools::htmlentitiesDecodeUTF8(Configuration::get('EUPLATESC_SETTINGS')))
            : [];
    
        $v = function($key, $default = '') use ($s) {
            return isset($s[$key]) ? $s[$key] : $default;
        };
    
        return [
            'title'        => $v('title'),
            'desc'         => $v('desc'),
            'mid'          => $v('mid'),
            'key'          => $v('key'),
            'idEPNew'      => (int) $v('epidn'),
            'idEPSuccess'  => (int) $v('epids'),
            'idEPFail'     => (int) $v('epidf'),
            'save_mode'    => (int) $v('save_mode', 0),
    
            'ep_cbrate'     => (int) $v('staterate', 0),
            'staterateapb'  => (int) $v('staterateapb', 0),
            'staterategbr'  => (int) $v('staterategbr', 0),
            'stateratebtrl' => (int) $v('stateratebtrl', 0),
            'stateratebrdf' => (int) $v('stateratebrdf', 0),
            'stateratepbr'  => (int) $v('stateratepbr', 0),
            'stateraterzb'  => (int) $v('stateraterzb', 0),
    
            'rateapb'   => $v('rateapb'),
            'rategbr'   => $v('rategbr'),
            'ratebtrl'  => $v('ratebtrl'),
            'ratebrdf'  => $v('ratebrdf'),
            'ratepbr'   => $v('ratepbr'),
            'raterzb'   => $v('raterzb'),
    
            'ordinerate'  => $v('ordinerate'),
            'ratecompact' => (int) $v('ratecompact', 0),
        ];
    }

    protected function getStatusesOptions()
    {
    $id_lang = (int) $this->context->language->id;
    $states = OrderState::getOrderStates($id_lang);
    $opts = [];
    foreach ($states as $st) {
        $opts[] = [
            'id'   => (int) $st['id_order_state'],
            'name' => $st['name'],
        ];
    }
    return $opts;
    }


    public function getStatuses($sel){
		$states=OrderState::getOrderStates((int)$this->context->language->id);
		$ret="";
		foreach($states as $state){
			$ret.="<option value='". $state['id_order_state'] ."'".($sel==$state['id_order_state']?" selected":"").">".$state['name']."</option>";
		}
		return $ret;
    }
	
	private function preProcess()
	{
		if (Tools::isSubmit('submitModule'))
		{
			
			self::$EP_SETTINGS['title'] = Tools::getValue('title');
			self::$EP_SETTINGS['desc'] = Tools::getValue('desc');
			self::$EP_SETTINGS['mid'] = Tools::getValue('mid');
			self::$EP_SETTINGS['key'] = Tools::getValue('key');
			self::$EP_SETTINGS['epidn'] = (Tools::getValue('idEPNew') !== '' ? Tools::getValue('idEPNew') : '3');
			self::$EP_SETTINGS['epids'] = (Tools::getValue('idEPSuccess') !== '' ? Tools::getValue('idEPSuccess') : '2');
			self::$EP_SETTINGS['epidf'] = (Tools::getValue('idEPFail') !== '' ? Tools::getValue('idEPFail') : '8');
			self::$EP_SETTINGS['save_mode'] = (Tools::getValue('save_mode') == '1' ? '1' : '0');
			self::$EP_SETTINGS['key'] = Tools::getValue('key');
			self::$EP_SETTINGS['staterate'] = Tools::getValue('ep_cbrate')=='1'?'1':'0';
			self::$EP_SETTINGS['staterateapb'] = Tools::getValue('staterateapb')=='1'?'1':'0';
			self::$EP_SETTINGS['staterategbr'] = Tools::getValue('staterategbr')=='1'?'1':'0';
			self::$EP_SETTINGS['stateratebtrl'] = Tools::getValue('stateratebtrl')=='1'?'1':'0';
			self::$EP_SETTINGS['stateratebrdf'] = Tools::getValue('stateratebrdf')=='1'?'1':'0';
			self::$EP_SETTINGS['stateratepbr'] = Tools::getValue('stateratepbr')=='1'?'1':'0';
			self::$EP_SETTINGS['stateraterzb'] = Tools::getValue('stateraterzb')=='1'?'1':'0';
			
			self::$EP_SETTINGS['rateapb'] = Tools::getValue('rateapb');
			self::$EP_SETTINGS['rategbr'] = Tools::getValue('rategbr');
			self::$EP_SETTINGS['ratebtrl'] = Tools::getValue('ratebtrl');
			self::$EP_SETTINGS['ratebrdf'] = Tools::getValue('ratebrdf');
			self::$EP_SETTINGS['raterzb'] = Tools::getValue('raterzb');
			self::$EP_SETTINGS['ratepbr'] = Tools::getValue('ratepbr');
			
			self::$EP_SETTINGS['ordinerate'] = Tools::getValue('ordinerate');
			self::$EP_SETTINGS['ratecompact'] = Tools::getValue('ratecompact')=='1'?'1':'0';
			
			Configuration::updateValue('EUPLATESC_SETTINGS', Tools::htmlentitiesUTF8(serialize(self::$EP_SETTINGS)));
			
			return '<div class="conf confirm"><img src="../img/admin/enabled.gif"/>'.$this->l('Configuration updated').'</div>';
		}
		return '';
	}
	
	public function hmacsha1($key,$data){
	   $blocksize = 64;
	   $hashfunc  = 'md5';
	   if(strlen($key) > $blocksize)
		 $key = pack('H*', $hashfunc($key));
	   $key  = str_pad($key, $blocksize, chr(0x00));
	   $ipad = str_repeat(chr(0x36), $blocksize);
	   $opad = str_repeat(chr(0x5c), $blocksize);
	   $hmac = pack('H*', $hashfunc(($key ^ $opad) . pack('H*', $hashfunc(($key ^ $ipad) . $data))));
	   return bin2hex($hmac);
	}

	public function euplatesc_mac($data, $key){
	  $str = NULL;
	  foreach($data as $d){
		if($d === NULL || strlen($d) == 0)
		  $str .= '-';
		else
		  $str .= strlen($d) . $d;
	  }
	  $key = pack('H*', $key);
	  return $this->hmacsha1($key, $str);
	}
}
