define(['jquery', 'bootstrap', 'backend', 'table', 'form','layer'], function ($, undefined, Backend, Table, Form,Layer) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'rfid/rfid/index' + location.search,
                    add_url: 'rfid/rfid/add',
                    table: 'rfid',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                dblClickToEdit: false,
                search:false,
                searchFormVisible:true,// 最后开放开
                columns: [
                    [
                        {field: 'id', title: __('Id')},
                        {field: 'r_id', title: __('R_id')},
                        {field: 'create_user_id', title: __('Create_user_id'),},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'is_write', title: __('Is_write'),formatter:Table.api.formatter.flag,custom:{'0':'danger',"1":'success'},searchList:{0:"未写入",1:"已写入"}},
                        {field: 'is_to_dealer', title: __('Is_to_dealer'),formatter:Table.api.formatter.flag,custom:{'0':'danger',"1":'success'},searchList:{0:"未分配",1:"已分配"}},
                        {field: 'is_to_user', title: __('Is_to_user'),formatter:Table.api.formatter.flag,custom:{'0':'danger',"1":'success'},searchList:{0:"未分配",1:"已分配"}},
                        {field: 'status', title: __('Status'),formatter:Table.api.formatter.flag,custom:{'0':'success',"1":'danger',"9":'warning',"10":"primary"},searchList:{0:"正常",1:"损坏",9:"待回收",10:"已回收"}},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons:[
                                {
                                    name:"submit_revision_attr",
                                    title:__('Sub_Edit_Attr'),
                                    classname:  'btn btn-xs btn-danger btn-dialog',
                                    text: __('Sub_Edit_Attr'),
                                    // icon:'fa fa-list',
                                    url:'rfid/rfid/submit_revision_attr',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
                                },
                                {
                                    name:"write",
                                    title: __('Write'),
                                    text: __('Write'),
                                    classname:  'btn btn-xs btn-success btn-magic btn-click',
                                    url:'rfid/rfid/getinfo?id='+$(this).parent().siblings(":first").text(),
                                    hidden:function (row) {
                                        // 如果已写入，隐藏掉
                                        if (row.is_write === 1){
                                            return true;
                                        }
                                    },
                                    // confirm:"请复制"
                                    click:function () {
                                        //  检查当前设备是否连接
                                        Layer.alert("这里为测试，暂不会写入！<br>" +
                                            "数据库中自带了一个已写入的数据，请转移操作"+
                                            "请复制下面的链接至IE浏览器打开！<br>" +
                                            "" +window.location.protocol+"//"+window.location.host+
                                            "/admin/rfid/rfid/generate_write_page?id=" +
                                            $(this).parent().siblings(":first").text()+
                                            "");
                                        // if (checkRfidConn() == true){
                                        //     // 联网查询
                                        //     // 这里需要怎么处理
                                        //     $.ajax({
                                        //         // url: 'rfid/rfid/write?id='+$(this).parent().siblings(":first").text(),
                                        //         url: 'rfid/rfid/generate_write_page?id='+$(this).parent().siblings(":first").text(),
                                        //         type: 'post',
                                        //         data: {
                                        //             "id":table.bootstrapTable('getSelections')
                                        //         },
                                        //         success:function (data) {
                                        //             Layer.alert(data);
                                        //             table.bootstrapTable('refresh');
                                        //         }
                                        //     })
                                        // }else{
                                        //     Layer.alert("未检测到设备，请检查是否安装读卡器驱动或是否连接读卡器");
                                        // }
                                    }
                                },
                                {
                                    name:"look",
                                    title: __('Look'),
                                    classname:  'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-info-circle',
                                    text:__('Look'),
                                    url:'rfid/rfid/look',
                                    callback:function (data) {
                                        console.log("接收到回传数据："+data);
                                    }
                                },
                                {
                                    name:"assign_to_dealer",
                                    title:__('Assing_to_dealer'),
                                    classname:  'btn btn-xs btn-info btn-dialog',
                                    // icon:'fa fa-list',
                                    text:__('Assing_to_dealer'),
                                    url:'rfid/rfid/assign_to_dealer',
                                    callback:function(data){
                                        console.log("接收到回传数据："+data);
                                    }
                                },
                                {
                                    name:"touser",
                                    title:__('Assing_to_user'),
                                    classname:  'btn btn-xs btn-info btn-dialog',
                                    // icon:'fa fa-list',
                                    text:__('Assing_to_user'),
                                    url:'rfid/rfid/assign_to_user',
                                    callback:function(data){
                                        console.log("接收到回传数据："+data);
                                    }
                                },
                                {
                                    name:"recovery",
                                    title:__('Recovery'),
                                    classname:  'btn btn-xs btn-danger btn-ajax',
                                    // icon:'fa fa-list',
                                    text:__('Recovery'),
                                    url:'rfid/rfid/recovery',
                                    confirm: '是否回收',
                                },
                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        write:function () {
        },
        assign_to_dealer:function () {
            Controller.api.bindevent();
        },
        look:function () {
        },
        recovery:function(){
            Controller.api.bindevent();
        },
        submit_revision_attr:function () {
            // Form.bindevent();
            Controller.api.bindevent();
            Form.api.bindevent($("form[role=form]"),function() {
            },function () {
            },function (success,error) {
                $.ajax({
                    type:"POST",
                    url: "rfid/rfid/check_has_apply?ids="+this.attr("ids"),
                    success:function (data) {
                        Layer.confirm(data.msg,{btn:['确认','取消'],title:"提示"},function (index) {
                            Form.api.submit($("form[role=form]"),function (data) {
                                Layer.close(index);
                            },function (error) {
                                Layer.close(index);
                            });
                        });
                    }
                });
                return false;
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    // 检测硬件设备
    function checkRfidConn() {
        if ( 2 > 1 ){
            return true;
        }else{
            return false;
        }
    }
    // 我的申请记录
    $(document).on("click",".btn-my_revise",function () {
        var url = "rfid/revise/index?oneself=true";
        var msg = "我的申请记录";
        Fast.api.open(url,msg,{
            area:['95%','95%'],
        });
    })
    return Controller;
});