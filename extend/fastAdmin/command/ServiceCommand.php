<?php
/**
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/8/8
 * Time: 9:57
 */

namespace fastAdmin\command;

use think\console\command\Make;

class ServiceCommand extends Make
{
    protected $type = "Service";

    protected function configure()
    {
        parent::configure();
        $this->setName('make:service')
            ->setDescription('Create a new service class');
    }

    protected function getStub()
    {
        return __DIR__ . '/../tpl/service.fast';
    }

    protected function getNamespace($appNamespace, $module)
    {
        return parent::getNamespace($appNamespace, $module) . '\service';
    }
}