<?php

require_once 'CRM/Core/Form.php';
require_once 'CRM/Pendingcontribution/Contribution.php';
require_once 'CRM/Pendingcontribution/PendingContributions.php';
require_once 'CRM/Pendingcontribution/RecurringContribution.php';

use \tech\vanagas\civicrm\extension\payment\pendingcontribution\Contribution as Contribution;
use \tech\vanagas\civicrm\extension\payment\pendingcontribution\PendingContributions as PendingContributions;
use \tech\vanagas\civicrm\extension\payment\pendingcontribution\RecurringContribution as RecurringContribution;

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Pendingcontribution_Form_PayPendingContributionsForm extends CRM_Core_Form
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
     * The result set of pending payments for the given contribution id.
     *
     * @var array
     */
    protected $_pendingContributions;

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
     * Custom debugging
     */
    function xdebug($somevar)
    {
        xdebug_enable();
        xdebug_var_dump($somevar);
    }

    /**
     * Set variables up before form is built.
     */
    public function preProcess()
    {
        /* get the reference to session object */
        $session = CRM_Core_Session::singleton();

        /* retrieve the logged in user id */
        $this->_id = $session->getLoggedInContactID();

        /* retrieve the value of parameter 'contribution' from URL */
        $this->_contribution = CRM_Utils_Request::retrieve('contribution', 'Positive');

        /* Generate the results of the pending payments for the provided contribution id */
        /* Contribution and ContributionPage objects get loaded by the end of this call */
        $this->_pendingContributions = new PendingContributions($this->_contribution, $this); /* TODO Handle return value (errors) */

        /* let the parent take over with any of its defaults */
        parent::preProcess();


        /* generate default URL for this module and set the context in session */
        $url = CRM_Utils_System::url('civicrm/pay-pending-contributions-form', 'reset=1');
        $session->pushUserContext($url);

        /* Set the title for the displayed page */
        CRM_Utils_System::setTitle(ts('Pay Pending Contributions'));
    }

    public function buildQuickForm()
    {
        parent::buildQuickForm();

        if(!is_null($this->_pendingContributions)) {
            /* Refresh for latest values */
            $this->_pendingContributions->setupFormVariables();
        }
    }

    public function postProcess()
    {
        parent::postProcess();
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
