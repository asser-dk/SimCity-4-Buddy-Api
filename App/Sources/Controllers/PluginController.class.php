<?php
class PluginController implements IController
{
    public function RouteTable()
    {
        return array(
            'plugin' => array(
                'methods' => array(
                    'GET' => array('method' => 'GetPlugin')
                ),
                'controller' => $this,
                'documentation' => array('/plugins/{pluginId]' => 'Get info on a specific plugin'),
                'regex' => '/^\/plugins\/[A-z0-9-]{36}$/',
                'arguments' => array(
                    'pluginId' => array(
                        'pattern' => '/^\/plugins\/([A-z0-9-]{36})$/',
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
            case 'GetPlugin':
                return $this->GetPlugin($arguments['pluginId']);
            default:
                throw new NotFoundException(GeneralError::ResourceNotFound, 'The requested resource was not found on this server.');
        }
    }

    public function GetPlugin(string $pluginId)
    {
        if(!Guid::IsValid($pluginId))
        {
            throw new BadRequestException(GeneralError::MalformedId, 'Plugin id is malformed.');
        }

        $plugin = $this->Register->GetPlugin($pluginId);

        if($plugin === null)
        {
            header('HTTP/1.1 404 Not Found');
        }
        else
        {
            header('HTTP/1.1 200 OK');
        }

        return $plugin;
    }
}
?>
