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
                columns: [
                    [
                        {field: 'id', title: __('Id')},
                        {field: 'r_id', title: __('R_id')},
                        {field: 'create_time', title: __('Create_time')+"11", operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'create_user_id', title: __('Create_user_id')},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'delete_time', title: __('Delete_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status')},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons:[
                                {
                                    name:"write",
                                    title: __('Write'),
                                    text: __('Write'),
                                    classname:  'btn btn-xs btn-success btn-magic btn-click',
                                    // callback:function (data) {
                                    //     // Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    //     Toastr.success(JSON.stringify(data));
                                    // },
                                    url:"",
                                    click:function (data) {
                                        //  检查当前设备是否连接
                                        // console.log(checkRfidConn())
                                        if (checkRfidConn() == true){
                                            // 联网查询
                                            // console.log()
                                            $.ajax({
                                                url: 'rfid/rfid/write?id='+$(this).parent().siblings(":first").text(),
                                                type: 'post',
                                                data: {
                                                    "id":table.bootstrapTable('getSelections')
                                                },
                                                success:function (data) {
                                                    Layer.alert(data)
                                                }
                                            })
                                        }else{
                                            Layer.alert("未检测到设备，请检查是否安装读卡器驱动或是否连接读卡器");
                                        }
                                    }
                                }
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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };

    function checkRfidConn() {
        if ( 2 > 1 ){
            return true;
        }else{
            return false;
        }
    }

    return Controller;
});
