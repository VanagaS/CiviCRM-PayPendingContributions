<?php

/**
 * Created by PhpStorm.
 * User: sanje
 * Date: 15-09-2016
 * Time: 19:33
 */
class CRM_Pendingcontribution_VersionCompatibility
{
    public static function getInvoiceSettings($name) {
        //$invoiceSettings = Civi::settings()->get($name);
        $invoiceSettings = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME, $name);

    }

    /**
     * Add JS to show icons for the accepted credit cards.
     * Taken from 4.7.10 version of CiviCRM -- /CRM/Financial/Form/Payment
     */
    public static function addCreditCardJs() {
        $creditCardTypes = CRM_Core_Payment_Form::getCreditCardCSSNames();
        CRM_Core_Resources::singleton()
            ->addScriptFile('civicrm', 'templates/CRM/Core/BillingBlock.js', 10)
            // workaround for CRM-13634
            // ->addSetting(array('config' => array('creditCardTypes' => $creditCardTypes)));
            ->addScript('CRM.config.creditCardTypes = ' . json_encode($creditCardTypes) . ';');
    }

    /**
     * Get address params ready to be passed to the payment processor.
     * Taken from 4.7.10 version of CiviCRM -- /CRM/Contribute/BAO/Contribution.php
     *
     * We need address params in a couple of formats. For the payment processor we wan state_province_id-5.
     * To create an address we need state_province_id.
     *
     * @param array $params
     * @param int $billingLocationTypeID
     *
     * @return array
     */
    public static function getPaymentProcessorReadyAddressParams($params, $billingLocationTypeID) {
        list($hasBillingField, $addressParams) = self::getBillingAddressParams($params, $billingLocationTypeID);
        foreach ($addressParams as $name => $field) {
            if (substr($name, 0, 8) == 'billing_') {
                $addressParams[substr($name, 9)] = $addressParams[$field];
            }
        }
        return array($hasBillingField, $addressParams);
    }

    /**
     * Taken from 4.7.10 version of CiviCRM -- /CRM/Contribute/BAO/Contribution.php
     *
     * @param $params
     * @param $billingLocationTypeID
     *
     * @return array
     */
    protected static function getBillingAddressParams($params, $billingLocationTypeID) {
        $hasBillingField = FALSE;
        $billingFields = array(
            'street_address',
            'city',
            'state_province_id',
            'postal_code',
            'country_id',
        );

        //build address array
        $addressParams = array();
        $addressParams['location_type_id'] = $billingLocationTypeID;
        $addressParams['is_billing'] = 1;

        $billingFirstName = CRM_Utils_Array::value('billing_first_name', $params);
        $billingMiddleName = CRM_Utils_Array::value('billing_middle_name', $params);
        $billingLastName = CRM_Utils_Array::value('billing_last_name', $params);
        $addressParams['address_name'] = "{$billingFirstName}" . CRM_Core_DAO::VALUE_SEPARATOR . "{$billingMiddleName}" . CRM_Core_DAO::VALUE_SEPARATOR . "{$billingLastName}";

        foreach ($billingFields as $value) {
            $addressParams[$value] = CRM_Utils_Array::value("billing_{$value}-{$billingLocationTypeID}", $params);
            if (!empty($addressParams[$value])) {
                $hasBillingField = TRUE;
            }
        }
        return array($hasBillingField, $addressParams);
    }

    /**
     * Process payment after confirmation.
     * Taken from 4.7.10 version of CiviCRM -- /CRM/Contribute/BAO/Contribution/Utils.php and modified
     *
     * @param CRM_Core_Form $form
     *   Form object.
     * @param array $paymentParams
     *   Array with payment related key.
     *   value pairs
     * @param int $contactID
     *   Contact id.
     * @param int $contributionTypeId
     *   Financial type id.
     * @param int|string $component component id
     * @param bool $isTest
     * @param bool $isRecur
     *
     * @throws CRM_Core_Exception
     * @throws Exception
     * @return array
     *   associated array
     *
     */
    public static function processConfirm(
        &$form,
        &$paymentParams,
        $contactID,
        $contributionTypeId,
        $component = 'contribution',
        $isTest,
        $isRecur
    ) {
        CRM_Core_Payment_Form::mapParams($form->_bltID, $form->_params, $paymentParams, TRUE);
        $lineItems = $form->_lineItem;
        $isPaymentTransaction = self::isPaymentTransaction($form);

        $financialType = new CRM_Financial_DAO_FinancialType();
        $financialType->id = $contributionTypeId;
        $financialType->find(TRUE);
        if ($financialType->is_deductible) {
            $form->assign('is_deductible', TRUE);
            $form->set('is_deductible', TRUE);
        }

        // add some financial type details to the params list
        // if folks need to use it
        //CRM-15297 - contributionType is obsolete - pass financial type as well so people can deprecate it
        $paymentParams['financialType_name'] = $paymentParams['contributionType_name'] = $form->_params['contributionType_name'] = $financialType->name;
        //CRM-11456
        $paymentParams['financialType_accounting_code'] = $paymentParams['contributionType_accounting_code'] = $form->_params['contributionType_accounting_code'] = CRM_Financial_BAO_FinancialAccount::getAccountingCode($contributionTypeId);
        $paymentParams['contributionPageID'] = $form->_params['contributionPageID'] = $form->_values['id'];
        $paymentParams['contactID'] = $form->_params['contactID'] = $contactID;

        //fix for CRM-16317
        $form->_params['receive_date'] = date('YmdHis');
        $form->assign('receive_date',
            CRM_Utils_Date::mysqlToIso($form->_params['receive_date'])
        );

        if ($isPaymentTransaction) {
            $contributionParams = array(
                'contact_id' => $contactID,
                'line_item' => $lineItems,
                'is_test' => $isTest,
                'campaign_id' => CRM_Utils_Array::value('campaign_id', $paymentParams, CRM_Utils_Array::value('campaign_id', $form->_values)),
                'contribution_page_id' => $form->_id,
                'source' => CRM_Utils_Array::value('source', $paymentParams, CRM_Utils_Array::value('description', $paymentParams)),
            );
            $isMonetary = !empty($form->_values['is_monetary']);
            if ($isMonetary) {
                if (empty($paymentParams['is_pay_later'])) {
                    // @todo look up payment_instrument_id on payment processor table.
                    $contributionParams['payment_instrument_id'] = 1;
                }
            }
            $contribution = self::processFormContribution(
                $form,
                $paymentParams,
                NULL,
                $contributionParams,
                $financialType,
                TRUE,
                $form->_bltID,
                $isRecur
            );

            $paymentParams['contributionTypeID'] = $contributionTypeId;
            $paymentParams['item_name'] = $form->_params['description'];

            $paymentParams['qfKey'] = $form->controller->_key;
            if ($component == 'membership') {
                return array('contribution' => $contribution);
            }

            $paymentParams['contributionID'] = $contribution->id;
            //CRM-15297 deprecate contributionTypeID
            $paymentParams['financialTypeID'] = $paymentParams['contributionTypeID'] = $contribution->financial_type_id;
            $paymentParams['contributionPageID'] = $contribution->contribution_page_id;
            if (isset($paymentParams['contribution_source'])) {
                $paymentParams['source'] = $paymentParams['contribution_source'];
            }

            if ($form->_values['is_recur'] && $contribution->contribution_recur_id) {
                $paymentParams['contributionRecurID'] = $contribution->contribution_recur_id;
            }
            if (isset($paymentParams['contribution_source'])) {
                $form->_params['source'] = $paymentParams['contribution_source'];
            }

            // get the price set values for receipt.
            if ($form->_priceSetId && $form->_lineItem) {
                $form->_values['lineItem'] = $form->_lineItem;
                $form->_values['priceSetID'] = $form->_priceSetId;
            }

            $form->_values['contribution_id'] = $contribution->id;
            $form->_values['contribution_page_id'] = $contribution->contribution_page_id;


            if (!empty($form->_paymentProcessor)) {
                try {
                    $payment = Civi\Payment\System::singleton()->getByProcessor($form->_paymentProcessor);
                    if ($form->_contributeMode == 'notify') {
                        // We want to get rid of this & make it generic - eg. by making payment processing the last thing
                        // and always calling it first.
                        $form->postProcessHook();
                    }
                    $result = $payment->doPayment($paymentParams);
                    $form->_params = array_merge($form->_params, $result);
                    $form->assign('trxn_id', CRM_Utils_Array::value('trxn_id', $result));
                    if (!empty($result['trxn_id'])) {
                        $contribution->trxn_id = $result['trxn_id'];
                    }
                    if (!empty($result['payment_status_id'])) {
                        $contribution->payment_status_id = $result['payment_status_id'];
                    }
                    $result['contribution'] = $contribution;
                    /* Irrespective of whether payment processor has option of sending receipt, we just send an e-mail */
                    if (!empty($result['payment_status_id']) && $result['payment_status_id'] == CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution',
                            'status_id', 'Pending')) {
                        CRM_Contribute_BAO_ContributionPage::sendMail($contactID,
                            $form->_values,
                            $contribution->is_test
                        );
                    }
                    return $result;
                }
                catch (\Civi\Payment\Exception\PaymentProcessorException $e) {
                    // Clean up DB as appropriate.
                    if (!empty($paymentParams['contributionID'])) {
                        CRM_Contribute_BAO_Contribution::failPayment($paymentParams['contributionID'],
                            $paymentParams['contactID'], $e->getMessage());
                    }
                    if (!empty($paymentParams['contributionRecurID'])) {
                        CRM_Contribute_BAO_ContributionRecur::deleteRecurContribution($paymentParams['contributionRecurID']);
                    }

                    $result['is_payment_failure'] = TRUE;
                    $result['error'] = $e;
                    return $result;
                }
            }
        }

        // Only pay later or unpaid should reach this point, although pay later likely does not & is handled via the
        // manual processor, so it's unclear what this set is for and whether the following send ever fires.
        $form->set('params', $form->_params);

        if ($form->_params['amount'] == 0) {
            // This is kind of a back-up for pay-later $0 transactions.
            // In other flows they pick up the manual processor & get dealt with above (I
            // think that might be better...).
            return array(
                'payment_status_id' => 1,
                'contribution' => $contribution,
                'payment_processor_id' => 0,
            );
        }
        elseif (empty($form->_values['amount'])) {
            // If the amount is not in _values[], set it
            $form->_values['amount'] = $form->_params['amount'];
        }
        CRM_Contribute_BAO_ContributionPage::sendMail($contactID,
            $form->_values,
            $contribution->is_test
        );
    }

    /**
     * Process the contribution.
     * Taken from 4.7.10 version of CiviCRM -- /CRM/Contribute/Form/Contribution/Confirm.php and modified
     *
     * @param CRM_Core_Form $form
     * @param array $params
     * @param array $result
     * @param array $contributionParams
     *   Parameters to be passed to contribution create action.
     *   This differs from params in that we are currently adding params to it and 1) ensuring they are being
     *   passed consistently & 2) documenting them here.
     *   - contact_id
     *   - line_item
     *   - is_test
     *   - campaign_id
     *   - contribution_page_id
     *   - source
     *   - payment_type_id
     *   - thankyou_date (not all forms will set this)
     *
     * @param CRM_Financial_DAO_FinancialType $financialType
     * @param bool $online
     *   Is the form a front end form? If so set a bunch of unpredictable things that should be passed in from the form.
     *
     * @param int $billingLocationID
     *   ID of billing location type.
     * @param bool $isRecur
     *   Is this recurring?
     *
     * @return \CRM_Contribute_DAO_Contribution
     * @throws \Exception
     */
    public static function processFormContribution(
        &$form,
        $params,
        $result,
        $contributionParams,
        $financialType,
        $online,
        $billingLocationID,
        $isRecur
    ) {
        $transaction = new CRM_Core_Transaction();
        $contactID = $contributionParams['contact_id'];

        $isEmailReceipt = !empty($form->_values['is_email_receipt']);
        $isSeparateMembershipPayment = empty($params['separate_membership_payment']) ? FALSE : TRUE;
        $pledgeID = !empty($params['pledge_id']) ? $params['pledge_id'] : CRM_Utils_Array::value('pledge_id', $form->_values);
        if (!$isSeparateMembershipPayment && !empty($form->_values['pledge_block_id']) &&
            (!empty($params['is_pledge']) || $pledgeID)) {
            $isPledge = TRUE;
        }
        else {
            $isPledge = FALSE;
        }

        // add these values for the recurringContrib function ,CRM-10188
        $params['financial_type_id'] = $financialType->id;

        $contributionParams['address_id'] = CRM_Contribute_BAO_Contribution::createAddress($params, $billingLocationID);

        //@todo - this is being set from the form to resolve CRM-10188 - an
        // eNotice caused by it not being set @ the front end
        // however, we then get it being over-written with null for backend contributions
        // a better fix would be to set the values in the respective forms rather than require
        // a function being shared by two forms to deal with their respective values
        // moving it to the BAO & not taking the $form as a param would make sense here.
        if (!isset($params['is_email_receipt']) && $isEmailReceipt) {
            $params['is_email_receipt'] = $isEmailReceipt;
        }
        $params['is_recur'] = $isRecur;
        $recurringContributionID = self::processRecurringContribution($form, $params, $contactID, $financialType);
        $nonDeductibleAmount = self::getNonDeductibleAmount($params, $financialType, $online);

        $now = date('YmdHis');
        $receiptDate = CRM_Utils_Array::value('receipt_date', $params);
        if ($isEmailReceipt) {
            $receiptDate = $now;
        }

        if (isset($params['amount'])) {
            $contributionParams = array_merge(self::getContributionParams(
                $params, $financialType->id, $nonDeductibleAmount, TRUE,
                $result, $receiptDate,
                $recurringContributionID), $contributionParams
            );
            $contribution = CRM_Contribute_BAO_Contribution::add($contributionParams);

            $invoiceSettings = self::getInvoiceSettings('contribution_invoice_settings');
            $invoicing = CRM_Utils_Array::value('invoicing', $invoiceSettings);
            if ($invoicing) {
                $dataArray = array();
                // @todo - interrogate the line items passed in on the params array.
                // No reason to assume line items will be set on the form.
                foreach ($form->_lineItem as $lineItemKey => $lineItemValue) {
                    foreach ($lineItemValue as $key => $value) {
                        if (isset($value['tax_amount']) && isset($value['tax_rate'])) {
                            if (isset($dataArray[$value['tax_rate']])) {
                                $dataArray[$value['tax_rate']] = $dataArray[$value['tax_rate']] + CRM_Utils_Array::value('tax_amount', $value);
                            }
                            else {
                                $dataArray[$value['tax_rate']] = CRM_Utils_Array::value('tax_amount', $value);
                            }
                        }
                    }
                }
                $smarty = CRM_Core_Smarty::singleton();
                $smarty->assign('dataArray', $dataArray);
                $smarty->assign('totalTaxAmount', $params['tax_amount']);
            }
            if (is_a($contribution, 'CRM_Core_Error')) {
                $message = CRM_Core_Error::getMessages($contribution);
                CRM_Core_Error::fatal($message);
            }

            // lets store it in the form variable so postProcess hook can get to this and use it
            $form->_contributionID = $contribution->id;
        }

        //handle pledge stuff.
        if ($isPledge) {
            if ($pledgeID) {
                //when user doing pledge payments.
                //update the schedule when payment(s) are made
                $amount = $params['amount'];
                $pledgePaymentParams = array();
                foreach ($params['pledge_amount'] as $paymentId => $dontCare) {
                    $scheduledAmount = CRM_Core_DAO::getFieldValue(
                        'CRM_Pledge_DAO_PledgePayment',
                        $paymentId,
                        'scheduled_amount',
                        'id'
                    );

                    $pledgePayment = ($amount >= $scheduledAmount) ? $scheduledAmount : $amount;
                    if ($pledgePayment > 0) {
                        $pledgePaymentParams[] = array(
                            'id' => $paymentId,
                            'contribution_id' => $contribution->id,
                            'status_id' => $contribution->contribution_status_id,
                            'actual_amount' => $pledgePayment,
                        );
                        $amount -= $pledgePayment;
                    }
                }
                if ($amount > 0 && count($pledgePaymentParams)) {
                    $pledgePaymentParams[count($pledgePaymentParams) - 1]['actual_amount'] += $amount;
                }
                foreach ($pledgePaymentParams as $p) {
                    CRM_Pledge_BAO_PledgePayment::add($p);
                }

                //update pledge status according to the new payment statuses
                CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($pledgeID);
            }
            else {
                //when user creating pledge record.
                $pledgeParams = array();
                $pledgeParams['contact_id'] = $contribution->contact_id;
                $pledgeParams['installment_amount'] = $pledgeParams['actual_amount'] = $contribution->total_amount;
                $pledgeParams['contribution_id'] = $contribution->id;
                $pledgeParams['contribution_page_id'] = $contribution->contribution_page_id;
                $pledgeParams['financial_type_id'] = $contribution->financial_type_id;
                $pledgeParams['frequency_interval'] = $params['pledge_frequency_interval'];
                $pledgeParams['installments'] = $params['pledge_installments'];
                $pledgeParams['frequency_unit'] = $params['pledge_frequency_unit'];
                if ($pledgeParams['frequency_unit'] == 'month') {
                    $pledgeParams['frequency_day'] = intval(date("d"));
                }
                else {
                    $pledgeParams['frequency_day'] = 1;
                }
                $pledgeParams['create_date'] = $pledgeParams['start_date'] = $pledgeParams['scheduled_date'] = date("Ymd");
                $pledgeBlock = CRM_Pledge_BAO_PledgeBlock::getPledgeBlock($contribution->contribution_page_id);
                if (CRM_Utils_Array::value('start_date', $params) || !CRM_Utils_Array::value('is_pledge_start_date_visible', $pledgeBlock)) {
                    $pledgeStartDate = CRM_Utils_Array::value('start_date', $params, NULL);
                    $pledgeParams['start_date'] = $pledgeParams['scheduled_date'] = CRM_Pledge_BAO_Pledge::getPledgeStartDate($pledgeStartDate, $pledgeBlock);
                }
                $pledgeParams['status_id'] = $contribution->contribution_status_id;
                $pledgeParams['max_reminders'] = $form->_values['max_reminders'];
                $pledgeParams['initial_reminder_day'] = $form->_values['initial_reminder_day'];
                $pledgeParams['additional_reminder_day'] = $form->_values['additional_reminder_day'];
                $pledgeParams['is_test'] = $contribution->is_test;
                $pledgeParams['acknowledge_date'] = date('Ymd');
                $pledgeParams['original_installment_amount'] = $pledgeParams['installment_amount'];

                //inherit campaign from contirb page.
                $pledgeParams['campaign_id'] = CRM_Utils_Array::value('campaign_id', $contributionParams);

                $pledge = CRM_Pledge_BAO_Pledge::create($pledgeParams);

                $form->_params['pledge_id'] = $pledge->id;

                //send acknowledgment email. only when pledge is created
                if ($pledge->id && $isEmailReceipt) {
                    //build params to send acknowledgment.
                    $pledgeParams['id'] = $pledge->id;
                    $pledgeParams['receipt_from_name'] = $form->_values['receipt_from_name'];
                    $pledgeParams['receipt_from_email'] = $form->_values['receipt_from_email'];

                    //scheduled amount will be same as installment_amount.
                    $pledgeParams['scheduled_amount'] = $pledgeParams['installment_amount'];

                    //get total pledge amount.
                    $pledgeParams['total_pledge_amount'] = $pledge->amount;

                    CRM_Pledge_BAO_Pledge::sendAcknowledgment($form, $pledgeParams);
                }
            }
        }

        if ($online && $contribution) {
            self::postProcess($params,
                'civicrm_contribution',
                $contribution->id,
                'Contribution'
            );
        }
        elseif ($contribution) {
            //handle custom data.
            $params['contribution_id'] = $contribution->id;
            if (!empty($params['custom']) &&
                is_array($params['custom']) &&
                !is_a($contribution, 'CRM_Core_Error')
            ) {
                CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_contribution', $contribution->id);
            }
        }
        // Save note
        if ($contribution && !empty($params['contribution_note'])) {
            $noteParams = array(
                'entity_table' => 'civicrm_contribution',
                'note' => $params['contribution_note'],
                'entity_id' => $contribution->id,
                'contact_id' => $contribution->contact_id,
                'modified_date' => date('Ymd'),
            );

            CRM_Core_BAO_Note::add($noteParams, array());
        }

        if (isset($params['related_contact'])) {
            $contactID = $params['related_contact'];
        }
        elseif (isset($params['cms_contactID'])) {
            $contactID = $params['cms_contactID'];
        }

        //create contribution activity w/ individual and target
        //activity w/ organisation contact id when onbelf, CRM-4027
        $targetContactID = NULL;
        if (!empty($params['hidden_onbehalf_profile'])) {
            $targetContactID = $contribution->contact_id;
            $contribution->contact_id = $contactID;
        }

        // create an activity record
        if ($contribution) {
            CRM_Activity_BAO_Activity::addActivity($contribution, NULL, $targetContactID);
        }

        $transaction->commit();
        // CRM-13074 - create the CMSUser after the transaction is completed as it
        // is not appropriate to delete a valid contribution if a user create problem occurs
        CRM_Contribute_BAO_Contribution_Utils::createCMSUser($params,
            $contactID,
            'email-' . $billingLocationID
        );
        return $contribution;
    }


    /**
     * Create the recurring contribution record.
     * Taken from 4.7.10 version of CiviCRM -- /CRM/Contribute/Form/Contribution/Confirm.php
     *
     * @param CRM_Core_Form $form
     * @param array $params
     * @param int $contactID
     * @param string $contributionType
     *
     * @return int|null
     */
    public static function processRecurringContribution(&$form, &$params, $contactID, $contributionType) {

        if (empty($params['is_recur'])) {
            return NULL;
        }

        $recurParams = array('contact_id' => $contactID);
        $recurParams['amount'] = CRM_Utils_Array::value('amount', $params);
        $recurParams['auto_renew'] = CRM_Utils_Array::value('auto_renew', $params);
        $recurParams['frequency_unit'] = CRM_Utils_Array::value('frequency_unit', $params);
        $recurParams['frequency_interval'] = CRM_Utils_Array::value('frequency_interval', $params);
        $recurParams['installments'] = CRM_Utils_Array::value('installments', $params);
        $recurParams['financial_type_id'] = CRM_Utils_Array::value('financial_type_id', $params);
        $recurParams['currency'] = CRM_Utils_Array::value('currency', $params);

        // CRM-14354: For an auto-renewing membership with an additional contribution,
        // if separate payments is not enabled, make sure only the membership fee recurs
        if (!empty($form->_membershipBlock)
            && $form->_membershipBlock['is_separate_payment'] === '0'
            && isset($params['selectMembership'])
            && $form->_values['is_allow_other_amount'] == '1'
            // CRM-16331
            && !empty($form->_membershipTypeValues)
            && !empty($form->_membershipTypeValues[$params['selectMembership']]['minimum_fee'])
        ) {
            $recurParams['amount'] = $form->_membershipTypeValues[$params['selectMembership']]['minimum_fee'];
        }

        $recurParams['is_test'] = 0;
        if (($form->_action & CRM_Core_Action::PREVIEW) ||
            (isset($form->_mode) && ($form->_mode == 'test'))
        ) {
            $recurParams['is_test'] = 1;
        }

        $recurParams['start_date'] = $recurParams['create_date'] = $recurParams['modified_date'] = date('YmdHis');
        if (!empty($params['receive_date'])) {
            $recurParams['start_date'] = $params['receive_date'];
        }
        $recurParams['invoice_id'] = CRM_Utils_Array::value('invoiceID', $params);
        $recurParams['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending');
        $recurParams['payment_processor_id'] = CRM_Utils_Array::value('payment_processor_id', $params);
        $recurParams['is_email_receipt'] = CRM_Utils_Array::value('is_email_receipt', $params);
        // we need to add a unique trxn_id to avoid a unique key error
        // in paypal IPN we reset this when paypal sends us the real trxn id, CRM-2991
        $recurParams['trxn_id'] = CRM_Utils_Array::value('trxn_id', $params, $params['invoiceID']);
        $recurParams['financial_type_id'] = $contributionType->id;

        if (!empty($form->_values['is_monetary'])) {
            $recurParams['payment_instrument_id'] = 1;
        }

        $campaignId = CRM_Utils_Array::value('campaign_id', $params, CRM_Utils_Array::value('campaign_id', $form->_values));
        $recurParams['campaign_id'] = $campaignId;

        $recurring = CRM_Contribute_BAO_ContributionRecur::add($recurParams);
        if (is_a($recurring, 'CRM_Core_Error')) {
            CRM_Core_Error::displaySessionError($recurring);
            $urlString = 'civicrm/contribute/transact';
            $urlParams = '_qf_Main_display=true';
            if (get_class($form) == 'CRM_Contribute_Form_Contribution') {
                $urlString = 'civicrm/contact/view/contribution';
                $urlParams = "action=add&cid={$form->_contactID}";
                if ($form->_mode) {
                    $urlParams .= "&mode={$form->_mode}";
                }
            }
            CRM_Utils_System::redirect(CRM_Utils_System::url($urlString, $urlParams));
        }
        // Only set contribution recur ID for contributions since offline membership recur payments are handled somewhere else.
        if (!is_a($form, "CRM_Member_Form_Membership")) {
            $form->_params['contributionRecurID'] = $recurring->id;
        }

        return $recurring->id;
    }

    /**
     * Is a payment being made.
     * Note that setting is_monetary on the form is somewhat legacy and the behaviour around this setting is confusing. It would be preferable
     * to look for the amount only (assuming this cannot refer to payment in goats or other non-monetary currency
     *
     * Taken from 4.7.10 version of CiviCRM -- /CRM/Contribute/BAO/Contribution/Utils.php
     *
     * @param CRM_Core_Form $form
     *
     * @return bool
     */
    static protected function isPaymentTransaction($form) {
        if (!empty($form->_values['is_monetary']) && $form->_amount >= 0.0) {
            return TRUE;
        }
        return FALSE;

    }

    /**
     * Get non-deductible amount.
     * Taken from 4.7.10 version of CiviCRM -- /CRM/Contribute/Form/Contribution/Confirm.php
     *
     * This is a bit too much about wierd form interpretation to be this deep.
     *
     * CRM-11885
     *  if non_deductible_amount exists i.e. Additional Details fieldset was opened [and staff typed something] -> keep
     * it.
     *
     * @param array $params
     * @param CRM_Financial_BAO_FinancialType $financialType
     * @param bool $online
     *
     * @return array
     */
    protected static function getNonDeductibleAmount($params, $financialType, $online) {
        if (isset($params['non_deductible_amount']) && (!empty($params['non_deductible_amount']))) {
            return $params['non_deductible_amount'];
        }
        else {
            if ($financialType->is_deductible) {
                if ($online && isset($params['selectProduct'])) {
                    $selectProduct = CRM_Utils_Array::value('selectProduct', $params);
                }
                if (!$online && isset($params['product_name'][0])) {
                    $selectProduct = $params['product_name'][0];
                }
                // if there is a product - compare the value to the contribution amount
                if (isset($selectProduct) &&
                    $selectProduct != 'no_thanks'
                ) {
                    $productDAO = new CRM_Contribute_DAO_Product();
                    $productDAO->id = $selectProduct;
                    $productDAO->find(TRUE);
                    // product value exceeds contribution amount
                    if ($params['amount'] < $productDAO->price) {
                        $nonDeductibleAmount = $params['amount'];
                        return $nonDeductibleAmount;
                    }
                    // product value does NOT exceed contribution amount
                    else {
                        return $productDAO->price;
                    }
                }
                // contribution is deductible - but there is no product
                else {
                    return '0.00';
                }
            }
            // contribution is NOT deductible
            else {
                return $params['amount'];
            }
        }
    }

    /**
     * Post process function.
     * Taken from 4.7.10 version of CiviCRM -- /CRM/Core/BAO/CustomValueTable.php
     *
     * @param array $params
     * @param $entityTable
     * @param int $entityID
     * @param $customFieldExtends
     */
    public static function postProcess(&$params, $entityTable, $entityID, $customFieldExtends) {
        $customData = self::customFieldPostProcess($params,
            $entityID,
            $customFieldExtends
        );

        if (!empty($customData)) {
            self::store($customData, $entityTable, $entityID);
        }
    }

    /**
     * Post process function.
     * Taken from 4.7.10 version of CiviCRM -- /CRM/Core/BAO/CustomField.php
     *
     * @param array $params
     * @param int $entityID
     * @param string $customFieldExtends
     * @param bool $inline
     * @param bool $checkPermissions
     *
     * @return array
     */
    public static function customFieldPostProcess(
        &$params,
        $entityID,
        $customFieldExtends,
        $inline = FALSE,
        $checkPermissions = TRUE
    ) {
        $customData = array();

        foreach ($params as $key => $value) {
            if ($customFieldInfo = CRM_Core_BAO_CustomField::getKeyID($key, TRUE)) {

                // for autocomplete transfer hidden value instead of label
                if ($params[$key] && isset($params[$key . '_id'])) {
                    $value = $params[$key . '_id'];
                }

                // we need to append time with date
                if ($params[$key] && isset($params[$key . '_time'])) {
                    $value .= ' ' . $params[$key . '_time'];
                }

                CRM_Core_BAO_CustomField::formatCustomField($customFieldInfo[0],
                    $customData,
                    $value,
                    $customFieldExtends,
                    $customFieldInfo[1],
                    $entityID,
                    $inline,
                    $checkPermissions
                );
            }
        }
        return $customData;
    }

    /**
     * Obtain the domain settings.
     * Taken from 4.7.10 version of CiviCRM -- /Civi.php and customized
     *
     * @param int|null $domainID
     *   For the default domain, leave $domainID as NULL.
     * @return \Civi\Core\SettingsBag
     */
    public static function settings($domainID = NULL) {
        return self::getBootService('settings_manager')->getBagByDomain($domainID);
    }

    /**
     * Set the parameters to be passed to contribution create function.
     * Taken from 4.7.10 version of CiviCRM -- /CRM/Contribute/Form/Contribution/Confirm.php
     *
     * @param array $params
     * @param int $financialTypeID
     * @param float $nonDeductibleAmount
     * @param bool $pending
     * @param array $paymentProcessorOutcome
     * @param string $receiptDate
     * @param int $recurringContributionID
     *
     * @return array
     */
    public static function getContributionParams(
        $params, $financialTypeID, $nonDeductibleAmount, $pending,
        $paymentProcessorOutcome, $receiptDate, $recurringContributionID) {
        $contributionParams = array(
            'financial_type_id' => $financialTypeID,
            'receive_date' => (CRM_Utils_Array::value('receive_date', $params)) ? CRM_Utils_Date::processDate($params['receive_date']) : date('YmdHis'),
            'non_deductible_amount' => $nonDeductibleAmount,
            'total_amount' => $params['amount'],
            'tax_amount' => CRM_Utils_Array::value('tax_amount', $params),
            'amount_level' => CRM_Utils_Array::value('amount_level', $params),
            'invoice_id' => $params['invoiceID'],
            'currency' => $params['currencyID'],
            'is_pay_later' => CRM_Utils_Array::value('is_pay_later', $params, 0),
            //configure cancel reason, cancel date and thankyou date
            //from 'contribution' type profile if included
            'cancel_reason' => CRM_Utils_Array::value('cancel_reason', $params, 0),
            'cancel_date' => isset($params['cancel_date']) ? CRM_Utils_Date::format($params['cancel_date']) : NULL,
            'thankyou_date' => isset($params['thankyou_date']) ? CRM_Utils_Date::format($params['thankyou_date']) : NULL,
            //setting to make available to hook - although seems wrong to set on form for BAO hook availability
            'skipLineItem' => CRM_Utils_Array::value('skipLineItem', $params, 0),
        );

        if ($paymentProcessorOutcome) {
            $contributionParams['payment_processor'] = CRM_Utils_Array::value('payment_processor', $paymentProcessorOutcome);
        }
        if (!$pending && $paymentProcessorOutcome) {
            $contributionParams += array(
                'fee_amount' => CRM_Utils_Array::value('fee_amount', $paymentProcessorOutcome),
                'net_amount' => CRM_Utils_Array::value('net_amount', $paymentProcessorOutcome, $params['amount']),
                'trxn_id' => $paymentProcessorOutcome['trxn_id'],
                'receipt_date' => $receiptDate,
                // also add financial_trxn details as part of fix for CRM-4724
                'trxn_result_code' => CRM_Utils_Array::value('trxn_result_code', $paymentProcessorOutcome),
            );
        }

        // CRM-4038: for non-en_US locales, CRM_Contribute_BAO_Contribution::add() expects localised amounts
        $contributionParams['non_deductible_amount'] = trim(CRM_Utils_Money::format($contributionParams['non_deductible_amount'], ' '));
        $contributionParams['total_amount'] = trim(CRM_Utils_Money::format($contributionParams['total_amount'], ' '));

        if ($recurringContributionID) {
            $contributionParams['contribution_recur_id'] = $recurringContributionID;
        }

        $contributionParams['contribution_status_id'] = $pending ? 2 : 1;
        if (isset($contributionParams['invoice_id'])) {
            $contributionParams['id'] = CRM_Core_DAO::getFieldValue(
                'CRM_Contribute_DAO_Contribution',
                $contributionParams['invoice_id'],
                'id',
                'invoice_id'
            );
        }

        return $contributionParams;
    }

    /**
     * Taken from 4.7.10 version of CiviCRM -- /Civi/Core/Container.php and customized
     * @param $name
     * @return mixed
     */
    public static function getBootService($name) {
        return \Civi::$statics['Civi\Core\Container']['boot'][$name];
    }

}