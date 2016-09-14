<?php

/**
 * State machine for managing different states of Pending Payment process.
 *
 */
class CRM_Pendingcontribution_StateMachine_PendingContribution extends CRM_Core_StateMachine {

  /**
   * Class constructor.
   *
   * @param CRM_Core_Controller $controller
   * @param \const|int $action
   *
   * @return CRM_Contribute_StateMachine_Contribution
   */
  public function __construct($controller, $action = CRM_Core_Action::NONE) {
    parent::__construct($controller, $action);

    $this->_pages = array(
      'CRM_Pendingcontribution_Form_PaymentProcessor_Main' => NULL,
      'CRM_Pendingcontribution_Form_PaymentProcessor_Confirm' => NULL,
      'CRM_Pendingcontribution_Form_PaymentProcessor_ThankYou' => NULL,
    );

    $this->addSequentialPages($this->_pages, $action);
  }

}
