<?php

namespace app\admin\controller\rfid;

use app\common\controller\Backend;
use http\Exception;
use function Matrix\identity;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * rfid管理
 *
 * @icon fa fa-circle-o
 */
class Rfid extends Backend
{
    protected $noNeedRight = ['*'];
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

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     * @throws \think\Exception
     */


    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("list/a");
            if ($params['u_id'] == "Admin") $params["u_id"] = "01";
            // 防伪码
            $antiCode = "00";
            // 重组时间
            $params['p_bnumer'] = substr(substr($params['p_bnumer'],0,strrpos($params['p_bnumer'],"-")),2).substr($params['p_bnumer'],strripos($params['p_bnumer'],"-")+1);
            // 顺序
            // 省级+当前企业编号
            $com['one'] = $params['c_id'] . $params['u_id'];
            // 产品名称
            $com['two'] = $params['p_id'];
            // 产品规格
            $com['three'] = $params['s_id'];
            // 执行标准+流水号
            $com['four'] = $params['es'] != "00" ? $params['es']."00" :  $params['es'].$params['p_snumer'];
            // 产品批号（年月）
            $com['five'] = $params['p_bnumer'];
            // 流水号
            $com['six'] = $params['p_snumer'];

            $data['r_id'] = join("",$com).$antiCode;
            $data['is_write'] = 0;
            $data['create_user_id'] = session("admin.id");
//            020106120100160811
//            02010612010016081100
            if ($data) {
                $data = $this->preExcludeFields($data);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $data[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    $result = $this->model->allowField(true)->save($data);
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
            $this_model  = $this->model->find($id);
            if ($this_model->getData("is_write") != 1){
                $result = $this->model->save([
                    'is_write' => 1,
                    'write_time'=> time()
                ],['id'=>$id]);
                echo $result ? "写入成功" : "写入失败";
            }else{
                echo "请勿重复写入";
            }
        }else{
            $this->error(__('Network error'));
        }
    }

    /**
     * RFID信息读取,包含所有记录
     * @throws \think\Exception
     */
    public function look(){
        $data = "123123";
        $this->view->display("data",$data);
        return $this->view->fetch();
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


}
