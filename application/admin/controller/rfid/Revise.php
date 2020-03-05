<?php

namespace app\admin\controller\rfid;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use app\admin\model\rfid\Rfid as RfidModel;
use app\admin\model\rfid\Revise as ReviseModel;
use think\Lang;

/**
 * 操作记录管理
 *
 * @icon fa fa-circle-o
 */
class Revise extends Backend
{
    
    /**
     * Revise模型对象
     * @var \app\admin\model\rfid\Revise
     */
    protected $model = null;

    protected $noNeedRight = ['*'];

    // 只允许创建者读取
    protected $dataLimit = false;
//
    protected $dataLimitField = '';

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\rfid\Revise;

        // 提交者ID
//        if ($this->auth->getGroupIds()[0] == config("group.manufacturer")) {
        if (in_array($this->auth->getGroupIds()[0],config("group"))) {
            $this->dataLimit = 'personal';
            $this->dataLimitField = 'submission_id';
        }
    }

    public function index($tag="")
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        $oneself = $this->request->param("oneself");

        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
//             设置不同状态的方式
//            if ($tag == 0) $additional = ['status'=>0];
//            if ($tag == 1 ) $additional = ['option'=>1,'status'=>0];
//            if ($tag == 2 ) $additional = ['option'=>0,'status'=>0];
            $additional = [];
            if ($oneself) {
//                $additional['submission_id'] = $this->auth->id;
                // 移除状态为0，自己可查看自己提价的所有
//                unset($additional['status']);
            }else{
                $additional['status'] = 0;
            }


            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($additional)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->where($additional)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        // 设置标题
        $siteList = [];
        $groupList = config("rfid.revise_process");
        foreach ($groupList as $k => $v) {
            $siteList[$k]['name'] = $k;
            $siteList[$k]['title'] = __($v);
        }
        $index = 0;
        $tags = ['0','1','2'];
        foreach ($siteList as $k => &$v) {
            $v['active'] = !$index ? true : false;
            $v['tag'] = $tags[$index];
            $index++;
        }
        if ($oneself) $this->assignconfig("oneself",true);
        $this->view->assign("siteList",$siteList);
        return $this->view->fetch();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 审核
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    public function option(){
        if ($this->request->isPost()){
            $id =  $this->request->param("ids");
            $type = $this->request->param("type");
            $data['option'] = 1;
            $data['auditor_id'] = $this->auth->id;
            $data['reject_reason'] = $type == 0 ? "" : $this->request->param("reason");
            $data['option_time'] = time();
            // 获取当前修改的信息，重新排列组合
            // 通过后修改RFID信息，如果操作是修改属性的话
            if ($type == 0){
                $result = false; // RFID 表
                $result1 = false;// 申请表
                Db::startTrans();
                try{
                    $data['success'] = 1;
                    // 获取这次修改的属性及RFID
                    $rfid = $this->model->where(["id"=>$id])->column("id,rfid_id,attr")[$id];
                    $r_data = [];
                    // 得到新修改的信息
                    foreach(json_decode($rfid['attr']) as $key => $value){
                        $r_data[$key] = substr($value,stripos($value,">")+1);
                    }
                    // 获取到之前的信息
                    $rfid_info = (new \app\admin\model\rfid\Rfid)->where(["id"=>$rfid['rfid_id']])->column("id,rfid_attr_countries_id,rfid_attr_es_id,rfid_attr_productname_id,rfid_attr_specs_id,batch_number,serial_number,create_user_id")[$rfid['rfid_id']];
                    $params = array_merge($rfid_info,$r_data);
                    // 格式化数据
                    foreach ($params as $key => &$value){
                        $value = (string)sprintf("%02d",$value);
                    }
                    $r_id = (new Rfid())->generateRfidParam($params);
                    // 得到新的RFID编号
                    $params['r_id'] = $r_id;
                    // 重置写入状态
                    $params['is_write'] = 0;
                    $params['status'] = 0;// 回复正常状态
                    $result = RfidModel::update($params);
                    $data['id'] = $id;
                    $result1 = ReviseModel::update($data);
                    Db::commit();
                }catch (Exception $e){
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result && $result1) $this->success();
                else $this->error("操作失败");
            }
            // 驳回
            if ($type == 1){
                $data['success'] = 0;
                $result = ReviseModel::update($data,['id'=>$id]);
                if ($result) $this->success();
                else $this->error("操作失败");
            }
        }else{
            // 填写驳回原因
            $id = $this->request->param("ids");
            $this->assign("id",$id);
            return $this->view->fetch("reason");
        }
    }

    /**
     * 撤回
     */
    public function withdraw(){
        if ($this->request->isAjax()){
            $id = $this->request->param("ids");
            $data['option_time'] = time();
            $data['id'] = $id;
            $data['status'] = 1;
            $result = false;
            $result2 = false;
            Db::startTrans();
            try{
                $result = ReviseModel::update($data);
                // 获取到这个ID
                $rfid_id = (new ReviseModel())->where(["id"=>$id])->value("rfid_id");
//                dump($rfid_id);
                // 修改RFID表的状态为0
//                \app\admin\model\rfid\Rfid::update(['id'=>$id,'status'=>0]);
                $result2 = (new \app\admin\model\rfid\Rfid())->isUpdate(true)->save(['id'=>$rfid_id,'status'=>0]);
                Db::commit();
            }catch (Exception $e){
                Db::rollback();
                $this->error($e);
            }

            if ($result && $result2) $this->success("撤回成功");
            else $this->error("撤回失败");
        }else{
            $this->error(__('Network error'));
        }
    }
}
