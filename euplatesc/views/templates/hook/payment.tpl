{literal}<style>
	.payment_module a.euplatesc {
		background: url({/literal}{$this_path}euplatesc.gif{literal}) 15px 15px no-repeat #fbfbfb;    
		padding-left: 109px;	
		cursor:pointer;
	}
	p.payment_module a.euplatesc:after {
      display: block;
      content: "\f054";
      position: absolute;
      right: 15px;
      margin-top: -11px;
      top: 50%;
      font-family: "FontAwesome";
      font-size: 25px;
      height: 22px;
      width: 14px;
      color: #777777; }
</style>{/literal}

<p class="payment_module">
	<a onclick="window.location='{$link->getModuleLink('euplatesc', 'validation', [], true)|escape:'html'}'" title="{l s='Plateste Online cu card bancar prin EuPlatesc.ro' mod='euplatesc'}" rel="nofollow" class="euplatesc">
		{l s='Plateste Online cu card bancar prin EuPlatesc.ro' mod='euplatesc'}
		<br />{l s='Vei fi redirectionat catre pagina securizata de plata EuPlatesc.ro' mod='euplatesc'}
		<br style="clear:both;" />
	</a>
</p>
