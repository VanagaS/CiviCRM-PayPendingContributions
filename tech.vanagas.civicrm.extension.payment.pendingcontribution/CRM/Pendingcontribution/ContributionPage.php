<?php

namespace tech\vanagas\civicrm\extension\payment\pendingcontribution {

    /**
     * Class ContributionPage
     * @package tech\vanagas\civicrm\extension\payment\pendingcontribution
     */
    class ContributionPage
    {
        /**
         * Contribution Page ID.
         *
         * @var int
         */
        protected $_pageID;

        /**
         * Contribution Title.
         *
         * @var string
         */
        protected $_contributionTitle;

        /**
         * Contribution Introductory Text
         *
         * @var string
         */
        protected $_contributionIntroText;

        /**
         * Contribution Payment Processor ID.
         *
         * @var int
         */
        protected $_paymentProcessor;

        /**
         * Contribution default amount
         *
         * @var float
         */
        protected $_defaultAmount;

        /**
         * Contribution minimum amount
         *
         * @var float
         */
        protected $_minAmount;

        /**
         * Contribution maximum amount
         *
         * @var float
         */
        protected $_maxAmount;

        /**
         * Contribution Thankyou title
         *
         * @var string
         */
        protected $_thankyouTitle;

        /**
         * Contribution Thankyou title
         *
         * @var string
         */
        protected $_thankyouText;

        /**
         * Contribution Thankyou title
         *
         * @var string
         */
        protected $_thankyouFooter;

        /**
         * Should send e-mail receipt
         *
         * @var boolean
         */
        protected $_isEmailReceipt;

        /**
         * Receipt From Email
         *
         * @var string
         */
        protected $_emailFromEmail;

        /**
         * Receipt From Name
         *
         * @var string
         */
        protected $_emailFromName;

        /**
         * Receipt From CC
         *
         * @var string
         */
        protected $_emailToCC;

        /**
         * Receipt Email BCC
         *
         * @var string
         */
        protected $_emailToBCC;

        /**
         * Receipt Text
         *
         * @var string
         */
        protected $_receiptText;

        /**
         * Contribution is Active
         *
         * @var boolean
         */
        protected $_isActive;

        /**
         * Amount Block is Active
         *
         * @var boolean
         */
        protected $_amountBlockActive;

        /**
         * Contribution Currency
         *
         * @var string
         */
        protected $_currency;

        /**
         * Can be Shared?
         *
         * @var boolean
         */
        protected $_isShared;

        /**
         * Is billing required?
         *
         * @var boolean
         */
        protected $_isBillingRequired;

        /**
         * Financial Type
         *
         * @var integer
         */
        protected $_financialTypeID;

        /**
         * Store the reference to form
         *
         * @var object
         */
        protected $_form;

        /**
         * Store the raw values as is
         *
         * @var array
         */
        protected $_values;


        /**
         * ContributionPage constructor.
         * @param $_pageID
         * @param $form
         * @param null $listPage
         */
        public function __construct($_pageID, &$form, $listPage = null)
        {
            $this->_pageID = $_pageID;

            $this->fetchContributionPage();
            $this->_form = &$form;

            /** Assign the raw object to the form
             * FIXME: Need to remove this to save memory, once we transfer all required properties of this->_values to Object properties
             * If listPage is null, which is default, assign the values, otherwise, save memory
             */
            if(is_null($listPage)) {
                $this->_form->_values = $this->_values;
            }
        }

        /**
         *
         */
        public function setupFormVariables()
        {
            /* Send Contribution Page ID to template */
            $this->_form->assign('contributionPageID', $this->_pageID);
            $this->_form->assign('contribution_page_title', $this->_contributionTitle);
        }


        /**
         * @return array|null
         */
        function fetchContributionPage()
        {
            try {
                $result = \civicrm_api3('ContributionPage', 'get', array(
                    'sequential' => 1,
                    'id' => $this->_pageID,
                ));

                foreach ($result['values'] as $value) {
                    $this->_values = $value;
                    $this->setAmountBlockActive(@$value['amount_block_is_active'])
                        ->setContributionIntroText(@$value['intro_text'])
                        ->setContributionTitle(@$value['title'])
                        ->setCurrency(@$value['currency'])
                        ->setDefaultAmount(@$value['default_amount_id'])
                        ->setEmailFromEmail(@$value['receipt_from_email'])
                        ->setEmailFromName((@$value['receipt_from_name']))
                        ->setIsEmailReceipt(@$value['is_email_receipt'])
                        ->setEmailToBCC(@$value['bcc_receipt'])
                        ->setEmailToCC(@$value['cc_receipt'])
                        ->setFinancialTypeID(@$value['financial_type_id'])
                        ->setIsActive(@$value['is_active'])
                        ->setIsBillingRequired(@$value['is_billing_required'])
                        ->setIsShared(@$value['is_share'])
                        ->setMinAmount(@$value['min_amount'])
                        ->setPaymentProcessor(@$value['payment_processor'])
                        ->setReceiptText(@$value['receipt_text'])
                        ->setThankyouText(@$value['thankyou_text'])
                        ->setThankyouTitle(@$value['thankyou_title'])
                        ->setThankyouFooter(@$value['thankyou_footer'])
                        ->setMaxAmount(@$value['max_amount']);
                }

            } catch (\CiviCRM_API3_Exception $e) {
                // Handle error here.
                $errorMessage = $e->getMessage();
                $errorCode = $e->getErrorCode();
                $errorData = $e->getExtraParams();
                return array(
                    'error' => $errorMessage,
                    'error_code' => $errorCode,
                    'error_data' => $errorData,
                );
            }

            return null;
        }

        /**
         * @return int
         */
        public function getPageID()
        {
            return $this->_pageID;
        }

        /**
         * @param int $pageID
         * @return ContributionPage
         */
        public function setPageID($pageID)
        {
            $this->_pageID = $pageID;
            return $this;
        }

        /**
         * @return string
         */
        public function getContributionTitle()
        {
            return $this->_contributionTitle;
        }

        /**
         * @param string $contributionTitle
         * @return ContributionPage
         */
        public function setContributionTitle($contributionTitle)
        {
            $this->_contributionTitle = $contributionTitle;
            return $this;
        }

        /**
         * @return string
         */
        public function getContributionIntroText()
        {
            return $this->_contributionIntroText;
        }

        /**
         * @param string $contributionIntroText
         * @return ContributionPage
         */
        public function setContributionIntroText($contributionIntroText)
        {
            $this->_contributionIntroText = $contributionIntroText;
            return $this;
        }

        /**
         * @return int
         */
        public function getPaymentProcessor()
        {
            return $this->_paymentProcessor;
        }

        /**
         * @param int $paymentProcessor
         * @return ContributionPage
         */
        public function setPaymentProcessor($paymentProcessor)
        {
            $this->_paymentProcessor = $paymentProcessor;
            return $this;
        }

        /**
         * @return float
         */
        public function getDefaultAmount()
        {
            return $this->_defaultAmount;
        }

        /**
         * @param float $defaultAmount
         * @return ContributionPage
         */
        public function setDefaultAmount($defaultAmount)
        {
            $this->_defaultAmount = $defaultAmount;
            return $this;
        }

        /**
         * @return float
         */
        public function getMinAmount()
        {
            return $this->_minAmount;
        }

        /**
         * @param float $minAmount
         * @return ContributionPage
         */
        public function setMinAmount($minAmount)
        {
            $this->_minAmount = $minAmount;
            return $this;
        }

        /**
         * @return float
         */
        public function getMaxAmount()
        {
            return $this->_maxAmount;
        }

        /**
         * @param float $maxAmount
         * @return ContributionPage
         */
        public function setMaxAmount($maxAmount)
        {
            $this->_maxAmount = $maxAmount;
            return $this;
        }

        /**
         * @return string
         */
        public function getThankyouTitle()
        {
            return $this->_thankyouTitle;
        }

        /**
         * @param string $thankyouTitle
         * @return ContributionPage
         */
        public function setThankyouTitle($thankyouTitle)
        {
            $this->_thankyouTitle = $thankyouTitle;
            return $this;
        }

        /**
         * @return string
         */
        public function getThankyouText()
        {
            return $this->_thankyouText;
        }

        /**
         * @param string $thankyouText
         * @return ContributionPage
         */
        public function setThankyouText($thankyouText)
        {
            $this->_thankyouText = $thankyouText;
            return $this;
        }

        /**
         * @return boolean
         */
        public function isIsEmailReceipt()
        {
            return $this->_isEmailReceipt;
        }

        /**
         * @param boolean $emailReceipt
         * @return ContributionPage
         */
        public function setIsEmailReceipt($emailReceipt)
        {
            $this->_isEmailReceipt = $emailReceipt;
            return $this;
        }

        /**
         * @return string
         */
        public function getEmailFromEmail()
        {
            return $this->_emailFromEmail;
        }

        /**
         * @param string $emailFromEmail
         * @return ContributionPage
         */
        public function setEmailFromEmail($emailFromEmail)
        {
            $this->_emailFromEmail = $emailFromEmail;
            return $this;
        }

        /**
         * @return string
         */
        public function getEmailFromName()
        {
            return $this->_emailFromName;
        }

        /**
         * @param string $emailFromName
         * @return ContributionPage
         */
        public function setEmailFromName($emailFromName)
        {
            $this->_emailFromName = $emailFromName;
            return $this;
        }

        /**
         * @return string
         */
        public function getEmailToCC()
        {
            return $this->_emailToCC;
        }

        /**
         * @param string $emailToCC
         * @return ContributionPage
         */
        public function setEmailToCC($emailToCC)
        {
            $this->_emailToCC = $emailToCC;
            return $this;
        }

        /**
         * @return string
         */
        public function getEmailToBCC()
        {
            return $this->_emailToBCC;
        }

        /**
         * @param string $emailToBCC
         * @return ContributionPage
         */
        public function setEmailToBCC($emailToBCC)
        {
            $this->_emailToBCC = $emailToBCC;
            return $this;
        }

        /**
         * @return string
         */
        public function getReceiptText()
        {
            return $this->_receiptText;
        }

        /**
         * @param string $receiptText
         * @return ContributionPage
         */
        public function setReceiptText($receiptText)
        {
            $this->_receiptText = $receiptText;
            return $this;
        }

        /**
         * @return boolean
         */
        public function isIsActive()
        {
            return $this->_isActive;
        }

        /**
         * @param boolean $isActive
         * @return ContributionPage
         */
        public function setIsActive($isActive)
        {
            $this->_isActive = $isActive;
            return $this;
        }

        /**
         * @return boolean
         */
        public function isAmountBlockActive()
        {
            return $this->_amountBlockActive;
        }

        /**
         * @param boolean $amountBlockActive
         * @return ContributionPage
         */
        public function setAmountBlockActive($amountBlockActive)
        {
            $this->_amountBlockActive = $amountBlockActive;
            return $this;
        }

        /**
         * @return string
         */
        public function getCurrency()
        {
            return $this->_currency;
        }

        /**
         * @param string $currency
         * @return ContributionPage
         */
        public function setCurrency($currency)
        {
            $this->_currency = $currency;
            return $this;
        }

        /**
         * @return boolean
         */
        public function isIsShared()
        {
            return $this->_isShared;
        }

        /**
         * @param boolean $isShared
         * @return ContributionPage
         */
        public function setIsShared($isShared)
        {
            $this->_isShared = $isShared;
            return $this;
        }

        /**
         * @return boolean
         */
        public function isIsBillingRequired()
        {
            return $this->_isBillingRequired;
        }

        /**
         * @param boolean $isBillingRequired
         * @return ContributionPage
         */
        public function setIsBillingRequired($isBillingRequired)
        {
            $this->_isBillingRequired = $isBillingRequired;
            return $this;
        }

        /**
         * @return mixed
         */
        public function getFinancialTypeID()
        {
            return $this->_financialTypeID;
        }

        /**
         * @param mixed $financialTypeID
         * @return ContributionPage
         */
        public function setFinancialTypeID($financialTypeID)
        {
            $this->_financialTypeID = $financialTypeID;
            return $this;
        }

        /**
         * @return string
         */
        public function getThankyouFooter()
        {
            return $this->_thankyouFooter;
        }

        /**
         * @param string $thankyouFooter
         * @return ContributionPage
         */
        public function setThankyouFooter($thankyouFooter)
        {
            $this->_thankyouFooter = $thankyouFooter;
            return $this;
        }

    }

}