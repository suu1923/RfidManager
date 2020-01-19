<?php

namespace app\admin\controller\rfid;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
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

    public function _initialize()
    {
        parent::_initialize();

        $this->model = new \app\admin\model\rfid\Revise;
//        $this->view->assign("successList", $this->model->getSuccessList());
//        $this->view->assign("optionList", $this->model->getOptionList());

//        $this->view->assign("statusList", $this->model->getStatusList());
    }

    public function index($tag="")
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
//             设置不同状态的方式
//            if ($tag == 0) $additional = ['status'=>0];
            $additional = ['status'=>0];
//            if ($tag == 1 ) $additional = ['option'=>1,'status'=>0];
//            if ($tag == 2 ) $additional = ['option'=>0,'status'=>0];
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
            $data['reason'] = $type == 0 ? "" : $this->request->param(["reason"]);
            $data['option_time'] = time();
            // 获取当前修改的信息，重新排列组合
            // 通过后修改RFID信息，如果操作是修改属性的话
            if ($type == 0){
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
                    $params['is_write'] = 0;
                    $result = (new \app\admin\model\rfid\Rfid())->save($params,['id'=>$rfid['rfid_id']]);
                    $result1 = $this->model->save($data,['id'=>$id]);


                    if ($result && $result1) $this->success();
                    else $this->error("操作失败");
                    Db::commit();
                }catch (Exception $e){
                    Db::rollback();
                    $this->error($e->getMessage());
                }
            }

//            if ($result) $this->success();
//
//            else $this->error("审核失败");
        }else{
            $this->error(__('Network error'));
        }
    }

}
