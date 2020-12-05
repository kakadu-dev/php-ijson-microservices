<?php
/**
 * Created by mikhail.
 * Date: 5/8/20
 * Time: 15:00
 */

namespace Kakadu\Microservices;

use Kakadu\Microservices\interfaces\ILogDriver;

/**
 * Class    ConsoleLogDriver
 *
 * @package Kakadu\Microservices
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class ConsoleLogDriver implements ILogDriver
{
    /**
     * @var bool
     */
    protected bool $dummy = false;

    /**
     * ConsoleLogDriver constructor.
     *
     * @param bool $dummy
     */
    public function __construct(bool $dummy = false)
    {
        $this->dummy = $dummy;
    }

    /**
     * @inheritDoc
     */
    public function log(string $message, string $type, $id = null): void
    {
        if ($this->dummy) {
            return;
        }

        $color = "";

        switch ($type) {
            /**
             * 0 - in
             * 1 - out
             */
            case 0:
            case 1:
                $color = "\e[36m"; // cyan
            break;

            /**
             * 2 - in (internal)
             * 3 - out (internal)
             */
            case 2:
            case 3:
                $color = "\e[34m"; // blue
            break;

            case 4:
                $color = "\e[31m"; // red
            break;
        }

        echo "$color $message \n";
    }
}
