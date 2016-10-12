<?php
/**
 * Created by PhpStorm.
 * User: steverhoades
 * Date: 10/11/16
 * Time: 1:33 PM
 */

namespace OpenIDConnectClient\Validator;


trait ValidatorTrait
{

    protected $name;

    protected $message;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getMessage()
    {
        return $this->message;
    }
}
