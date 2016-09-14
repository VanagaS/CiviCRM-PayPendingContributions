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
class CRM_Pendingcontribution_Form_PaymentProcessor_Confirm extends CRM_Pendingcontribution_Form_PaymentProcessor_Base
{
    /* Set the Contribution Page ID */
    public $_id;

    /* Set the amount */
    public $_amount;

    /* Contribution mode */
    public $_contributeMode;

    /* PriceSetId */
    public $_priceSetId;

    /* Fields */
    public $_fields;

    /**
     * CRM_Pendingcontribution_Form_PaymentProcessor_Confirm constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Set variables up before form is built.
     */
    public function preProcess()
    {
        $config = CRM_Core_Config::singleton();
        parent::preProcess();

        // lineItem isn't set until Register postProcess
        $this->_lineItem = $this->get('lineItem');
        $this->_paymentProcessor = $this->get('paymentProcessor');
        $this->_params = $this->controller->exportValues('Main');
        $this->_params['ip_address'] = CRM_Utils_System::ipAddress();
        $this->_params['amount'] = $this->_amount = $this->_params['amount_other'];
        $this->_params['tax_amount'] = $this->get('tax_amount');
        $this->_useForMember = $this->get('useForMember');

        if (isset($this->_params['credit_card_exp_date'])) {
            $this->_params['year'] = CRM_Core_Payment_Form::getCreditCardExpirationYear($this->_params);
            $this->_params['month'] = CRM_Core_Payment_Form::getCreditCardExpirationMonth($this->_params);
        }

        $this->_params['currencyID'] = $config->defaultCurrency;

        $this->_params['is_pay_later'] = $this->get('is_pay_later');
        $this->assign('is_pay_later', $this->_params['is_pay_later']);

        $this->_params['invoiceID'] = $this->get('invoiceID');

        //carry campaign from profile.
        if (array_key_exists('contribution_campaign_id', $this->_params)) {
            $this->_params['campaign_id'] = $this->_params['contribution_campaign_id'];
        }

        // assign contribution page id to the template so we can add css class for it
        $this->_id = $this->_values['id'];
        $this->assign('contributionPageID', $this->_id);
        $this->assign('is_for_organization', CRM_Utils_Array::value('is_for_organization', $this->_params));
        $this->set('params', $this->_params);

        /* Set the defaluts *FIXME* Hardcoding for now as this is confirmed to be payment mode */
        $this->_contributeMode = 'direct';
        $this->assign('contributeMode', $this->_contributeMode);
        $this->assign('is_monetary', true);
    }

    public function buildQuickForm()
    {
        $this->buildQuickFormExt();
    }

    public function buildQuickFormExt()
    {
        $this->assignToTemplate();

        $params = $this->_params;

        $this->assign('receiptFromEmail', CRM_Utils_Array::value('receipt_from_email', $this->_values));
        $amount_block_is_active = $this->get('amount_block_is_active');
        $this->assign('amount_block_is_active', $amount_block_is_active);

        $invoiceSettings = Civi::settings()->get('contribution_invoice_settings');
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

        $config = CRM_Core_Config::singleton();
        $contribButton = ts('Make Contribution');
        $this->assign('button', ts('Make Contribution'));

        $this->addButtons(array(
                array(
                    'type' => 'next',
                    'name' => $contribButton,
                    'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
                    'isDefault' => TRUE,
                    'js' => array('onclick' => "return submitOnce(this,'" . $this->_name . "','" . ts('Processing') . "');"),
                ),
                array(
                    'type' => 'back',
                    'name' => ts('Go Back'),
                ),
            )
        );

        $defaults = array();
        $fields = array();
        if(!empty($this->_fields)) {
            $fields = array_fill_keys(array_keys($this->_fields), 1);
        }
        $fields["billing_state_province-{$this->_bltID}"] = $fields["billing_country-{$this->_bltID}"] = $fields["email-{$this->_bltID}"] = 1;

        $contact = $this->_params;
        $this->setDefaults($defaults);
        $this->freeze();
    }

    /**
     * Assign the minimal set of variables to the template.
     */
    public function assignToTemplate() {
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

        //fix for CRM-3767
        $assignCCInfo = FALSE;
        if ($this->_amount > 0.0) {
            $assignCCInfo = TRUE;
        }
        elseif (!empty($this->_params['selectMembership'])) {
            $memFee = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $this->_params['selectMembership'], 'minimum_fee');
            if ($memFee > 0.0) {
                $assignCCInfo = TRUE;
            }
        }


                $date = CRM_Utils_Date::format(CRM_Utils_Array::value('credit_card_exp_date', $this->_params));
                $date = CRM_Utils_Date::mysqlToIso($date);
                $this->assign('credit_card_exp_date', $date);
                $this->assign('credit_card_number',
                    CRM_Utils_System::mungeCreditCard(CRM_Utils_Array::value('credit_card_number', $this->_params))
                );


        $this->assign('email',
            $this->controller->exportValue('Main', "email-{$this->_bltID}")
        );

        // also assign the receipt_text
        if (isset($this->_values['receipt_text'])) {
            $this->assign('receipt_text', $this->_values['receipt_text']);
        }
    }

    /**
     * Add local and global form rules.
     */
    public function addRules()
    {
        //$this->addFormRule(array('CRM_PendingContribution_Form_PaymentProcessor_Confirm', 'formRule'), $this);
    }

    /**
     * Global form rule.
     *
     * @param array $fields
     *   The input form values.
     * @param array $files
     *   The uploaded files if any.
     * @param CRM_Core_Form $self
     *
     * @return bool|array
     *   true if no errors, else array of errors
     */
    public static function formRule($fields, $files, $self)
    {
        $errors = array();
        $amount = CRM_Contribute_Form_Contribution_Main::computeAmount($fields, $self->_values);

        if (isset($fields['amount_other'])) {
            if ($fields['amount_other'] < 0) {
                $errors['_qf_default'] = ts('Contribution can not be less than zero. Please select the options accordingly');
            } else {
                $amount = $fields['amount_other'];
            }
        } else {
            $errors["_qf_default"] = ts('Amount is required field.');
        }

        if (CRM_Utils_Array::value('payment_processor_id', $fields) == NULL) {
            $errors['payment_processor_id'] = ts('Payment Method is a required field.');
        } else {
            CRM_Core_Payment_Form::validatePaymentInstrument(
                $fields['payment_processor_id'],
                $fields,
                $errors,
                'billing'
            );
        }

        return empty($errors) ? TRUE : $errors;
    }

    public function postProcess()
    {
        xdebug_var_dump("Post Process");
        exit;
        $contactID = $this->getContactID();
        $result = $this->processFormSubmission($contactID);
        if (is_array($result) && !empty($result['is_payment_failure'])) {
            // We will probably have the function that gets this error throw an exception on the next round of refactoring.
            CRM_Core_Session::singleton()->setStatus(ts("Payment Processor Error message :") .
                $result['error']->getMessage());
            CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contribute/transact',
                "_qf_Main_display=true&qfKey={$this->_params['qfKey']}"
            ));
        }
        // Presumably this is for hooks to access? Not quite clear & perhaps not required.
        $this->set('params', $this->_params);
    }

    /**
     * Submit function.
     *
     * This is the guts of the postProcess made also accessible to the test suite.
     *
     * @param array $params
     *   Submitted values.
     */
    public function submit($params)
    {
        xdebug_var_dump("In Here in Submit");
        exit;
        $form = new CRM_Contribute_Form_Contribution_Confirm();
        $form->_id = $params['id'];

        CRM_Contribute_BAO_ContributionPage::setValues($form->_id, $form->_values);
        $form->_separateMembershipPayment = CRM_Contribute_BAO_ContributionPage::getIsMembershipPayment($form->_id);
        //this way the mocked up controller ignores the session stuff
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $form->controller = new CRM_Contribute_Controller_Contribution();
        $params['invoiceID'] = md5(uniqid(rand(), TRUE));
        $paramsProcessedForForm = $form->_params = self::getFormParams($params['id'], $params);
        $form->_amount = $params['amount'];
        // hack these in for test support.
        $form->_fields['billing_first_name'] = 1;
        $form->_fields['billing_last_name'] = 1;
        // CRM-18854 - Set form values to allow pledge to be created for api test.
        if (CRM_Utils_Array::value('pledge_block_id', $params)) {
            $form->_values['pledge_block_id'] = $params['pledge_block_id'];
            $pledgeBlock = CRM_Pledge_BAO_PledgeBlock::getPledgeBlock($params['id']);
            $form->_values['max_reminders'] = $pledgeBlock['max_reminders'];
            $form->_values['initial_reminder_day'] = $pledgeBlock['initial_reminder_day'];
            $form->_values['additional_reminder_day'] = $pledgeBlock['additional_reminder_day'];
            $form->_values['is_email_receipt'] = FALSE;
        }
        $priceSetID = $form->_params['priceSetId'] = $paramsProcessedForForm['price_set_id'];
        $priceFields = CRM_Price_BAO_PriceSet::getSetDetail($priceSetID);
        $priceSetFields = reset($priceFields);
        $form->_values['fee'] = $priceSetFields['fields'];
        $form->_priceSetId = $priceSetID;
        $form->setFormAmountFields($priceSetID);
        if (!empty($params['payment_processor_id'])) {
            $form->_paymentProcessor = civicrm_api3('payment_processor', 'getsingle', array(
                'id' => $params['payment_processor_id'],
            ));
            // The concept of contributeMode is deprecated as is the billing_mode concept.
            if ($form->_paymentProcessor['billing_mode'] == 1) {
                $form->_contributeMode = 'direct';
            }
            else {
                $form->_contributeMode = 'notify';
            }
        }
        else {
            $form->_params['payment_processor_id'] = 0;
        }
        $priceFields = $priceFields[$priceSetID]['fields'];
        CRM_Price_BAO_PriceSet::processAmount($priceFields, $paramsProcessedForForm, $lineItems, 'civicrm_contribution');
        $form->_lineItem = array($priceSetID => $lineItems);
        $form->processFormSubmission(CRM_Utils_Array::value('contact_id', $params));
    }

    /**
     * Assign the billing mode to the template.
     *
     * This is required for legacy support for contributeMode in templates.
     *
     * The goal is to remove this parameter & use more relevant parameters.
     */
    protected function setContributeMode() {
        switch ($this->_paymentProcessor['billing_mode']) {
            case CRM_Core_Payment::BILLING_MODE_FORM:
                $this->set('contributeMode', 'direct');
                break;

            case CRM_Core_Payment::BILLING_MODE_BUTTON:
                $this->set('contributeMode', 'express');
                break;

            case CRM_Core_Payment::BILLING_MODE_NOTIFY:
                $this->set('contributeMode', 'notify');
                break;
        }
    }

    /**
     * Initializes default form values
     *
     * @param     array    $defaultValues       values used to fill the form
     * @param     mixed    $filter              (optional) filter(s) to apply to all default values
     * @since     1.0
     * @access    public
     * @return    void
     * @throws    HTML_QuickForm_Error
     */
    function setDefaults($defaultValues = null, $filter = null)
    {
        if (is_array($defaultValues)) {
            if (isset($filter)) {
                if (is_array($filter) && (2 != count($filter) || !is_callable($filter))) {
                    foreach ($filter as $val) {
                        if (!is_callable($val)) {
                            return PEAR::raiseError(null, QUICKFORM_INVALID_FILTER, null, E_USER_WARNING, "Callback function does not exist in QuickForm::setDefaults()", 'HTML_QuickForm_Error', true);
                        } else {
                            $defaultValues = $this->_recursiveFilter($val, $defaultValues);
                        }
                    }
                } elseif (!is_callable($filter)) {
                    return PEAR::raiseError(null, QUICKFORM_INVALID_FILTER, null, E_USER_WARNING, "Callback function does not exist in QuickForm::setDefaults()", 'HTML_QuickForm_Error', true);
                } else {
                    $defaultValues = $this->_recursiveFilter($filter, $defaultValues);
                }
            }
            $this->_defaultValues = HTML_QuickForm::arrayMerge($this->_defaultValues, $defaultValues);
            foreach (array_keys($this->_elements) as $key) {
                $this->_elements[$key]->onQuickFormEvent('updateValue', null, $this);
            }
        }
    } // end func setDefaults


    /**
     * Function for unit tests on the postProcess function.
     *
     * @param array $params
     */
    public function testSubmit($params)
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->controller = new CRM_Pendingcontribution_Form_PaymentProcessor_Confirm();
        $this->submit($params);
    }
}
