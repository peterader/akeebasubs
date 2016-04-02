<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\Helper\Select;

/** @var \Akeeba\Subscriptions\Site\View\Level\Html $this */

\JHtml::_('formbehavior.chosen');

$script = <<<JS

akeebasubs_level_id = {$this->item->akeebasubs_level_id};

JS;
JFactory::getDocument()->addScriptDeclaration($script);

$requireCoupon       = $this->cparams->reqcoupon;
$paymentMethodsCount = count(Select::paymentmethods('paymentmethod', '', ['id'              => 'paymentmethod',
																		  'level_id'        => $this->item->akeebasubs_level_id,
																		  'return_raw_list' => 1]));
$hidePaymentMethod   =
	(($paymentMethodsCount <= 1) && $this->cparams->hidelonepaymentoption) || ($this->validation->price->gross < 0.01);
?>

<div id="akeebasubs">

	{{-- "Do Not Track" warning --}}
	@include('site:com_akeebasubs/Level/default_donottrack')

	{{-- Module position 'akeebasubscriptionsheader' --}}
	@modules('akeebasubscriptionsheader')

	{{-- Steps bar --}}
	@if ($this->cparams->stepsbar && ($this->validation->price->net > 0.01))
	@include('site:com_akeebasubs/Level/steps', [
	'step' => 'subscribe',
	'akeebasubs_subscription_level' => $this->item->akeebasubs_level_id
	])
	@endif

	{{-- Subscription level summary --}}
	@include('site:com_akeebasubs/Level/default_level')

	{{-- Warning when Javascript is disabled --}}
	<noscript>
		<hr/>
		<h1>@lang('COM_AKEEBASUBS_LEVEL_ERR_NOJS_HEADER')</h1>
		<p>@lang('COM_AKEEBASUBS_LEVEL_ERR_NOJS_BODY')</p>
		<hr/>
	</noscript>

	{{-- Login form --}}
	@if (JFactory::getUser()->guest)
		@include('site:com_akeebasubs/Level/default_login')
	@endif

	<form
		action="@route('index.php?option=com_akeebasubs&view=Subscribe&layout=default&slug=' . $this->input->getString('slug', ''))"
		method="post"
		id="signupForm" class="form form-horizontal">

		<input type="hidden" name="{{{ JFactory::getSession()->getFormToken() }}}" value="1"/>

		{{-- User account & invoicing information fields --}}
		@include('site:com_akeebasubs/Level/default_fields')

		{{-- Payment summary --}}
		@include('site:com_akeebasubs/Level/default_summary')

		{{-- Custom fields after payment summary --}}
		<fieldset>
			<legend class="subs">@lang('COM_AKEEBASUBS_LEVEL_SUBSCRIBE')</legend>

			@include('site:com_akeebasubs/Level/default_prepayment')

			{{-- Coupon code --}}
			@if ($requireCoupon || ($this->validation->price->net > 0))
				<div class="control-group form-group">
					<label for="coupon" class="control-label col-sm-2">
						@lang('COM_AKEEBASUBS_LEVEL_FIELD_COUPON')
					</label>

					<div class="controls col-sm-3">
						<input type="text" class="form-control input-medium" name="coupon" id="coupon"
							   value="{{{$this->cache['coupon']}}}"/>
					</div>
				</div>
			@endif
		</fieldset>

		{{-- Payment methods --}}
		<div id="paymentmethod-container" class="{{$hidePaymentMethod ? 'hidden' : ''}}">
			<div class="control-group form-group">
				<label for="paymentmethod" class="control-label col-sm-2">
					@lang('COM_AKEEBASUBS_LEVEL_FIELD_METHOD')
				</label>

				<div id="paymentlist-container" class="controls col-sm-3">
					<?php
					$country = !empty($this->userparams->country) && ($this->userparams->country != 'XX') ?
						$this->userparams->country : $this->cache['country'];

					/** @var \Akeeba\Subscriptions\Site\Model\PaymentMethods $paymentMethods */
					$paymentMethods = $this->getContainer()->factory->model('PaymentMethods')->tmpInstance();
					$defaultPayment = $paymentMethods->getLastPaymentPlugin(JFactory::getUser()->id, $country);

					echo Select::paymentmethods(
						'paymentmethod',
						$defaultPayment,
						array(
							'id'       => 'paymentmethod',
							'level_id' => $this->item->akeebasubs_level_id,
							'country'  => $country
						)
					) ?>
				</div>
			</div>
		</div>

		{{-- Subscribe Now button --}}
		<div class="well">
			<button id="subscribenow" class="btn btn-large btn-primary" type="submit"
					style="display:block;margin:auto">
				@lang('COM_AKEEBASUBS_LEVEL_BUTTON_SUBSCRIBE')
			</button>
			<img class="ui-disable-spinner" src="{{{JUri::base()}}}media/com_akeebasubs/images/throbber.gif"
				 style="display: none"/>
		</div>

	</form>

	{{-- Module position 'akeebasubscriptionsfooter' --}}
	@modules('akeebasubscriptionsfooter')

</div>

<?php
$aks_msg_error_overall = JText::_('COM_AKEEBASUBS_LEVEL_ERR_JSVALIDATIONOVERALL', true);
$script                = <<<JS

akeebasubs_apply_validation = {$this->apply_validation};

akeeba.jQuery(document).ready(function(){
	validatePassword();
	validateName();
	validateEmail();
	validateAddress();
	validateBusiness();
	validateForm();
});

function onSignupFormSubmit()
{
	if (akeebasubs_valid_form == false) {
		alert('$aks_msg_error_overall');
	}

	return akeebasubs_valid_form;
}

JS;
JFactory::getDocument()->addScriptDeclaration($script);