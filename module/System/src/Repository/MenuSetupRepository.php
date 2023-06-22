<?php

namespace System\Repository;

use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use System\Model\MenuSetup;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;

class MenuSetupRepository implements RepositoryInterface {

    private $adapter;
    private $tableGateway;
    private $roleId;
    private $authService;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(MenuSetup::TABLE_NAME, $adapter);

        $this->authService = new AuthenticationService();
        $recordDetail = $this->authService->getIdentity();
        $this->roleId = $recordDetail['role_id'];
    }

    public function setRoleId($id) {
        $this->roleId = $id;
    }

    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function edit(Model $model, $id) {
        $this->tableGateway->update($model->getArrayCopyForDB(), [MenuSetup::MENU_ID => $id]);
    }

    public function fetchAll() {
        return $this->tableGateway->select([MenuSetup::STATUS => "E"]);
    }

    public function fetchById($id) {
        $result = $this->tableGateway->select([MenuSetup::MENU_ID => $id]);
        return $result->current();
    }

    public function delete($id) {
        $this->tableGateway->update([MenuSetup::STATUS => "D"], [MenuSetup::MENU_ID => $id]);
    }

    public function getMenuList($id) {
        $sql = "SELECT * FROM HRIS_MENUS WHERE STATUS='E'";

        $statement = $this->adapter->query($sql);
        $resultset = $statement->execute();

        $entitiesArray = array();
        $entitiesArray[-1] = "None";
        foreach ($resultset as $result) {
            If ($id != $result['MENU_ID']) {
                $entitiesArray[$result['MENU_ID']] = $result['MENU_NAME'];
            }
        }
        return $entitiesArray;
    }

    public function getHierarchicalMenu($parent_menu = null) {
        $where = "";
        if ($parent_menu == null) {
            $where .= " AND LEVEL=1";
        } else {
            $where .= " AND PARENT_MENU=" . $parent_menu;
        }

        $sql = "SELECT MENU_NAME,MENU_ID ,PARENT_MENU,ROUTE,ACTION,ICON_CLASS, LEVEL,CONNECT_BY_ISLEAF is_leaf FROM HRIS_MENUS WHERE STATUS = 'E'" . $where . " CONNECT BY PRIOR MENU_ID = PARENT_MENU START WITH PARENT_MENU IS NULL ORDER BY MENU_ID ASC";

        $statement = $this->adapter->query($sql);
        $resultset = $statement->execute();
        return $resultset;
    }

    public function getAllCHildMenu($menuId) {
        $sql = "SELECT MENU_ID,MENU_NAME,PARENT_MENU,STATUS, LEVEL
      FROM HRIS_MENUS WHERE STATUS='E'
      START WITH MENU_ID =" . $menuId . "
      CONNECT BY PRIOR MENU_ID = PARENT_MENU
      ORDER SIBLINGS BY MENU_ID";

        $statement = $this->adapter->query($sql);
        $resultset = $statement->execute();
        return $resultset;
    }

    public function getAllParentMenu($menuId) {
        $sql = "SELECT MENU_ID,MENU_NAME,PARENT_MENU,STATUS, LEVEL
      FROM HRIS_MENUS WHERE STATUS='E'
      START WITH MENU_ID =" . $menuId . "
      CONNECT BY PRIOR PARENT_MENU = MENU_ID
      ORDER SIBLINGS BY MENU_ID";

        $statement = $this->adapter->query($sql);
        $resultset = $statement->execute();
        return $resultset;
    }

    public function getMenuListOfSameParent($menuId) {
        return $this->tableGateway->select([MenuSetup::STATUS => "E", MenuSetup::PARENT_MENU => $menuId]);
    }

    public function getHierarchicalMenuWithRoleId($parent_menu = null) {
        $boundedParameter = [];
        $boundedParameter['roleId']=$this->roleId;
        $where = "";
        if ($parent_menu == null) {
            $where .= " AND PARENT_MENU IS NULL";
        } else {
            $boundedParameter['parentMenu']=$parent_menu;
            $where .= " AND PARENT_MENU=:parentMenu ";
        }
        $where .= " AND HR.ROLE_ID = :roleId " ;

        $sql = "SELECT IS_VISIBLE,MENU_NAME,HM.MENU_ID,PARENT_MENU,ROUTE,ACTION,ICON_CLASS
			             FROM HRIS_MENUS HM, HRIS_ROLE_PERMISSIONS HR
			            WHERE HM.STATUS = 'E'
			            AND HR.STATUS = 'E'
                     AND HM.MENU_ID = HR.MENU_ID
			            " . $where . "
			ORDER BY HM.MENU_INDEX ASC";

        $statement = $this->adapter->query($sql);
        $resultset = $statement->execute($boundedParameter);
        return $resultset;
    }

    public function checkMenuIndex($menuIdex, $menuId = null) {
        if ($menuId != null) {
            $select = MenuSetup::MENU_INDEX . "=" . $menuIdex . " AND " . MenuSetup::MENU_ID . "!=" . $menuId;
        } else {
            $select = MenuSetup::MENU_INDEX . "=" . $menuIdex;
        }
        $result = $this->tableGateway->select([$select]);
        return $result->current();
    }

}
