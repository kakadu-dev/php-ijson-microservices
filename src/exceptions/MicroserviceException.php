<?php
/**
 * Created by mikhail.
 * Date: 5/8/20
 * Time: 16:21
 */

namespace Kakadu\Microservices\exceptions;

/**
 * Class    MicroserviceException
 *
 * @package Kakadu\Microservices\exceptions
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class MicroserviceException extends BaseException
{
    /**
     * @var int
     */
    protected $code = 10;

    /**
     * @var string
     */
    protected $message = 'Unknown microservice exception.';

    /**
     * @var string
     */
    public static string $service = 'microservice';
}
