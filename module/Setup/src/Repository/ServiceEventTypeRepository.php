<?php

namespace Setup\Repository;

use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;
use Setup\Model\ServiceEventType;

class ServiceEventTypeRepository implements RepositoryInterface
{
    private $tableGateway;
    public function __construct(AdapterInterface $adapter)
    {
        $this->tableGateway=new TableGateway(ServiceEventType::TABLE_NAME,$adapter);
        $this->adapter = $adapter;
    }

    public function add(Model $model)
    {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function edit(Model $model,$id)
    {
        $array=$model->getArrayCopyForDB();
        unset($array[ServiceEventType::SERVICE_EVENT_TYPE_ID]);
        unset($array[ServiceEventType::CREATED_DT]);
        $this->tableGateway->update( $array,[ServiceEventType::SERVICE_EVENT_TYPE_ID=>$id]);
    }

    public function fetchAll()
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("S.SERVICE_EVENT_TYPE_ID as SERVICE_EVENT_TYPE_ID"),
            new Expression("S.SERVICE_EVENT_TYPE_CODE as SERVICE_EVENT_TYPE_CODE"),
            new Expression("S.SERVICE_EVENT_TYPE_NAME as SERVICE_EVENT_TYPE_NAME"),
        ], true);

        $select->from(['S' => ServiceEventType::TABLE_NAME]);
        $select->where(["S.STATUS" => 'E']);
        $statement = $sql->prepareStatementForSqlObject($select);

        return $statement->execute();
    }

    public function fetchById($id)
    {
        $rowset= $this->tableGateway->select([ServiceEventType::SERVICE_EVENT_TYPE_ID=>$id]);
        return $rowset->current();
    }
    public function fetchActiveRecord()
    {
        return  $rowset= $this->tableGateway->select([ServiceEventType::STATUS=>'E']);
    }

    public function delete($id)
    {
        $this->tableGateway->update([ServiceEventType::STATUS=>'D'],[ServiceEventType::SERVICE_EVENT_TYPE_ID=>$id]);
    }
}