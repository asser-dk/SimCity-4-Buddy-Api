<?php
class PluginRegister
{
    private $MySql;

    public function __construct(mysqli $mySqli)
    {
        $this->MySql = $mySqli;
    }

    public function GetPlugin(string $pluginId)
    {
        $statement = $this->MySql->prepare('
            SELECT
                `Plugin`.`Id` AS `id`,
                `Plugin`.`Name` AS `name`,
                `Plugin`.`Author` AS `author`,
                `Plugin`.`Link` AS `link`,
                `Plugin`.`Description` AS `description`,
                `Plugin`.`Version` AS `version`
            FROM `Plugin`
            WHERE `Plugin`.`Id` = ?');

        $statement->bind_param('s', strtoupper($pluginId));
        $statement->execute();
        $statement->bind_result($id, $name, $author, $link, $description, $version);
        $statement->fetch();

        $plugin = new Plugin();
        $plugin->Id = strtoupper($id);
        $plugin->Name = $name;
        $plugin->Author = $author;
        $plugin->Link = $link;
        $plugin->Description = $description;
        $plugin->Version = $version;

        $statement->close();

        if($plugin->Id === null)
        {
            return null;
        }

        return $plugin;
    }

    public function AddPlugin(Plugin $plugin)
    {
        $statement = $this->MySql->prepare('
            INSERT INTO `Plugin` (`Id`, `Name`, `Author`, `Link`, `Description`, `Version`) VALUE (?, ?, ?, ?, ?, ?)');

        $statement->bind_param('ssssss', strtoupper($plugin->Id), $plugin->Name, $plugin->Author, $plugin->Link, $plugin->Description, $plugin->Version);
        $statement->execute();
    }

    public function IsUrlInUse(string $link, $existingPluginId = null)
    {
        if ($existingPluginId == null)
        {
            $statement = $this->MySql->prepare('SELECT `Plugin`.`Id` AS `id` FROM `Plugin` WHERE `Plugin`.`Link` = ?');
            $statement->bind_param('s', $link);
        }else{
            $statement = $this->MySql->prepare('SELECT `Plugin`.`Id` AS `id` FROM `Plugin` WHERE `Plugin`.`Link` = ? AND `Plugin`.`Id` != ?');
            $statement->bind_param('ss', $link, $existingPluginId);
        }

        $statement->execute();
        $statement->bind_result($id);
        $statement->fetch();
        $statement->close();

        return $id !== null;
    }

    public function UpdatePlugin(Plugin $plugin)
    {
        $statement = $this->MySql->prepare('
            UPDATE `Plugin`
            SET
            `Plugin`.`Name` = ?,
            `Plugin`.`Author` = ?,
            `Plugin`.`Description` = ?,
            `Plugin`.Link = ?,
            `Plugin`.`Version` = ?
            WHERE `Plugin`.`Id` = ?');
        $statement->bind_param('ssssss', $plugin->Name, $plugin->Author, $plugin->Description,  $plugin->Link, $plugin->Version, strtoupper($plugin->Id));
        $statement->execute();
        $statement->close();

        return $plugin;
    }

    public function GetPlugins($page, $perPage, $orderByString)
    {
        $offset = PaginationHelper::CalculateOffset($page, $perPage);

        $statementString = '
            SELECT
                `Plugin`.`Id` AS `id`,
                `Plugin`.`Name` AS `name`,
                `Plugin`.`Author` AS `author`,
                `Plugin`.`Description` AS `description`,
                `Plugin`.`Link` AS `link`
            FROM `Plugin`
            ORDER BY ' . $orderByString . '
            LIMIT ?, ?';
        $statement = $this->MySql->prepare($statementString);
        $statement->bind_param('ii',$offset, $perPage);
        $statement->execute();
        $statement->bind_result($id, $name, $author, $description, $link);

        $plugins = [];
        while($statement->fetch())
        {
            $plugin = new Plugin();
            $plugin->Id = strtoupper($id);
            $plugin->Name = $name;
            $plugin->Author = $author;
            $plugin->Description = $description;
            $plugin->Link = $link;
            $plugins[] = $plugin;
        }

        $statement->close();

        return $plugins;
    }
}
?>
