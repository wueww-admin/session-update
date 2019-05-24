<?php


namespace SessionUpdate\Exception;


class UserDataParseException extends \RuntimeException
{
    /**
     * @var string
     */
    private $fieldName;

    public function __construct(string $fieldName, \Throwable $previous = null)
    {
        $this->fieldName = $fieldName;

        parent::__construct('Failed to parse field: ' . $fieldName, 0, $previous);
    }
}