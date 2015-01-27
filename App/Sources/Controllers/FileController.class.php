<?php
class FileController implements IController
{
    const MaxFilesPerPage = 100;

    private $Register;

    public function __construct(FileRegister $fileRegister)
    {
        $this->Register = $fileRegister;
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
            )
        );
    }

    public function ProcessRequest(string $memberName, array $arguments = null)
    {
        switch($memberName)
        {
            case 'GetAllFiles':
                return $this->GetAllFiles((int)$arguments['page'], (int)$arguments['perPage'], $arguments['orderBy']);
            default:
                throw new NotFoundException(GeneralError::ResourceNotFound, 'The requested resource was not found on this server.');
        }
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
