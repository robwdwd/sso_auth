<?php

/**
 * Validation class for Forms.
 *
 * The class performs validation functions for various forms.
 *
 * Other reusable code is included here including a trim function with sanitizing
 * option. Form validation for checking of form Tokens.
 */

namespace SSOAuth;

class Validate
{
    private $hasError = false;
    private $errorMessages = [];
    private $data;
    private $formTokenKey = 'FormToken';

    /**
     * @param array $data  array of data to validate, usually $_POST is passed
     *                     here
     * @param bool  $clean trim and sanitize data on construction. Default not.
     */
    public function __construct($data, $clean = false)
    {
        $this->data = $data;

        if (true === $clean) {
            $this->trim();
        }
    }

    /**
     * Trims all the values in the $data array. By default this also sanatizes
     * the values as well, removing all html entities and special characters.
     *
     * This should be run if not constucted with $clean = true to avoid xss
     * vulnerabilies etc.
     *
     * @param bool $sanitize run sanitize filter. Default true.
     */
    public function trim($sanitize = true)
    {
        array_walk_recursive($this->data, function (&$value) use ($sanitize) {
            $value = trim($value);
            if ($sanitize) {
                $value = filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
            }
        });
    }

    /**
     * Get the data array.
     *
     *
     * @return array the input as it stands
     */
    public function get()
    {
        return $this->data;
    }

    /**
     * Validates the Login form. Will set the error condition if errors are found
     * and adds any errors messages to the errors array.
     */
    public function validateLogin()
    {
        $this->errorMessages = [];

        $this->validForm();

        if (empty($this->data['username'])) {
            $this->errorMessages[] = 'Username is required.';
            $this->hasError = true;
        }

        if (empty($this->data['password'])) {
            $this->errorMessages[] = 'Password is required.';
            $this->hasError = true;
        }

        // Make username lower case.
        //
        $this->data['username'] = strtolower($this->data['username']);
    }


    /**
     * Sets the key to use for the form token.
     */
    public function setFormTokenKey($formTokenKey)
    {
        $this->formTokenKey = $formTokenKey;
    }

    /**
     * Does the current data set have errors.
     *
     * @return bool true if in an error state
     */
    public function hasError()
    {
        return $this->hasError;
    }

    /**
     * Error messages.
     *
     * @return array all error messages currently showing for this validation
     */
    public function errorMessage()
    {
        return $this->errorMessages;
    }

    /**
     * Checks to see if the form token that we generated on the page matches
     * what is in the session. Sets error if it does not.
     */
    private function validForm()
    {
        if (!isset($_SESSION[$this->formTokenKey]) || empty($_SESSION[$this->formTokenKey])) {
            $this->errorMessages[] = 'Form security error [102]';
            $this->hasError = true;

            return;
        }

        if (!isset($this->data[$this->formTokenKey]) || empty($this->data[$this->formTokenKey])) {
            $this->errorMessages[] = 'Form security error [103]';
            $this->hasError = true;

            return;
        }

        if ($this->data[$this->formTokenKey] != $_SESSION[$this->formTokenKey]) {
            $this->errorMessages[] = 'Form security error [104]';
            $this->hasError = true;

            return;
        }
    }
}
