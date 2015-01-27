<?php
    class ForbiddenException extends HttpStatusException
    {
        public function __construct(integer $errorCode, string $message)
        {
            parent::__construct(403, 'Forbidden', $errorCode, $message);
        }
    }
?>
