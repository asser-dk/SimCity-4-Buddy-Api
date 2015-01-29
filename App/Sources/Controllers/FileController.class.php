<?php
class FileController extends BaseController
{
    const MaxFilesPerPage = 100;

    private $Register;

    private $PluginRegister;

    public function __construct(FileRegister $fileRegister, PluginRegister $pluginRegister)
    {
        $this->Register = $fileRegister;
        $this->PluginRegister = $pluginRegister;
    }

    public function RouteTable()
    {
        return array(
            'allFiles' => array(
                'methods' => array(
                    'GET' => array('method' => 'GetAllFiles'),
                ),
                'regex' => '/^\/files$/',
                'controller' => $this,
                'documentation' => array('/files' => 'Lists all files known to the server.'),
                'arguments' => PaginationHelper::GetRoutePaginationArguments()
            ),
            'filesForPlugin' => array(
                'methods' => array(
                    'POST' => array('method' => 'PostFilesForPlugin', 'authentication' => 'PostFiles'),
                    'PUT' => array('method' => 'PutFilesForPlugin', 'authentication' => 'PutFiles'),
                    'GET' => array('method' => 'GetFilesForPlugin')
                ),
                'controller' => $this,
                'documentation' => array('/plugins/{pluginId}/files' => 'Lists all files for a specific plugin.'),
                'regex' => '/^\/plugins\/[A-z0-9-]{36}\/files$/',
                'arguments' => array(
                    'pluginId' => array(
                        'pattern' => '/^\/plugins\/([A-z0-9-]{36})/',
                        'index' => 1
                    )
                )
            )
        );
    }

    public function ProcessRequest(string $memberName, array $arguments = null)
    {
        switch($memberName)
        {
            case 'GetAllFiles':
                return $this->GetAllFiles((int)$arguments['page'], (int)$arguments['perPage'], $arguments['orderBy']);
            case 'PostFilesForPlugin':
                return $this->PostFilesForPlugin($arguments['pluginId'], $arguments['payload']);
            case 'PutFilesForPlugin':
                return $this->PutFilesForPluigin($arguments['pluginId'], $arguments['payload']);
            case 'GetFilesForPlugin':
                return $this->GetFilesForPlugin($arguments['pluginId']);
            default:
                throw new NotFoundException(GeneralError::ResourceNotFound, 'The requested resource was not found on this server.');
        }
    }

    public function GetFilesForPlugin(string $pluginId)
    {
        self::ThrowErrorOnInvalidGuid($pluginId, 'Plugin id is malformed.');

        $plugin = $this->PluginRegister->GetPlugin($pluginId);

        if($plugin === null)
        {
            throw new NotFoundException(GeneralError::ResourceNotFound, 'No plugin with the id '. $pluginId . ' found.');
        }

        header('HTTP/1.1 200 OK');
        return $this->Register->GetFilesForPlugin($pluginId);
    }

    private function PutFilesForPluigin(string $pluginId, array $payload = null)
    {
        self::ThrowErrorOnInvalidGuid($pluginId, 'Plugin id is malformed.');

        $plugin = $this->PluginRegister->GetPlugin($pluginId);

        if($plugin === null)
        {
            throw new NotFoundException(GeneralError::ResourceNotFound, 'No plugin with the id '. $pluginId . ' found.');
        }

        self::ThrowErrorOnEmptyPayload($payload, 'Request payload is malformed.');

        $existingFiles = $this->Register->GetFilesForPlugin($pluginId);

        $newFiles = [];
        $updatedFiles = [];
        $removedFiles = [];
        foreach($payload as $rawFile) {
            self::ThrowErrorOnNullOrEmptyString($rawFile['Checksum'], 'Checksum is missing.');
            self::ThrowErrorOnNullOrEmptyString($rawFile['Filename'], 'Filename is missing.');

            if (preg_match('/.+\..+/', $rawFile['Filename']) === FALSE) {
                throw new BadRequestException(GeneralError::InvalidParameter, 'Filename does not contain an extension.');
            }

            $file = new File();
            if($rawFile['Id'] !== null)
            {
                self::ThrowErrorOnInvalidGuid($rawFile['Id'], 'File id '. $rawFile['Id'] . ' is malformed.');
                foreach ($existingFiles as $existingFile) {
                    if ($existingFile->Id === $rawFile['Id']) {
                        $file->Id = $existingFile->Id;
                        break;
                    }
                }

                self::ThrowErrorOnNull($file->Id, 'File id '. $rawFile['Id'] . ' does not belong to this plugin.', GeneralError::ResourceNotFound);
            }
            else
            {
                $file->Id = Guid::NewGuid();
            }

            $file->Checksum = $rawFile['Checksum'];
            $file->Filename = $rawFile['Filename'];
            $file->Plugin = $pluginId;
            if($rawFile['Id'] === null)
            {
                $newFiles[] = $file;
            }else{
                $updatedFiles[] = $file;
            }
        }

        foreach ($existingFiles as $existingFile) {
            foreach ($updatedFiles as $updatedFile) {
                if($existingFile->Id == $updatedFile->Id)
                {
                    continue 2;
                }
            }

            $removedFiles[] = $existingFile;
        }

        foreach ($newFiles as $newFile) {
            $this->Register->AddFile($newFile);
        }

        foreach ($updatedFiles as $updatedFile) {
            $this->Register->UpdateFile($updatedFile);
        }

        foreach ($removedFiles as $removedFile) {
            $this->Register->RemoveFile($removedFile);
        }


        header('HTTP/1.1 200 OK');
        return $this->Register->GetFilesForPlugin($pluginId);
    }

    public function PostFilesForPlugin(string $pluginId, array $rawFiles = null)
    {
        self::ThrowErrorOnInvalidGuid($pluginId, 'Plugin id is malformed.');

        $plugin = $this->PluginRegister->GetPlugin($pluginId);

        if($plugin === null)
        {
            throw new NotFoundException(GeneralError::ResourceNotFound, 'No plugin with the id ' . $pluginId . ' found.');
        }

        self::ThrowErrorOnEmptyPayload($rawFiles, 'Request payload is malformed.');

        if($this->Register->HasFiles($pluginId))
        {
            throw new BadRequestException(GeneralError::ResourceAlreadyExists, 'There are already files defined for this plugin. Use PUT to update.');
        }

        foreach($rawFiles as $rawFile)
        {
            self::ThrowErrorOnNullOrEmptyString($rawFile['Checksum'], 'Checksum is missing.');
            self::ThrowErrorOnNullOrEmptyString($rawFile['Filename'], 'Filename is missing.');

            if(preg_match('/.+\..+/', $rawFile['Filename']) === FALSE)
            {
                throw new BadRequestException(GeneralError::InvalidParameter, 'Filename does not contain an extension.');
            }

            $file = new File();
            $file->Id = Guid::NewGuid();
            $file->Checksum = $rawFile['Checksum'];
            $file->Filename = $rawFile['Filename'];
            $file->Plugin = $pluginId;

            $this->Register->AddFile($file);
        }

        header('HTTP/1.1 201 Created');
        return $plugin;
    }

    public function GetAllFiles(integer $page, integer $perPage, string $orderBy)
    {
        PaginationHelper::ValidateAndSetPage($page);
        PaginationHelper::ValidateAndSetPerPage($perPage, self::MaxFilesPerPage);

        $map = array(
            'filename' => '`File`.`Filename`'
        );

        $orderByString = PaginationHelper::ValidateAndGenerateOrderByString($orderBy, $map, 'filename');

        $files = $this->Register->GetAllFiles($page, $perPage, $orderByString);

        if(count($files) > 0)
        {
            header('HTTP/1.1 200 OK');
        }
        else
        {
            header('HTTP/1.1 404 Not Found');
        }

        return $files;
    }
}
?>
