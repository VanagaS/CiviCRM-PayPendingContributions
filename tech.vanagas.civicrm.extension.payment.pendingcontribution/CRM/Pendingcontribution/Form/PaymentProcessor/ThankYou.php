<?php

require_once 'CRM/Core/Form.php';
require_once 'CRM/Pendingcontribution/Form/PaymentProcessorBase.php';
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
     * CRM_Pendingcontribution_Form_PaymentProcessorLiveForm constructor.
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
        parent::preProcess();
    }

    public function buildQuickForm()
    {
        parent::buildQuickForm();
    }

    public function buildQuickFormExt()
    {
        /**
         * If the selected contribution's Contribution page is inactive, this means that its no longer accepting
         * contributions, lets throw error.
         */
        if (empty($this->_values['is_active'])) {
            throw new CRM_Contribute_Exception_InactiveContributionPageException(ts('The page you requested is currently unavailable.'), $this->_id);
        }
        if (!empty($this->_paymentProcessors)) {
            foreach ($this->_paymentProcessors as $key => $name) {
                if ($name['billing_mode'] == 1) {
                    $onlinePaymentProcessorEnabled = TRUE;
                }
                $this->addElement('hidden', 'payment_processor_id', $name['id']);
            }
        }

        CRM_Utils_System::setTitle(ts($this->_values['title']));
        $submitButton = array(
            'type' => 'upload',
            'name' => ts('Make Payment'),
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
            'isDefault' => TRUE,
        );

        // Add submit-once behavior when confirm page disabled
        //if (empty($this->_values['is_confirm_enabled'])) {
        $submitButton['js'] = array('onclick' => "return submitOnce(this,'" . $this->_name . "','" . ts('Processing') . "');");
        //}
        $this->addButtons(array($submitButton));

        $this->_mode = ($this->_action == 1024) ? 'test' : 'live';
        $this->set('type', $this->_paymentProcessorID);
        $this->set('mode', $this->_mode);
        $this->set('paymentProcessor', $this->_paymentProcessor);

        CRM_Core_Payment_ProcessorForm::preProcess($this);
        CRM_Financial_Form_Payment::addCreditCardJs();

        if (!empty($this->_paymentProcessor)) {
            $this->_defaults['payment_processor_id'] = $this->_paymentProcessor;
        }
        $this->assign('payment_processor_id', $this->_paymentProcessor);
        $this->assign('paymentProcessorID', $this->_paymentProcessorID);
        $this->assign('billing_profile_id', (CRM_Utils_Array::value('is_billing_required', $this->_values) ? 'billing' : ''));
    }

    /**
     * Add local and global form rules.
     */
    public function addRules()
    {
        $this->addFormRule(array('CRM_PendingContribution_Form_PaymentProcessorLiveForm', 'formRule'), $this);
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
        // we first reset the confirm page so it accepts new values
        $this->controller->resetPage('Confirm');

        // get the submitted form values.
        $params = $this->controller->exportValues($this->_name);
        $this->submit($params);
        if (empty($this->_values['is_confirm_enabled'])) {
            $this->skipToThankYouPage();
        }
        
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
       $params['currencyID'] = CRM_Core_Config::singleton()->defaultCurrency;

        // from here on down, $params['amount'] holds a monetary value (or null) rather than an option ID
        $params['amount'] = CRM_Contribute_Form_Contribution_Main::computeAmount($params, $this->_values);

        /* Now that we are charging the customer, lets change the is_pay_later value to '0' */
        $params['is_pay_later'] = 0;

        // Would be nice to someday understand the point of this set.
        $this->set('is_pay_later', $params['is_pay_later']);

        // assign pay later stuff
        $this->_params['is_pay_later'] = CRM_Utils_Array::value('is_pay_later', $params, FALSE);
        $this->assign('is_pay_later', $params['is_pay_later']);

        // generate and set an invoiceID for this transaction
        $invoiceID = md5(uniqid(rand(), TRUE));

        $this->set('invoiceID', $invoiceID);
        $params['invoiceID'] = $invoiceID;
        $params['description'] = ts('Online Contribution') . ': ' . $this->_values['title'];
        $params['button'] = $this->controller->getButtonName();

        // required only if is_monetary and valid positive amount
        if ($this->_values['is_monetary'] &&
            !empty($this->_paymentProcessor) &&
            ((float )$params['amount'] > 0.0 )
        ) {
            // The concept of contributeMode is deprecated - as should be the 'is_monetary' setting.
            $this->setContributeMode();
            // Really this setting of $this->_params & params within it should be done earlier on in the function
            // probably the values determined here should be reused in confirm postProcess as there is no opportunity to alter anything
            // on the confirm page. However as we are dealing with a stable release we go as close to where it is used
            // as possible.
            // In general the form has a lack of clarity of the logic of why things are set on the form in some cases &
            // the logic around when $this->_params is used compared to other params arrays.
            $this->_params = array_merge($params, $this->_params);

            /* Since we are using test processor, this will never be true */
            if ($this->_paymentProcessor &&
                $this->_paymentProcessor['object']->supports('preApproval')
            ) {
                $this->handlePreApproval($this->_params);
            }
        }
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
     * Function for unit tests on the postProcess function.
     *
     * @param array $params
     */
    public function testSubmit($params)
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->controller = new CRM_Pendingcontribution_Form_PaymentProcessorLiveForm();
        $this->submit($params);
    }
}
