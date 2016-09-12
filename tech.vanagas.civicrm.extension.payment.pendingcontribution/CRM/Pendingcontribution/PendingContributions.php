<?php
namespace tech\vanagas\civicrm\extension\payment\pendingcontribution {

    require_once 'CRM/Core/Form.php';
    require_once 'CRM/Pendingcontribution/Contribution.php';
    require_once 'CRM/Pendingcontribution/ContributionPage.php';
    require_once 'CRM/Pendingcontribution/RecurringContribution.php';

    use \tech\vanagas\civicrm\extension\payment\pendingcontribution\Contribution as Contribution;
    use \tech\vanagas\civicrm\extension\payment\pendingcontribution\ContributionPage as ContributionPage;
    use \tech\vanagas\civicrm\extension\payment\pendingcontribution\RecurringContribution as RecurringContribution;

    /**
     * Form controller class
     *
     * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
     */
    class PendingContributions
    {

        /**
         * The logged_in user id.
         *
         * @var int
         */
        protected $_id;

        /**
         * The requested contribution id.
         *
         * @var int
         */
        protected $_contribution;

        /**
         * The requested contribution id.
         *
         * @var object
         */
        protected $_selectedContribution;

        /**
         * Contribution Page for selected Contribution.
         *
         * @var object
         */
        protected $_contributionPage;

        /**
         * The result set of pending payments for the given contribution id.
         *
         * @var array
         */
        protected $_contributions;

        /**
         * Whether the requested contribution id has any pending balances.
         *
         * @var boolean
         */
        protected $_isPending;

        /**
         * It is possible that payment could be made for any contribution ID. Whether the user is the owner
         * for the requested contribution ID.
         *
         * @var boolean
         */
        protected $_isOwner;

        /**
         * Owner of the requested contribution. (It is possible that payment could be made for any contribution ID)
         *
         * @var int
         */
        protected $_contributionOwner;

        /**
         * Whether there is any error during pre processing or during building form
         *
         * @var boolean
         */
        protected $_isError;

        /**
         * If there is any error, what's the error message
         *
         * @var string
         */
        protected $_errorMessage;

        /**
         * Store the reference to form
         *
         * @var object
         */
        protected $_form;

        /**
         * Set variables up before form is built.
         */
        public function __construct($contributionid, &$form)
        {

            /* set the form for assiging variables directly to template */
            $this->_form = &$form;
            /* set the selected contribution ID */
            $this->_contribution = $contributionid;


            /* get the reference to session object */
            $session = \CRM_Core_Session::singleton();

            /* retrieve the logged in user id */
            $this->_id = $session->getLoggedInContactID();

            /* Generate the results of the pending payments for the provided contribution id */
            /* Contribution and ContributionPage objects get loaded by the end of this call */
            $this->fetchContactsWithPendingPayments(); /* TODO Handle return value (errors) */

            /* Set the title for the displayed page */
            \CRM_Utils_System::setTitle(ts('Pay Pending Contributions'));

        }

        public function setupFormVariables()
        {
            /* Pass the user id to template */
            $this->_form->assign('contact_id', $this->_id);
            /* Pass the contribution(s) (array) to template */
            $this->_form->assign('contributions', $this->_contributions);
            /* Pass the default (selected) contribution to template */
            $this->_form->assign('selected_contribution', $this->_selectedContribution);
            /* Pass on the error state (PayPendingContributionsForm) to template */
            $this->_form->assign('ppcf_error_status', $this->_isError);
            /* Pass on the error message (PayPendingContributionsForm) to template */
            $this->_form->assign('ppcf_error_message', $this->_errorMessage);
            /* Contribution Amount */
            $this->_form->assign('contribution_amount', $this->_selectedContribution->getAmount());
            $this->_form->assign('currency', $this->_selectedContribution->getCurrency());

            /* All all the imported Objects */
            $this->_contributionPage->setupFormVariables();
        }

        /**
         * Fetches the list of all the pending payments or the pending payment requested for a particular contribution id.
         *
         * Case 1:
         * If no contribution id is provided, will fetch the list of all pending payments of the logged in user.
         *
         * Case 2:
         * If contribution id is provided, only the details of the request contribution will be fetched.
         *
         * Case 3:
         * If user is not logged in, no list will be provided.
         *
         * @return array|null Array consisting of the Contribution Objects or NULL
         */
        public function fetchContactsWithPendingPayments()
        {
            /*
             * If user is not logged in, chances are that, this class won't be initialized. However, lets not list out
             * any entries for unauthenticated attempts.
             */

            if (is_null($this->_contribution) && is_null($this->_id)) {
                \CRM_Core_Session::setStatus(ts('Listing of Pending contributions for unauthenticated users is disabled!'), ts('Not Logged In'), 'error');
                return null;
            }

            $params = array(
                'sequential' => 1,
                'contribution_page_id' => array('IS NOT NULL' => 1),
                'contribution_status_id' =>
                    array('IN' =>
                        array("Pending", "In Progress", "Overdue", "Partially paid", "Failed")),
                //'return' => array("*"),
            );

            /*
             * It is possible that payment could be made for any contribution ID. If contribution id is given, lets
             * skip restriction on fetching data for the logged in user only. If contribution id is not given, then
             * lets limit the pending contributions to logged in user only.         *
             */
            if (!is_null($this->_contribution)) {
                $params['id'] = $this->_contribution;
            } else {
                /* Since we only fetch the logged in user's data, lets update the given contributions as user owned. */
                $this->_isOwner = true;
                $params['contact_id'] = $this->_id;
            }

            try {
                $result = \civicrm_api3('Contribution', 'get', $params);

                /* Check whether we have any entries that needs to be fulfilled */
                if (!$result['is_error']) { // API call is successful and has no errors
                    $count = $result['count']; // Number of contributions to be paid (member variable required?)

                    /**
                     * If there are any pending payments, lets update some member variables to have easy access,
                     * where required.
                     *
                     * Given the chance that, the user has not entered any contribution ID, we will fetch all the pending
                     * contributions of the (logged-in) user, in which case, there can be multiple pending entries.
                     */
                    if ($count > 0) {
                        $this->_isPending = true;

                        //$this->xdebug($result);
                        /* populate the contribution object(s) with respective values */
                        $this->_contributions = $this->generateContributions($result);

                        /*
                         * If _isOwner is false, the list contains a single entry, that of a given contribution id
                         * Chances are that, this single contribution is owned by the logged in user or of another user.
                         */
                        if (!$this->_isOwner) {
                            $this->_contributionOwner = $result['values'][0]['contact_id'];
                            /* for a likely chance that, the given contribution owner is for the same logged in user */
                            if ($this->_contributionOwner == $this->_id) {
                                $this->_isOwner = true; /* Reset the value of _isOwner */
                            } else {
                                $this->_errorMessage = ts('You are not the owner of the requested contribution. Please 
                            check if the provided contribution id is not given by mistake.<br/><br/> However, you may continue 
                            to make payment for other users as well, if it is intended.');
                                /* Contribution owner is different from the logged in user */
                                \CRM_Core_Session::setStatus($this->_errorMessage, ts('Check Contribution ID'), 'warning');
                            }
                        }
                    } else {
                        $this->_errorMessage = ts('Either the requested contribution ID doesn\'t exist or you do not have any pending payments for the given contribution ID');
                        /* No results returned */
                        \CRM_Core_Session::setStatus($this->_errorMessage, ts('Check Contribution ID'), 'warning');
                    }
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
         * @param $result
         * @return array
         */
        function generateContributions($result)
        {
            $contributions = array();
            foreach ($result['values'] as $value) {
                $contribution = new Contribution();
                $contributions[] = $contribution->setAmount($value['total_amount'])
                    ->setFinancialType($value['financial_type'])
                    ->setContributionSource($value['contribution_source'])
                    ->setContributionStatus($value['contribution_status'])
                    ->setIsRecurring($value['contribution_recur_id'])
                    ->setCurrency($value['currency'])
                    ->setIsPayLater(($value['is_pay_later']))
                    ->setOwnerContactID($value['contact_id'])
                    ->setOwnerDisplayName($value['display_name'])
                    ->setContributionID($value['contribution_id'])
                    ->setContributionPageID($value['contribution_page_id']);

                if($this->_contribution) {
                    /* A a contribution id is already specified, there will be only one result */
                    $this->_selectedContribution = $contribution;
                    $this->_contributionPage = new ContributionPage($contribution->getContributionPageID(), $this->_form);
                }

                if (!is_null($value['contribution_recur_id'])) {
                    $contribution->setRecurringContributions($this->fetchRecurringContributions($value['contribution_recur_id']));
                }
            }
            return $contributions;
        }

        function fetchRecurringContributions($contribution_recur_id)
        {
            try {
                $result = \civicrm_api3('ContributionRecur', 'get', array(
                    'id' => $contribution_recur_id,
                ));

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
            return $result;
        }

        function generateRecurringContributions($result)
        {
            $recurringContributions = array();
            foreach ($result['values'] as $value) {
                if ($value['is_test']) {
                    continue;
                }
                $recurringContribution = new RecurringContribution();
                $recurringContributions[] = $recurringContribution->setAmount($value['amount'])
                    ->setOwnerContactID($value['contact_id'])
                    ->setCurrency($value['currency'])
                    ->setContributionStatus($value['contribution_status_id'])
                    ->setFrequencyInterval($value['frequency_interval'])
                    ->setFrequencyUnit($value['frequency_unit'])
                    ->setStartDate($value['start_date'])
                    ->setRecurringContributionID($value['id']);
            }
            return $recurringContributions;
        }

        /**
         * @return int
         */
        public function getId()
        {
            return $this->_id;
        }

        /**
         * @param int $id
         * @return PendingContributions
         */
        public function setId($id)
        {
            $this->_id = $id;
            return $this;
        }

        /**
         * @return int
         */
        public function getContribution()
        {
            return $this->_contribution;
        }

        /**
         * @param int $contribution
         * @return PendingContributions
         */
        public function setContribution($contribution)
        {
            $this->_contribution = $contribution;
            return $this;
        }

        /**
         * @return object
         */
        public function getSelectedContribution()
        {
            return $this->_selectedContribution;
        }

        /**
         * @param object $selectedContribution
         * @return PendingContributions
         */
        public function setSelectedContribution($selectedContribution)
        {
            $this->_selectedContribution = $selectedContribution;
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
         * @return PendingContributions
         */
        public function setContributionPage($contributionPage)
        {
            $this->_contributionPage = $contributionPage;
            return $this;
        }

        /**
         * @return array
         */
        public function getContributions()
        {
            return $this->_contributions;
        }

        /**
         * @param array $contributions
         * @return PendingContributions
         */
        public function setContributions($contributions)
        {
            $this->_contributions = $contributions;
            return $this;
        }

        /**
         * @return boolean
         */
        public function isIsPending()
        {
            return $this->_isPending;
        }

        /**
         * @param boolean $isPending
         * @return PendingContributions
         */
        public function setIsPending($isPending)
        {
            $this->_isPending = $isPending;
            return $this;
        }

        /**
         * @return boolean
         */
        public function isIsOwner()
        {
            return $this->_isOwner;
        }

        /**
         * @param boolean $isOwner
         * @return PendingContributions
         */
        public function setIsOwner($isOwner)
        {
            $this->_isOwner = $isOwner;
            return $this;
        }

        /**
         * @return int
         */
        public function getContributionOwner()
        {
            return $this->_contributionOwner;
        }

        /**
         * @param int $contributionOwner
         * @return PendingContributions
         */
        public function setContributionOwner($contributionOwner)
        {
            $this->_contributionOwner = $contributionOwner;
            return $this;
        }

        /**
         * @return boolean
         */
        public function isIsError()
        {
            return $this->_isError;
        }

        /**
         * @param boolean $isError
         * @return PendingContributions
         */
        public function setIsError($isError)
        {
            $this->_isError = $isError;
            return $this;
        }

        /**
         * @return string
         */
        public function getErrorMessage()
        {
            return $this->_errorMessage;
        }

        /**
         * @param string $errorMessage
         * @return PendingContributions
         */
        public function setErrorMessage($errorMessage)
        {
            $this->_errorMessage = $errorMessage;
            return $this;
        }

        /**
         * @return object
         */
        public function getForm()
        {
            return $this->_form;
        }

        /**
         * @param object $form
         * @return PendingContributions
         */
        public function setForm($form)
        {
            $this->_form = $form;
            return $this;
        }
    }
}