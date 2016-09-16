<?php

namespace tech\vanagas\civicrm\extension\payment\pendingcontribution {

    /**
     * Class Contribution
     * @package tech\vanagas\civicrm\extension\payment\pendingcontribution
     */
    class Contribution
    {
        /**
         * Amount designated for contribution.
         *
         * @var float
         */
        protected $_amount;

        /**
         * Type of contribution (Donation/Event Fees/Membership...).
         *
         * @var string
         */
        protected $_financialType;

        /**
         * The source for which this contribution was made for.
         *
         * @var string
         */
        protected $_contributionSource;

        /**
         * The current status of the contribution ("Pending", "In Progress", "Overdue", "Partially paid", "Failed").
         *
         * @var string
         */
        protected $_contributionStatus;

        /**
         * The Id of current status of the contribution ("Pending", "In Progress", "Overdue", "Partially paid", "Failed").
         *
         * @var integer
         */
        protected $_contributionStatusID;

        /**
         * Is this a Pledge?
         *
         * @var boolean
         */
        protected $_isPledge;

        /**
         * Is this a recurring payment?
         *
         * @var boolean
         */
        protected $_isRecurring;

        /**
         * Is this a pay later entry?
         *
         * @var boolean
         */
        protected $_isPayLater;

        /**
         * Contact ID of contribution owner
         *
         * @var integer
         */
        protected $_ownerContactID;

        /**
         * Display Name of contribution owner
         *
         * @var string
         */
        protected $_ownerDisplayName;

        /**
         * Contribution ID
         *
         * @var integer
         */
        protected $_contributionID;

        /**
         * Contribution Page ID
         *
         * @var integer
         */
        protected $_contributionPageID;

        /**
         * Contribution Page
         *
         * @var object
         */
        protected $_contributionPage;

        /**
         * Currency of the contribution
         *
         * @var string
         */
        protected $_currency;

        /**
         * List of recurring contributions (if any)
         *
         * @var array
         */
        protected $_recurringContributions;

        /**
         * Receive Date
         *
         * @var string
         */
        protected $_receiveDate;

        /**
         * Store the raw values as is
         *
         * @var array
         */
        protected $_values;

        /**
         * Payment Instrument (cheque, cc, bank)
         *
         * @var string
         */
        protected $_paymentInstrument;

        /**
         * If Payment is via Cheque, then we need cheque number
         *
         * @var string
         */
        protected $_paymentInstrumentNumber;

        /**
         * @return mixed
         */
        public function getRecurringContributions()
        {
            return $this->_recurringContributions;
        }

        /**
         * @param mixed $recurringContributions
         * @return Contribution
         */
        public function setRecurringContributions($recurringContributions)
        {
            $this->_recurringContributions = $recurringContributions;
            return $this;
        }



        /**
         * @return float
         */
        public function getAmount()
        {
            return $this->_amount;
        }


        /**
         * @param float $amount
         * @return Contribution
         */
        public function setAmount($amount)
        {
            $this->_amount = $amount;
            return $this;
        }

        /**
         * @return string
         */
        public function getFinancialType()
        {
            return $this->_financialType;
        }

        /**
         * @param string $financialType
         * @return Contribution
         */
        public function setFinancialType($financialType)
        {
            $this->_financialType = $financialType;
            return $this;
        }

        /**
         * @return string
         */
        public function getContributionSource()
        {
            return $this->_contributionSource;
        }

        /**
         * @param string $contributionSource
         * @return Contribution
         */
        public function setContributionSource($contributionSource)
        {
            $this->_contributionSource = $contributionSource;
            return $this;
        }

        /**
         * @return boolean
         */
        public function isIsPledge()
        {
            return $this->_isPledge;
        }

        /**
         * @param boolean $isPledge
         * @return Contribution
         */
        public function setIsPledge($isPledge)
        {
            $this->_isPledge = $isPledge;
            return $this;
        }

        /**
         * @return boolean
         */
        public function isIsRecurring()
        {
            return $this->_isRecurring;
        }

        /**
         * @param boolean $isRecurring
         * @return Contribution
         */
        public function setIsRecurring($isRecurring)
        {
            $this->_isRecurring = $isRecurring;
            return $this;
        }

        /**
         * @return boolean
         */
        public function isIsPayLater()
        {
            return $this->_isPayLater;
        }

        /**
         * @param boolean $isPayLater
         * @return Contribution
         */
        public function setIsPayLater($isPayLater)
        {
            $this->_isPayLater = $isPayLater;
            return $this;
        }

        /**
         * @return int
         */
        public function getOwnerContactID()
        {
            return $this->_ownerContactID;
        }

        /**
         * @param int $ownerContactID
         * @return Contribution
         */
        public function setOwnerContactID($ownerContactID)
        {
            $this->_ownerContactID = $ownerContactID;
            return $this;
        }

        /**
         * @return string
         */
        public function getOwnerDisplayName()
        {
            return $this->_ownerDisplayName;
        }

        /**
         * @param string $ownerDisplayName
         * @return Contribution
         */
        public function setOwnerDisplayName($ownerDisplayName)
        {
            $this->_ownerDisplayName = $ownerDisplayName;
            return $this;
        }

        /**
         * @return int
         */
        public function getContributionID()
        {
            return $this->_contributionID;
        }

        /**
         * @param int $contributionID
         * @return Contribution
         */
        public function setContributionID($contributionID)
        {
            $this->_contributionID = $contributionID;
            return $this;
        }

        /**
         * @return int
         */
        public function getContributionPageID()
        {
            return $this->_contributionPageID;
        }

        /**
         * @param int $contributionPageID
         * @return Contribution
         */
        public function setContributionPageID($contributionPageID)
        {
            $this->_contributionPageID = $contributionPageID;
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
         * @return Contribution
         */
        public function setCurrency($currency)
        {
            $this->_currency = $currency;
            return $this;
        }

        /**
         * @return mixed
         */
        public function getReceiveDate()
        {
            return $this->_receiveDate;
        }

        /**
         * @param mixed $receiveDate
         * @return Contribution
         */
        public function setReceiveDate($receiveDate)
        {
            $this->_receiveDate = $receiveDate;
            return $this;
        }

        /**
         * @return array
         */
        public function getValues()
        {
            return $this->_values;
        }

        /**
         * @param array $values
         * @return Contribution
         */
        public function setValues($values)
        {
            $this->_values = $values;
            return $this;
        }

        /**
         * @return string
         */
        public function getContributionStatus()
        {
            return $this->_contributionStatus;
        }

        /**
         * @param string $contributionStatus
         * @return Contribution
         */
        public function setContributionStatus($contributionStatus)
        {
            $this->_contributionStatus = $contributionStatus;
            return $this;
        }

        /**
         * @return int
         */
        public function getContributionStatusID()
        {
            return $this->_contributionStatusID;
        }

        /**
         * @param int $contributionStatusID
         * @return Contribution
         */
        public function setContributionStatusID($contributionStatusID)
        {
            $this->_contributionStatusID = $contributionStatusID;
            return $this;
        }

        /**
         * @return string
         */
        public function getPaymentInstrument()
        {
            return $this->_paymentInstrument;
        }

        /**
         * @param string $paymentInstrument
         * @return Contribution
         */
        public function setPaymentInstrument($paymentInstrument)
        {
            $this->_paymentInstrument = $paymentInstrument;
            return $this;
        }

        /**
         * @return object
         */
        public function getContributionPage()
        {
            return $this->_contributionPage;
        }

        /**
         * @param object $contributionPage
         * @return Contribution
         */
        public function setContributionPage($contributionPage)
        {
            $this->_contributionPage = $contributionPage;
            return $this;
        }

        /**
         * @return string
         */
        public function getPaymentInstrumentNumber()
        {
            return $this->_paymentInstrumentNumber;
        }

        /**
         * @param string $paymentInstrumentNumber
         * @return Contribution
         */
        public function setPaymentInstrumentNumber($paymentInstrumentNumber)
        {
            $this->_paymentInstrumentNumber = $paymentInstrumentNumber;
            return $this;
        }

    }
}