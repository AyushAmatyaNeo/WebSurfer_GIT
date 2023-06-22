<?php

namespace System\Repository;

use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use System\Model\Setting;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;

class SettingRepository implements RepositoryInterface {

    private $adapter;
    private $tableGateway;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(Setting::TABLE_NAME, $adapter);
    }

    public function add(Model $model) {
        
    }

    public function delete($id) {
        
    }

    public function edit(Model $model, $id) {
        return $this->tableGateway->update($model->getArrayCopyForDB(), [Setting::USER_ID => $id]);
    }

    public function fetchAll() {
        
    }

    public function fetchById($id) {
        return $this->tableGateway->select([Setting::USER_ID => $id])->current();
    }

}
