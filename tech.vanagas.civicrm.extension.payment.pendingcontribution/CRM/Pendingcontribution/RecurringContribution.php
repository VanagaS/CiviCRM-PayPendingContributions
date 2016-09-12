<?php

namespace tech\vanagas\civicrm\extension\payment\pendingcontribution {

    /**
     * Class RecurringContribution
     * @package tech\vanagas\civicrm\extension\payment\pendingcontribution
     */
    class RecurringContribution
    {
        /**
         * Amount designated for recurring contribution.
         *
         * @var float
         */
        protected $_amount;

        /**
         * The Unit of frequency: (Week, Month, Year).
         *
         * @var string
         */
        protected $_frequencyUnit;

        /**
         * The start date on which this contribution was made for.
         *
         * @var string
         */
        protected $_startDate;

        /**
         * The current status of the contribution ("Pending", "In Progress", "Overdue", "Partially paid", "Failed").
         *
         * @var boolean
         */
        protected $_contributionStatus;

        /**
         * Contact ID of contribution owner
         *
         * @var integer
         */
        protected $_ownerContactID;

        /**
         * Number of intervals for recurrence
         *
         * @var integer
         */
        protected $_frequencyInterval;

        /**
         * Recurring Contribution ID
         *
         * @var integer
         */
        protected $_recurringContributionID;

        /**
         * Currency of the contribution
         *
         * @var string
         */
        protected $_currency;


        /**
         * @return float
         */
        public function getAmount()
        {
            return $this->_amount;
        }

        /**
         * @param float $amount
         * @return RecurringContribution
         */
        public function setAmount($amount)
        {
            $this->_amount = $amount;
            return $this;
        }

        /**
         * @return string
         */
        public function getFrequencyUnit()
        {
            return $this->_frequencyUnit;
        }

        /**
         * @param string $frequencyUnit
         * @return RecurringContribution
         */
        public function setFrequencyUnit($frequencyUnit)
        {
            $this->_frequencyUnit = $frequencyUnit;
            return $this;
        }

        /**
         * @return string
         */
        public function getStartDate()
        {
            return $this->_startDate;
        }

        /**
         * @param string $startDate
         * @return RecurringContribution
         */
        public function setStartDate($startDate)
        {
            $this->_startDate = $startDate;
            return $this;
        }

        /**
         * @return boolean
         */
        public function isContributionStatus()
        {
            return $this->_contributionStatus;
        }

        /**
         * @param boolean $contributionStatus
         * @return RecurringContribution
         */
        public function setContributionStatus($contributionStatus)
        {
            $this->_contributionStatus = $contributionStatus;
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
         * @return RecurringContribution
         */
        public function setOwnerContactID($ownerContactID)
        {
            $this->_ownerContactID = $ownerContactID;
            return $this;
        }

        /**
         * @return string
         */
        public function getFrequencyInterval()
        {
            return $this->_frequencyInterval;
        }

        /**
         * @param int $frequencyInterval
         * @return RecurringContribution
         */
        public function setFrequencyInterval($frequencyInterval)
        {
            $this->_frequencyInterval = $frequencyInterval;
            return $this;
        }

        /**
         * @return int
         */
        public function getRecurringContributionID()
        {
            return $this->_recurringContributionID;
        }

        /**
         * @param int $recurringContributionID
         * @return RecurringContribution
         */
        public function setRecurringContributionID($recurringContributionID)
        {
            $this->_recurringContributionID = $recurringContributionID;
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
         * @return RecurringContribution
         */
        public function setCurrency($currency)
        {
            $this->_currency = $currency;
            return $this;
        }
    }

    /**
     * Available values:
     *
     * "values": [
     * {
     * "id": "6",
     * "contact_id": "204",
     * "amount": "2000.00",
     * "currency": "USD",
     * "frequency_unit": "month",
     * "frequency_interval": "5",
     * "installments": "5",
     * "start_date": "2016-09-08 19:28:12",
     * "create_date": "2016-09-08 19:28:12",
     * "modified_date": "2016-09-08 19:28:12",
     * "trxn_id": "7c5e4ab142b6a8f21bb2e0ebdbc609aa-2",
     * "invoice_id": "7c5e4ab142b6a8f21bb2e0ebdbc609aa-2",
     * "contribution_status_id": "2",
     * "is_test": "0",
     * "cycle_day": "1",
     * "failure_count": "0",
     * "auto_renew": "0",
     * "payment_processor_id": "1",
     * "financial_type_id": "3",
     * "payment_instrument_id": "1",
     * "is_email_receipt": "1"
     * }]*/

}