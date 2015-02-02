<?php
namespace scoop\persistence\driver

class SQLException extends Exception {
  /** Information that provides additional information for context of Exception (e.g. SQL statement or DSN). */
  protected $userInfo;

  /** Native RDBMS error string */
  protected $nativeError;

  /**
    * Constructs a SQLException.
    * @param string $msg Error message
    * @param string $native Native DB error message.
    * @param string $userinfo More info, e.g. the SQL statement or the connection string that caused the error.
  */
  public function __construct($msg, $native = null, $userinfo = null) {
    parent::__construct($msg);
    if ($native !== null) {
      $this->setNativeError($native);
    }
    if ($userinfo !== null) {
      $this->setUserInfo($userinfo);
    }
  }

  /**
    * Sets additional user / debug information for this error.
    *
    * @param array $info
    * @return void
  */
  public function setUserInfo($info) {
    $this->userInfo = $info;
    $this->message .= " [User Info: " .$this->userInfo . "]";
  }


  /**
    * Returns the additional / debug information for this error.
    *
    * @return array hash of user info properties.
  */
  public function getUserInfo() {
    return $this->userInfo;
  }

  /**
    * Sets driver native error message.
    *
    * @param string $info
    * @return void
    */
  public function setNativeError($msg) {
    $this->nativeError = $msg;
    $this->message .= " [Native Error: " .$this->nativeError . "]";
  }

  /**
    * Gets driver native error message.
    *
    * @return string
  */
  public function getNativeError() {
    return $this->nativeError;
  }

  /**
    * @deprecated This method only exists right now for easier compatibility w/ PHPUnit!
  */
  public function __toString() {
    return $this->getMessage();
  }
}
