<?php

namespace app\admin\controller\rfid;

use app\admin\model\rfid\RfidBindDealer;
use app\admin\model\rfid\RfidBindUser;
use app\common\controller\Backend;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;

use app\admin\model\rfid\Revise;

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

    // 只允许创建者读取
    protected $dataLimit = false;
//
    protected $dataLimitField = '';

    protected $noNeedLogin = ['generate_write_page'];
    protected $noNeedRight = ['look','get_dealers','check_has_apply'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\rfid\Rfid;

//        dump($this->auth->getGroups());
//        dump($this->request->action());
        if ($this->request->action() != "generate_write_page"){
            // 查看自己相关
            if ($this->auth->getGroupIds()[0] == config("group.manufacturer")) {
                $this->dataLimit = 'personal';
                $this->dataLimitField = 'create_user_id';
            }
            if ($this->auth->getGroupIds()[0] == config("group.sales_manufacturer")) {
                $this->dataLimit = 'personal';
                $this->dataLimitField = 'dealer_id';
            }
        }

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

            $params['batch_number'] = str_replace("-","",$params['batch_number']);
            $params['r_id'] = $this->generateRfidParam($params);
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
//        dump(session("admin.id"));
        return $this->view->fetch();
    }

    /**
     * RFID写入
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundExceptions
     */
    public function write(){
        if ($this->request->isPost()){
            $id = $this->request->param("id");
            $isWrite = $this->checkRfid($id);
            if ($isWrite !== true){
                return json($isWrite);
            }
            $this_model  = $this->model->find($id);
            if ($this_model->getData("is_write") != 1){
                $result = $this->model->save([
                    'is_write' => 1,
                    'write_time'=> time()
                ],['id'=>$id]);
                return json($result ? __('Write_0') : __('Write_1'));
            }else{
                return json(__('Write_repeat'));
            }
        }else{
            $this->view->fetch();
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
                    $this->error(__("Operation_0"));
                }
            }else{
                $this->error(__("Operation_1"));
            }
        }
        $id = $this->request->param("ids");
        if ($this->checkIsAssign($id)) return $this->view->fetch("tips",['tips'=>__("Already_to_dealer")]);
        if ($this->checkRfid($id) !== true) return $this->view->fetch("tips",['tips'=>$this->checkRfid($id)]);
        if ($this->checkIsWrite($id)) return $this->view->fetch("tips",['tips'=>__("Wait_to_dealer_rfid_not_write")]);

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
        return json(['list'=>$data ? $data : [__("Get_null")],'total'=>count($data)]);
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
                    return $this->error(__("Validate_username_error"));
                }
                if(!preg_match($id_card,$params['idcard'])){
                    return $this->error(__("Validate_user_id_card_error"));
                }
                $data = [
                    'rfid_id'=>$rfid_id,
                    'dealer_id'=>$this->auth->id,
                    'user_name'=>$params['user_name'],
                    'idcard'=>$params['idcard'],
                    'create_time'=>time()
                ];
                // 更改状态..写入数据您已分配用户，请在详情中查看
                $bindDealer = Db::name("rfid_bind_user")->insert($data);
                if ($bindDealer){
                    $changeStatus = Db::name("rfid")->where(['id'=>$rfid_id])->update(['is_to_user'=>1]);
                    if ($changeStatus){
                        $this->success();
                    }else{
                        $this->error(__("Operation_0"));
                    }
                }else{
                    $this->error(__("Operation_1"));
                }
            }
        }
        $id = $this->request->param("ids");
        if ($this->checkRfid($id) !== true) return $this->view->fetch("tips",['tips'=>$this->checkRfid($id)]);
        if (!$this->checkIsAssign($id)) return $this->view->fetch("tips",['tips'=>__("Wait_to_dealer")]);
        if ($this->checkIsUsed($id)) return $this->view->fetch("tips",['tips'=>__("Already_to_user")]);
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
        if ($result['is_write'] != 1){
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
        $data['operation']['record'] = $this->ReacordOperation($id);
        return $this->view->fetch('rfid_info',$data);
    }

    /**
     * 提交修改基本属性
     * @param $id
     */
    public function submit_revision_attr($ids = null,$type=""){
        $this->checkRfid($ids);
        // 检查状态
        $row = $this->model->where(["id"=>$ids])->column("id,rfid_attr_countries_id,rfid_attr_es_id,rfid_attr_productname_id,rfid_attr_specs_id,batch_number,serial_number")[$ids];
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        foreach ($row as $key => &$value){
            if ($key == "id") continue;
            $value = (string)sprintf("%02d",$value);
        }
//        $adminIds = $this->getDataLimitAdminIds();
//        if (is_array($adminIds)) {
//            if (!in_array($row[$this->dataLimitField], $adminIds)) {
//                $this->error(__('You have no permission'));
//            }
//        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $params['batch_number'] = str_replace("-","",$params['batch_number']);
                $data['type'] = $params['type'];
                $data['reason'] = $params['reason'];
                unset($params['type'],$params['reason']);
                $diff = array_diff_assoc($params,$row);
                if (!$diff){ $this->error(__("No_processing"));}
                // 新旧数据处理
                foreach ($diff as $key => &$value){
                    // data: after => before
                    $value = $row[$key]."=>".$value;
                }
                $data['attr'] = json_encode($diff);
                $data['submission_id'] = $this->auth->id;
                $data['rfid_id'] = $ids;
                // 添加记录
                // 先修改上一个状态为1,再添加新的记录  同时修改自身状态为3
                Db::startTrans();
                try{
                    $result1 = \app\admin\model\rfid\Revise::update(["status"=>1],['rfid_id'=>$ids,'submission_id'=>$this->auth->id,'option'=>0]);
                    $result2 = \app\admin\model\rfid\Revise::create($data);
                    $result3 = $this->model->isUpdate(true)->save(['status'=>3,"id"=>$ids]);
                    Db::commit();
                }catch (Exception $e){
                    Db::rollback();
                    $this->error($e->getMessage());
                }

                if ($result1 && $result2 && $result3) {
                    $this->success();
                }else{
                    $this->error(__("Operation_1"));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $id = $this->request->param("ids");
        if ($this->checkRfid($id) !== true) return $this->view->fetch("tips",['tips'=>$this->checkRfid($id)]);
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
            return ['list'=>[__("Get_null")],'total'=>0];
        } catch (ModelNotFoundException $e) {
            return ['list'=>[__("Get_null")],'total'=>0];
        } catch (DbException $e) {
            return ['list'=>[__("Get_null")],'total'=>0];
        }
        return ['list'=>$attr,'total'=>count($attr)];
    }

    /**
     * 操作记录
     * @param
     */
    public function ReacordOperation($r_id){
        // 查询修改记录
        $data['rfid_id'] = $r_id;
        $data['status'] = 0;
        $data['option'] = 1;
        $template = "%s在%s申请修改了%s:%s,原因:%s";
        $res_template = "由%s审核";
        // 查询Resive
        $data = Revise::where($data)->field(["submission_id","auditor_id","reason","type","attr","success","reject_reason"])->select();
        // 统计条数
        $result['res'] = array();
        $num = count($data);
        $result['num'] = 0;
        if ($num == 0){
            $result['res'] = __("Get_opt_null");
        }else{
            foreach ($data as $key){
                if ($key['type'] == 1) $key['type'] = "属性";
                $key['reason'] = $key['reason'] ? $key['reason'] : "无";
                $key['reject_reason'] = $key['reject_reason'] ? $key['reject_reason'] : "无";
                if ($key['success'] == 1){
                    $notice = sprintf($template,$key['submission_id'][0],$key['create_time'],$key['type'],$key['attr'],$key['reason'])."。由{$key['auditor_id'][0]}审核通过<br/>";
                }
                if ($key['success'] == 0){
                    $notice = sprintf($template,$key['submission_id'][0],$key['create_time'],$key['type'],$key['attr'],$key['reason'])."。由{$key['auditor_id'][0]}审核,驳回,原因:{$key['reject_reason']}<br/>";
                }
                array_push($result['res'],$notice);
            }
        }
        $result['num'] = $num;
        return $result;
    }

    /**
     * 回收
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function recovery(){
        if ($this->request->isPost()){
            $id = $this->request->param("ids");
            $this_model  = $this->model->find($id)->toArray();
            if ($this_model["status"] != 10){
                $result = $this->model->save([
                    'status' => 10,
                    'update_time'=> time()
                ],['id'=>$id]);
                $this->success($result ? __("Recover_0") : __("Recover_1"));
            }else{
                $this->error(__("Recover_repeat"));
            }
        }else{
            $this->error(__('Network error'));
        }
    }

    /**
     * 生成RFID编号所需数组
     * @param $param
     * @return array
     */
    public function generateRfidParam($params){

        $result = array();
        // 组成部分            // 顺序
        // 省级+当前企业编号
        $result['provice'] = $params['rfid_attr_countries_id'] . $params['create_user_id'];
        // 产品名称
        $result['paoductname'] = $params['rfid_attr_productname_id'];
        // 产品规格
        $result['specs'] = $params['rfid_attr_specs_id'];
        // 执行标准+流水号
        $result['es'] = $params['rfid_attr_es_id'] != "00" ? $params['rfid_attr_es_id']."00" :  $params['rfid_attr_es_id'].$params['serial_number'];
        // 产品批号（年月）
        $result['batch'] = $params['batch_number'];
        // 流水号
        $result['serial'] = (string)sprintf("%02d",$params['serial_number']);

        return join("",$result);
    }

    /**
     * 检查用户是否提交过申请
     */
    public function check_has_apply(){
        if ($this->request->isPost()){
            $result = \app\admin\model\rfid\Revise::get(['rfid_id'=>$this->request->param("ids"),'submission_id'=>$this->auth->id,"option"=>0,"status"=>0]);
            return !$result || $result == NULL
                ? json(["status"=>0,"msg"=>__('Check_has_apply_tips_0')])
                : json(["status"=>1,"msg"=>__('Check_has_apply_tips_1')]);
        }else{
            $this->error(__('Network error'));
        }
    }

    /**
     * 权限检查
     */
    public function checkRfid($id,$rule=''){
        /**
         * 状态如果是损坏、异常、待回收、已回收、在修改状态，所有操作不允许
         *
         * 1. 检查状态
         * 2. 检查是否是该账户下拥有
         * 3. 重订属性
         */

        $data = $this->model->find($id);
        $status = $data['status'];
        if ($status != 0){
            $status_text = $this->model->getStatus($status);
            return __("Status_error",$status_text);
        }
        return true;
    }

    /**
     * 生成临时写入页面
     */
    public function generate_write_page(){
        // 设置有效时长
//        return "dddd";
        // 生成序列号，前面加00000
        $rfid_id = $this->request->param("id");
        if (empty($rfid_id)) return "拒绝访问";
        // 查询ID是否正确
        $data = $this->model->where(["id"=>$rfid_id])->field(["id","r_id"])->find()->toArray();

//        dump(count($data));

        if (!$data || count($data) == 0) return "ID错误";

        $data['r_id'] = (string)"000000".$data['r_id'];
//        dump($data['r_id']);
        $this->assign("rfid",$data['r_id']);
        return $this->view->fetch("w_page");
    }
}
