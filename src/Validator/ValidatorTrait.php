<?php

/**
 * @author Steve Rhoades <sedonami@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace OpenIDConnectClient\Validator;

trait ValidatorTrait
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $message;

    /** @var bool */
    protected $required;

    /**
     * @param string $name
     * @param bool $required
     */
    public function __construct($name, $required = false)
    {
        $this->name = $name;
        $this->required = $required;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function isRequired()
    {
        return $this->required;
    }
}
