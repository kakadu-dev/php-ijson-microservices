<?php
/**
 * Created by mikhail.
 * Date: 5/8/20
 * Time: 14:55
 */

namespace Kakadu\Microservices\interfaces;

/**
 * Interface ILogDriver
 *
 * @package  Kakadu\Microservices\interfaces
 * @author   Yarmaliuk Mikhail
 * @version  1.0
 */
interface ILogDriver
{
    /**
     * Log microservice action
     *
     * @param string     $message
     * @param string     $type
     * @param string|int $id
     *
     * @return void
     */
    public function log(string $message, string $type, $id): void;
}
