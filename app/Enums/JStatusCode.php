<?php
namespace App\Enums;

abstract class JStatusCode {

    // CLIENT SIDE STATUS
    public const SUCCESS = '200';
    public const ACCEPTED = '202';
    public const BAD_REQUEST = '400';
    public const UNAUTHORIZED = '401';
    public const FORBIDDEN = '403';
    public const NOT_FOUND = '404';
    public const UNPROCESSABLE_CONTENT = '422';


    // SERVER SIDE STATUS
    public const INTERNAL_SERVER_ERROR = '500' ;
    public const NOT_IMPLEMENTED = '501' ;
    public const BAD_GATEWAY = '502' ;
    public const SERVICE_UNAVAILABLE = '503' ;
    public const GATEWAY_TIMED_OUT = '504' ;
}