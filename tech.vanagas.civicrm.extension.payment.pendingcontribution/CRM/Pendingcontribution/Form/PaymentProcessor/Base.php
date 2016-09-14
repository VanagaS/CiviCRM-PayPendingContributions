<?php

require_once 'CRM/Core/Form.php';
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
     * @var integer
     */
    public $_mode;

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
        xdebug_var_dump("Hello");
        exit;
        parent::preProcess();

        /* retrieve the value of parameter 'contribution' from URL */
        $contribution_id = CRM_Utils_Request::retrieve('contribution', 'Positive');

        if ($contribution_id) {
            $this->set('contribution_id', $contribution_id);
        } else {
            $contribution_id = $this->get('contribution_id');
        }
        if(!$contribution_id) {
            CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/pay-pending-contributions-form', 'reset=1'));
        }

        $this->_bltID = $this->get('bltID');
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
        /* Set the defaluts *FIXME* Hardcoding for now as this is confirmed to be payment mode */
        $this->_contributeMode = 'direct';
        $this->assign('contributeMode', $this->_contributeMode);
        $this->assign('is_monetary', true);
        $this->_mode = ($this->_action == 1024) ? 'test' : 'live';
        $this->set('mode', $this->_mode);
        if (isset($this->_params)) {
            $this->_amount = $this->_params['amount_other'];
        }
        $this->_id = $this->_values['id'];

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

    public function buildQuickFormExt() {
        /* */
    }

    /**
     * Add the custom fields.
     *
     * @param int $id
     * @param string $name
     * @param bool $viewOnly
     * @param null $profileContactType
     * @param array $fieldTypes
     */
    public function buildCustom($id, $name, $viewOnly = FALSE, $profileContactType = NULL, $fieldTypes = NULL) {
        if ($id) {
            $contactID = $this->getContactID();

            // we don't allow conflicting fields to be
            // configured via profile - CRM 2100
            $fieldsToIgnore = array(
                'receive_date' => 1,
                'trxn_id' => 1,
                'invoice_id' => 1,
                'net_amount' => 1,
                'fee_amount' => 1,
                'non_deductible_amount' => 1,
                'total_amount' => 1,
                'amount_level' => 1,
                'contribution_status_id' => 1,
                'payment_instrument' => 1,
                'check_number' => 1,
                'financial_type' => 1,
            );

            $fields = CRM_Core_BAO_UFGroup::getFields($id, FALSE, CRM_Core_Action::ADD, NULL, NULL, FALSE,
                NULL, FALSE, NULL, CRM_Core_Permission::CREATE, NULL
            );

            if ($fields) {
                // unset any email-* fields since we already collect it, CRM-2888
                foreach (array_keys($fields) as $fieldName) {
                    if (substr($fieldName, 0, 6) == 'email-' && !in_array($profileContactType, array('honor', 'onbehalf'))) {
                        unset($fields[$fieldName]);
                    }
                }

                if (array_intersect_key($fields, $fieldsToIgnore)) {
                    $fields = array_diff_key($fields, $fieldsToIgnore);
                    CRM_Core_Session::setStatus(ts('Some of the profile fields cannot be configured for this page.'), ts('Warning'), 'alert');
                }

                $fields = array_diff_assoc($fields, $this->_fields);

                CRM_Core_BAO_Address::checkContactSharedAddressFields($fields, $contactID);
                $addCaptcha = FALSE;
                foreach ($fields as $key => $field) {
                    if ($viewOnly &&
                        isset($field['data_type']) &&
                        $field['data_type'] == 'File' || ($viewOnly && $field['name'] == 'image_URL')
                    ) {
                        // ignore file upload fields
                        continue;
                    }

                    if ($profileContactType) {
                        //Since we are showing honoree name separately so we are removing it from honoree profile just for display
                        if ($profileContactType == 'honor') {
                            $honoreeNamefields = array(
                                'prefix_id',
                                'first_name',
                                'last_name',
                                'suffix_id',
                                'organization_name',
                                'household_name',
                            );
                            if (in_array($field['name'], $honoreeNamefields)) {
                                unset($fields[$field['name']]);
                                continue;
                            }
                        }
                        if (!empty($fieldTypes) && in_array($field['field_type'], $fieldTypes)) {
                            CRM_Core_BAO_UFGroup::buildProfile(
                                $this,
                                $field,
                                CRM_Profile_Form::MODE_CREATE,
                                $contactID,
                                TRUE,
                                $profileContactType
                            );
                            $this->_fields[$profileContactType][$key] = $field;
                        }
                        else {
                            unset($fields[$key]);
                        }
                    }
                    else {
                        CRM_Core_BAO_UFGroup::buildProfile(
                            $this,
                            $field,
                            CRM_Profile_Form::MODE_CREATE,
                            $contactID,
                            TRUE
                        );
                        $this->_fields[$key] = $field;
                    }
                    // CRM-11316 Is ReCAPTCHA enabled for this profile AND is this an anonymous visitor
                    if ($field['add_captcha'] && !$this->_userID) {
                        $addCaptcha = TRUE;
                    }
                }

                $this->assign($name, $fields);

                if ($addCaptcha && !$viewOnly) {
                    $captcha = CRM_Utils_ReCAPTCHA::singleton();
                    $captcha->add($this);
                    $this->assign('isCaptcha', TRUE);
                }
            }
        }
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
