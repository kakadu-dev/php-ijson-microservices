<?php
/**
 * Created by mikhail.
 * Date: 5/8/20
 * Time: 15:38
 */

namespace Kakadu\Microservices\exceptions;

/**
 * Class    BaseException
 *
 * @package Kakadu\Microservices\exceptions
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class BaseException extends \Exception
{
    /**
     * @var string
     */
    public static string $service = 'unknown';
    /**
     * @var int
     */
    protected $code = 0;
    /**
     * @var int
     */
    protected int $status = 0;
    /**
     * @var string
     */
    protected $message = 'Undefined error.';

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        $service = static::$service;

        return "Error: $this->message. Service: $service. Code: $this->code ($this->status).";
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function toJSON(): array
    {
        return [
            'code'    => $this->code,
            'status'  => $this->status,
            'service' => static::$service,
            'message' => $this->message,
        ];
    }
}
