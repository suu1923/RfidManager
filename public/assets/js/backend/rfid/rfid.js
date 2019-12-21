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
                        {field: 'create_user_id', title: __('Create_user_id')},
                        {field: 'is_write', title: __('Is_write'),formatter:Table.api.formatter.flag,custom:{'0':'danger',"1":'success'},searchList:{0:"未写入",1:"已写入"}},
                        {field: 'write_time', title:__('Write_time'),operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'update_time', title: __('Update_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'delete_time', title: __('Delete_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'),formatter:Table.api.formatter.flag,custom:{'0':'success',"1":'danger'},searchList:{0:"正常",1:"损坏"}},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons:[
                                {
                                    name:"look",
                                    title: __('Look'),
                                    text: __('Look'),
                                    classname:  'btn btn-xs btn-success btn-magic btn-click',
                                    url:'rfid/rfid/getinfo?id='+$(this).parent().siblings(":first").text(),
                                    hidden:function (row) {
                                        // 如果已写入，隐藏掉
                                        if (row.is_write === 1){
                                            return true
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
                                                    Layer.alert(data)
                                                    table.bootstrapTable('refresh')
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
                                    classname:  'btn btn-xs btn-info btn-magic',
                                    icon: 'fa fa-info-circle',
                                    url:'rfid/rfid/look',
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
                                                    Layer.alert(data)
                                                    table.bootstrapTable('refresh')
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
        look:function () {
            console.log("s")
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
