<?php

namespace app\admin\controller\rfid;

use app\admin\model\rfid\RfidBindDealer;
use app\admin\model\rfid\RfidBindUser;
use app\common\controller\Backend;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;

/**
 * rfid管理
 *
 * @icon fa fa-circle-o
 */
class Rfid extends Backend
{
    
    /**
     * Rfid模型对象
     * @var \app\admin\model\rfid\Rfid
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\rfid\Rfid;

    }

    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params['create_user_id'] == "Admin") $params["create_user_id"] = "01";
            // 防伪码
//            $antiCode = "00";
            $params['batch_number'] = str_replace("-","",$params['batch_number']);
            // 组成部分            // 顺序
            // 省级+当前企业编号
            $com['one'] = $params['rfid_attr_countries_id'] . $params['create_user_id'];
            // 产品名称
            $com['two'] = $params['rfid_attr_productname_id'];
            // 产品规格
            $com['three'] = $params['rfid_attr_specs_id'];
            // 执行标准+流水号
            $com['four'] = $params['rfid_attr_es_id'] != "00" ? $params['rfid_attr_es_id']."00" :  $params['rfid_attr_es_id'].$params['serial_number'];
            // 产品批号（年月）
            $com['five'] = $params['batch_number'];
            // 流水号
            $com['six'] = $params['serial_number'];
//            $params['r_id'] = join("",$com).$antiCode;
            $params['r_id'] = join("",$com);
            $params['is_write'] = 0;
            $params['create_user_id'] = session("admin.id");

            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }
    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundExceptions
     * RFID写入
     */
    public function write(){
        if ($this->request->isPost()){
            $id = $this->request->param("id");
            if (!$this->checkStatus($id)) return "该标签已经无法操作";
            $this_model  = $this->model->find($id);
            if ($this_model->getData("is_write") != 1){
                $result = $this->model->save([
                    'is_write' => 1,
                    'write_time'=> time()
                ],['id'=>$id]);
                return json($result ? "写入成功" : "写入失败");
            }else{
                return json("请勿重复写入");
            }
        }else{
            $this->error(__('Network error'));
        }
    }


    /**
     * 分配至经销商
     * @throws DbException
     */
    public function assign_to_dealer(){
        if ($this->request->isPost()){
            $dealer_id = $this->request->param("dealer_id");
            $rfid_id = $this->request->param("ids");

            // 插入数据
            $data = [
                'rfid_id'=>$rfid_id,
                'operator_id'=>$this->auth->id,
                'dealer_id'=>$dealer_id,
                'create_time'=>time()
            ];
            $bindDealer = Db::name("rfid_bind_dealer")->insert($data);
            if ($bindDealer){
                $changeStatus = Db::name("rfid")->where(['id'=>$rfid_id])->update(['is_to_dealer'=>1,'dealer_id'=>$dealer_id]);
                if ($changeStatus){
                    $this->success();
                }else{
                    $this->error("操作失败");
                }
            }else{
                $this->error("操作失败");
            }
        }
        $id = $this->request->param("ids");
        if (!$this->checkIsWrite($id)) return "请先写入RFID再分配经销商";
        if ($this->checkIsAssign($id)) return "已经分配至经销商";

        return $this->view->fetch('dealer_choose');
    }

    /**
     * 获取全部经销商
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function get_dealers()
    {
        $data = Db::table("fa_auth_group_access")
            ->alias("aga")
            ->join("fa_admin admin", "aga.uid = admin.id")
            ->field("id,username as name")
            ->where(["group_id" => 8, "status" => 'normal'])  // 这里是8代表目前经销商的组ID
            ->select();
        return json(['list'=>$data ? $data : ["暂无"],'total'=>count($data)]);
    }
    /**
     * 经销商分配至用户
     * @throws DbException
     * @throws \think\Exception
     */
    public function assign_to_user(){
        if ($this->request->isPost()){
            $params = $this->request->param();
            if ($params) {
                $rfid_id = $this->request->param("ids");
                $unmae = "/^[\x{4e00}-\x{9fa5}]+$/u";
                $id_card = "/(^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$)|(^[1-9]\d{5}\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{2}$)/";
                if (!preg_match($unmae,$params['user_name'])){
                    return $this->error('用户名格式错误');
                }
                if(!preg_match($id_card,$params['idcard'])){
                    return $this->error('身份证格式错误');
                }
                // 插入数据
                $data = [
                    'rfid_id'=>$rfid_id,
                    'dealer_id'=>$this->auth->id,
                    'user_name'=>$params['user_name'],
                    'idcard'=>$params['idcard'],
                    'create_time'=>time()
                ];
                // 更改状态..写入数据
                $bindDealer = Db::name("rfid_bind_user")->insert($data);
                if ($bindDealer){
                    $changeStatus = Db::name("rfid")->where(['id'=>$rfid_id])->update(['is_to_user'=>1]);
                    if ($changeStatus){
                        $this->success();
                    }else{
                        $this->error("操作失败");
                    }
                }else{
                    $this->error("操作失败");
                }
            }
        }
        $rfid_id = $this->request->param("ids");
        // 已经分配
        if (!$this->checkStatus($rfid_id)) return "该标签已经无法操作";
        if (!$this->checkIsAssign($rfid_id)) return "请等待生成厂家分配";
        if ($this->checkIsUsed($rfid_id)) return "您已分配用户，请在详情中查看";

        return $this->view->fetch("user_add");

    }

    /**
     * 检测是否分配至经销商
     * @param $id
     * @return bool
     * @throws DbException
     */
    private function checkIsAssign($id){
        $result = \app\admin\model\rfid\Rfid::get($id);
        if ($result['is_to_dealer'] == 1){
            return true;
        }
        return false;
    }

    /**
     * 检测是否写入
     * @param $id
     * @return bool
     * @throws DbException
     */
    private function checkIsWrite($id){
        $result = \app\admin\model\rfid\Rfid::get($id);
        if ($result['is_write'] == 1){
            return true;
        }
        return false;
    }

    /**
     * 检测是否分配用户
     * @param $id
     * @throws DbException
     */
    private function checkIsUsed($id){
        $result = \app\admin\model\rfid\Rfid::get($id);
        if ($result['is_to_user'] == 1){
            return true;
        }
        return false;
    }

    /**
     * 查看详细信息
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function look(){
        $id = $this->request->param("ids");
        // 基本信息
        $data['baseInfo'] = $this->model->find($id);
        if($data['baseInfo']->is_to_dealer == true){
            $data['operation']['to_dealer'] = RfidBindDealer::get(
                function ($query) use ($id){
                    $query->where(['rfid_id'=>$id])->field(["dealer_id","create_time"]);
                }
            )->toArray();
        }
        if($data['baseInfo']->is_to_user == true){
            $data['operation']['to_user'] = RfidBindUser::get(
                function ($query) use ($id){
                    $query->where(['rfid_id'=>$id])->field(["user_name","idcard","create_time"]);
                }
            )->toArray();
        }
        $data['operation']['record'] = NULL;
        return $this->view->fetch('rfid_info',$data);
    }

    /**
     * 检查状态
     * @param $id
     * @return bool
     * @throws DbException
     */
    private function checkStatus($id){
        $result = \app\admin\model\rfid\Rfid::get($id);
        if ($result['status'] != 0 ) return false;
    }

    /**
     * 提交修改基本属性
     * @param $id
     */
    public function submit_revision_attr($ids = null){
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
//        $info['countries'] = \app\admin\model\rfid\AttrCountries::all();
        $info['countries'] = Db::name('rfid_attr_countries')->field('c_id,name')->select();
        $info['es'] = \app\admin\model\rfid\AttrEs::all();
        $info['specs'] = \app\admin\model\rfid\AttrSpecs::all();
        $info['product_name'] = \app\admin\model\rfid\AttrProductName::all();
        $this->view->assign("info",$info);
        $this->view->assign("row", $row);
        return $this->view->fetch('edit_rfid_attr');
    }

    // 获取属性数据
    public function getRfidRegParams($table,$key){
        try {
            $attr = Db::name("rfid_attr_".$table)->order("create_time desc")->field($key . ",name")->select();
        } catch (DataNotFoundException $e) {
            return ['list'=>["暂无数据，等待管理员添加"],'total'=>0];
        } catch (ModelNotFoundException $e) {
            return ['list'=>["暂无数据，等待管理员添加"],'total'=>0];
        } catch (DbException $e) {
            return ['list'=>["暂无数据，等待管理员添加"],'total'=>0];
        }
        return ['list'=>$attr,'total'=>count($attr)];
    }

    /**
     * 操作记录
     * @param $
     */
    public function ReacordOperation(){

    }

    /**
     * 回收
     * @return \think\response\Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function recovery(){
        if ($this->request->isGet()){
            $id = $this->request->param("id");
            $this_model  = $this->model->find($id);
            if ($this_model->getData("status") != 1){
                $result = $this->model->save([
                    'status' => 1,
                    'update_time'=> time()
                ],['id'=>$id]);
                return json($result ? "回收成功" : "回收成功");
            }else{
                return json("请勿重复回收");
            }
        }else{
            $this->error(__('Network error'));
        }
    }
}
