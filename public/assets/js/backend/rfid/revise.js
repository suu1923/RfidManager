define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'rfid/revise/index' + location.search,
                    table: 'rfid_revise',
                }
            });

            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                dblClickToEdit: false,
                escape:false,
                columns: [
                    [
                        {field: 'rfid_id', title: __('R_id'),formatter:Table.api.formatter.search},
                        {field: 'submission_id', title: __('Submission_id')},
                        {field: 'auditor_id', title: __('Auditor_id')},
                        {field: 'type', title: __('Type'),formatter:Table.api.formatter.flag,custom:{'1':'info'},searchList:{1:"属性"}},
                        {field: 'attr', title: __('Attr')},
                        {field: 'reason', title: __('Sub_reason')},
                        {field: 'success', title: __('Success'), searchList: {"0":__('Success 0'),"1":__('Success 1')}, formatter: Table.api.formatter.normal},
                        // {field: 'notice_id', title: __('Notice_id')},
                        {field: 'reject_reason', title: __('Reject_reason')},
                        {field: 'option', title: __('Option'), searchList: {"0":__('Option 0'),"1":__('Option 1')}, formatter: Table.api.formatter.flag,custom:{'0':'danger','1':'success'}},
                        {field: 'read', title: __('Read'), searchList: {"0":__('Read 0'),"1":__('Read 1')}, formatter: Table.api.formatter.normal},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'option_time', title: __('Option_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        // {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1')}, formatter: Table.api.formatter.status},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons:[
                                {
                                    name:"adopt",
                                    title:__('Adopt'),
                                    classname:  'btn btn-xs btn-info btn-success btn-ajax',
                                    text: __('Adopt'),
                                    url:'rfid/revise/option?type=0',
                                    hidden:function (row) {
                                        // 如果已写入，隐藏掉
                                        if (row.option == 1){
                                            return true;
                                        }
                                    },
                                    refresh:true, // 自动刷新
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    },
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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});