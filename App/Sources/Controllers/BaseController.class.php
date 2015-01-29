<?php
abstract class BaseController implements IController
{
    public static function ThrowErrorOnNullOrEmptyString(string $value, string $message, integer $errorCode = null)
    {
        self::ThrowErrorOnNull($value, $message, $errorCode);

        if($value === '')
        {
            throw new BadRequestException($errorCode === null ? GeneralError::MissingParameter : $errorCode, $message);
        }
    }

    public static function ThrowErrorOnNull($value, string $message, integer $errorCode = null)
    {
        if($value === null)
        {
            throw new BadRequestException($errorCode === null ? GeneralError::MissingParameter : $errorCode, $message);
        }
    }

    public static function ThrowErrorOnInvalidGuid(string $guid, string $message)
    {
        if(!Guid::IsValid($guid))
        {
            throw new BadRequestException(GeneralError::MalformedId, $message);
        }
    }

    public static function ThrowErrorOnEmptyPayload(array $payload = null, string $message)
    {
        if($payload === null)
        {
            throw new BadRequestException(GeneralError::EmptyRequest, $message);
        }

        if(!is_array($payload))
        {
            throw new BadRequestException(GeneralError::PayloadMalformed, $message);
        }
    }
}
?>
