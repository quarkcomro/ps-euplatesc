<?php
/*
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
*/

/**
 * @since 1.5.0
 */
class EuPlatescValidationModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    private $settings;
    protected $err = 0;

    private function ipn()
    {
        if (isset($_POST['message']) && strpos(strtolower($_POST['message']), 'pending') !== false) {
            echo 'no pending';
            die();
        }

        $obj_euplatesc = $this->module;
        $key = $this->settings['key'];

        if (isset($_POST['cart_id'])) {
            $epId = addslashes($_POST['cart_id']);

            $mapped = Db::getInstance()->getValue(
                'SELECT invoice_id FROM '._DB_PREFIX_.'ep_trans WHERE ep_id="'.pSQL($epId).'"'
            );

            if (isset($_POST['sec_status']) && $mapped) {
                if ($_POST['sec_status'] == 8 || $_POST['sec_status'] == 9) {
                    if ($this->settings['save_mode'] == 1) {
                        $order = new Order((int)$mapped);
						$this->insertPaymentData($mapped,$zcrsp['ep_id'],$zcrsp['amount']);
                        $order->setCurrentState((int)euplatesc::$EP_SETTINGS['epids']);
                        
                    } else {
                        $idCart = (int)$mapped;
                        
                        $amountPaid = isset($_POST['amount']) ? (float)$_POST['amount'] : 0.0;
                        $txnId = (string)($_POST['ep_id'] ?? $_POST['cart_id']);

                        $this->module->validateOrder(
                            (int)$idCart,
                            (int)euplatesc::$EP_SETTINGS['epids'], 
                            $amountPaid,
                            $this->module->displayName,
                            isset($_POST['message']) ? $_POST['message'] : null,
                            ['transaction_id' => $txnId],
                            null,
                            false,
                            @$_POST['ExtraData']['ckey']
                        );
						$n_order_id = (int)$this->module->currentOrder;
						$this->insertPaymentData($n_order_id,$zcrsp['ep_id'],$zcrsp['amount']);
                        $this->update_ep_iid($txnId, $n_order_id);
                    }
                    echo 'cid ok';
                } elseif ($_POST['sec_status'] == 5 || $_POST['sec_status'] == 6) {
                    if ($this->settings['save_mode'] == 1) {
                        $order = new Order((int)$mapped);
                        if (Validate::isLoadedObject($order)) {
                            $order->setCurrentState((int)euplatesc::$EP_SETTINGS['epidf']);
                        }
                    }
                    echo 'cid fld';
                }
            } else {
                echo 'nfnd';
            }

        } elseif (isset($_POST['sec_status']) && isset($_POST['invoice_id'])) {
            $zcrsp = array(
                'amount'     => addslashes(trim(@$_POST['amount'])),
                'curr'       => addslashes(trim(@$_POST['curr'])),
                'invoice_id' => addslashes(trim(@$_POST['invoice_id'])),
                'ep_id'      => addslashes(trim(@$_POST['ep_id'])),
                'merch_id'   => addslashes(trim(@$_POST['merch_id'])),
                'action'     => addslashes(trim(@$_POST['action'])),
                'message'    => addslashes(trim(@$_POST['message'])),
                'approval'   => addslashes(trim(@$_POST['approval'])),
                'timestamp'  => addslashes(trim(@$_POST['timestamp'])),
                'nonce'      => addslashes(trim(@$_POST['nonce'])),
                'sec_status' => addslashes(trim(@$_POST['sec_status'])),
            );

            $zcrsp['fp_hash'] = strtoupper($obj_euplatesc->euplatesc_mac($zcrsp, $key));
            $fp_hash = addslashes(trim(@$_POST['fp_hash']));

            if ($zcrsp['fp_hash'] != $fp_hash) {
                echo 'Invalid FP_HASH 1';
            } else {
                if ((int)$_POST['action'] == 0) {
                    if ((int)$_POST['sec_status'] == 8 || (int)$_POST['sec_status'] == 9) {
                        if ($this->settings['save_mode'] == 1) {
                            $order = new Order((int)$zcrsp['invoice_id']);
							$this->insertPaymentData($zcrsp['invoice_id'],$zcrsp['ep_id'],$zcrsp['amount']);
                            $order->setCurrentState((int)euplatesc::$EP_SETTINGS['epids']);
                            
                        } else {
                            $this->module->validateOrder(
                                (int)$zcrsp['invoice_id'],
                                (int)euplatesc::$EP_SETTINGS['epids'], // direct PLĂTIT
                                (float)$zcrsp['amount'],
                                $this->module->displayName,
                                $zcrsp['message'],
                                ['transaction_id' => (string)$zcrsp['ep_id']],
                                null,
                                false,
                                @$_POST['ExtraData']['ckey']
                            );

                            $n_order_id = (int)$this->module->currentOrder;
							$this->insertPaymentData($n_order_id,$zcrsp['ep_id'],$zcrsp['amount']);
                            $this->update_ep_iid($zcrsp['ep_id'], $n_order_id);
                        }
                        echo 'ss ok';
                    } else {
                        Db::getInstance()->execute(
                            'INSERT INTO '._DB_PREFIX_.'ep_trans(id, ep_id, invoice_id) VALUES (null, "'.pSQL($zcrsp['ep_id']).'", "'.pSQL($zcrsp['invoice_id']).'")'
                        );
                        echo 'ss ins';
                    }
                } else {
                    if ($this->settings['save_mode'] == 1) {
                        $order = new Order((int)$zcrsp['invoice_id']);
                        if (Validate::isLoadedObject($order)) {
                            $order->setCurrentState((int)euplatesc::$EP_SETTINGS['epidf']);
                        }
                    }
                    echo 'ss fld';
                }
            }

        } else {
            $zcrsp = array(
                'amount'     => addslashes(trim(@$_POST['amount'])),
                'curr'       => addslashes(trim(@$_POST['curr'])),
                'invoice_id' => addslashes(trim(@$_POST['invoice_id'])),
                'ep_id'      => addslashes(trim(@$_POST['ep_id'])),
                'merch_id'   => addslashes(trim(@$_POST['merch_id'])),
                'action'     => addslashes(trim(@$_POST['action'])),
                'message'    => addslashes(trim(@$_POST['message'])),
                'approval'   => addslashes(trim(@$_POST['approval'])),
                'timestamp'  => addslashes(trim(@$_POST['timestamp'])),
                'nonce'      => addslashes(trim(@$_POST['nonce'])),
            );

            $zcrsp['fp_hash'] = strtoupper($this->module->euplatesc_mac($zcrsp, $key));
            $fp_hash = addslashes(trim(@$_POST['fp_hash']));

            if ($zcrsp['fp_hash'] === $fp_hash) {
                if ($zcrsp['action'] == '0') {
                    if ($this->settings['save_mode'] == 1) {
                        $order = new Order((int)$zcrsp['invoice_id']);
                        $this->insertPaymentData($zcrsp['invoice_id'],$zcrsp['ep_id'],$zcrsp['amount']);
                        $order->setCurrentState((int)euplatesc::$EP_SETTINGS['epids']);
                        
                    } else {
                        $this->module->validateOrder(
                            (int)$zcrsp['invoice_id'],
                            (int)euplatesc::$EP_SETTINGS['epids'], 
                            (float)$zcrsp['amount'],
                            $this->module->displayName,
                            $zcrsp['message'],
                            ['transaction_id' => (string)$zcrsp['ep_id']],
                            null,
                            false,
                            @$_POST['ExtraData']['ckey']
                        );
						
						$n_order_id = (int)$this->module->currentOrder;
						$this->insertPaymentData($n_order_id,$zcrsp['ep_id'],$zcrsp['amount']);
                        $this->update_ep_iid($zcrsp['ep_id'], $n_order_id);
                    }
                    echo 'ok';
                } else {
                    if ($this->settings['save_mode'] == 1) {
                        $order = new Order((int)$zcrsp['invoice_id']);
                        if (Validate::isLoadedObject($order)) {
                            $order->setCurrentState((int)euplatesc::$EP_SETTINGS['epidf']);
                        }
                    }
                    echo 'fld';
                }
            } else {
                echo 'Invalid FP_HASH 2';
            }
        }
    }
	
	public function insertPaymentData($oid,$txnId,$amountPaid){
		$order = new Order((int)$oid);
		if (Validate::isLoadedObject($order)) {
			if (!Db::getInstance()->getValue(
				'SELECT id_order_payment FROM '._DB_PREFIX_.'order_payment WHERE transaction_id = "'.pSQL($txnId).'"'
			)) {
				$order->addOrderPayment(
					$amountPaid,
					$this->module->displayName,
					$txnId,
					new Currency((int)$order->id_currency)
				);
			}
		}
	}

    public function update_ep_iid($epid, $niid)
    {
        $key = $this->settings['key'];
        $data = array(
            'method'    => 'update_iid_mid',
            'mid'       => $this->settings['mid'],
            'epid'      => $epid,
            'invoice_id'=> $niid,
            'timestamp' => gmdate('YmdHis'),
            'nonce'     => md5(mt_rand().time()),
        );

        $data['fp_hash'] = strtoupper($this->module->euplatesc_mac($data, $key));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://manager.euplatesc.ro/v3/index.php?action=ws');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close($ch);
    }

    public function postProcess()
    {
        $this->settings = Configuration::get('EUPLATESC_SETTINGS') != ''
            ? unserialize(Tools::htmlentitiesDecodeUTF8(Configuration::get('EUPLATESC_SETTINGS')))
            : [];

        if (isset($_POST['fp_hash']) && !isset($_GET['success'])) {
            $this->ipn();
            exit;
        }

        if (Tools::getValue('success')) {
            $cartid = (int)Tools::getValue('success');

            $order = Order::getByCartId($cartid);
            if (!$order || !Validate::isLoadedObject($order)) {
                $this->setRedirectAfter($this->context->link->getPageLink('order', true, null, 'step=1'));
                $this->redirect();
            }

            $customer = new Customer((int)$order->id_customer);
            if (!Validate::isLoadedObject($customer)) {
                $this->setRedirectAfter($this->context->link->getPageLink('order', true, null, 'step=1'));
                $this->redirect();
            }

            $success_url = $this->context->link->getPageLink(
                'order-confirmation',
                true,
                null,
                [
                    'id_cart'   => (int)$cartid,
                    'id_module' => (int)$this->module->id,
                    'id_order'  => (int)$order->id,
                    'key'       => $customer->secure_key,
                ]
            );

            $this->setRedirectAfter($success_url);
            $this->redirect(); 
        }

        if (
            (int)$this->context->cart->id_customer === 0 ||
            (int)$this->context->cart->id_address_delivery === 0 ||
            (int)$this->context->cart->id_address_invoice === 0 ||
            !$this->module->active
        ) {
            $this->setRedirectAfter($this->context->link->getPageLink('order', true, null, 'step=1'));
            $this->redirect();
        }

        if (Tools::getValue('confirm') || $this->isRateActive() == false) {
            $this->err = 0;

            if (isset($_POST['ptype']) && (int)$_POST['ptype'] === 1) {
                if (
                    isset($_POST['epbanca'], $_POST['epnrrate']) &&
                    ($_POST['epbanca'] === 'no' || $_POST['epnrrate'] === 'Selecteaza nr. rate')
                ) {
                    $this->err = 1;
                }
            }

            if (!$this->err) {
                if (!empty($this->settings['save_mode']) && (int)$this->settings['save_mode'] === 1) {
                    $customer = new Customer((int)$this->context->cart->id_customer);
                    $total = (float)$this->context->cart->getOrderTotal(true, Cart::BOTH);

                    $this->module->validateOrder(
                        (int)$this->context->cart->id,
                        (int)$this->settings['epidn'],
                        $total,
                        $this->module->displayName,
                        null,
                        [],
                        null,
                        false,
                        $customer->secure_key
                    );
                }

                $this->setRedirectAfter($this->getEuPlatescLink());
                $this->redirect();
            }
        }
    }

    public function getSuccessLink($cid)
    {
        return $this->context->link->getModuleLink($this->module->name, 'validation', array('success' => $cid), true);
    }

    public function getIPNLink()
    {
        return $this->context->link->getModuleLink($this->module->name, 'validation', array(), true);
    }

    public function getEuPlatescLink()
    {
        $currency = new Currency((int)$this->context->cart->id_currency);

        if ($this->settings['save_mode'] == 1) {
            $order_id = $this->module->currentOrder;
        } else {
            $order_id = $this->context->cart->id;
        }

        $dataAll = array(
            'amount'     => $this->context->cart->getOrderTotal(true, Cart::BOTH),
            'curr'       => strtoupper($currency->iso_code),
            'invoice_id' => $order_id,
            'order_desc' => 'Comanda Online',
            'merch_id'   => $this->settings['mid'],
            'timestamp'  => gmdate('YmdHis'),
            'nonce'      => md5(microtime() . mt_rand()),
        );

        $dataAll['fp_hash'] = strtoupper($this->module->euplatesc_mac($dataAll, $this->settings['key']));

        $customer  = new Customer((int)$this->context->cart->id_customer);
        $address   = new Address((int)$this->context->cart->id_address_invoice);
        $address_sh = new Address((int)$this->context->cart->id_address_delivery);

        $state = new State((int)$address->id_state);
        $dataAll['fname']   = $address->firstname;
        $dataAll['lname']   = $address->lastname;
        $dataAll['add']     = $address->address1;
        $dataAll['city']    = $address->city;
        $dataAll['state']   = $state->name;
        $dataAll['zip']     = $address->postcode;
        $dataAll['phone']   = $address->phone.'/'.$address->phone_mobile;
        $dataAll['email']   = $customer->email;
        $dataAll['country'] = $address->country;
        $dataAll['company'] = $address->company;

        if (Validate::isLoadedObject($address_sh)) {
            $state_sh = new State((int)$address_sh->id_state);
            $dataAll['sfname']   = $address_sh->firstname;
            $dataAll['slname']   = $address_sh->lastname;
            $dataAll['sadd']     = $address_sh->address1;
            $dataAll['scity']    = $address_sh->city;
            $dataAll['sstate']   = $state_sh->name;
            $dataAll['szip']     = $address_sh->postcode;
            $dataAll['sphone']   = $address_sh->phone.'/'.$address_sh->phone_mobile;
            $dataAll['scountry'] = $address_sh->country;
            $dataAll['scompany'] = $address_sh->company;
        }

        $dataAll['ExtraData[successurl]']  = $this->getSuccessLink($this->context->cart->id);
        $dataAll['ExtraData[silenturl]']   = $this->getIPNLink();
        $dataAll['ExtraData[api_version]'] = '1';

        if ($this->settings['save_mode'] != 1) {
            $dataAll['ExtraData[ckey]'] = $customer->secure_key;
            $dataAll['ExtraData[tkey]'] = base64_encode($dataAll['amount']);
        }

        if ($this->settings['ratecompact'] == 0) {
            if (Tools::getValue('ptype')) {
                $dataAll['ExtraData[rate]'] = Tools::getValue('eprata');
            }
        } else {
            if (Tools::getValue('epbanca') && Tools::getValue('epnrrate')) {
                $dataAll['ExtraData[rate]'] = Tools::getValue('epbanca') . '-' . Tools::getValue('epnrrate');
            }
        }

        return 'https://secure.euplatesc.ro/tdsprocess/tranzactd.php?' . http_build_query($dataAll);
    }

    public function getRateScript(){
		$table=array();
		$table['no']=array("Selecteaza nr. rate");
		
		foreach($this->getRateNames() as $rata=>$name){		
			if($this->settings['staterate'.$rata]==1){
				if($this->settings['rate'.$rata]!=""){
					$ratenr=explode(',',$this->settings['rate'.$rata]);
					array_unshift($ratenr,"Selecteaza nr. rate");
					$table[$rata]=$ratenr;
				}
			}
		}
		return
		'<script>function ep_change(op){if(op){document.getElementById("ep_ratec").style.display="block";}else{document.getElementById("ep_ratec").style.display="none";}}
			window.epr_table=epr_table='.json_encode($table).';
			function ep_rata_change(obj){var container=document.getElementById("epcompactnr");var html="";for(var i=0;i<epr_table[obj.value].length;i++){html+="<option value=\'"+epr_table[obj.value][i]+"\'>"+epr_table[obj.value][i]+"</option>";}container.innerHTML=html;}
		</script>';
		
	}

    public function getRateNames()
    {
        $rate = array(
            'apb'  => 'Alpha Bank',
            'gbr'  => 'Garanti Bank',
            'btrl' => 'Banca Transilvania',
            'brdf' => 'BRD Finance',
            'pbr'  => 'Piraeus Bank',
            'rzb'  => 'Raiffeisen Bank'
        );
        return $rate;
    }

    public function getRateIndex($i)
    {
        $cnt = 1;
        foreach ($this->getRateNames() as $key => $value) {
            if ($cnt == $i) {
                return array($key, $value);
            } else {
                $cnt++;
            }
        }
        return array();
    }

    public function isRateActive()
    {
        if ($this->settings['staterate'] == 0) {
            return false;
        }

        foreach ($this->getRateNames() as $rata => $name) {
            if ($this->settings['staterate'.$rata] == 1)
                if ($this->settings['rate'.$rata] != '')
                    return true;
        }
        return false;
    }

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
	{
		parent::initContent();
		
		$ratehtml='';
		$this->settings=Configuration::get('EUPLATESC_SETTINGS') != '' ? unserialize(Tools::htmlentitiesDecodeUTF8(Configuration::get('EUPLATESC_SETTINGS'))) : array();
		if($this->isRateActive()){
			$ratehtml.=$this->getRateScript();
			$ratehtml.='<br>Alege tipul platii:<br><br>';
			$ratehtml.='<input type="radio" name="ptype" checked onchange="ep_change(0)" value="0" autocomplete="off"><label>Plata integrala</label><br>';
			$ratehtml.='<input type="radio" name="ptype" onchange="ep_change(1)" value="1" autocomplete="off"><label>Plata in rate</label><br><div id="ep_ratec" style="margin-left:20px;display:none;">';
			if($this->settings['ratecompact']==0){
				$firstr=1;
				$orinelista=array();
				if($this->settings['ordinerate']!=""){
					$rordonate=explode(',',$this->settings['ordinerate']);
					foreach($rordonate as $idrata){
						$rata=$this->getRateIndex($idrata)[0];
						$name=$this->getRateIndex($idrata)[1];
						if($this->settings['staterate'.$rata]==1){
							if($this->settings['rate'.$rata]!=""){
								$orinelista[] = $rata;
								$ratenr=explode(',',$this->settings['rate'.$rata]);
								foreach($ratenr as $nrr){
									$ratehtml.='<input type="radio" '.($firstr==1?'checked':'').' name="eprata" value="'.$rata.'-'.$nrr.'"><label>'.$nrr.' rate '.$name.'</label><br>';
									$firstr=0;
								}
							}
						}
					}
					
				}
				
				foreach($this->getRateNames() as $rata=>$name){
					
					if($this->settings['staterate'.$rata]==1){
						if($this->settings['rate'.$rata]!="" && !in_array($rata,$orinelista) ){
							$ratenr=explode(',',$this->settings['rate'.$rata]);
							foreach($ratenr as $nrr){
								$ratehtml.='<input type="radio" '.($firstr==1?'checked':'').' name="eprata" value="'.$rata.'-'.$nrr.'"><label>'.$nrr.' rate '.$name.'</label><br>';
								$firstr=0;
							}
						}
					}
				}
			}else{
				$primarata="";
				
				$ratehtml.="<select autocomplete='off' onchange='ep_rata_change(this)' name='epbanca' style='width:200px;'><option value='no'>Selecteaza banca</option>";
				$orinelista=array();
				if($this->settings['ordinerate']!=""){
					$rordonate=explode(',',$this->settings['ordinerate']);
					foreach($rordonate as $idrata){
						$rata=$this->getRateIndex($idrata)[0];
						$name=$this->getRateIndex($idrata)[1];
						if($this->settings['staterate'.$rata]==1){
							if($this->settings['rate'.$rata]!=""){
								$orinelista[] = $rata;
								$ratehtml.="<option value='".$rata."'>".$name."</option>";
							}
						}
					}
					
				}
				
				foreach($this->getRateNames() as $rata=>$name){
					
					if($this->settings['staterate'.$rata]==1){
						if($this->settings['rate'.$rata]!="" && !in_array($rata,$orinelista) ){
							$ratehtml.="<option value='".$rata."'>".$name."</option>";
						}
					}
				}
				$ratehtml.="</select><br><select autocomplete='off' name='epnrrate' id='epcompactnr' style='width:200px;'><option value='0'>Selecteaza nr. rate</option></select>";
				
			}
			$ratehtml.="</div>";
		}
		$currency = new Currency(intval($this->context->cart->id_currency));
		
		$this->context->smarty->assign(array(
			'total' => $this->context->cart->getOrderTotal(true, Cart::BOTH),
			'this_path' => $this->module->getPathUri(),//keep for retro compat
			'this_path_cod' => $this->module->getPathUri(),
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/',
			'rate' => $ratehtml,
			'curr' => $currency->iso_code,
			'err' => $this->err==1?"Va rugam sa alegeti banca si numarul de rate":""
		));

		$this->setTemplate('module:euplatesc/views/templates/front/validation.tpl');
	}
}
