<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Config;
use think\Db;

/**
 * 控制台
 *
 * @icon fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{

    /**
     * 查看
     */
    public function index()
    {
//        $seventtime = \fast\Date::unixtime('day', -7);
//        $paylist = $createlist = [];
//        for ($i = 0; $i < 7; $i++)
//        {
//            $day = date("Y-m-d", $seventtime + ($i * 86400));
//            $createlist[$day] = mt_rand(20, 200);
//            $paylist[$day] = mt_rand(1, mt_rand(1, $createlist[$day]));
//        }
//        $hooks = config('addons.hooks');
//        $uploadmode = isset($hooks['upload_config_init']) && $hooks['upload_config_init'] ? implode(',', $hooks['upload_config_init']) : 'local';
//        $addonComposerCfg = ROOT_PATH . '/vendor/karsonzhang/fastadmin-addons/composer.json';
//        Config::parse($addonComposerCfg, "json", "composer");
//        $config = Config::get("composer");
//        $addonVersion = isset($config['version']) ? $config['version'] : __('Unknown');
//        $this->view->assign([
//            'totaluser'        => 35200,
//            'totalviews'       => 219390,
//            'totalorder'       => 32143,
//            'totalorderamount' => 174800,
//            'todayuserlogin'   => 321,
//            'todayusersignup'  => 430,
//            'todayorder'       => 2324,
//            'unsettleorder'    => 132,
//            'sevendnu'         => '80%',
//            'sevendau'         => '32%',
//            'paylist'          => $paylist,
//            'createlist'       => $createlist,
//            'addonversion'       => $addonVersion,
//            'uploadmode'       => $uploadmode
//        ]);


//            return json($this->getT());


            echo  "<h1>Default Page</h1>";
//        return $this->view->fetch();
    }

    public  function  getT($pid=0){
        $tree = array();

        $data = Db::name("canada")->where(["pid"=>$pid])->select();
        foreach ($data as &$v){
//            dump($v["pid"]);
            $has = Db::name("canada")->where(["pid"=>$v["id"]])->select();
//            dump(count($has));
            if ($has){
//                dump($has);
                $v["son"] = $has;
            }else{
                $v["son"] = [];
            }
        }

//            if ($v["pid"] == $id){
//                $v["son"] = $this->getT($data,$v['id']);
//                if (empty($v['son'])) $v['son'] = [];
//                array_push($tree,$v);
//            }
//        }
        return $data;
    }

}
