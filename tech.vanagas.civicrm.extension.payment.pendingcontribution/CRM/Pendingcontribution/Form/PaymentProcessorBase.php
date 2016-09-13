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
class CRM_Pendingcontribution_Form_PaymentProcessor_Base extends CRM_Core_Form
{
    /**
     * Pending contributions
     *
     * @var array (PendingContributions)
     */
    public $_pendingContribution;
    /**
     * Array of payment related fields to potentially display on this form (generally credit card or debit card fields). This is rendered via billingBlock.tpl
     * @var array
     */
    public $_paymentFields = array();

    public $_paymentProcessor;
    public $_paymentProcessorID;
    public $_paymentProcessors;

    public $_values;

    /**
     * CRM_Pendingcontribution_Form_PaymentProcessor_Base constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_action = CRM_Core_Action::UPDATE;
    }

    /**
     * Set variables up before form is built.
     */
    public function preProcess()
    {
        parent::preProcess();

        /* retrieve the value of parameter 'contribution' from URL */
        $contribution_id = CRM_Utils_Request::retrieve('contribution', 'Positive');

        if ($contribution_id) {
            $this->set('contribution_id', $contribution_id);
        } else {
            $contribution_id = $this->get('contribution_id');
        }

        /* get the reference to session object */
        $session = CRM_Core_Session::singleton();
        /* generate default URL for this module and set the context in session */
        $url = CRM_Utils_System::url('civicrm/pay-pending-contributions-form', 'reset=1&contribution=' . $contribution_id . '&smartyDebug=1');
        $session->pushUserContext($url);

        if ($contribution_id) {
            /* instantiate the PendingContributions Object */
            /* Contribution and ContributionPage objects get loaded by the end of this call */
            $this->_pendingContribution = new PendingContributions($contribution_id, $this);
            /* Refresh for latest values */
            /* Let this call be the first on so that we fetch all the required objects */
            $this->_pendingContribution->setupFormVariables();

            /* Setup the payment processor */
            $this->setupPaymentProcess();

        }
    }

    public function setupPaymentProcess()
    {
        /* Setup and initialize payment system */
        $this->_paymentProcessors = $this->get('paymentProcessors');

        /* Get the payment processor id registered with the selected contribution type */
        $this->_paymentProcessorID = $paymentProcessor_id = $this->_values['payment_processor'];
        /* Fetch the payment processor by id */
        $this->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($paymentProcessor_id);
        if (empty($this->_paymentProcessors)) {
            $this->_paymentProcessors[] = $this->_paymentProcessor;
            $this->set('paymentProcessors', $this->_paymentProcessors);
        }
        $this->assignBillingType();
        // $this->preProcessPaymentOptions();

        /* Setup payment fields */
        CRM_Core_Payment_Form::setPaymentFieldsByProcessor($this, $this->_paymentProcessor);
        /* Build Payment form */
        CRM_Core_Payment_Form::buildPaymentForm($this, $this->_paymentProcessor, FALSE, TRUE);

        CRM_Financial_Form_Payment::addCreditCardJs();
        $this->assign('paymentProcessorID', $this->_paymentProcessorID);
        $this->assign('billing_profile_id', (CRM_Utils_Array::value('is_billing_required', $this->_values) ? 'billing' : ''));
    }

    public function buildQuickForm()
    {
        $contactID = $this->getContactID();
        if ($contactID) {
            $this->assign('contact_id', $contactID);
            $this->assign('display_name', CRM_Contact_BAO_Contact::displayName($contactID));
        }

        if (!is_null($this->_pendingContribution)) {
            $this->buildQuickFormExt();
        } else {
            // CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/pay-pending-contributions-form', 'reset=1'));
        }

        $this->assign('elementNames', $this->getRenderableElementNames());
        parent::buildQuickForm();
    }

    public function buildQuickExt() {
        /* */
    }

    /**
     * Get the fields/elements defined in this form.
     *
     * @return array (string)
     */
    public function getRenderableElementNames()
    {
        // The _elements list includes some items which should not be
        // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
        // items don't have labels.  We'll identify renderable by filtering on
        // the 'label'.
        $elementNames = array();
        foreach ($this->_elements as $element) {
            /** @var HTML_QuickForm_Element $element */
            $label = $element->getLabel();
            if (!empty($label)) {
                $elementNames[] = $element->getName();
            }
        }
        return $elementNames;
    }
}
