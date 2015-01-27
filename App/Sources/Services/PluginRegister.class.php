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

        return $plugin;
    }
}
?>
