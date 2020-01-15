define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'rfid/rfid/index' + location.search,
                    add_url: 'rfid/rfid/add',
                    edit_url: 'rfid/rfid/edit',
                    del_url: 'rfid/rfid/del',
                    multi_url: 'rfid/rfid/multi',
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
                searchFormVisible:false,// 最后开放开
                columns: [
                    [
                        {field: 'id', title: __('Id')},
                        {field: 'r_id', title: __('R_id')},
                        {field: 'create_user_id', title: __('Create_user_id')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'is_write', title: __('Is_write'),formatter:Table.api.formatter.flag,custom:{'0':'danger',"1":'success'},searchList:{0:"未写入",1:"已写入"}},
                        {field: 'is_to_dealer', title: __('Is_to_dealer'),formatter:Table.api.formatter.flag,custom:{'0':'danger',"1":'success'},searchList:{0:"未分配",1:"已分配"}},
                        // {field: 'dealer_id', title: __('Dealer_id')},
                        {field: 'is_to_user', title: __('Is_to_user'),formatter:Table.api.formatter.flag,custom:{'0':'danger',"1":'success'},searchList:{0:"未分配",1:"已分配"}},
                        //{field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'delete_time', title: __('Delete_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'write_time', title: __('Write_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'),formatter:Table.api.formatter.flag,custom:{'0':'success',"1":'danger'},searchList:{0:"正常",1:"已回收",9:"待回收"}},
                        // {field: 'rfid_attr_countries_id', title: __('Rfid_attr_countries_id')},
                        // {field: 'rfid_attr_es_id', title: __('Rfid_attr_es_id')},
                        // {field: 'rfid_attr_productname_id', title: __('Rfid_attr_productname_id')},
                        // {field: 'rfid_attr_specs_id', title: __('Rfid_attr_specs_id')},
                        // {field: 'batch_number', title: __('Batch_number')},
                        // {field: 'serial_number', title: __('Serial_number')},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons:[
                                {
                                    name:"sub_edit_attr",
                                    title:__('Sub_Edit_Attr'),
                                    classname:  'btn btn-xs btn-danger btn-dialog',
                                    text: __('Sub_Edit_Attr'),
                                    icon:'fa fa-list',
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
                                    click:function () {
                                        //  检查当前设备是否连接
                                        if (checkRfidConn() == true){
                                            // 联网查询
                                            $.ajax({
                                                url: 'rfid/rfid/write?id='+$(this).parent().siblings(":first").text(),
                                                type: 'post',
                                                data: {
                                                    "id":table.bootstrapTable('getSelections')
                                                },
                                                success:function (data) {
                                                    Layer.alert(data);
                                                    table.bootstrapTable('refresh');
                                                }
                                            })
                                        }else{
                                            Layer.alert("未检测到设备，请检查是否安装读卡器驱动或是否连接读卡器");
                                        }
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
                                    icon:'fa fa-list',
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
                                    icon:'fa fa-list',
                                    text:__('Assing_to_user'),
                                    url:'rfid/rfid/assign_to_user',
                                    callback:function(data){
                                        console.log("接收到回传数据："+data);
                                    }
                                },
                                // {
                                //     name:"recovery",
                                //     title:__('Recovery'),
                                //     classname:  'btn btn-xs btn-danger btn-click',
                                //     text: __('Recovery'),
                                //     icon:'fa fa-list',
                                //     click:function () {
                                //         // var id = $(this).parent().siblings(":first").text();
                                //         $.ajax({
                                //             url: 'rfid/rfid/recovery?id='+$(this).parent().siblings(":first").text(),
                                //             type: 'post',
                                //             data: {
                                //                 "id":table.bootstrapTable('getSelections')
                                //             },
                                //             success:function (data) {
                                //                 Layer.alert(data);
                                //                 table.bootstrapTable('refresh');
                                //             }
                                //         });
                                //     }
                                // }
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
        look:function () {
        },
        sub_edit_attr:function () {

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
    return Controller;
});