<?php
/**
 * Created by mikhail.
 * Date: 5/8/20
 * Time: 16:21
 */

namespace Kakadu\Microservices\exceptions;

/**
 * Class    GatewayException
 *
 * @package Kakadu\Microservices\exceptions
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class GatewayException extends BaseException
{
    /**
     * @var int
     */
    protected $code = 5;

    /**
     * @var string
     */
    protected $message = 'Unknown gateway exception.';

    /**
     * @var string
     */
    public static string $service = 'gateway';
}
