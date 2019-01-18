<?php
/*------------------------------------------------------------------------
# com_j2store - J2Store
# ------------------------------------------------------------------------
# author    Sasi varna kumar - Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2014 - 19 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://j2store.org
# Technical Support:  Forum - http://j2store.org/forum/index.html
-------------------------------------------------------------------------*/

defined('_JEXEC') or die('Restricted access');

?>
<div class="note">
    <p><?php echo JText::_($vars->onselection_text); ?></p>
</div>
<table id="truevo_form" class="table">
    <tr>
        <td class="field_name"><?php echo JText::_( 'J2STORE_CREDITCARD_TYPE' ) ?></td>
        <td><?php echo $vars->cctype_input ?></td>
    </tr>
    <tr>
        <td class="field_name"><?php echo JText::_( 'J2STORE_CARDHOLDER_NAME' ) ?></td>
        <td><input type="text" class="required" name="cardholder"
                   size="35"
                   value="<?php echo !empty($vars->prepop['x_card_holder']) ? ($vars->prepop['x_card_holder']) : '' ?>"
                   title="<?php echo JText::_('J2STORE_CARDHOLDER_VALIDATION_ERROR_NAME'); ?>"
            />
            <div class="j2error"></div>
        </td>
    </tr>
    <tr>
        <td class="field_name"><?php echo JText::_( 'J2STORE_CARD_NUMBER' ) ?></td>
        <td><input type="text" class="required number"
                   name="cardnum"
                   size="35"
                   value="<?php echo !empty($vars->prepop['x_card_num']) ? ($vars->prepop['x_card_num']) : '' ?>"
                   title="<?php echo JText::_('J2STORE_CARDHOLDER_VALIDATION_ERROR_NUMBER'); ?>"
            />
            <div class="j2error"></div>
        </td>
    </tr>
    <tr>
        <td class="field_name"><?php echo JText::_( 'J2STORE_EXPIRY_DATE' ) ?></td>
        <td>
            <select name="month" class="required number"
                    title="<?php echo JText::_('J2STORE_EXPIRY_VALIDATION_ERROR_MONTH'); ?>"
            >
                <option value=""><?php echo JText::_('J2STORE_EXPIRY_MONTH'); ?></option>
                <option value="01">01</option>
                <option value="02">02</option>
                <option value="03">03</option>
                <option value="04">04</option>
                <option value="05">05</option>
                <option value="06">06</option>
                <option value="07">07</option>
                <option value="08">08</option>
                <option value="09">09</option>
                <option value="10">10</option>
                <option value="11">11</option>
                <option value="12">12</option>
            </select>
            <div class="j2error"></div>
            <select name="year" class="required number"
                    title="<?php echo JText::_('J2STORE_EXPIRY_VALIDATION_ERROR_YEAR'); ?>"
            >
                <option value=""><?php echo JText::_('J2STORE_EXPIRY_YEAR'); ?></option>
                <?php
                $two_digit_year = date('y');
                $four_digit_year = date('Y');
                ?>
                <?php for($i=$two_digit_year;$i<$two_digit_year+50;$i++) {?>
                    <option value="<?php echo $four_digit_year;?>"><?php echo $four_digit_year;?></option>
                    <?php
                    $four_digit_year++;
                } ?>
            </select>
            <div class="j2error"></div>
        </td>
    </tr>
    <tr>
        <td class="field_name"><?php echo JText::_( 'J2STORE_CARD_CVV' ) ?></td>
        <td>
            <input type="text" class="required number" name="cardcvv" size="10" value=""
                   title="<?php echo JText::_('J2STORE_CARD_VALIDATION_ERROR_CVV'); ?>"
            />
            <div class="j2error"></div>
        </td>
    </tr>
</table>