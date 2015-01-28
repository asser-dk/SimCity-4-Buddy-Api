<?php
    class ApiKeyService implements IApiKeyService
    {
        private $MySql;

        public function __construct(mysqli $mySql)
        {
            $this->MySql = $mySql;
        }

        public function ValidateApiKey(string $apiKey, string $requiredPrivilege)
        {
            if(!Guid::IsValid($apiKey))
            {
                throw new BadRequestException(GeneralError::InvalidApiKey, 'API key is invalid.');
            }

            $key = $this->GetApiKey($apiKey);

            if($key === null)
            {
                throw new BadRequestException(GeneralError::InvalidApiKey, 'API key is invalid.');
            }

            if($key->Expiration !== null && new DateTime() > $key->Expiration)
            {
                $this->DeleteApiKey($key->Id);
                throw new BadRequestException(GeneralError::ExpiredApiKey, 'API key has expired.');
            }

            if(!in_array($requiredPrivilege, $key->Privileges, TRUE))
            {
                throw new ForbiddenException(GeneralError::InsufficientPermissions, 'Insufficient permissions for the specified API key.');
            }
        }

        public function DeleteApiKey(string $apiKey)
        {
            $statement = $this->MySql->prepare('
                DELETE
                    FROM `ApiKey`
                    WHERE `ApiKey`.`Id` = ?
                    LIMIT 1');
            $statement->bind_param('s', $apiKey);
            $statement->execute();
            $statement->close();
        }

        public function GetApiKey(string $apiKey)
        {
            $statement = $this->MySql->prepare('
                SELECT
                    `ApiKey`.`Id` AS `id`,
                    `ApiKey`.`Expiration` AS `expiration`,
                    `ApiKey`.`Privileges` AS `privileges`
                    FROM `ApiKey`
                    WHERE `ApiKey`.`Id` = ?');

            $statement->bind_param('s', $apiKey);
            $statement->execute();
            $statement->bind_result($id, $expiration, $privileges);
            if(!$statement->fetch())
            {
                return null;
            }

            $apiKeyModel = new ApiKey();
            $apiKeyModel->Id = $id;
            $apiKeyModel->Privileges = explode('|', $privileges);

            if($expiration !== null)
            {
                $apiKeyModel->Expiration = new DateTime($expiration);
            }

            $statement->close();

            return $apiKeyModel;
        }
    }
?>
