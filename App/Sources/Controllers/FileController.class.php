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
            'postFilesForPlugin' => array(
                'methods' => array(
                    'POST' => array('method' => 'PostFilesForPlugin', 'authentication' => 'PostFiles')
                ),
                'controller' => $this,
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
            default:
                throw new NotFoundException(GeneralError::ResourceNotFound, 'The requested resource was not found on this server.');
        }
    }

    public function PostFilesForPlugin(string $pluginId, array $rawFiles = null)
    {
        self::ThrowErrorOnInvalidGuid($pluginId, 'Plugin id is malformed.');

        $plugin = $this->PluginRegister->GetPlugin($pluginId);

        if($plugin === null)
        {
            throw new NotFoundException(GeneralError::ResourceNotFound, 'No plugin with the id ' . $pluginId . ' found.');
        }

        if($rawFiles === null)
        {
            throw new BadRequestException(GeneralError::EmptyRequest, 'Files data not defined.');
        }

        if(!is_array($rawFiles))
        {
            throw new BadRequestException(GeneralError::PayloadMalformed, 'Files JSON is malformed.');
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
