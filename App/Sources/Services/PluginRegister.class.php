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
                `Plugin`.`Description` AS `description`
            FROM `Plugin`
            WHERE `Plugin`.`Id` = ?');

        $statement->bind_param('s', $pluginId);
        $statement->execute();
        $statement->bind_result($id, $name, $author, $link, $description);
        $statement->fetch();

        $plugin = new Plugin();
        $plugin->Id = $id;
        $plugin->Name = $name;
        $plugin->Author = $author;
        $plugin->Link = $link;
        $plugin->Description = $description;

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
            INSERT INTO `Plugin` (`Id`, `Name`, `Author`, `Link`, `Description`) VALUE (?, ?, ?, ?, ?)');

        $statement->bind_param('sssss', $plugin->Id, $plugin->Name, $plugin->Author, $plugin->Link, $plugin->Description);
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
            `Plugin`.Link = ?
            WHERE `Plugin`.`Id` = ?');
        $statement->bind_param('sssss', $plugin->Name, $plugin->Author, $plugin->Description,  $plugin->Link, $plugin->Id);
        $statement->execute();
        $statement->close();

        return $plugin;
    }
}
?>
