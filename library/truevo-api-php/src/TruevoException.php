<?php
/**
 * Created by PhpStorm.
 * User: smp
 * Date: 17/01/19
 * Time: 09:57 AM
 */

namespace Truevo;


class TruevoException extends \Exception
{
    public function __construct($message, $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}