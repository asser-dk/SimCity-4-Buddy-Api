<?php

class UnknownPluginRegister
{
    private $MySql;

    public function __construct(mysqli $mysqli)
    {
        $this->MySql = $mysqli;
    }

    public function GetPlugin(string $pluginId)
    {
        $statement = $this->MySql->prepare(
            'SELECT
                `UnknownPlugin`.`Id` AS `id`,
                `UnknownPlugin`.`Link` AS `link`
            FROM `UnknownPlugin`
            WHERE `UnknownPlugin`.`Id` = ?');

        $statement->bind_param('s', strtoupper($pluginId));
        $statement->execute();
        $statement->bind_result($id, $link);
        $statement->fetch();

        $plugin = new UnknownPlugin();
        $plugin->Id = strtoupper($id);
        $plugin->Link = $link;

        $statement->close();

        if ($plugin->Id === null)
        {
            return null;
        }

        return $plugin;
    }

    public function AddPlugin(UnknownPlugin $plugin)
    {
        $statement = $this->MySql->prepare('INSERT INTO `UnknownPlugin` (`Id`, `Link`) VALUE (?, ?)');

        $statement->bind_param('ss', strtoupper($plugin->Id), $plugin->Link);
        $statement->execute();
        $statement->close();
    }

    public function IncrementSubmissions(string $pluginId)
    {
        $statement = $this->MySql->prepare(
            'UPDATE `UnknownPlugin` SET `IdenticalSubmissions` = `IdenticalSubmissions` + 1 WHERE `Id` = ?');
        $statement->bind_param('s', $pluginId);
        $statement->execute();
        $statement->close();
    }

    public function TryGetPluginId(UnknownPlugin $plugin)
    {
        $statement = $this->MySql->prepare('SELECT `Id` FROM `UnknownPlugin` WHERE `Link` = ?');
        $statement->bind_param('s', $plugin->Link);
        $statement->execute();
        $statement->bind_result($id);
        $statement->close();

        return $id;
    }
}