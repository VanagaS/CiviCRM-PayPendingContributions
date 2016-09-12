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

    public function buildQuickForm() {

        $this->_pendingContributions->setupFormVariables();
        //$contributionPage = $this->_pendingContributions->getContributionPage();
        CRM_Utils_System::setTitle(ts($this->_values['title']));
        //$this->assign('paymentFields', $this->_paymentFields);
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

    public function submit($params) {
        //carry campaign from profile.
        if (array_key_exists('contribution_campaign_id', $params)) {
            $params['campaign_id'] = $params['contribution_campaign_id'];
        }

        $params['currencyID'] = CRM_Core_Config::singleton()->defaultCurrency;

        $is_quick_config = 0;
        if (!empty($params['priceSetId'])) {
            $is_quick_config = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_priceSetId, 'is_quick_config');
            if ($is_quick_config) {
                $priceField = new CRM_Price_DAO_PriceField();
                $priceField->price_set_id = $params['priceSetId'];
                $priceField->orderBy('weight');
                $priceField->find();

                $priceOptions = array();
                while ($priceField->fetch()) {
                    CRM_Price_BAO_PriceFieldValue::getValues($priceField->id, $priceOptions);
                    if (($selectedPriceOptionID = CRM_Utils_Array::value("price_{$priceField->id}", $params)) != FALSE && $selectedPriceOptionID > 0) {
                        switch ($priceField->name) {
                            case 'membership_amount':
                                $this->_params['selectMembership'] = $params['selectMembership'] = CRM_Utils_Array::value('membership_type_id', $priceOptions[$selectedPriceOptionID]);
                                $this->set('selectMembership', $params['selectMembership']);

                            case 'contribution_amount':
                                $params['amount'] = $selectedPriceOptionID;
                                if ($priceField->name == 'contribution_amount' ||
                                    ($priceField->name == 'membership_amount' &&
                                        CRM_Utils_Array::value('is_separate_payment', $this->_membershipBlock) == 0)
                                ) {
                                    $this->_values['amount'] = CRM_Utils_Array::value('amount', $priceOptions[$selectedPriceOptionID]);
                                }
                                $this->_values[$selectedPriceOptionID]['value'] = CRM_Utils_Array::value('amount', $priceOptions[$selectedPriceOptionID]);
                                $this->_values[$selectedPriceOptionID]['label'] = CRM_Utils_Array::value('label', $priceOptions[$selectedPriceOptionID]);
                                $this->_values[$selectedPriceOptionID]['amount_id'] = CRM_Utils_Array::value('id', $priceOptions[$selectedPriceOptionID]);
                                $this->_values[$selectedPriceOptionID]['weight'] = CRM_Utils_Array::value('weight', $priceOptions[$selectedPriceOptionID]);
                                break;

                            case 'other_amount':
                                $params['amount_other'] = $selectedPriceOptionID;
                                break;
                        }
                    }
                }
            }
        }
        // from here on down, $params['amount'] holds a monetary value (or null) rather than an option ID
        $params['amount'] = self::computeAmount($params, $this->_values);

        $params['separate_amount'] = $params['amount'];
        $memFee = NULL;
        if (!empty($params['selectMembership'])) {
            if (empty($this->_membershipTypeValues)) {
                $this->_membershipTypeValues = CRM_Member_BAO_Membership::buildMembershipTypeValues($this,
                    (array) $params['selectMembership']
                );
            }
            $membershipTypeValues = $this->_membershipTypeValues[$params['selectMembership']];
            $memFee = $membershipTypeValues['minimum_fee'];
            if (!$params['amount'] && !$this->_separateMembershipPayment) {
                $params['amount'] = $memFee ? $memFee : 0;
            }
        }
        //If the membership & contribution is used in contribution page & not separate payment
        $fieldId = $memPresent = $membershipLabel = $fieldOption = $is_quick_config = NULL;
        $proceFieldAmount = 0;
        if (property_exists($this, '_separateMembershipPayment') && $this->_separateMembershipPayment == 0) {
            $is_quick_config = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_priceSetId, 'is_quick_config');
            if ($is_quick_config) {
                foreach ($this->_priceSet['fields'] as $fieldKey => $fieldVal) {
                    if ($fieldVal['name'] == 'membership_amount' && !empty($params['price_' . $fieldKey])) {
                        $fieldId = $fieldVal['id'];
                        $fieldOption = $params['price_' . $fieldId];
                        $proceFieldAmount += $fieldVal['options'][$this->_submitValues['price_' . $fieldId]]['amount'];
                        $memPresent = TRUE;
                    }
                    else {
                        if (!empty($params['price_' . $fieldKey]) && $memPresent && ($fieldVal['name'] == 'other_amount' || $fieldVal['name'] == 'contribution_amount')) {
                            $fieldId = $fieldVal['id'];
                            if ($fieldVal['name'] == 'other_amount') {
                                $proceFieldAmount += $this->_submitValues['price_' . $fieldId];
                            }
                            elseif ($fieldVal['name'] == 'contribution_amount' && $this->_submitValues['price_' . $fieldId] > 0) {
                                $proceFieldAmount += $fieldVal['options'][$this->_submitValues['price_' . $fieldId]]['amount'];
                            }
                            unset($params['price_' . $fieldId]);
                            break;
                        }
                    }
                }
            }
        }

        if (!isset($params['amount_other'])) {
            $this->set('amount_level', CRM_Utils_Array::value('amount_level', $params));
        }

        if ($priceSetId = CRM_Utils_Array::value('priceSetId', $params)) {
            $lineItem = array();
            $is_quick_config = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $priceSetId, 'is_quick_config');
            if ($is_quick_config) {
                foreach ($this->_values['fee'] as $key => & $val) {
                    if ($val['name'] == 'other_amount' && $val['html_type'] == 'Text' && !empty($params['price_' . $key])) {
                        // Clean out any currency symbols.
                        $params['price_' . $key] = CRM_Utils_Rule::cleanMoney($params['price_' . $key]);
                        if ($params['price_' . $key] != 0) {
                            foreach ($val['options'] as $optionKey => & $options) {
                                $options['amount'] = CRM_Utils_Array::value('price_' . $key, $params);
                                break;
                            }
                        }
                        $params['price_' . $key] = 1;
                        break;
                    }
                }
            }
            $component = '';
            if ($this->_membershipBlock) {
                $component = 'membership';
            }

            CRM_Price_BAO_PriceSet::processAmount($this->_values['fee'], $params, $lineItem[$priceSetId], $component);
            if ($params['tax_amount']) {
                $this->set('tax_amount', $params['tax_amount']);
            }

            if ($proceFieldAmount) {
                $lineItem[$params['priceSetId']][$fieldOption]['unit_price'] = $proceFieldAmount;
                $lineItem[$params['priceSetId']][$fieldOption]['line_total'] = $proceFieldAmount;
                if (isset($lineItem[$params['priceSetId']][$fieldOption]['tax_amount'])) {
                    $proceFieldAmount += $lineItem[$params['priceSetId']][$fieldOption]['tax_amount'];
                }
                if (!$this->_membershipBlock['is_separate_payment']) {
                    //require when separate membership not used
                    $params['amount'] = $proceFieldAmount;
                }
            }
            $this->set('lineItem', $lineItem);
        }

        if ($params['amount'] != 0 && (($this->_values['is_pay_later'] &&
                    empty($this->_paymentProcessor) &&
                    !array_key_exists('hidden_processor', $params)) ||
                (CRM_Utils_Array::value('payment_processor_id', $params) == 0))
        ) {
            $params['is_pay_later'] = 1;
        }
        else {
            $params['is_pay_later'] = 0;
        }

        // Would be nice to someday understand the point of this set.
        $this->set('is_pay_later', $params['is_pay_later']);
        // assign pay later stuff
        $this->_params['is_pay_later'] = CRM_Utils_Array::value('is_pay_later', $params, FALSE);
        $this->assign('is_pay_later', $params['is_pay_later']);
        if ($params['is_pay_later']) {
            $this->assign('pay_later_text', $this->_values['pay_later_text']);
            $this->assign('pay_later_receipt', $this->_values['pay_later_receipt']);
        }

        if ($this->_membershipBlock['is_separate_payment'] && !empty($params['separate_amount'])) {
            $this->set('amount', $params['separate_amount']);
        }
        else {
            $this->set('amount', $params['amount']);
        }

        // generate and set an invoiceID for this transaction
        $invoiceID = md5(uniqid(rand(), TRUE));
        $this->set('invoiceID', $invoiceID);
        $params['invoiceID'] = $invoiceID;
        $params['description'] = ts('Online Contribution') . ': ' . ((!empty($this->_pcpInfo['title']) ? $this->_pcpInfo['title'] : $this->_values['title']));
        $params['button'] = $this->controller->getButtonName();
        // required only if is_monetary and valid positive amount
        // @todo it seems impossible for $memFee to be greater than 0 & $params['amount'] not to
        // be & by requiring $memFee down here we make it harder to do a sensible refactoring of the function
        // above (ie. extract the amount in a small function).
        if ($this->_values['is_monetary'] &&
            !empty($this->_paymentProcessor) &&
            ((float ) $params['amount'] > 0.0 || $memFee > 0.0)
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
            $this->setRecurringMembershipParams();
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
     * Process confirm function and pass browser to the thank you page.
     */
    protected function skipToThankYouPage() {
        // call the post process hook for the main page before we switch to confirm
        $this->postProcessHook();

        // build the confirm page
        $confirmForm = &$this->controller->_pages['Confirm'];
        $confirmForm->preProcess();
        $confirmForm->buildQuickForm();

        // the confirmation page is valid
        $data = &$this->controller->container();
        $data['valid']['Confirm'] = 1;

        // confirm the contribution
        // mainProcess calls the hook also
        $confirmForm->mainProcess();
        $qfKey = $this->controller->_key;

        // redirect to thank you page
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contribute/transact', "_qf_ThankYou_display=1&qfKey=$qfKey", TRUE, NULL, FALSE));
    }



    /**
     * Function for unit tests on the postProcess function.
     *
     * @param array $params
     */
    public function testSubmit($params) {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->controller = new CRM_Contribute_Controller_Contribution();
        $this->submit($params);
    }
}
