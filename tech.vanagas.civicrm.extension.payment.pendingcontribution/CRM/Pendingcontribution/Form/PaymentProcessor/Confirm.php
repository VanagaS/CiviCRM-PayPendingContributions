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

        /* Contribution ID, and respective Contribution details get set here */
        parent::preProcess();


        // save contribution id
        $this->_params['id'] = $this->_contributionID;
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
        $this->_params['id'] = $this->_params['contribution_id'] = $this->_params['contributionID'] = $this->_contributionID;

        //carry campaign from profile.
        if (array_key_exists('contribution_campaign_id', $this->_params)) {
            $this->_params['campaign_id'] = $this->_params['contribution_campaign_id'];
        }

        // assign contribution page id to the template so we can add css class for it
        $this->_id = $this->_values['id'];
        $this->assign('contributionPageID', $this->_id);
        $this->assign('is_for_organization', CRM_Utils_Array::value('is_for_organization', $this->_params));
        $this->assign('contribution_id', $this->_contributionID);
        $this->set('params', $this->_params);
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
        if (!empty($this->_fields)) {
            $fields = array_fill_keys(array_keys($this->_fields), 1);
        }
        $fields["billing_state_province-{$this->_bltID}"] = $fields["billing_country-{$this->_bltID}"] = $fields["email-{$this->_bltID}"] = 1;

        $contact = $this->_params;
        $this->setDefaults($defaults);
        $this->freeze();
    }

    /**
     * Overwrite action.
     *
     * Since we are only showing elements in frozen mode no help display needed.
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
        } elseif (!empty($this->_params['selectMembership'])) {
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
        $this->addFormRule(array('CRM_PendingContribution_Form_PaymentProcessor_Confirm', 'formRule'), $this);
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
        // save a reference to _fields
        $self->_fields = $fields;
        return TRUE;
    }

    public function postProcess()
    {
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
            } else {
                $form->_contributeMode = 'notify';
            }
        } else {
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
    protected function setContributeMode()
    {
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
     * @param     array $defaultValues values used to fill the form
     * @param     mixed $filter (optional) filter(s) to apply to all default values
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
     * Post form submission handling.
     *
     * This is also called from the test suite.
     *
     * @param int $contactID
     *
     * @return array
     */
    protected function processFormSubmission($contactID)
    {
        $isPayLater = $this->_params['is_pay_later'];
        if (!isset($this->_params['payment_processor_id'])) {
            // If there is no processor we are using the pay-later manual pseudo-processor.
            // (note it might make sense to make this a row in the processor table in the db).
            $this->_params['payment_processor_id'] = 0;
        }
        if (isset($this->_params['payment_processor_id']) && $this->_params['payment_processor_id'] === 0) {
            $this->_params['is_pay_later'] = $isPayLater = TRUE;
        }
        // add a description field at the very beginning
        $this->_params['description'] = ts('Online Contribution') . ': ' . $this->_values['title'];

        $this->_params['accountingCode'] = CRM_Utils_Array::value('accountingCode', $this->_values);

        // fix currency ID
        $this->_params['currencyID'] = CRM_Core_Config::singleton()->defaultCurrency;

        //carry payment processor id.
        if (CRM_Utils_Array::value('id', $this->_paymentProcessor)) {
            $this->_params['payment_processor_id'] = $this->_paymentProcessor['id'];
        }

        $params = $this->_params;
        if (!empty($params['image_URL'])) {
            CRM_Contact_BAO_Contact::processImageParams($params);
        }

        $fields = array('email-Primary' => 1);

        // get the add to groups
        $addToGroups = array();

        // now set the values for the billing location.
        foreach ($this->_fields as $name => $value) {
            $fields[$name] = 1;

            // get the add to groups for uf fields
            if (!empty($value['add_to_group_id'])) {
                $addToGroups[$value['add_to_group_id']] = $value['add_to_group_id'];
            }
        }

        $fields = $this->formatParamsForPaymentProcessor($fields);

        // billing email address
        $fields["email-{$this->_bltID}"] = 1;

        // if onbehalf-of-organization contribution, take out
        // organization params in a separate variable, to make sure
        // normal behavior is continued. And use that variable to
        // process on-behalf-of functionality.
        if (!empty($this->_values['onbehalf_profile_id'])) {
            $behalfOrganization = array();
            $orgFields = array('organization_name', 'organization_id', 'org_option');
            foreach ($orgFields as $fld) {
                if (array_key_exists($fld, $params)) {
                    $behalfOrganization[$fld] = $params[$fld];
                    unset($params[$fld]);
                }
            }

            if (is_array($params['onbehalf']) && !empty($params['onbehalf'])) {
                foreach ($params['onbehalf'] as $fld => $values) {
                    if (strstr($fld, 'custom_')) {
                        $behalfOrganization[$fld] = $values;
                    } elseif (!(strstr($fld, '-'))) {
                        if (in_array($fld, array(
                            'contribution_campaign_id',
                            'member_campaign_id',
                        ))) {
                            $fld = 'campaign_id';
                        } else {
                            $behalfOrganization[$fld] = $values;
                        }
                        $this->_params[$fld] = $values;
                    }
                }
            }

            if (array_key_exists('onbehalf_location', $params) && is_array($params['onbehalf_location'])) {
                foreach ($params['onbehalf_location'] as $block => $vals) {
                    //fix for custom data (of type checkbox, multi-select)
                    if (substr($block, 0, 7) == 'custom_') {
                        continue;
                    }
                    // fix the index of block elements
                    if (is_array($vals)) {
                        foreach ($vals as $key => $val) {
                            //dont adjust the index of address block as
                            //it's index is WRT to location type
                            $newKey = ($block == 'address') ? $key : ++$key;
                            $behalfOrganization[$block][$newKey] = $val;
                        }
                    }
                }
                unset($params['onbehalf_location']);
            }
            if (!empty($params['onbehalf[image_URL]'])) {
                $behalfOrganization['image_URL'] = $params['onbehalf[image_URL]'];
            }
        }

        // check for profile double opt-in and get groups to be subscribed
        $subscribeGroupIds = CRM_Core_BAO_UFGroup::getDoubleOptInGroupIds($params, $contactID);

        // since we are directly adding contact to group lets unset it from mailing
        if (!empty($addToGroups)) {
            foreach ($addToGroups as $groupId) {
                if (isset($subscribeGroupIds[$groupId])) {
                    unset($subscribeGroupIds[$groupId]);
                }
            }
        }

        foreach ($addToGroups as $k) {
            if (array_key_exists($k, $subscribeGroupIds)) {
                unset($addToGroups[$k]);
            }
        }
        $contactType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactID, 'contact_type');

        $contactID = CRM_Contact_BAO_Contact::createProfileContact(
            $params,
            $fields,
            $contactID,
            $addToGroups,
            NULL,
            $contactType,
            TRUE
        );

        // Make the contact ID associated with the contribution available at the Class level.
        // Also make available to the session.
        //@todo consider handling this in $this->getContactID();
        $this->set('contactID', $contactID);
        $this->_contactID = $contactID;

        //get email primary first if exist
        $subscriptionEmail = array('email' => CRM_Utils_Array::value('email-Primary', $params));
        if (!$subscriptionEmail['email']) {
            $subscriptionEmail['email'] = CRM_Utils_Array::value("email-{$this->_bltID}", $params);
        }
        // subscribing contact to groups
        if (!empty($subscribeGroupIds) && $subscriptionEmail['email']) {
            CRM_Mailing_Event_BAO_Subscribe::commonSubscribe($subscribeGroupIds, $subscriptionEmail, $contactID);
        }

        // lets store the contactID in the session
        // for things like tell a friend
        $session = CRM_Core_Session::singleton();
        if (!$session->get('userID')) {
            $session->set('transaction.userID', $contactID);
        } else {
            $session->set('transaction.userID', NULL);
        }

        // at this point we've created a contact and stored its address etc
        // all the payment processors expect the name and address to be in the
        // so we copy stuff over to first_name etc.
        $paymentParams = $this->_params;

        if (!empty($paymentParams['onbehalf']) &&
            is_array($paymentParams['onbehalf'])
        ) {
            foreach ($paymentParams['onbehalf'] as $key => $value) {
                if (strstr($key, 'custom_')) {
                    $this->_params[$key] = $value;
                }
            }
        }

        $result = CRM_Pendingcontribution_VersionCompatibility::processConfirm($this, $paymentParams,
            $contactID,
            $this->wrangleFinancialTypeID($this->_values['financial_type_id']),
            'contribution',
            ($this->_mode == 'test') ? 1 : 0,
            CRM_Utils_Array::value('is_recur', $paymentParams)
        );

        if (!empty($result['contribution'])) {
            // Not quite sure why it would be empty at this stage but tests show it can be ... at least in tests.
            $this->completeTransaction($result, $result['contribution']->id);
        }
        return $result;

    }

    /**
     * Wrangle financial type ID.
     *
     * This wrangling of the financialType ID was happening in a shared function rather than in the form it relates to & hence has been moved to that form
     * Pledges are not relevant to the membership code so that portion will not go onto the membership form.
     *
     * Comments from previous refactor indicate doubt as to what was going on.
     *
     * @param int $contributionTypeId
     *
     * @return null|string
     */
    public function wrangleFinancialTypeID($contributionTypeId)
    {
        if (isset($paymentParams['financial_type'])) {
            $contributionTypeId = $paymentParams['financial_type'];
        }
        return $contributionTypeId;
    }

    /**
     * Process the form.
     *
     * @param array $premiumParams
     * @param CRM_Contribute_BAO_Contribution $contribution
     */
    protected function postProcessPremium($premiumParams, $contribution)
    {
        $hour = $minute = $second = 0;
        // assigning Premium information to receipt tpl
        $selectProduct = CRM_Utils_Array::value('selectProduct', $premiumParams);
        if ($selectProduct &&
            $selectProduct != 'no_thanks'
        ) {
            $startDate = $endDate = "";
            $this->assign('selectPremium', TRUE);
            $productDAO = new CRM_Contribute_DAO_Product();
            $productDAO->id = $selectProduct;
            $productDAO->find(TRUE);
            $this->assign('product_name', $productDAO->name);
            $this->assign('price', $productDAO->price);
            $this->assign('sku', $productDAO->sku);
            $this->assign('option', CRM_Utils_Array::value('options_' . $premiumParams['selectProduct'], $premiumParams));

            $periodType = $productDAO->period_type;

            if ($periodType) {
                $fixed_period_start_day = $productDAO->fixed_period_start_day;
                $duration_unit = $productDAO->duration_unit;
                $duration_interval = $productDAO->duration_interval;
                if ($periodType == 'rolling') {
                    $startDate = date('Y-m-d');
                } elseif ($periodType == 'fixed') {
                    if ($fixed_period_start_day) {
                        $date = explode('-', date('Y-m-d'));
                        $month = substr($fixed_period_start_day, 0, strlen($fixed_period_start_day) - 2);
                        $day = substr($fixed_period_start_day, -2) . "<br/>";
                        $year = $date[0];
                        $startDate = $year . '-' . $month . '-' . $day;
                    } else {
                        $startDate = date('Y-m-d');
                    }
                }

                $date = explode('-', $startDate);
                $year = $date[0];
                $month = $date[1];
                $day = $date[2];

                switch ($duration_unit) {
                    case 'year':
                        $year = $year + $duration_interval;
                        break;

                    case 'month':
                        $month = $month + $duration_interval;
                        break;

                    case 'day':
                        $day = $day + $duration_interval;
                        break;

                    case 'week':
                        $day = $day + ($duration_interval * 7);
                }
                $endDate = date('Y-m-d H:i:s', mktime($hour, $minute, $second, $month, $day, $year));
                $this->assign('start_date', $startDate);
                $this->assign('end_date', $endDate);
            }

            $dao = new CRM_Contribute_DAO_Premium();
            $dao->entity_table = 'civicrm_contribution_page';
            $dao->entity_id = $this->_id;
            $dao->find(TRUE);
            $this->assign('contact_phone', $dao->premiums_contact_phone);
            $this->assign('contact_email', $dao->premiums_contact_email);

            //create Premium record
            $params = array(
                'product_id' => $premiumParams['selectProduct'],
                'contribution_id' => $contribution->id,
                'product_option' => CRM_Utils_Array::value('options_' . $premiumParams['selectProduct'], $premiumParams),
                'quantity' => 1,
                'start_date' => CRM_Utils_Date::customFormat($startDate, '%Y%m%d'),
                'end_date' => CRM_Utils_Date::customFormat($endDate, '%Y%m%d'),
            );
            if (!empty($premiumParams['selectProduct'])) {
                $daoPremiumsProduct = new CRM_Contribute_DAO_PremiumsProduct();
                $daoPremiumsProduct->product_id = $premiumParams['selectProduct'];
                $daoPremiumsProduct->premiums_id = $dao->id;
                $daoPremiumsProduct->find(TRUE);
                $params['financial_type_id'] = $daoPremiumsProduct->financial_type_id;
            }
            //Fixed For CRM-3901
            $daoContrProd = new CRM_Contribute_DAO_ContributionProduct();
            $daoContrProd->contribution_id = $contribution->id;
            if ($daoContrProd->find(TRUE)) {
                $params['id'] = $daoContrProd->id;
            }

            CRM_Contribute_BAO_Contribution::addPremium($params);
            if ($productDAO->cost && !empty($params['financial_type_id'])) {
                $trxnParams = array(
                    'cost' => $productDAO->cost,
                    'currency' => $productDAO->currency,
                    'financial_type_id' => $params['financial_type_id'],
                    'contributionId' => $contribution->id,
                );
                CRM_Core_BAO_FinancialTrxn::createPremiumTrxn($trxnParams);
            }
        } elseif ($selectProduct == 'no_thanks') {
            //Fixed For CRM-3901
            $daoContrProd = new CRM_Contribute_DAO_ContributionProduct();
            $daoContrProd->contribution_id = $contribution->id;
            if ($daoContrProd->find(TRUE)) {
                $daoContrProd->delete();
            }
        }
    }

    /**
     * Complete transaction if payment has been processed.
     *
     * Check the result for a success outcome & if paid then complete the transaction.
     *
     * Completing will trigger update of related entities and emails.
     *
     * @param array $result
     * @param int $contributionID
     *
     * @throws \CiviCRM_API3_Exception
     * @throws \Exception
     */
    protected function completeTransaction($result, $contributionID)
    {
        if (CRM_Utils_Array::value('payment_status_id', $result) == 1) {
            try {
                civicrm_api3('contribution', 'completetransaction', array(
                        'id' => $contributionID,
                        'trxn_id' => CRM_Utils_Array::value('trxn_id', $result),
                        'payment_processor_id' => $this->_paymentProcessor['id'],
                        'is_transactional' => FALSE,
                        'fee_amount' => CRM_Utils_Array::value('fee_amount', $result),
                        'receive_date' => CRM_Utils_Array::value('receive_date', $result),
                    )
                );
            } catch (CiviCRM_API3_Exception $e) {
                if ($e->getErrorCode() != 'contribution_completed') {
                    throw new CRM_Core_Exception('Failed to update contribution in database');
                }
            }
        }
    }

    /**
     * Format the fields for the payment processor.
     *
     * In order to pass fields to the payment processor in a consistent way we add some renamed
     * parameters.
     *
     * @param array $fields
     *
     * @return array
     */
    protected function formatParamsForPaymentProcessor($fields)
    {
        // also add location name to the array
        $this->_params["address_name-{$this->_bltID}"] = CRM_Utils_Array::value('billing_first_name', $this->_params) . ' ' . CRM_Utils_Array::value('billing_middle_name', $this->_params) . ' ' . CRM_Utils_Array::value('billing_last_name', $this->_params);
        $this->_params["address_name-{$this->_bltID}"] = trim($this->_params["address_name-{$this->_bltID}"]);
        // Add additional parameters that the payment processors are used to receiving.
        if (!empty($this->_params["billing_state_province_id-{$this->_bltID}"])) {
            $this->_params['state_province'] = $this->_params["state_province-{$this->_bltID}"] = $this->_params["billing_state_province-{$this->_bltID}"] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($this->_params["billing_state_province_id-{$this->_bltID}"]);
        }
        if (!empty($this->_params["billing_country_id-{$this->_bltID}"])) {
            $this->_params['country'] = $this->_params["country-{$this->_bltID}"] = $this->_params["billing_country-{$this->_bltID}"] = CRM_Core_PseudoConstant::countryIsoCode($this->_params["billing_country_id-{$this->_bltID}"]);
        }

        list($hasAddressField, $addressParams) =
            CRM_Pendingcontribution_VersionCompatibility::getPaymentProcessorReadyAddressParams($this->_params, $this->_bltID);
        if ($hasAddressField) {
            $this->_params = array_merge($this->_params, $addressParams);
        }

        $nameFields = array('first_name', 'middle_name', 'last_name');
        foreach ($nameFields as $name) {
            $fields[$name] = 1;
            if (array_key_exists("billing_$name", $this->_params)) {
                $this->_params[$name] = $this->_params["billing_{$name}"];
                $this->_params['preserveDBName'] = TRUE;
            }
        }
        return $fields;
    }

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