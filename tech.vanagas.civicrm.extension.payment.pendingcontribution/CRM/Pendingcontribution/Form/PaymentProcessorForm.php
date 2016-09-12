<?php

require_once 'CRM/Core/Form.php';
require_once 'CRM/Core/Payment/Form.php';
require_once 'CRM/Pendingcontribution/PendingContributions.php';
require_once 'CRM/Pendingcontribution/ContributionPage.php';

use \tech\vanagas\civicrm\extension\payment\pendingcontribution\PendingContributions as PendingContributions;
use \tech\vanagas\civicrm\extension\payment\pendingcontribution\ContributionPage as ContributionPage;

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
//class CRM_Pendingcontribution_Form_PaymentProcessorForm extends CRM_Contribute_Form_Contribution_Main
class CRM_Pendingcontribution_Form_PaymentProcessorForm extends CRM_Core_Form
{
    /**
     * Pending contributions
     *
     * @var array (PendingContributions)
     */
    protected $_pendingContributions;
    /**
     * Array of payment related fields to potentially display on this form (generally credit card or debit card fields). This is rendered via billingBlock.tpl
     * @var array
     */
    public $_paymentFields = array();

    protected $_paymentProcessor;
    protected $_paymentProcessorID;
    protected $_paymentProcessors;

    public $_values;

    /**
     * Set variables up before form is built.
     */
    public function preProcess()
    {
        /* retrieve the value of parameter 'contribution' from URL */
        $contribution_id = CRM_Utils_Request::retrieve('contribution', 'Positive');

        /* get the reference to session object */
        $session = CRM_Core_Session::singleton();
        /* generate default URL for this module and set the context in session */
        $url = CRM_Utils_System::url('civicrm/pay-pending-contributions-form', 'reset=1');
        $session->pushUserContext($url);

        if ($contribution_id) {
            /* instantiate the PendingContributions Object */
            /* Contribution and ContributionPage objects get loaded by the end of this call */
            $this->_pendingContributions = new PendingContributions($contribution_id, $this);
            /* Setup and initialize payment system) */
            $this->_paymentProcessors = $this->get('paymentProcessors');
            $paymentProcessor = $this->_values['payment_processor'];
            $this->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($paymentProcessor);
            $this->assignBillingType();
            //$this->_paymentFields = CRM_Core_Payment_Form::getPaymentFieldMetadata($this->_paymentProcessor);
            $this->preProcessPaymentOptions();
            CRM_Core_Payment_Form::setPaymentFieldsByProcessor($this, $this->_paymentProcessor);
            CRM_Core_Payment_Form::buildPaymentForm($this, $this->_paymentProcessor, FALSE, TRUE);
            //xdebug_var_dump($this);
            //exit;
        }

        // xdebug_var_dump($this->_pendingContributions);
        parent::preProcess();
    }

    public function buildQuickForm()
    {
        if(!is_null($this->_pendingContributions)) {
            /* Refresh for latest values */
            $this->_pendingContributions->setupFormVariables();

            //$contributionPage = $this->_pendingContributions->getContributionPage();
            CRM_Utils_System::setTitle(ts($this->_values['title']));
            //$this->assign('paymentFields', $this->_paymentFields);
            $submitButton = array(
                'type' => 'upload',
                'name' => ts('Make Payment'),
                'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
                'isDefault' => TRUE,
            );

            // Add submit-once behavior when confirm page disabled
            if (empty($this->_values['is_confirm_enabled'])) {
                $submitButton['js'] = array('onclick' => "return submitOnce(this,'" . $this->_name . "','" . ts('Processing') . "');");
            }
            $this->addButtons(array($submitButton));
        } else {
            CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/pay-pending-contributions-form', 'reset=1'));
        }
        parent::buildQuickForm();
        //xdebug_var_dump($this->_paymentFields);
        //exit;

    }

    public function postProcess()
    {
        /*// we first reset the confirm page so it accepts new values
        $this->controller->resetPage('Confirm');

        // get the submitted form values.
        $params = $this->controller->exportValues($this->_name);
        $this->submit($params);

        if (empty($this->_values['is_confirm_enabled'])) {
            $this->skipToThankYouPage();
        }*/
        parent::postProcess();
    }

    /**
     * Function for unit tests on the postProcess function.
     *
     * @param array $params
     */
    public function testSubmit($params)
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->controller = new CRM_Contribute_Controller_Contribution();
        $this->submit($params);
    }
}
