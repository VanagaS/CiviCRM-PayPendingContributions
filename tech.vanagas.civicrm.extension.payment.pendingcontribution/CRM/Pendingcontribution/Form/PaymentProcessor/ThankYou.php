<?php

require_once 'CRM/Core/Form.php';
require_once 'CRM/Pendingcontribution/Form/PaymentProcessor/Base.php';
require_once 'CRM/Pendingcontribution/PendingContributions.php';
require_once 'CRM/Pendingcontribution/ContributionPage.php';

use \tech\vanagas\civicrm\extension\payment\pendingcontribution\PendingContributions as PendingContributions;
use \tech\vanagas\civicrm\extension\payment\pendingcontribution\ContributionPage as ContributionPage;

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Pendingcontribution_Form_PaymentProcessor_ThankYou extends CRM_Pendingcontribution_Form_PaymentProcessor_Base
{

    /**
     * Membership price set status.
     */
    public $_useForMember;

    /**
     * Set variables up before form is built.
     */
    public function preProcess()
    {
        parent::preProcess();

        $this->_params = $this->get('params');
        $this->_lineItem = $this->get('lineItem');
        $is_deductible = $this->get('is_deductible');
        $this->assign('is_deductible', $is_deductible);
        $this->assign('thankyou_title', CRM_Utils_Array::value('thankyou_title', $this->_values));
        $this->assign('thankyou_text', CRM_Utils_Array::value('thankyou_text', $this->_values));
        $this->assign('thankyou_footer', CRM_Utils_Array::value('thankyou_footer', $this->_values));
        $this->assign('max_reminders', CRM_Utils_Array::value('max_reminders', $this->_values));
        $this->assign('initial_reminder_day', CRM_Utils_Array::value('initial_reminder_day', $this->_values));
        CRM_Utils_System::setTitle(CRM_Utils_Array::value('thankyou_title', $this->_values));
        // Make the contributionPageID available to the template
        $this->assign('contributionPageID', $this->_values['id']);
        $this->assign('isShare', $this->_values['is_share']);

        $this->_params['is_pay_later'] = $this->get('is_pay_later');
        $this->assign('is_pay_later', $this->_params['is_pay_later']);
        if ($this->_params['is_pay_later']) {
            $this->assign('pay_later_receipt', $this->_values['pay_later_receipt']);
        }
        $this->assign('is_for_organization', CRM_Utils_Array::value('is_for_organization', $this->_params));
    }

    /**
     * Overwrite action, since we are only showing elements in frozen mode
     * no help display needed
     *
     * @return int
     */
    public function getAction()
    {
        if ($this->_action & CRM_Core_Action::PREVIEW) {
            return CRM_Core_Action::VIEW | CRM_Core_Action::PREVIEW;
        } else {
            return CRM_Core_Action::VIEW;
        }
    }

    /**
     * Build the form object.
     */
    public function buildQuickForm()
    {
        parent::buildQuickForm();
        $this->_amount = $this->_params['amount_other'];
        $this->assignToTemplate();
        $productID = $this->get('productID');
        $option = $this->get('option');
        $membershipTypeID = $this->get('membershipTypeID');
        $this->assign('receiptFromEmail', CRM_Utils_Array::value('receipt_from_email', $this->_values));

        if ($productID) {
            CRM_Contribute_BAO_Premium::buildPremiumBlock($this, $this->_id, FALSE, $productID, $option);
        }
        $this->assign('useForMember', $this->get('useForMember'));

        $params = $this->_params;
        $invoiceSettings = CRM_Pendingcontribution_VersionCompatibility::getInvoiceSettings('contribution_invoice_settings');
        $invoicing = CRM_Utils_Array::value('invoicing', $invoiceSettings);
        if ($invoicing) {
            $getTaxDetails = FALSE;
            $taxTerm = CRM_Utils_Array::value('tax_term', $invoiceSettings);
            foreach ($this->_lineItem as $key => $value) {
                foreach ($value as $v) {
                    if (isset($v['tax_rate'])) {
                        if ($v['tax_rate'] != '') {
                            $getTaxDetails = TRUE;
                        }
                    }
                }
            }
            $this->assign('getTaxDetails', $getTaxDetails);
            $this->assign('taxTerm', $taxTerm);
            $this->assign('totalTaxAmount', $params['tax_amount']);
        }
        if (!empty($this->_values['honoree_profile_id']) && !empty($params['soft_credit_type_id'])) {
            $honorName = NULL;
            $softCreditTypes = CRM_Core_OptionGroup::values("soft_credit_type", FALSE);

            $this->assign('soft_credit_type', $softCreditTypes[$params['soft_credit_type_id']]);
            CRM_Contribute_BAO_ContributionSoft::formatHonoreeProfileFields($this, $params['honor']);

            $fieldTypes = array('Contact');
            $fieldTypes[] = CRM_Core_BAO_UFGroup::getContactType($this->_values['honoree_profile_id']);
            $this->buildCustom($this->_values['honoree_profile_id'], 'honoreeProfileFields', TRUE, 'honor', $fieldTypes);
        }

        $qParams = "reset=1&amp;id={$this->_id}";
        $this->assign('qParams', $qParams);

        if ($membershipTypeID) {
            $transactionID = $this->get('membership_trx_id');
            $membershipAmount = $this->get('membership_amount');
            $renewalMode = $this->get('renewal_mode');
            $this->assign('membership_trx_id', $transactionID);
            $this->assign('membership_amount', $membershipAmount);
            $this->assign('renewal_mode', $renewalMode);

            $this->buildMembershipBlock(
                $this->_membershipContactID,
                FALSE,
                $membershipTypeID,
                TRUE,
                NULL
            );

            if (!empty($params['auto_renew'])) {
                $this->assign('auto_renew', TRUE);
            }
        }

        $this->_separateMembershipPayment = $this->get('separateMembershipPayment');
        $this->assign("is_separate_payment", $this->_separateMembershipPayment);

        $this->assign('trxn_id',
            CRM_Utils_Array::value('trxn_id',
                $this->_params
            )
        );
        $this->assign('receive_date',
            CRM_Utils_Date::mysqlToIso(CRM_Utils_Array::value('receive_date', $this->_params))
        );

        $defaults = array();
        $fields = array();
        $fields['state_province'] = $fields['country'] = $fields['email'] = 1;
        $contact = $this->_params = $this->controller->exportValues('Main');

        foreach ($fields as $name => $dontCare) {
            if (isset($contact[$name])) {
                $defaults[$name] = $contact[$name];
                if (substr($name, 0, 7) == 'custom_') {
                    $timeField = "{$name}_time";
                    if (isset($contact[$timeField])) {
                        $defaults[$timeField] = $contact[$timeField];
                    }
                } elseif (in_array($name, array(
                        'addressee',
                        'email_greeting',
                        'postal_greeting',
                    )) && !empty($contact[$name . '_custom'])
                ) {
                    $defaults[$name . '_custom'] = $contact[$name . '_custom'];
                }
            }
        }

        $this->_submitValues = array_merge($this->_submitValues, $defaults);

        $this->setDefaults($defaults);

        $values['entity_id'] = $this->_id;
        $values['entity_table'] = 'civicrm_contribution_page';

        CRM_Friend_BAO_Friend::retrieve($values, $data);
        if (!empty($data['title'])) {
            $friendText = $data['title'];
            $this->assign('friendText', $friendText);
            $subUrl = "eid={$this->_id}&pcomponent=contribute";
            $tellAFriend = TRUE;

            if ($tellAFriend) {
                if ($this->_action & CRM_Core_Action::PREVIEW) {
                    $url = CRM_Utils_System::url("civicrm/friend",
                        "reset=1&action=preview&{$subUrl}"
                    );
                } else {
                    $url = CRM_Utils_System::url("civicrm/friend",
                        "reset=1&{$subUrl}"
                    );
                }
                $this->assign('friendURL', $url);
            }
        }

        $this->freeze();

        // can we blow away the session now to prevent hackery
        // CRM-9491
        $this->controller->reset();
    }

    /**
     * Assign the minimal set of variables to the template.
     */
    public function assignToTemplate()
    {
        $name = CRM_Utils_Array::value('billing_first_name', $this->_params);
        if (!empty($this->_params['billing_middle_name'])) {
            $name .= " {$this->_params['billing_middle_name']}";
        }
        $name .= ' ' . CRM_Utils_Array::value('billing_last_name', $this->_params);
        $name = trim($name);
        $this->assign('billingName', $name);
        $this->set('name', $name);

        $this->assign('paymentProcessor', $this->_paymentProcessor);
        $vars = array(
            'amount',
            'currencyID',
            'credit_card_type',
            'trxn_id',
            'amount_level',
        );

        $config = CRM_Core_Config::singleton();
        if (isset($this->_values['is_recur']) && !empty($this->_paymentProcessor['is_recur'])) {
            $this->assign('is_recur_enabled', 1);
            $vars = array_merge($vars, array(
                'is_recur',
                'frequency_interval',
                'frequency_unit',
                'installments',
            ));
        }

        if (in_array('CiviPledge', $config->enableComponents) &&
            CRM_Utils_Array::value('is_pledge', $this->_params) == 1
        ) {
            $this->assign('pledge_enabled', 1);

            $vars = array_merge($vars, array(
                'is_pledge',
                'pledge_frequency_interval',
                'pledge_frequency_unit',
                'pledge_installments',
            ));
        }

        // @todo - stop setting amount level in this function & call the CRM_Price_BAO_PriceSet::getAmountLevel
        // function to get correct amount level consistently. Remove setting of the amount level in
        // CRM_Price_BAO_PriceSet::processAmount. Extend the unit tests in CRM_Price_BAO_PriceSetTest
        // to cover all variants.
        if (isset($this->_params['amount_other']) || isset($this->_params['selectMembership'])) {
            $this->_params['amount_level'] = '';
        }

        foreach ($vars as $v) {
            if (isset($this->_params[$v])) {
                if ($v == "amount" && $this->_params[$v] === 0) {
                    $this->_params[$v] = CRM_Utils_Money::format($this->_params[$v], NULL, NULL, TRUE);
                }
                $this->assign($v, $this->_params[$v]);
            }
        }

        // assign the address formatted up for display
        $addressParts = array(
            "street_address-{$this->_bltID}",
            "city-{$this->_bltID}",
            "postal_code-{$this->_bltID}",
            "state_province-{$this->_bltID}",
            "country-{$this->_bltID}",
        );

        $addressFields = array();
        foreach ($addressParts as $part) {
            list($n, $id) = explode('-', $part);
            $addressFields[$n] = CRM_Utils_Array::value('billing_' . $part, $this->_params);
        }

        $this->assign('address', CRM_Utils_Address::format($addressFields));

        if (!empty($this->_params['onbehalf_profile_id']) && !empty($this->_params['onbehalf'])) {
            $this->assign('onBehalfName', $this->_params['organization_name']);
            $locTypeId = array_keys($this->_params['onbehalf_location']['email']);
            $this->assign('onBehalfEmail', $this->_params['onbehalf_location']['email'][$locTypeId[0]]['email']);
        }

        //fix for CRM-3767
        $assignCCInfo = FALSE;
        if ($this->_amount > 0.0) {
            $assignCCInfo = TRUE;
        } elseif (!empty($this->_params['selectMembership'])) {
            $memFee = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $this->_params['selectMembership'], 'minimum_fee');
            if ($memFee > 0.0) {
                $assignCCInfo = TRUE;
            }
        }

        // The concept of contributeMode is deprecated.
        // The payment processor object can provide info about the fields it shows.
        if ($this->_contributeMode == 'direct' && $assignCCInfo) {
            if ($this->_paymentProcessor &&
                $this->_paymentProcessor['payment_type'] & CRM_Core_Payment::PAYMENT_TYPE_DIRECT_DEBIT
            ) {
                $this->assign('account_holder', $this->_params['account_holder']);
                $this->assign('bank_identification_number', $this->_params['bank_identification_number']);
                $this->assign('bank_name', $this->_params['bank_name']);
                $this->assign('bank_account_number', $this->_params['bank_account_number']);
            } else {
                $date = CRM_Utils_Date::format(CRM_Utils_Array::value('credit_card_exp_date', $this->_params));
                $date = CRM_Utils_Date::mysqlToIso($date);
                $this->assign('credit_card_exp_date', $date);
                $this->assign('credit_card_number',
                    CRM_Utils_System::mungeCreditCard(CRM_Utils_Array::value('credit_card_number', $this->_params))
                );
            }
        }

        $this->assign('email',
            $this->controller->exportValue('Main', "email-{$this->_bltID}")
        );

        // also assign the receipt_text
        if (isset($this->_values['receipt_text'])) {
            $this->assign('receipt_text', $this->_values['receipt_text']);
        }
    }

    /**
     * Function for unit tests on the postProcess function.
     *
     * @param array $params
     */
    public function testSubmit($params)
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->controller = new CRM_Pendingcontribution_Form_PaymentProcessor_ThankYou();
        $this->submit($params);
    }
}