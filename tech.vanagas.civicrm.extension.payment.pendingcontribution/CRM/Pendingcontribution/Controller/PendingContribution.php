<?php

class CRM_Pendingcontribution_Controller_PendingContribution extends CRM_Core_Controller {

  /**
   * Class constructor.
   *
   * @param string $title
   * @param bool|int $action
   * @param bool $modal
   */
  public function __construct($title = NULL, $action = CRM_Core_Action::NONE, $modal = TRUE) {
    parent::__construct($title, $modal);

    $this->_stateMachine = new CRM_Pendingcontribution_StateMachine_PendingContribution($this, $action);

    // create and instantiate the pages
    $this->addPages($this->_stateMachine, $action);

    // add all the actions
    $uploadNames = $this->get('uploadNames');
    if (!empty($uploadNames)) {
      $config = CRM_Core_Config::singleton();
      $this->addActions($config->customFileUploadDir, $uploadNames);
    }
    else {
      $this->addActions();
    }
  }

  public function invalidKey() {
    $this->invalidKeyRedirect();
  }
}
