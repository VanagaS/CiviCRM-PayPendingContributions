<?php

namespace tech\vanagas\civicrm\extension\payment\pendingcontribution {

    /**
     * Class PaymentProcessor
     * @package tech\vanagas\civicrm\extension\payment\pendingcontribution
     */
    class PaymentProcessor
    {
        protected $_paymentProcessors;

        protected $_contribPageID;

        protected $_form;

        /**
         * PaymentProcessor constructor.
         */
        public function __construct($contribPageID, $pendingContributionsForm)
        {
            $this->_contribPageID = $contribPageID;
            $this->_form = $pendingContributionsForm;
        }

        public function preProcess() {
            $this->_paymentProcessors = $this->get('paymentProcessors');
            $this->preProcessPaymentOptions();

            // Make the contributionPageID available to the template
            $this->_form->assign('contributionPageID', $this->_contribPageID);
            $this->_form->assign('isShare', CRM_Utils_Array::value('is_share', $this->_values));
            $this->_form->assign('isConfirmEnabled', CRM_Utils_Array::value('is_confirm_enabled', $this->_values));

            $this->_form->assign('reset', CRM_Utils_Request::retrieve('reset', 'Boolean', CRM_Core_DAO::$_nullObject));
            $this->_form->assign('mainDisplay', CRM_Utils_Request::retrieve('_qf_Main_display', 'Boolean',
                CRM_Core_DAO::$_nullObject));

            if (!empty($this->_pcpInfo['id']) && !empty($this->_pcpInfo['intro_text'])) {
                $this->assign('intro_text', $this->_pcpInfo['intro_text']);
            }
            elseif (!empty($this->_values['intro_text'])) {
                $this->assign('intro_text', $this->_values['intro_text']);
            }
        }

        public function preProcessPaymentOptions() {

        }
    }
}