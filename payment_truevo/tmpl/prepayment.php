<?php
/**
 * --------------------------------------------------------------------------------
 * Payment Plugin - Truevo
 * --------------------------------------------------------------------------------
 * @package     Joomla 2.5 -  3.x
 * @subpackage  J2 Store
 * @author      J2Store <support@j2store.org>
 * @copyright   Copyright (c) 2014-19 J2Store . All rights reserved.
 * @license     GNU/GPL license: http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://j2store.org
 * --------------------------------------------------------------------------------
 * */


defined ( '_JEXEC' ) or die ( 'Restricted access' );

?>
<div class="note">
    <?php echo JText::_($vars->onbeforepayment_text); ?>
    <?php
    $image = $this->params->get('display_image', '');
    ?>
    <?php if(!empty($image)): ?>
        <span class="j2store-payment-image">
				<img class="payment-plugin-image payment_cash" src="<?php echo JUri::root().JPath::clean($image); ?>" />
			</span>
    <?php endif; ?>
    <p>
        <strong>
            <?php echo JText::_($vars->display_name); ?>
        </strong>
    </p>
</div>
<?php if (isset($vars->checkout_id)): ?>
<span class="wait">&nbsp<img src="/media/j2store/images/loader.gif" alt="" /><?php echo JText::_('J2STORE_TRUEVO_WAIT'); ?></span>
<form id="form-truevo" action="<?php echo $vars->shopperResultUrl; ?>" class="paymentWidgets" data-brands="<?php echo implode(" ", $vars->cctypes); ?>"></form>
<?php endif; ?>
<script type="text/javascript">

    if(typeof(j2store) == 'undefined') {
        var j2store = {};
    }
    if(typeof(j2store.jQuery) == 'undefined') {
        j2store.jQuery = jQuery.noConflict();
    }


    var script = document.createElement('script');
    script.src = "<?php echo $vars->urlWidget . $vars->checkout_id; ?>";
    script.async = true;
    document.getElementsByTagName('head')[0].appendChild(script);
    script.onload = function() {

        var formLoad = setInterval(checkLoadForm, 1000);
        var spinner = j2store.jQuery('.wpwl-container div.spinner');

        function checkLoadForm(){
            var container = j2store.jQuery('div.wpwl-container');
            var form = container.find('form');
            if (form.find('div').length) {
                j2store.jQuery('span.wait').hide();
                clearInterval(formLoad);
            }
        }
    }
</script>